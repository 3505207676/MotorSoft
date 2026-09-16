<?php

class ProductoController
{
    private InventarioService $inventarioService;
    private AuthService $authService;

    public function __construct(InventarioService $inventarioService, AuthService $authService)
    {
        $this->inventarioService = $inventarioService;
        $this->authService       = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.ver', 'No puede consultar el inventario');
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                $producto = $this->inventarioService->buscarProducto((int) $query['id']);
                ApiResponse::ok($producto->toArray());
                return;
            }
            $productos = $this->inventarioService->listarProductos([
                'id_categoria' => $query['id_categoria'] ?? null,
                'categoria'    => $query['categoria'] ?? null,
                'busqueda'     => $query['busqueda'] ?? null,
                'stock_bajo'   => $query['stock_bajo'] ?? $query['stockBajo'] ?? null,
            ]);
            $data = array_map(static function (Producto $producto) {
                return $producto->toArray();
            }, $productos);
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
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.crear', 'No puede crear productos');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $producto = $this->inventarioService->guardarProducto($body);
            ApiResponse::ok($producto->toArray(), 'Producto guardado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.editar', 'No puede editar productos');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_producto'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de producto requerido', HTTP_BAD_REQUEST);
            }
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $producto = $this->inventarioService->actualizarProducto($id, $body);
            ApiResponse::ok($producto->toArray(), 'Producto actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.eliminar', 'No puede eliminar productos');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_producto'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de producto requerido', HTTP_BAD_REQUEST);
            }
            $this->inventarioService->eliminarProducto($id, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ]);
            ApiResponse::ok(null, 'Producto eliminado');
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
