<?php

class ServiciosController
{
    private ServicioService $servicioService;
    private AuthService $authService;

    public function __construct(ServicioService $servicioService, AuthService $authService)
    {
        $this->servicioService = $servicioService;
        $this->authService     = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                $servicio = $this->servicioService->listarServicios((int) $query['id']);
                ApiResponse::ok($servicio->toArray());
                return;
            }
            $estado = $query['estado'] ?? null;
            if (($actor['tipo'] ?? '') === 'cliente') {
                $estado = 'Activo';
            }
            $resultado = $this->servicioService->listarServicios([
                'tipo'     => $query['tipo'] ?? $query['categoria'] ?? null,
                'estado'   => $estado,
                'busqueda' => $query['busqueda'] ?? null,
            ]);
            $data = array_map(static function (Servicio $servicio) {
                return $servicio->toArray();
            }, $resultado);
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function editar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->exigirSesion();
            $id = (int) ($_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de servicio requerido', HTTP_BAD_REQUEST);
            }
            $servicio = $this->servicioService->listarServicios($id);
            ApiResponse::ok($servicio->toArray());
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'servicios.crear', 'No puede crear servicios');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $servicio = $this->servicioService->crearServicio($body);
            ApiResponse::ok($servicio->toArray(), 'Servicio creado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'servicios.editar', 'No puede editar servicios');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_servicio'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de servicio requerido', HTTP_BAD_REQUEST);
            }
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $servicio = $this->servicioService->actualizarServicio($id, $body);
            ApiResponse::ok($servicio->toArray(), 'Servicio actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function cambiarEstado(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'servicios.eliminar', 'No puede cambiar el estado del servicio');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_servicio'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de servicio requerido', HTTP_BAD_REQUEST);
            }
            $contexto = [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ];
            if (!empty($body['estado'])) {
                $servicio = $this->servicioService->actualizarServicio($id, array_merge($body, $contexto));
            } else {
                $servicio = $this->servicioService->desactivarServicio($id, $contexto);
            }
            ApiResponse::ok($servicio->toArray(), 'Estado de servicio actualizado');
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
