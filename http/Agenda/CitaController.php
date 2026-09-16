<?php

class CitaController
{
    private AgendaService $agenda;
    private AuthService $authService;

    public function __construct(AgendaService $agenda, AuthService $authService)
    {
        $this->agenda      = $agenda;
        $this->authService = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                $cita = $this->agenda->buscarCita((int) $query['id']);
                $this->asegurarPuedeVer($actor, $cita);
                ApiResponse::ok($cita->toArray());
                return;
            }
            $filtros = [
                'fecha'  => $query['fecha'] ?? null,
                'estado' => $query['estado'] ?? null,
            ];
            if (($actor['tipo'] ?? '') === 'cliente') {
                $filtros['id_cliente'] = (int) $actor['cliente']->getIdCliente();
            } elseif ($actor['usuario'] && $actor['usuario']->esMecanico() && !$actor['usuario']->esAdministrador()) {
                $filtros['id_mecanico'] = (int) $actor['usuario']->getIdUsuario();
            } elseif (!empty($query['id_cliente'])) {
                $filtros['id_cliente'] = (int) $query['id_cliente'];
            } elseif (!empty($query['id_mecanico'])) {
                $filtros['id_mecanico'] = (int) $query['id_mecanico'];
            }
            $data = array_map(static fn (Cita $c) => $c->toArray(), $this->agenda->listarCitas($filtros));
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            if (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario']) {
                $this->authService->asegurarPermiso($actor['usuario'], 'citas.crear', 'No puede crear citas');
            }
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $cita = $this->agenda->crearCita($body, $actor);
            ApiResponse::ok($cita->toArray(), 'Cita agendada', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirActor();
            if (($actor['tipo'] ?? '') === 'usuario' && $actor['usuario']) {
                $this->authService->asegurarAlgunPermiso(
                    $actor['usuario'],
                    ['citas.crear', 'citas.confirmar', 'citas.cancelar'],
                    'No puede actualizar citas'
                );
            }
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_cita'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de cita requerido', HTTP_BAD_REQUEST);
            }
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $cita = $this->agenda->actualizarCita($id, $body, $actor);
            ApiResponse::ok($cita->toArray(), 'Cita actualizada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function convertir(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            if (($actor['tipo'] ?? '') !== 'usuario' || !$actor['usuario']) {
                throw new AppException('Solo el personal del taller puede convertir citas', HTTP_FORBIDDEN);
            }
            $this->authService->asegurarPermiso(
                $actor['usuario'],
                'citas.convertir_orden',
                'No puede convertir citas en órdenes de trabajo'
            );
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_cita'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de cita requerido', HTTP_BAD_REQUEST);
            }
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $resultado = $this->agenda->convertirCitaEnOrden($id, $body, $actor['usuario']);
            ApiResponse::ok($resultado, 'Cita convertida en orden de trabajo', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    private function asegurarPuedeVer(array $actor, Cita $cita): void
    {
        if (($actor['tipo'] ?? '') === 'cliente') {
            if ((int) $actor['cliente']->getIdCliente() !== $cita->getIdCliente()) {
                throw new AppException('No puede ver esta cita', HTTP_FORBIDDEN);
            }
            return;
        }
        $u = $actor['usuario'] ?? null;
        if ($u && $u->esMecanico() && !$u->esAdministrador()) {
            $horario = $cita->getHorario();
            if (!$horario || $horario->getIdUsuario() !== (int) $u->getIdUsuario()) {
                throw new AppException('No puede ver esta cita', HTTP_FORBIDDEN);
            }
        }
    }

    private function exigirActor(): array
    {
        return $this->authService->resolverActor(ApiRequest::bearerToken());
    }
}
