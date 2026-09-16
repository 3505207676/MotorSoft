<?php

class VehiculoController
{
    private VehiculoService $vehiculoService;
    private AuthService $authService;

    public function __construct(VehiculoService $vehiculoService, AuthService $authService)
    {
        $this->vehiculoService = $vehiculoService;
        $this->authService     = $authService;
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
                $this->authService->asegurarPermiso($actor['usuario'], 'vehiculos.ver', 'No puede consultar vehículos');
            }
            if (!empty($query['id'])) {
                $vehiculo = $this->vehiculoService->buscarPorId((int) $query['id']);
                if ($idClienteActor > 0 && $vehiculo->getIdCliente() !== $idClienteActor) {
                    throw new AppException('No puede ver este vehículo', HTTP_FORBIDDEN);
                }
                ApiResponse::ok($vehiculo->toArray());
                return;
            }
            if (!empty($query['placa'])) {
                $lista = $this->vehiculoService->listarVehiculos([
                    'busqueda'   => $query['placa'],
                    'id_cliente' => $idClienteActor > 0 ? $idClienteActor : null,
                ]);
                foreach ($lista as $item) {
                    if (strcasecmp($item->getPlaca(), (string) $query['placa']) === 0) {
                        ApiResponse::ok($item->toArray());
                        return;
                    }
                }
                throw new AppException('Vehículo no encontrado', HTTP_NOT_FOUND);
            }
            $vehiculos = $this->vehiculoService->listarVehiculos([
                'id_cliente' => $idClienteActor > 0
                    ? $idClienteActor
                    : ($query['id_cliente'] ?? $query['clienteId'] ?? null),
                'estado'     => $query['estado'] ?? null,
                'busqueda'   => $query['busqueda'] ?? null,
            ]);
            $data = array_map(static function (Vehiculo $vehiculo) {
                return $vehiculo->toArray();
            }, $vehiculos);
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'vehiculos.crear', 'No puede registrar vehículos');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $vehiculo = $this->vehiculoService->guardar($body);
            ApiResponse::ok($vehiculo->toArray(), 'Vehículo guardado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'vehiculos.editar', 'No puede editar vehículos');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_vehiculo'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de vehículo requerido', HTTP_BAD_REQUEST);
            }
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $vehiculo = $this->vehiculoService->actualizar($id, $body);
            ApiResponse::ok($vehiculo->toArray(), 'Vehículo actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'vehiculos.eliminar', 'No puede eliminar vehículos');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_vehiculo'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de vehículo requerido', HTTP_BAD_REQUEST);
            }
            $this->vehiculoService->eliminar($id, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ]);
            ApiResponse::ok(null, 'Vehículo eliminado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
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
