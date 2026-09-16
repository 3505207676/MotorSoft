<?php

class ClienteController
{
    private ClienteService $clienteService;
    private AuthService $authService;
    private AdjuntoService $adjuntos;

    public function __construct(ClienteService $clienteService, AuthService $authService, AdjuntoService $adjuntos)
    {
        $this->clienteService = $clienteService;
        $this->authService    = $authService;
        $this->adjuntos       = $adjuntos;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $query = ApiRequest::query();
            if (($actor['tipo'] ?? '') === 'cliente') {
                ApiResponse::ok($this->conAvatar($actor['cliente']));
                return;
            }
            if (!$actor['usuario']) {
                throw new AppException('No autorizado', HTTP_FORBIDDEN);
            }
            $this->authService->asegurarPermiso($actor['usuario'], 'clientes.ver', 'No puede consultar clientes');
            if (!empty($query['id'])) {
                $cliente = $this->clienteService->buscarPorId((int) $query['id']);
                ApiResponse::ok($this->conAvatar($cliente));
                return;
            }
            if (!empty($query['documento'])) {
                $cliente = $this->clienteService->buscarPorDoc((string) $query['documento']);
                ApiResponse::ok($this->conAvatar($cliente));
                return;
            }
            $clientes = $this->clienteService->listarClientes([
                'estado'   => $query['estado'] ?? null,
                'busqueda' => $query['busqueda'] ?? null,
            ]);
            $data = array_map(function (Cliente $cliente) {
                return $this->conAvatar($cliente);
            }, $clientes);
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'clientes.crear', 'No puede registrar clientes');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $cliente = $this->clienteService->guardar($body);
            ApiResponse::ok($this->conAvatar($cliente), 'Cliente guardado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirActor();
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_cliente'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if (($actor['tipo'] ?? '') === 'cliente') {
                $id = (int) $actor['cliente']->getIdCliente();
                unset($body['estado'], $body['documento'], $body['nombre']);
                $body['updated_by'] = $actor['cliente']->getCreatedBy() ?: null;
            } else {
                if ($id < 1) {
                    throw new AppException('ID de cliente requerido', HTTP_BAD_REQUEST);
                }
                $this->authService->asegurarPermiso($actor['usuario'], 'clientes.editar', 'No puede editar clientes');
                $body['updated_by'] = $actor['usuario']->getIdUsuario();
            }
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $cliente = $this->clienteService->actualizar($id, $body);
            ApiResponse::ok($this->conAvatar($cliente), 'Cliente actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'clientes.eliminar', 'No puede eliminar clientes');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_cliente'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de cliente requerido', HTTP_BAD_REQUEST);
            }
            $this->clienteService->eliminar($id, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ]);
            ApiResponse::ok(null, 'Cliente eliminado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /** @return array<string,mixed> */
    private function conAvatar(Cliente $cliente): array
    {
        $data = $cliente->toArray();
        $id = (int) ($data['id_cliente'] ?? 0);
        $avatar = $id > 0 ? $this->adjuntos->avatarCliente($id) : null;
        $data['avatar'] = $avatar ? $avatar->toArray() : null;
        return $data;
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
