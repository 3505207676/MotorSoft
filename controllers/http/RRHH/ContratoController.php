<?php

class ContratoController
{
    private RrhhService $rrhh;
    private AuthService $authService;

    public function __construct(RrhhService $rrhh, AuthService $authService)
    {
        $this->rrhh        = $rrhh;
        $this->authService = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->authService->exigirPermiso('contratos.ver', null, 'No puede consultar contratos');
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                ApiResponse::ok($this->rrhh->buscarContrato((int) $query['id'])->toArray());
                return;
            }
            $data = array_map(static fn (Contrato $c) => $c->toArray(), $this->rrhh->listarContratos());
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('contratos.gestionar', null, 'No puede gestionar contratos');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $contrato = $this->rrhh->guardarContrato($body, $actor['usuario']);
            ApiResponse::ok($contrato->toArray(), 'Contrato guardado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->authService->exigirPermiso('contratos.gestionar', null, 'No puede gestionar contratos');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_contrato'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de contrato requerido', HTTP_BAD_REQUEST);
            }
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $contrato = $this->rrhh->actualizarContrato($id, $body, $actor['usuario']);
            ApiResponse::ok($contrato->toArray(), 'Contrato actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->authService->exigirPermiso('contratos.gestionar', null, 'No puede gestionar contratos');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_contrato'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de contrato requerido', HTTP_BAD_REQUEST);
            }
            $this->rrhh->eliminarContrato($id, [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ], $actor['usuario']);
            ApiResponse::ok(null, 'Contrato eliminado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /** @return array{usuario:Usuario,sesion:Sesion} */
    private function exigirSesion(): array
    {
        $token = ApiRequest::bearerToken();
        if (!$token) {
            throw new AppException('Token requerido', HTTP_UNAUTHORIZED);
        }
        return $this->authService->validarToken($token);
    }

}
