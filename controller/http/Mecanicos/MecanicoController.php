<?php

/**
 * HTTP del panel mecánico. Delega a un servicio por caso de uso.
 */
class MecanicoController
{
    private DashboardMecanicoService $dashboard;
    private PerfilMecanicoService $perfil;
    private ConsumoMecanicoService $consumos;
    private AuthService $auth;

    public function __construct(
        DashboardMecanicoService $dashboard,
        PerfilMecanicoService $perfil,
        ConsumoMecanicoService $consumos,
        AuthService $auth
    ) {
        $this->dashboard = $dashboard;
        $this->perfil    = $perfil;
        $this->consumos  = $consumos;
        $this->auth      = $auth;
    }

    public function dashboard(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirStaff();
            ApiResponse::ok($this->dashboard->resumen($actor));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function perfil(): void
    {
        try {
            $metodo = ApiRequest::method();
            if ($metodo === 'GET') {
                $actor = $this->exigirStaff();
                ApiResponse::ok($this->perfil->obtener($actor['usuario']));
                return;
            }
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirStaff();
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            ApiResponse::ok($this->perfil->actualizar($actor['usuario'], $body), 'Perfil actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function consumos(): void
    {
        try {
            $metodo = ApiRequest::method();
            $actor = $this->exigirStaff();
            if ($metodo === 'GET') {
                $idOrden = (int) (ApiRequest::query()['id_orden'] ?? ApiRequest::query()['orden_id'] ?? 0);
                if ($idOrden < 1) {
                    throw new AppException('id_orden es requerido', HTTP_BAD_REQUEST);
                }
                ApiResponse::ok($this->consumos->listar($actor['usuario'], $idOrden));
                return;
            }
            ApiRequest::requireMethod(['POST']);
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $resultado = $this->consumos->registrar($actor['usuario'], $body);
            ApiResponse::ok($resultado, 'Consumo registrado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /** @return array<string,mixed> */
    private function exigirStaff(): array
    {
        $actor = $this->auth->resolverActor(ApiRequest::bearerToken());
        if (($actor['tipo'] ?? '') !== 'usuario' || empty($actor['usuario'])) {
            throw new AppException('Solo el personal del taller puede usar esta app', HTTP_FORBIDDEN);
        }
        return $actor;
    }
}
