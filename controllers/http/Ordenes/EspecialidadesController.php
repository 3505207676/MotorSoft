<?php

class EspecialidadesController
{
    private EspecialidadService $especialidadService;
    private AuthService $authService;

    public function __construct(EspecialidadService $especialidadService, AuthService $authService)
    {
        $this->especialidadService = $especialidadService;
        $this->authService         = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->authService->exigirAlgunPermiso(
                ['especialidades.ver', 'usuarios.ver'],
                null,
                'No puede consultar especialidades'
            );

            $id = (int) ($_GET['id'] ?? 0);
            $conUsuarios = !empty($_GET['usuarios']) || $id > 0;
            if ($id > 0) {
                $esp = $this->especialidadService->usuarios($id);
                ApiResponse::ok($esp->toArray());
                return;
            }
            $lista = $this->especialidadService->listar($conUsuarios);
            $data = array_map(static function (Especialidad $esp) {
                return $esp->toArray();
            }, $lista);
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('especialidades.gestionar', null, 'No puede gestionar especialidades');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $esp = $this->especialidadService->guardar($body, $actor['usuario']);
            ApiResponse::ok($esp->toArray(), 'Especialidad guardada', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->authService->exigirPermiso('especialidades.gestionar', null, 'No puede gestionar especialidades');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_especialidad'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de especialidad requerido', HTTP_BAD_REQUEST);
            }
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $esp = $this->especialidadService->actualizar($id, $body, $actor['usuario']);
            ApiResponse::ok($esp->toArray(), 'Especialidad actualizada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->authService->exigirPermiso('especialidades.gestionar', null, 'No puede gestionar especialidades');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_especialidad'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de especialidad requerido', HTTP_BAD_REQUEST);
            }
            $this->especialidadService->eliminar($id, [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ], $actor['usuario']);
            ApiResponse::ok(null, 'Especialidad eliminada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function asignar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT']);
            $actor = $this->authService->exigirPermiso('especialidades.gestionar', null, 'No puede asignar especialidades');
            $body = ApiRequest::jsonBody();
            $contexto = [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ];
            $idUsuario = (int) ($body['id_usuario'] ?? 0);
            $idEsp = (int) ($body['id_especialidad'] ?? 0);

            if ($idUsuario > 0) {
                $ids = $body['especialidades'] ?? $body['ids'] ?? [];
                if (!is_array($ids)) {
                    throw new AppException('La lista de especialidades no es válida', HTTP_BAD_REQUEST);
                }
                $lista = $this->especialidadService->asignarAUsuario($idUsuario, $ids, $contexto, $actor['usuario']);
                $data = array_map(static fn (Especialidad $esp) => $esp->toArray(), $lista);
                ApiResponse::ok($data, 'Especialidades del trabajador actualizadas');
                return;
            }

            if ($idEsp > 0) {
                $ids = $body['usuarios'] ?? $body['ids'] ?? [];
                if (!is_array($ids)) {
                    throw new AppException('La lista de usuarios no es válida', HTTP_BAD_REQUEST);
                }
                $esp = $this->especialidadService->asignarUsuarios($idEsp, $ids, $contexto, $actor['usuario']);
                ApiResponse::ok($esp->toArray(), 'Asignación de la especialidad actualizada');
                return;
            }

            throw new AppException('Indique el trabajador o la especialidad a asignar', HTTP_BAD_REQUEST);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }
}
