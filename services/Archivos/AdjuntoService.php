<?php

require_once __DIR__ . '/../../models/Chat/Conversacion.php';
require_once __DIR__ . '/ArchivoService.php';
require_once __DIR__ . '/AdjuntoSchema.php';
require_once __DIR__ . '/../../models/Archivos/Adjunto.php';
require_once __DIR__ . '/../../repositories/Archivos/AdjuntoRepository.php';
require_once __DIR__ . '/../../repositories/Chat/ConversacionRepository.php';

class AdjuntoService
{
    private ArchivoService $archivos;
    private AdjuntoRepository $adjuntos;
    private ConversacionRepository $conversaciones;
    private OrdenesService $ordenes;
    private FacturacionService $facturas;
    private AuthService $auth;

    public function __construct(
        ArchivoService $archivos,
        AdjuntoRepository $adjuntos,
        ConversacionRepository $conversaciones,
        OrdenesService $ordenes,
        FacturacionService $facturas,
        AuthService $auth,
        AdjuntoSchema $schema
    ) {
        $this->archivos        = $archivos;
        $this->adjuntos        = $adjuntos;
        $this->conversaciones  = $conversaciones;
        $this->ordenes         = $ordenes;
        $this->facturas        = $facturas;
        $this->auth            = $auth;
        $schema->asegurar();
    }

    public function buscarPorId(int $id): Adjunto
    {
        $adj = $this->adjuntos->buscarPorId($id);
        if (!$adj) {
            throw new AppException('Archivo no encontrado', HTTP_NOT_FOUND);
        }
        return $adj;
    }

    /** @return Adjunto[] */
    public function listar(array $actor, string $tipo, int $entidadId): array
    {
        $tipo = $this->validarTipo($tipo);
        $this->asegurarPuedeVer($actor, $tipo, $entidadId);
        return $this->adjuntos->listarPorEntidad($tipo, $entidadId);
    }

    public function avatarCliente(int $idCliente): ?Adjunto
    {
        if ($idCliente < 1) {
            return null;
        }
        return $this->adjuntos->ultimoDe(Adjunto::CLIENTE, $idCliente);
    }

    public function subir(array $actor, array $file, string $tipo, int $entidadId): Adjunto
    {
        $tipo = $this->validarTipo($tipo);
        $entidadId = $this->resolverEntidad($actor, $tipo, $entidadId);
        $this->asegurarPuedeSubir($actor, $tipo, $entidadId);

        $guardado = $this->archivos->guardar($file, $tipo);
        $idUsuario = (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario'])
            ? (int) $actor['usuario']->getIdUsuario()
            : null;
        $idCliente = (($actor['tipo'] ?? '') === 'cliente' && $actor['cliente'])
            ? (int) $actor['cliente']->getIdCliente()
            : null;
        if ($idCliente === null && $tipo === Adjunto::CONVERSACION) {
            $conv = $this->conversaciones->buscarPorId($entidadId);
            if ($conv && strcasecmp($conv->getTipoContacto(), Conversacion::TIPO_CLIENTE) === 0) {
                $idCliente = (int) $conv->getIdContacto();
            }
        }
        if ($idCliente === null && $tipo === Adjunto::ORDEN) {
            $detalle = $this->ordenes->obtenerDetalleCompleto($entidadId);
            $idCliente = (int) ($detalle['id_cliente'] ?? $detalle['vehiculo']['id_cliente'] ?? 0) ?: null;
        }

        if ($tipo === Adjunto::CLIENTE) {
            foreach ($this->adjuntos->listarPorEntidad(Adjunto::CLIENTE, $entidadId) as $viejo) {
                $viejo->marcarEliminado();
                $this->adjuntos->guardar($viejo);
                $this->archivos->eliminar($viejo->getRuta());
            }
        }

        $adjunto = Adjunto::crear($tipo, $entidadId, $guardado, $idUsuario, $idCliente);
        $guardadoAdj = $this->adjuntos->guardar($adjunto);
        $this->auth->registrarLog([
            'token'         => $actor['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Adjuntos',
            'registroId'    => (int) $guardadoAdj->getIdAdjunto(),
            'ipAddress'     => $actor['ip'] ?? null,
        ]);
        return $guardadoAdj;
    }

    public function vincularAMensaje(int $idAdjunto, int $idMensaje): Adjunto
    {
        $adj = $this->buscarPorId($idAdjunto);
        $adj->setEntidad(Adjunto::MENSAJE, $idMensaje);
        return $this->adjuntos->guardar($adj);
    }

    public function servir(array $actor, int $id): void
    {
        $adj = $this->buscarPorId($id);
        $this->asegurarAdjunto($actor, $adj);
        $abs = $this->archivos->absoluto($adj->getRuta());
        if (!is_file($abs)) {
            throw new AppException('El archivo ya no está en el disco', HTTP_NOT_FOUND);
        }
        $mime = $this->archivos->mimeDeRuta($abs);
        $nombre = $adj->getNombreOriginal() ?: $adj->getNombreSistema();
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($abs));
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $nombre) . '"');
        header('Cache-Control: private, max-age=120');
        readfile($abs);
        exit;
    }

    public function eliminar(array $actor, int $id): void
    {
        $adj = $this->buscarPorId($id);
        $this->asegurarAdjunto($actor, $adj);
        if (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario']) {
            $this->auth->asegurarPermiso($actor['usuario'], 'adjuntos.crear', 'No puede eliminar archivos');
        }
        $adj->marcarEliminado();
        $this->adjuntos->guardar($adj);
        $this->archivos->eliminar($adj->getRuta());
    }

    private function validarTipo(string $tipo): string
    {
        $tipo = strtolower(trim($tipo));
        if (!in_array($tipo, Adjunto::TIPOS, true)) {
            throw new AppException('Tipo de adjunto no válido', HTTP_BAD_REQUEST);
        }
        return $tipo;
    }

    private function resolverEntidad(array $actor, string $tipo, int $entidadId): int
    {
        if ($tipo === Adjunto::CLIENTE) {
            if (($actor['tipo'] ?? '') === 'cliente') {
                return (int) $actor['cliente']->getIdCliente();
            }
            if ($entidadId < 1) {
                throw new AppException('Cliente requerido', HTTP_BAD_REQUEST);
            }
            return $entidadId;
        }
        if ($tipo === Adjunto::CONVERSACION && $entidadId < 1 && ($actor['tipo'] ?? '') === 'cliente') {
            $idCli = (int) $actor['cliente']->getIdCliente();
            $existente = $this->conversaciones->buscarPorClave(
                Conversacion::CANAL_INTERNO,
                Conversacion::TIPO_CLIENTE,
                $idCli
            );
            if ($existente && $existente->getIdConversacion()) {
                return (int) $existente->getIdConversacion();
            }
            throw new AppException('Envíe un mensaje primero para abrir el chat y luego adjunte el archivo', HTTP_BAD_REQUEST);
        }
        return $entidadId;
    }

    private function asegurarPuedeSubir(array $actor, string $tipo, int $entidadId): void
    {
        $this->asegurarPuedeVer($actor, $tipo, $entidadId);
        if (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario'] && $tipo !== Adjunto::CLIENTE) {
            $this->auth->asegurarPermiso($actor['usuario'], 'adjuntos.crear', 'No puede subir archivos');
        }
    }

    private function asegurarAdjunto(array $actor, Adjunto $adj): void
    {
        if (($actor['tipo'] ?? '') === 'cliente' && $actor['cliente']) {
            $idCli = (int) $actor['cliente']->getIdCliente();
            if ((int) $adj->getIdCliente() === $idCli) {
                return;
            }
        }
        $this->asegurarPuedeVer($actor, $adj->getEntidadTipo(), $adj->getEntidadId());
    }

    private function asegurarPuedeVer(array $actor, string $tipo, int $entidadId): void
    {
        if ($tipo === Adjunto::MENSAJE) {
            if (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario']) {
                $this->auth->asegurarPermiso($actor['usuario'], 'chat.ver', 'No puede ver este archivo');
                return;
            }
            throw new AppException('No puede ver este archivo', HTTP_FORBIDDEN);
        }
        if ($tipo === Adjunto::CLIENTE) {
            if (($actor['tipo'] ?? '') === 'cliente') {
                if ((int) $actor['cliente']->getIdCliente() !== $entidadId) {
                    throw new AppException('No puede ver este archivo', HTTP_FORBIDDEN);
                }
                return;
            }
            if ($actor['usuario']) {
                $this->auth->asegurarPermiso($actor['usuario'], 'clientes.ver', 'No puede ver este archivo');
                return;
            }
            throw new AppException('No autorizado', HTTP_FORBIDDEN);
        }
        if ($tipo === Adjunto::CONVERSACION) {
            $this->asegurarConversacion($actor, $entidadId);
            return;
        }
        if ($tipo === Adjunto::ORDEN) {
            $detalle = $this->ordenes->obtenerDetalleCompleto($entidadId);
            $this->asegurarOrden($actor, $detalle);
            return;
        }
        if ($tipo === Adjunto::FACTURA) {
            $factura = $this->facturas->buscarFactura($entidadId);
            if (($actor['tipo'] ?? '') === 'cliente') {
                if ($factura->getIdCliente() !== (int) $actor['cliente']->getIdCliente()) {
                    throw new AppException('No puede ver este archivo', HTTP_FORBIDDEN);
                }
                return;
            }
            if ($actor['usuario']) {
                $this->auth->asegurarPermiso($actor['usuario'], 'facturas.ver', 'No puede ver este archivo');
                return;
            }
            throw new AppException('No autorizado', HTTP_FORBIDDEN);
        }
        throw new AppException('No autorizado', HTTP_FORBIDDEN);
    }

    private function asegurarConversacion(array $actor, int $idConv): void
    {
        $conv = $this->conversaciones->buscarPorId($idConv);
        if (!$conv) {
            throw new AppException('Conversación no encontrada', HTTP_NOT_FOUND);
        }
        if (($actor['tipo'] ?? '') === 'cliente') {
            $ok = strcasecmp($conv->getTipoContacto(), Conversacion::TIPO_CLIENTE) === 0
                && $conv->getIdContacto() === (int) $actor['cliente']->getIdCliente();
            if (!$ok) {
                throw new AppException('No puede usar esta conversación', HTTP_FORBIDDEN);
            }
            return;
        }
        if (!$actor['usuario']) {
            throw new AppException('No autorizado', HTTP_FORBIDDEN);
        }
        $this->auth->asegurarPermiso($actor['usuario'], 'chat.enviar', 'No puede adjuntar en el chat');
    }

    /** @param array<string,mixed> $detalle */
    private function asegurarOrden(array $actor, array $detalle): void
    {
        $idCli = (int) ($detalle['id_cliente'] ?? $detalle['vehiculo']['id_cliente'] ?? 0);
        if (($actor['tipo'] ?? '') === 'cliente') {
            if ($idCli !== (int) $actor['cliente']->getIdCliente()) {
                throw new AppException('No puede ver esta orden', HTTP_FORBIDDEN);
            }
            return;
        }
        if (!$actor['usuario']) {
            throw new AppException('No autorizado', HTTP_FORBIDDEN);
        }
        $this->auth->asegurarPermiso($actor['usuario'], 'ordenes.ver', 'No puede ver esta orden');
        if (
            $actor['usuario']->esMecanico()
            && !$actor['usuario']->esAdministrador()
            && (int) ($detalle['id_usuario'] ?? 0) !== (int) $actor['usuario']->getIdUsuario()
        ) {
            throw new AppException('No puede ver esta orden', HTTP_FORBIDDEN);
        }
    }
}
