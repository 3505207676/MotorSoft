<?php

class StockController
{
    private InventarioService $inventarioService;
    private AuthService $authService;

    public function __construct(InventarioService $inventarioService, AuthService $authService)
    {
        $this->inventarioService = $inventarioService;
        $this->authService       = $authService;
    }

    public function obtener(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.ver', 'No puede consultar el inventario');
            $query = ApiRequest::query();
            $idProducto = (int) ($query['id_producto'] ?? $query['id'] ?? 0);
            if ($idProducto < 1) {
                throw new AppException('ID de producto requerido', HTTP_BAD_REQUEST);
            }
            $producto = $this->inventarioService->buscarProducto($idProducto);
            $movimientos = $this->inventarioService->listarMovimientos($idProducto);
            ApiResponse::ok([
                'producto'    => $producto->toArray(),
                'movimientos' => array_map(static function (MovimientoStock $mov) {
                    return $mov->toArray();
                }, $movimientos),
            ]);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function movimiento(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'inventario.ajustar', 'No puede ajustar el stock');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $producto = $this->inventarioService->registrarMovimiento($body);
            ApiResponse::ok($producto->toArray(), 'Movimiento registrado', HTTP_CREATED);
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
