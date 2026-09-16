<?php

class AuditoriaController
{
    private AuditoriaService $auditoria;
    private AuthService $authService;

    public function __construct(AuditoriaService $auditoria, AuthService $authService)
    {
        $this->auditoria   = $auditoria;
        $this->authService = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->authService->exigirPermiso('auditoria.ver', null, 'No puede consultar la auditoría');
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                $log = $this->auditoria->buscarPorId((int) $query['id']);
                ApiResponse::ok(AuditoriaPresentador::presentar($log));
                return;
            }
            ApiResponse::ok($this->auditoria->listarPresentado($query));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }
}
