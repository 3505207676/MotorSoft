<?php

class ReportesController
{
    private ReportesService $reportes;
    private AuthService $authService;

    public function __construct(ReportesService $reportes, AuthService $authService)
    {
        $this->reportes    = $reportes;
        $this->authService = $authService;
    }

    public function obtener(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->authService->resolverActor(ApiRequest::bearerToken());
            $query = ApiRequest::query();
            $tipo = strtolower(trim((string) ($query['tipo'] ?? 'ordenes')));
            if ($tipo === 'nomina' || $tipo === 'nominas') {
                ApiResponse::ok($this->reportes->nomina($actor, $query));
                return;
            }
            if ($tipo === 'facturacion' || $tipo === 'facturas') {
                ApiResponse::ok($this->reportes->facturacion($actor, $query));
                return;
            }
            ApiResponse::ok($this->reportes->ordenes($actor, $query));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }
}
