<?php

class VentaController
{
    private FacturacionService $facturacion;
    private AuthService $authService;

    public function __construct(FacturacionService $facturacion, AuthService $authService)
    {
        $this->facturacion = $facturacion;
        $this->authService = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'ventas.ver', 'No puede consultar ventas');
            $query = ApiRequest::query();
            if (!empty($query['id'])) {
                ApiResponse::ok($this->facturacion->serializarVenta($this->facturacion->buscarVenta((int) $query['id'])));
                return;
            }
            ApiResponse::ok($this->facturacion->listarVentasComoArray());
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function crear(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'ventas.crear', 'No puede registrar ventas');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['actor'] = $actor['usuario'];
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $factura = $this->facturacion->emitirVentaDirecta($body);
            ApiResponse::ok($factura->toArray(), 'Venta registrada', HTTP_CREATED);
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
