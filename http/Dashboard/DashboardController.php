<?php

class DashboardController
{
    private DashboardService $dashboard;
    private AuthService $authService;

    public function __construct(DashboardService $dashboard, AuthService $authService)
    {
        $this->dashboard   = $dashboard;
        $this->authService = $authService;
    }

    public function obtener(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirStaff();
            $q = ApiRequest::query();
            $soloAlertas = (string) ($q['alertas'] ?? '');
            if ($soloAlertas !== '' && $soloAlertas !== '0') {
                ApiResponse::ok($this->dashboard->alertas($actor));
                return;
            }
            $periodo = (string) ($q['periodo'] ?? 'week');
            ApiResponse::ok($this->dashboard->resumen($actor, $periodo));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /** @return array<string,mixed> */
    private function exigirStaff(): array
    {
        $actor = $this->authService->resolverActor(ApiRequest::bearerToken());
        if (($actor['tipo'] ?? '') !== 'usuario' || empty($actor['usuario'])) {
            throw new AppException('Solo el personal del taller puede ver este panel', HTTP_FORBIDDEN);
        }
        return $actor;
    }
}
