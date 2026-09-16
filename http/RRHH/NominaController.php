<?php

class NominaController
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
            $this->authService->exigirPermiso('nominas.ver', null, 'No puede consultar nóminas');
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                ApiResponse::ok($this->rrhh->buscarNomina((int) $query['id'])->toArray());
                return;
            }
            $data = array_map(static fn (Nomina $n) => $n->toArray(), $this->rrhh->listarNominas());
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('nominas.crear', null, 'No puede registrar nómina');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $nomina = $this->rrhh->registrarNomina($body, $actor['usuario']);
            ApiResponse::ok($nomina->toArray(), 'Nómina registrada', HTTP_CREATED);
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
