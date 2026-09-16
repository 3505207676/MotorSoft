<?php

class OrdenesController
{
    private OrdenesService $ordenesService;
    private AuthService $authService;

    public function __construct(OrdenesService $ordenesService, AuthService $authService)
    {
        $this->ordenesService = $ordenesService;
        $this->authService    = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $query = ApiRequest::query();
            $idClienteActor = (($actor['tipo'] ?? '') === 'cliente' && $actor['cliente'])
                ? (int) $actor['cliente']->getIdCliente()
                : 0;
            if ($idClienteActor < 1 && $actor['usuario']) {
                $this->authService->asegurarPermiso($actor['usuario'], 'ordenes.ver', 'No puede consultar órdenes');
            }
            if (!empty($query['id'])) {
                $detalle = $this->ordenesService->obtenerDetalleCompleto((int) $query['id']);
                $idCli = (int) ($detalle['id_cliente'] ?? $detalle['vehiculo']['id_cliente'] ?? 0);
                if ($idClienteActor > 0 && $idCli !== $idClienteActor) {
                    throw new AppException('No puede ver esta orden', HTTP_FORBIDDEN);
                }
                if (
                    ($actor['tipo'] ?? '') === 'usuario'
                    && $actor['usuario']
                    && $actor['usuario']->esMecanico()
                    && !$actor['usuario']->esAdministrador()
                    && (int) ($detalle['id_usuario'] ?? 0) !== (int) $actor['usuario']->getIdUsuario()
                ) {
                    throw new AppException('No puede ver esta orden', HTTP_FORBIDDEN);
                }
                ApiResponse::ok($detalle);
                return;
            }
            $idUsuarioFiltro = $query['id_usuario'] ?? $query['mecanico_id'] ?? $query['mecanicoId'] ?? null;
            if (
                ($actor['tipo'] ?? '') === 'usuario'
                && $actor['usuario']
                && $actor['usuario']->esMecanico()
                && !$actor['usuario']->esAdministrador()
            ) {
                $idUsuarioFiltro = (int) $actor['usuario']->getIdUsuario();
            }
            $filtros = [
                'estado'      => $query['estado'] ?? null,
                'id_usuario'  => $idUsuarioFiltro,
                'id_vehiculo' => $query['id_vehiculo'] ?? $query['vehiculo_id'] ?? null,
            ];
            if ($idClienteActor > 0) {
                $filtros['id_cliente'] = $idClienteActor;
            }
            ApiResponse::ok($this->ordenesService->listarOrdenesComoArray($filtros));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function mostrar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'ordenes.ver', 'No puede consultar órdenes');
            $id = (int) ($_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de orden requerido', HTTP_BAD_REQUEST);
            }
            ApiResponse::ok($this->ordenesService->obtenerDetalleCompleto($id));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function crear(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'ordenes.crear', 'No puede crear órdenes');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['permitir_precio_manual'] = $actor['usuario']->puede('servicios.editar');
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $orden = $this->ordenesService->registrarOrdenes($body);
            ApiResponse::ok($orden->toArray(), 'Orden registrada', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarAlgunPermiso(
                $actor['usuario'],
                ['ordenes.crear', 'ordenes.agregar_servicios', 'ordenes.agregar_productos', 'ordenes.cambiar_estado'],
                'No puede guardar esta orden'
            );
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $body['permitir_precio_manual'] = $actor['usuario']->puede('servicios.editar');
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $this->exigirCierreSiAplica($actor['usuario'], (string) ($body['estado'] ?? ''));
            $orden = $this->ordenesService->guardarOrden($body);
            ApiResponse::ok($orden->toArray(), 'Orden guardada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizarEstado(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'ordenes.cambiar_estado', 'No puede cambiar el estado de la orden');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_orden'] ?? $_GET['id'] ?? 0);
            $estado = (string) ($body['estado'] ?? '');
            if ($id < 1 || $estado === '') {
                throw new AppException('id_orden y estado son requeridos', HTTP_BAD_REQUEST);
            }
            $this->exigirCierreSiAplica($actor['usuario'], $estado);
            $detalle = $this->ordenesService->obtenerDetalleCompleto($id);
            if (
                $actor['usuario']->esMecanico()
                && !$actor['usuario']->esAdministrador()
                && (int) ($detalle['id_usuario'] ?? 0) !== (int) $actor['usuario']->getIdUsuario()
            ) {
                throw new AppException('No puede cambiar el estado de esta orden', HTTP_FORBIDDEN);
            }
            $orden = $this->ordenesService->cambiarEstado($id, $estado, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ]);
            ApiResponse::ok($orden->toArray(), 'Estado de orden actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'ordenes.anular', 'No puede anular órdenes');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_orden'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de orden requerido', HTTP_BAD_REQUEST);
            }
            $this->ordenesService->eliminarOrden($id, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ]);
            ApiResponse::ok(null, 'Orden eliminada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    private function exigirCierreSiAplica(Usuario $usuario, string $estado): void
    {
        if ($estado !== 'Pendiente Pago') {
            return;
        }
        $this->authService->asegurarAlgunPermiso(
            $usuario,
            ['ordenes.cerrar', 'ordenes.crear'],
            'No puede marcar la orden lista para facturar'
        );
    }

    /** @return array{tipo:string,usuario:?Usuario,sesion:?Sesion,cliente:?Cliente} */
    private function exigirActor(): array
    {
        return $this->authService->resolverActor(ApiRequest::bearerToken());
    }

    /** @return array{usuario:Usuario,sesion:Sesion} */
    private function exigirSesion(): array
    {
        $actor = $this->exigirActor();
        if (($actor['tipo'] ?? '') !== 'usuario' || !$actor['usuario']) {
            throw new AppException('Solo el personal del taller puede hacer esto', HTTP_FORBIDDEN);
        }
        return ['usuario' => $actor['usuario'], 'sesion' => $actor['sesion']];
    }
}
