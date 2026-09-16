<?php

class AgendaController
{
    private AgendaService $agenda;
    private AuthService $authService;

    public function __construct(AgendaService $agenda, AuthService $authService)
    {
        $this->agenda      = $agenda;
        $this->authService = $authService;
    }

    public function listarHorarios(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $query = ApiRequest::query();
            $filtros = [
                'fecha'      => $query['fecha'] ?? null,
                'desde'      => $query['desde'] ?? null,
                'id_usuario' => $query['id_usuario'] ?? $query['id_mecanico'] ?? null,
            ];
            if (($actor['tipo'] ?? '') === 'cliente') {
                $filtros['desde'] = $filtros['desde'] ?: date('Y-m-d');
                $filtros['estado'] = Horario::DISPONIBLE;
            }
            if (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario'] && $actor['usuario']->esMecanico() && !$actor['usuario']->esAdministrador()) {
                if (empty($filtros['id_usuario'])) {
                    $filtros['id_usuario'] = $actor['usuario']->getIdUsuario();
                }
            }
            $data = array_map(static fn (Horario $h) => $h->toArray(), $this->agenda->listarHorarios($filtros));
            if (($actor['tipo'] ?? '') === 'cliente') {
                $data = array_values(array_filter($data, static fn ($h) => ($h['cupos_libres'] ?? 0) > 0 && ($h['estado'] ?? '') === Horario::DISPONIBLE));
            }
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardarHorario(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirStaff();
            $this->authService->asegurarPermiso($actor['usuario'], 'agenda.horarios', 'No puede gestionar horarios');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $horario = $this->agenda->crearHorario($body, $actor['usuario']);
            ApiResponse::ok($horario->toArray(), 'Horario creado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function cancelarHorario(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirStaff();
            $this->authService->asegurarPermiso($actor['usuario'], 'agenda.horarios', 'No puede gestionar horarios');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_horario'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de horario requerido', HTTP_BAD_REQUEST);
            }
            $horario = $this->agenda->cancelarHorario($id, [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ], $actor['usuario']);
            ApiResponse::ok($horario->toArray(), 'Horario cancelado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function mecanicos(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->exigirStaff();
            $data = array_map(static fn (Usuario $u) => $u->toPublicArray(), $this->agenda->mecanicos());
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    private function exigirActor(): array
    {
        return $this->authService->resolverActor(ApiRequest::bearerToken());
    }

    /** @return array{tipo:string,usuario:Usuario,sesion:Sesion} */
    private function exigirStaff(): array
    {
        $actor = $this->exigirActor();
        if (($actor['tipo'] ?? '') !== 'usuario' || !$actor['usuario']) {
            throw new AppException('Solo el personal del taller puede hacer esto', HTTP_FORBIDDEN);
        }
        return $actor;
    }
}
