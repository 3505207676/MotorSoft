<?php

class CategoriaController
{
    private CategoriaService $categoriaService;
    private AuthService $authService;

    public function __construct(CategoriaService $categoriaService, AuthService $authService)
    {
        $this->categoriaService = $categoriaService;
        $this->authService      = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.ver', 'No puede consultar categorías');
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                $categoria = $this->categoriaService->buscarPorId((int) $query['id']);
                ApiResponse::ok($categoria->toArray());
                return;
            }
            $data = array_map(static function (Categoria $categoria) {
                return $categoria->toArray();
            }, $this->categoriaService->listar());
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
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.categorias', 'No puede gestionar categorías');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $categoria = $this->categoriaService->guardar($body);
            ApiResponse::ok($categoria->toArray(), 'Categoría guardada', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.categorias', 'No puede gestionar categorías');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_categoria'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de categoría requerido', HTTP_BAD_REQUEST);
            }
            $this->categoriaService->eliminar($id, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
            ]);
            ApiResponse::ok(null, 'Categoría eliminada');
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
