<?php

require_once __DIR__ . '/../../models/Chat/Mensaje.php';
require_once __DIR__ . '/../../models/Chat/Conversacion.php';
require_once __DIR__ . '/../../repositories/Chat/MensajeRepository.php';
require_once __DIR__ . '/../../repositories/Chat/ConversacionRepository.php';
require_once __DIR__ . '/ChatSchema.php';
require_once __DIR__ . '/ChatContactoResolver.php';
require_once __DIR__ . '/Canales/CanalChatFactory.php';
require_once __DIR__ . '/BuzonWhatsApp.php';
require_once __DIR__ . '/../../models/Archivos/Adjunto.php';
require_once __DIR__ . '/../../repositories/Archivos/AdjuntoRepository.php';

class ChatService
{
    private MensajeRepository $mensajes;
    private ConversacionRepository $conversaciones;
    private ChatContactoResolver $contactos;
    private CanalChatFactory $canales;
    private AuthService $auth;
    private ?AdjuntoRepository $adjuntos;
    private BuzonWhatsApp $buzonWa;

    public function __construct(
        MensajeRepository $mensajes,
        ConversacionRepository $conversaciones,
        ChatContactoResolver $contactos,
        CanalChatFactory $canales,
        AuthService $auth,
        ChatSchema $schema,
        ?AdjuntoRepository $adjuntos = null,
        ?BuzonWhatsApp $buzonWa = null
    ) {
        $this->mensajes       = $mensajes;
        $this->conversaciones = $conversaciones;
        $this->contactos      = $contactos;
        $this->canales        = $canales;
        $this->auth           = $auth;
        $this->adjuntos       = $adjuntos;
        $this->buzonWa        = $buzonWa ?: new BuzonWhatsApp();
        // El mantenimiento del esquema es útil durante el desarrollo, pero en
        // hosting compartido ejecutarlo con cada petición/poll del chat causa
        // decenas de consultas y bloqueos. Producción debe usar la BD migrada.
        if (!defined('ENTORNO') || ENTORNO !== 'production') {
            $schema->asegurar();
        }
    }

    /** @return array<int,array> */
    public function conversaciones(array $actor, array $filtros = []): array
    {
        if (($actor['tipo'] ?? '') === 'cliente') {
            return [$this->dtoHiloCliente($actor)];
        }

        $tipoFiltro  = trim((string) ($filtros['tipo_contacto'] ?? ''));
        $canalFiltro = trim((string) ($filtros['canal'] ?? ''));
        $busqueda    = trim((string) ($filtros['q'] ?? $filtros['busqueda'] ?? ''));
        $idUsuario   = (int) $actor['usuario']->getIdUsuario();

        if ($tipoFiltro !== '') {
            $lista = $this->directorioComoConversaciones($tipoFiltro, $idUsuario, $canalFiltro);
        } else {
            $lista = $this->hilosPersistidos($idUsuario, $canalFiltro);
        }

        if ($busqueda !== '') {
            $q = mb_strtolower($busqueda);
            $lista = array_values(array_filter($lista, static function (array $c) use ($q) {
                $blob = mb_strtolower(
                    ($c['nombre'] ?? '') . ' ' . ($c['documento'] ?? '') . ' ' . ($c['telefono'] ?? '')
                );
                return str_contains($blob, $q);
            }));
        }

        usort($lista, static function ($a, $b) {
            $ua = ((int) ($a['no_leidos'] ?? 0)) > 0 ? 1 : 0;
            $ub = ((int) ($b['no_leidos'] ?? 0)) > 0 ? 1 : 0;
            if ($ua !== $ub) {
                return $ub <=> $ua;
            }
            $fa = (string) ($a['actividad_at'] ?? $a['ultimo']['fecha_hora'] ?? '');
            $fb = (string) ($b['actividad_at'] ?? $b['ultimo']['fecha_hora'] ?? '');
            if ($fa !== $fb) {
                return strcmp($fb, $fa);
            }
            $ta = (int) ($a['total_mensajes'] ?? 0);
            $tb = (int) ($b['total_mensajes'] ?? 0);
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }
            return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
        });
        return $lista;
    }

    /** @return array<int,array> */
    public function contactos(array $actor, string $tipo, string $busqueda = ''): array
    {
        $this->exigirStaff($actor);
        $excluir = (int) $actor['usuario']->getIdUsuario();
        return $this->contactos->directorio($tipo, $excluir, $busqueda);
    }

    public function crearProveedor(array $data, array $actor): array
    {
        $this->exigirStaff($actor);
        $contacto = $this->contactos->crearProveedor($data, (int) $actor['usuario']->getIdUsuario());
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Proveedores',
            'registroId'    => (int) $contacto['id_contacto'],
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $contacto;
    }

    public function abrir(array $data, array $actor): array
    {
        $this->exigirStaff($actor);
        $idUsuario = (int) $actor['usuario']->getIdUsuario();
        $conv = $this->resolverConversacion($data, $actor, true);
        $contacto = $this->contactos->contacto(
            $conv->getTipoContacto(),
            $this->idContactoVisible($conv, $idUsuario)
        );
        return $this->hidratarDto($conv, $contacto, $idUsuario);
    }

    /** @return array<string,mixed> */
    public function asegurarHiloCliente(array $actor): array
    {
        if (($actor['tipo'] ?? '') !== 'cliente' || empty($actor['cliente'])) {
            throw new AppException('Solo el cliente puede abrir su hilo', HTTP_FORBIDDEN);
        }
        $idCliente = (int) $actor['cliente']->getIdCliente();
        $idUsuario = $this->usuarioDestinoPorDefecto();
        $cli = $this->contactos->contacto(Conversacion::TIPO_CLIENTE, $idCliente);
        $this->obtenerOCrear(
            Conversacion::CANAL_INTERNO,
            Conversacion::TIPO_CLIENTE,
            $idCliente,
            $idUsuario,
            $cli['telefono'],
            $cli['nombre']
        );
        return $this->dtoHiloCliente($actor);
    }

    /** @return array[] */
    public function mensajesDeConversacion(array $actor, array $ref): array
    {
        if (($actor['tipo'] ?? '') === 'cliente') {
            $idCliente = (int) $actor['cliente']->getIdCliente();
            $conv = $this->conversaciones->buscarPorClave(
                Conversacion::CANAL_INTERNO,
                Conversacion::TIPO_CLIENTE,
                $idCliente
            );
            $lista = $conv
                ? $this->mensajes->listarPorConversacion((int) $conv->getIdConversacion())
                : $this->mensajes->listarPorCliente($idCliente);
            return $this->mapearMensajes($lista, $actor);
        }

        $conv = $this->resolverConversacion($ref, $actor, false);
        if ($conv && $conv->getIdConversacion()) {
            $lista = $this->mensajes->listarPorConversacion((int) $conv->getIdConversacion());
        } else {
            $idCliente = (int) ($ref['id_cliente'] ?? 0);
            $lista = $idCliente > 0 ? $this->mensajes->listarPorCliente($idCliente) : [];
        }
        return $this->mapearMensajes($lista, $actor);
    }

    public function enviar(array $data, array $actor): Mensaje
    {
        $texto = trim((string) ($data['contenido'] ?? $data['texto'] ?? ''));
        $idAdjunto = (int) ($data['id_adjunto'] ?? 0);
        $adjunto = null;
        if ($idAdjunto > 0) {
            if (!$this->adjuntos) {
                throw new AppException('Los adjuntos no están disponibles', HTTP_BAD_REQUEST);
            }
            $adjunto = $this->adjuntos->buscarPorId($idAdjunto);
            if (!$adjunto) {
                throw new AppException('El archivo no existe', HTTP_NOT_FOUND);
            }
            if ($texto === '') {
                $texto = $adjunto->getNombreOriginal() ?: 'Archivo adjunto';
            }
        }
        if ($texto === '') {
            throw new AppException('El mensaje no puede ir vacío', HTTP_BAD_REQUEST);
        }
        if (mb_strlen($texto) > 4000) {
            throw new AppException('El mensaje es demasiado largo', HTTP_BAD_REQUEST);
        }

        if (($actor['tipo'] ?? '') === 'usuario' && !empty($actor['usuario'])) {
            $this->auth->asegurarPermiso($actor['usuario'], 'chat.enviar', 'No puede enviar mensajes');
        }

        if (($actor['tipo'] ?? '') === 'cliente') {
            $msg = $this->enviarComoCliente($texto, $data, $actor, $idAdjunto);
            $this->vincularAdjuntoMensaje($adjunto, $msg);
            return $msg;
        }

        $conv = $this->resolverConversacion($data, $actor, true);
        if ($idAdjunto > 0 && strcasecmp($conv->getCanal(), Conversacion::CANAL_WHATSAPP) === 0) {
            throw new AppException('En WhatsApp todavía no se envían archivos. Use el chat interno.', HTTP_BAD_REQUEST);
        }
        $idUsuario = (int) $actor['usuario']->getIdUsuario();
        $this->asegurarTelefonoWhatsApp($conv, $idUsuario);
        $receptorId = $this->receptorParaStaff($conv, $idUsuario);
        $borrador = new Mensaje(
            $idUsuario,
            $receptorId,
            Mensaje::TIPO_USUARIO,
            $texto,
            (int) $conv->getIdConversacion(),
            $conv->getCanal()
        );
        if ($idAdjunto > 0) {
            $borrador->setIdAdjunto($idAdjunto);
        }

        $canal = $this->canales->obtener($conv->getCanal());
        $resultado = $canal->enviar($conv, $borrador, $actor);
        $guardado = $resultado['mensaje'];
        $this->vincularAdjuntoMensaje($adjunto, $guardado);

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Mensajes',
            'registroId'    => (int) $guardado->getIdMensaje(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $guardado;
    }

    public function marcarLeido(array $actor, array $ref): void
    {
        if (($actor['tipo'] ?? '') === 'cliente') {
            $idCliente = (int) $actor['cliente']->getIdCliente();
            $conv = $this->conversaciones->buscarPorClave(
                Conversacion::CANAL_INTERNO,
                Conversacion::TIPO_CLIENTE,
                $idCliente
            );
            if ($conv && $conv->getIdConversacion()) {
                $this->mensajes->marcarLeidosConversacion(
                    (int) $conv->getIdConversacion(),
                    Mensaje::TIPO_CLIENTE,
                    $idCliente
                );
                return;
            }
            $this->mensajes->marcarLeidosEntrantes($idCliente, true);
            return;
        }

        $conv = $this->resolverConversacion($ref, $actor, false);
        if ($conv && $conv->getIdConversacion()) {
            $this->mensajes->marcarLeidosConversacion(
                (int) $conv->getIdConversacion(),
                Mensaje::TIPO_USUARIO,
                (int) $actor['usuario']->getIdUsuario()
            );
            return;
        }
        $idCliente = (int) ($ref['id_cliente'] ?? 0);
        if ($idCliente < 1) {
            throw new AppException('Conversación requerida', HTTP_BAD_REQUEST);
        }
        $this->mensajes->marcarLeidosEntrantes($idCliente, false);
    }

    public function noLeidos(array $actor): int
    {
        $total = 0;
        foreach ($this->conversaciones($actor, []) as $c) {
            $total += (int) ($c['no_leidos'] ?? 0);
        }
        return $total;
    }

    public function canalesDisponibles(): array
    {
        return $this->canales->disponibles();
    }

    /** @return array<string,mixed> */
    public function estadoWhatsApp(): array
    {
        $d = $this->canales->whatsapp()->resumen();
        $d['eventos'] = $this->buzonWa->listar(12);
        return $d;
    }

    /** @return array<string,mixed> */
    public function probarWhatsApp(): array
    {
        $d = $this->canales->whatsapp()->diagnosticar();
        $this->buzonWa->guardar(
            'probar',
            !empty($d['ok']),
            (string) ($d['error'] ?? ($d['ok'] ? 'Meta respondió' : 'Sin conexión')),
            ['numero' => $d['numero'] ?? null]
        );
        $d['eventos'] = $this->buzonWa->listar(8);
        return $d;
    }

    /** @return array<int,array<string,mixed>> */
    public function eventosWhatsApp(int $limite = 20): array
    {
        return $this->buzonWa->listar($limite);
    }

    public function verificarWebhookWhatsApp(array $query): ?string
    {
        $mode = trim((string) ($query['hub_mode'] ?? $query['hub.mode'] ?? $query['hub-mode'] ?? ''));
        $token = trim((string) ($query['hub_verify_token'] ?? $query['hub.verify_token'] ?? $query['hub-verify_token'] ?? ''));
        $challenge = (string) ($query['hub_challenge'] ?? $query['hub.challenge'] ?? $query['hub-challenge'] ?? '');
        if ($mode !== 'subscribe' || $challenge === '') {
            return null;
        }
        $esperado = $this->canales->whatsapp()->tokenVerificacion();
        $ok = $esperado !== '' && hash_equals($esperado, $token);
        try {
            $this->buzonWa->guardar(
                'verify',
                $ok,
                $ok ? 'Meta (o prueba local) validó el webhook' : 'Verify token no coincide',
                []
            );
        } catch (Throwable $e) {
        }
        if (!$ok) {
            return null;
        }
        return $challenge;
    }

    public function recibirWebhookWhatsApp(array $payload, string $origen = 'inbound'): void
    {
        $nMsg = 0;
        $nEst = 0;
        try {
            $nEst = $this->aplicarEstadosWhatsApp($payload);
            $mensajes = $this->extraerMensajesWhatsApp($payload);
            $nMsg = count($mensajes);
            if ($mensajes) {
                $createdBy = $this->usuarioDestinoPorDefecto();
                foreach ($mensajes as $item) {
                    $this->ingestarMensajeWhatsApp($item, $createdBy);
                }
            }
            if ($nMsg > 0 || $nEst > 0 || $origen === 'simulado') {
                $this->buzonWa->guardar(
                    $origen !== '' ? $origen : 'inbound',
                    true,
                    $nMsg . ' mensaje(s), ' . $nEst . ' estado(s)',
                    ['telefono' => $mensajes[0]['telefono'] ?? null]
                );
            }
        } catch (Throwable $e) {
            try {
                $this->buzonWa->guardar('error', false, $e->getMessage());
            } catch (Throwable $ignored) {
            }
            throw $e;
        }
    }

    /**
     * @return array{ok:bool,telefono:string,nombre:string,texto:string}
     */
    public function simularWhatsApp(array $data, array $actor): array
    {
        $this->exigirStaff($actor);
        $tel = CanalWhatsApp::normalizarTelefono((string) ($data['telefono'] ?? ''));
        $texto = trim((string) ($data['texto'] ?? $data['mensaje'] ?? ''));
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($tel === '' || strlen($tel) < 10) {
            throw new AppException('Indique un celular válido (Colombia: 10 dígitos o con 57)', HTTP_BAD_REQUEST);
        }
        if ($texto === '') {
            throw new AppException('Escriba el texto del mensaje', HTTP_BAD_REQUEST);
        }
        if ($nombre === '') {
            $nombre = 'WhatsApp ' . $tel;
        }
        $id = 'wamid.sim.' . bin2hex(random_bytes(8));
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry'  => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [[
                            'wa_id'   => $tel,
                            'profile' => ['name' => $nombre],
                        ]],
                        'messages' => [[
                            'from' => $tel,
                            'id'   => $id,
                            'type' => 'text',
                            'text' => ['body' => $texto],
                        ]],
                    ],
                ]],
            ]],
        ];
        $this->recibirWebhookWhatsApp($payload, 'simulado');
        return [
            'ok'       => true,
            'telefono' => $tel,
            'nombre'   => $nombre,
            'texto'    => $texto,
            'id'       => $id,
        ];
    }

    /** @param array{id_externo:string,telefono:string,nombre:string,texto:string} $item */
    private function ingestarMensajeWhatsApp(array $item, int $createdBy): void
    {
        if ($this->mensajes->buscarPorIdExterno($item['id_externo'])) {
            return;
        }
        $contacto = $this->contactos->buscarPorTelefono($item['telefono']);
        if (!$contacto) {
            $contacto = $this->contactos->crearProveedorWhatsApp(
                $item['nombre'] !== '' ? $item['nombre'] : ('WhatsApp ' . $item['telefono']),
                $item['telefono'],
                $createdBy
            );
        }
        $telefono = CanalWhatsApp::normalizarTelefono($contacto['telefono'] ?: $item['telefono']);
        $conv = $this->obtenerOCrear(
            Conversacion::CANAL_WHATSAPP,
            $contacto['tipo_contacto'],
            (int) $contacto['id_contacto'],
            $createdBy,
            $telefono !== '' ? $telefono : $item['telefono'],
            $contacto['nombre']
        );
        $tipoEmisor = $contacto['tipo_contacto'] === Conversacion::TIPO_USUARIO
            ? Mensaje::TIPO_USUARIO
            : ($contacto['tipo_contacto'] === Conversacion::TIPO_PROVEEDOR
                ? Mensaje::TIPO_PROVEEDOR
                : Mensaje::TIPO_CLIENTE);
        $borrador = new Mensaje(
            (int) $contacto['id_contacto'],
            $createdBy,
            $tipoEmisor,
            $item['texto'],
            (int) $conv->getIdConversacion(),
            Conversacion::CANAL_WHATSAPP,
            $item['id_externo']
        );
        $this->canales->obtener(Conversacion::CANAL_INTERNO)->enviar($conv, $borrador, [
            'tipo' => 'sistema',
        ]);
    }

    private function enviarComoCliente(string $texto, array $data, array $actor, int $idAdjunto = 0): Mensaje
    {
        $idCliente = (int) $actor['cliente']->getIdCliente();
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        if ($idUsuario < 1) {
            $idUsuario = $this->usuarioDestinoPorDefecto();
        }
        $usuario = $this->contactos->contacto(Conversacion::TIPO_USUARIO, $idUsuario);
        $cli = $this->contactos->contacto(Conversacion::TIPO_CLIENTE, $idCliente);
        $conv = $this->obtenerOCrear(
            Conversacion::CANAL_INTERNO,
            Conversacion::TIPO_CLIENTE,
            $idCliente,
            $idUsuario,
            $cli['telefono'],
            $cli['nombre']
        );
        $borrador = new Mensaje(
            $idCliente,
            (int) $usuario['id_contacto'],
            Mensaje::TIPO_CLIENTE,
            $texto,
            (int) $conv->getIdConversacion(),
            Conversacion::CANAL_INTERNO
        );
        if ($idAdjunto > 0) {
            $borrador->setIdAdjunto($idAdjunto);
        }
        $resultado = $this->canales->obtener(Conversacion::CANAL_INTERNO)->enviar($conv, $borrador, $actor);
        $guardado = $resultado['mensaje'];
        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Mensajes',
            'registroId'    => (int) $guardado->getIdMensaje(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);
        return $guardado;
    }

    private function resolverConversacion(array $data, array $actor, bool $crearSiFalta): ?Conversacion
    {
        $idConv = (int) ($data['id_conversacion'] ?? $data['conversacionId'] ?? 0);
        if ($idConv > 0) {
            $conv = $this->conversaciones->buscarPorId($idConv);
            if ($conv) {
                $this->asegurarParticipa($conv, $actor);
                return $conv;
            }
        }

        $canal = $this->contactos->validarCanal((string) ($data['canal'] ?? Conversacion::CANAL_INTERNO));
        $tipo  = trim((string) ($data['tipo_contacto'] ?? ''));
        $idContacto = (int) ($data['id_contacto'] ?? 0);
        $idCliente  = (int) ($data['id_cliente'] ?? $data['cliente_id'] ?? 0);
        if ($tipo === '' && $idCliente > 0) {
            $tipo = Conversacion::TIPO_CLIENTE;
            $idContacto = $idCliente;
        }
        if ($tipo === '' || $idContacto < 1) {
            if ($idConv > 0) {
                throw new AppException('Conversación no encontrada', HTTP_NOT_FOUND);
            }
            if ($crearSiFalta) {
                throw new AppException('Seleccione un contacto', HTTP_BAD_REQUEST);
            }
            return null;
        }

        $tipo = $this->contactos->validarTipo($tipo);
        $contacto = $this->contactos->contacto($tipo, $idContacto);
        $idYo = ($actor['tipo'] ?? '') === 'usuario'
            ? (int) $actor['usuario']->getIdUsuario()
            : $this->usuarioDestinoPorDefecto();
        $existente = $this->buscarHilo($canal, $tipo, $idContacto, $idYo);
        if ($existente) {
            if (!$existente->getTelefono() && $contacto['telefono']) {
                $existente->setTelefono($contacto['telefono']);
                $this->conversaciones->guardar($existente);
            }
            return $existente;
        }
        if (!$crearSiFalta) {
            return null;
        }
        [$idClave, $idPar] = $this->claveConversacion($tipo, $idContacto, $idYo);
        return $this->obtenerOCrear(
            $canal,
            $tipo,
            $idClave,
            $idYo,
            $contacto['telefono'],
            $contacto['nombre'],
            $idPar
        );
    }

    private function obtenerOCrear(
        string $canal,
        string $tipo,
        int $idContacto,
        int $createdBy,
        ?string $telefono,
        ?string $titulo,
        int $idPar = 0
    ): Conversacion {
        $existente = $this->conversaciones->buscarPorClave($canal, $tipo, $idContacto, $idPar);
        if ($existente) {
            return $existente;
        }
        $conv = new Conversacion($canal, $tipo, $idContacto, $createdBy, $telefono, $titulo, $idPar);
        return $this->conversaciones->guardar($conv);
    }

    private function buscarHilo(string $canal, string $tipo, int $idContacto, int $idYo): ?Conversacion
    {
        if (strcasecmp($tipo, Conversacion::TIPO_USUARIO) === 0) {
            return $this->conversaciones->buscarParUsuarios($canal, $idYo, $idContacto);
        }
        return $this->conversaciones->buscarPorClave($canal, $tipo, $idContacto, 0);
    }

    /** @return array{0:int,1:int} */
    private function claveConversacion(string $tipo, int $idContacto, int $idYo): array
    {
        if (strcasecmp($tipo, Conversacion::TIPO_USUARIO) === 0) {
            return Conversacion::parUsuarios($idYo, $idContacto);
        }
        return [$idContacto, 0];
    }

    private function idContactoVisible(Conversacion $conv, int $idUsuario): int
    {
        if (strcasecmp($conv->getTipoContacto(), Conversacion::TIPO_USUARIO) === 0) {
            return $conv->idOtroUsuario($idUsuario);
        }
        return $conv->getIdContacto();
    }

    private function asegurarTelefonoWhatsApp(Conversacion $conv, int $idUsuario): void
    {
        if (strcasecmp($conv->getCanal(), Conversacion::CANAL_WHATSAPP) !== 0) {
            return;
        }
        $actual = CanalWhatsApp::normalizarTelefono($conv->getTelefono());
        if ($actual !== '') {
            if ($actual !== (string) $conv->getTelefono()) {
                $conv->setTelefono($actual);
                $this->conversaciones->guardar($conv);
            }
            return;
        }
        try {
            $contacto = $this->contactos->contacto(
                $conv->getTipoContacto(),
                $this->idContactoVisible($conv, $idUsuario)
            );
        } catch (Throwable $e) {
            return;
        }
        $tel = CanalWhatsApp::normalizarTelefono($contacto['telefono'] ?? '');
        if ($tel === '') {
            return;
        }
        $conv->setTelefono($tel);
        $this->conversaciones->guardar($conv);
    }

    private function asegurarParticipa(Conversacion $conv, array $actor): void
    {
        if (($actor['tipo'] ?? '') !== 'usuario') {
            return;
        }
        if (strcasecmp($conv->getTipoContacto(), Conversacion::TIPO_USUARIO) !== 0) {
            return;
        }
        $id = (int) $actor['usuario']->getIdUsuario();
        if ($conv->getIdContacto() !== $id && $conv->getIdPar() !== $id) {
            throw new AppException('Conversación no encontrada', HTTP_NOT_FOUND);
        }
    }

    /** @return array<int,array> */
    private function directorioComoConversaciones(string $tipo, int $idUsuario, string $canalFiltro): array
    {
        $tipo = $this->contactos->validarTipo($tipo);
        $canal = $canalFiltro !== '' ? $this->contactos->validarCanal($canalFiltro) : Conversacion::CANAL_INTERNO;
        $lista = [];
        foreach ($this->contactos->directorio($tipo, $idUsuario) as $contacto) {
            $real = $this->buscarHilo($canal, $tipo, (int) $contacto['id_contacto'], $idUsuario);
            if ($real) {
                $lista[] = $this->hidratarDto($real, $contacto, $idUsuario);
            } else {
                $lista[] = $this->dtoVirtual($contacto, $canal);
            }
        }
        return $lista;
    }

    /** @return array<int,array> */
    private function hilosPersistidos(int $idUsuario, string $canalFiltro): array
    {
        $filtros = [];
        if ($canalFiltro !== '') {
            $filtros['canal'] = $this->contactos->validarCanal($canalFiltro);
        }

        $hilos = [];
        $tipos = [];
        $ids = [];
        foreach ($this->conversaciones->listar($filtros) as $conv) {
            if (strcasecmp($conv->getTipoContacto(), Conversacion::TIPO_USUARIO) === 0) {
                if ($conv->getIdContacto() !== $idUsuario && $conv->getIdPar() !== $idUsuario) {
                    continue;
                }
            }
            $hilos[] = $conv;
            $tipos[$conv->getTipoContacto()] = true;
            if ($conv->getIdConversacion()) {
                $ids[] = (int) $conv->getIdConversacion();
            }
        }

        // Resolver contactos en bloque: máximo tres consultas (clientes,
        // usuarios y proveedores), en vez de una consulta por conversación.
        $directorio = [];
        foreach (array_keys($tipos) as $tipo) {
            foreach ($this->contactos->directorio($tipo) as $contacto) {
                $clave = $tipo . ':' . (int) $contacto['id_contacto'];
                $directorio[$clave] = $contacto;
            }
        }
        $resumenes = $this->mensajes->resumenConversaciones($ids, $idUsuario);

        $lista = [];
        foreach ($hilos as $conv) {
            $idConv = (int) ($conv->getIdConversacion() ?? 0);
            $resumen = $resumenes[$idConv] ?? null;
            if (!$resumen || (int) $resumen['total'] < 1) {
                continue;
            }
            $idVisible = $this->idContactoVisible($conv, $idUsuario);
            $contacto = $directorio[$conv->getTipoContacto() . ':' . $idVisible] ?? null;
            if (!$contacto) {
                continue;
            }

            /** @var Mensaje|null $ultimo */
            $ultimo = $resumen['ultimo'];
            $dto = $this->dtoBase(
                $contacto,
                $conv->getCanal(),
                $idConv,
                $ultimo,
                (int) $resumen['no_leidos'],
                (int) $resumen['total']
            );
            $dto['ultimo_mio'] = $ultimo ? $ultimo->esMioStaff($idUsuario) : false;
            $dto['actividad_at'] = $ultimo ? $ultimo->getFechaHora()->format('Y-m-d H:i:s') : null;
            $lista[] = $dto;
        }
        return $lista;
    }

    private function hidratarDto(Conversacion $conv, array $contacto, int $idUsuario): array
    {
        $idConv = (int) $conv->getIdConversacion();
        $msgs = $idConv > 0 ? $this->mensajes->listarPorConversacion($idConv) : [];
        $ultimo = $msgs ? $msgs[count($msgs) - 1] : null;
        $noLeidos = $idConv > 0
            ? $this->mensajes->contarNoLeidosConversacion($idConv, Mensaje::TIPO_USUARIO, $idUsuario)
            : 0;
        $dto = $this->dtoBase($contacto, $conv->getCanal(), $idConv, $ultimo, $noLeidos, count($msgs));
        $dto['ultimo_mio'] = $ultimo ? $ultimo->esMioStaff($idUsuario) : false;
        $dto['actividad_at'] = $ultimo ? $ultimo->getFechaHora()->format('Y-m-d H:i:s') : null;
        return $dto;
    }

    private function dtoVirtual(array $contacto, string $canal): array
    {
        $dto = $this->dtoBase($contacto, $canal, null, null, 0, 0);
        $dto['ultimo_mio'] = false;
        $dto['actividad_at'] = null;
        return $dto;
    }

    private function dtoBase(
        array $contacto,
        string $canal,
        ?int $idConversacion,
        ?Mensaje $ultimo,
        int $noLeidos,
        int $total
    ): array {
        return [
            'id_conversacion' => $idConversacion,
            'tipo_contacto'   => $contacto['tipo_contacto'],
            'id_contacto'     => (int) $contacto['id_contacto'],
            'id_cliente'      => $contacto['tipo_contacto'] === Conversacion::TIPO_CLIENTE
                ? (int) $contacto['id_contacto']
                : 0,
            'canal'           => $canal,
            'nombre'          => $contacto['nombre'],
            'documento'       => $contacto['documento'],
            'telefono'        => $contacto['telefono'],
            'email'           => $contacto['email'] ?? null,
            'ultimo'          => $ultimo ? $ultimo->toArray() : null,
            'no_leidos'       => $noLeidos,
            'total_mensajes'  => $total,
        ];
    }

    private function dtoHiloCliente(array $actor): array
    {
        $idCliente = (int) $actor['cliente']->getIdCliente();
        $contacto = $this->contactos->contacto(Conversacion::TIPO_CLIENTE, $idCliente);
        $conv = $this->conversaciones->buscarPorClave(
            Conversacion::CANAL_INTERNO,
            Conversacion::TIPO_CLIENTE,
            $idCliente
        );
        $msgs = $conv
            ? $this->mensajes->listarPorConversacion((int) $conv->getIdConversacion())
            : $this->mensajes->listarPorCliente($idCliente);
        $ultimo = $msgs ? $msgs[count($msgs) - 1] : null;
        $noLeidos = 0;
        foreach ($msgs as $m) {
            if (!$m->esDeCliente() && strcasecmp($m->getEstado(), Mensaje::LEIDO) !== 0) {
                $noLeidos++;
            }
        }
        return [
            'id_conversacion' => $conv ? $conv->getIdConversacion() : null,
            'tipo_contacto'   => Conversacion::TIPO_CLIENTE,
            'id_contacto'     => $idCliente,
            'id_cliente'      => $idCliente,
            'canal'           => Conversacion::CANAL_INTERNO,
            'nombre'          => 'Taller El Paisa',
            'documento'       => $contacto['documento'],
            'telefono'        => $contacto['telefono'],
            'email'           => $contacto['email'],
            'ultimo'          => $ultimo ? $ultimo->toArray() : null,
            'ultimo_mio'      => $ultimo ? $ultimo->esDeCliente() : false,
            'actividad_at'    => $ultimo ? $ultimo->getFechaHora()->format('Y-m-d H:i:s') : null,
            'no_leidos'       => $noLeidos,
            'total_mensajes'  => count($msgs),
        ];
    }

    /** @param Mensaje[] $lista */
    private function mapearMensajes(array $lista, array $actor): array
    {
        $out = [];
        $esCliente = ($actor['tipo'] ?? '') === 'cliente';
        $idUsuario = $esCliente ? 0 : (int) $actor['usuario']->getIdUsuario();
        $idCliente = $esCliente ? (int) $actor['cliente']->getIdCliente() : 0;
        foreach ($lista as $m) {
            $arr = $m->toArray();
            if ($esCliente) {
                $arr['mio'] = $m->esDeCliente() && $m->getEmisorId() === $idCliente;
            } else {
                $arr['mio'] = $m->esMioStaff($idUsuario);
            }
            if (!empty($arr['id_adjunto'])) {
                $adj = is_array($arr['adjunto']) ? $arr['adjunto'] : [];
                $adj['id_adjunto'] = (int) $arr['id_adjunto'];
                $adj['url'] = '/controllers/api/adjuntos/ver.php?id=' . (int) $arr['id_adjunto'];
                $arr['adjunto'] = $adj;
            }
            $out[] = $arr;
        }
        return $out;
    }

    private function vincularAdjuntoMensaje(?Adjunto $adjunto, Mensaje $mensaje): void
    {
        if (!$adjunto || !$this->adjuntos || !$mensaje->getIdMensaje()) {
            return;
        }
        $adjunto->setEntidad(Adjunto::MENSAJE, (int) $mensaje->getIdMensaje());
        $this->adjuntos->guardar($adjunto);
    }

    private function receptorParaStaff(Conversacion $conv, int $idUsuario): int
    {
        if (strcasecmp($conv->getTipoContacto(), Conversacion::TIPO_USUARIO) === 0) {
            $otro = $conv->idOtroUsuario($idUsuario);
            if ($otro < 1) {
                throw new AppException('Seleccione otro usuario del taller', HTTP_BAD_REQUEST);
            }
            return $otro;
        }
        return $conv->getIdContacto();
    }

    private function exigirStaff(array $actor): void
    {
        if (($actor['tipo'] ?? '') !== 'usuario') {
            throw new AppException('Solo el personal del taller puede hacer esto', HTTP_FORBIDDEN);
        }
    }

    private function aplicarEstadosWhatsApp(array $payload): int
    {
        $n = 0;
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach (($change['value']['statuses'] ?? []) as $st) {
                    $id = trim((string) ($st['id'] ?? ''));
                    $status = strtolower(trim((string) ($st['status'] ?? '')));
                    if ($id === '' || $status === '') {
                        continue;
                    }
                    $estado = $status === 'read' ? Mensaje::LEIDO : Mensaje::ENVIADO;
                    if ($this->mensajes->actualizarEstadoPorIdExterno($id, $estado)) {
                        $n++;
                    }
                }
            }
        }
        return $n;
    }

    /** @return array<int,array{id_externo:string,telefono:string,nombre:string,texto:string}> */
    private function extraerMensajesWhatsApp(array $payload): array
    {
        $out = [];
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];
                $nombres = [];
                foreach ($value['contacts'] ?? [] as $c) {
                    $wa = (string) ($c['wa_id'] ?? '');
                    $nombres[$wa] = (string) ($c['profile']['name'] ?? '');
                }
                foreach ($value['messages'] ?? [] as $msg) {
                    $from = (string) ($msg['from'] ?? '');
                    $id   = (string) ($msg['id'] ?? '');
                    if ($from === '' || $id === '') {
                        continue;
                    }
                    $texto = $this->textoWhatsApp($msg);
                    if ($texto === '') {
                        continue;
                    }
                    $out[] = [
                        'id_externo' => $id,
                        'telefono'   => $from,
                        'nombre'     => $nombres[$from] ?? '',
                        'texto'      => $texto,
                    ];
                }
            }
        }
        return $out;
    }

    private function textoWhatsApp(array $msg): string
    {
        $tipo = (string) ($msg['type'] ?? 'text');
        if ($tipo === 'text') {
            return trim((string) ($msg['text']['body'] ?? ''));
        }
        if ($tipo === 'button') {
            return trim((string) ($msg['button']['text'] ?? '[botón]'));
        }
        if ($tipo === 'interactive') {
            $btn = trim((string) ($msg['interactive']['button_reply']['title'] ?? ''));
            $lst = trim((string) ($msg['interactive']['list_reply']['title'] ?? ''));
            if ($btn !== '') {
                return $btn;
            }
            if ($lst !== '') {
                return $lst;
            }
            return '[respuesta interactiva]';
        }
        if ($tipo === 'image') {
            $cap = trim((string) ($msg['image']['caption'] ?? ''));
            return $cap !== '' ? '[imagen] ' . $cap : '[imagen]';
        }
        if ($tipo === 'video') {
            $cap = trim((string) ($msg['video']['caption'] ?? ''));
            return $cap !== '' ? '[video] ' . $cap : '[video]';
        }
        if ($tipo === 'audio' || $tipo === 'voice') {
            return '[audio]';
        }
        if ($tipo === 'document') {
            $nom = trim((string) ($msg['document']['filename'] ?? ''));
            return $nom !== '' ? '[archivo] ' . $nom : '[archivo]';
        }
        if ($tipo === 'sticker') {
            return '[sticker]';
        }
        if ($tipo === 'location') {
            $lat = $msg['location']['latitude'] ?? '';
            $lng = $msg['location']['longitude'] ?? '';
            $name = trim((string) ($msg['location']['name'] ?? ''));
            $bits = array_filter([$name, $lat !== '' && $lng !== '' ? $lat . ',' . $lng : '']);
            return '[ubicación] ' . implode(' · ', $bits);
        }
        return '[' . $tipo . ']';
    }

    private function usuarioDestinoPorDefecto(): int
    {
        $primero = 0;
        foreach ($this->contactos->directorio(Conversacion::TIPO_USUARIO) as $u) {
            if (!empty($u['es_admin'])) {
                return (int) $u['id_contacto'];
            }
            if ($primero < 1) {
                $primero = (int) $u['id_contacto'];
            }
        }
        if ($primero > 0) {
            return $primero;
        }
        throw new AppException('No hay personal disponible', HTTP_BAD_REQUEST);
    }
}
