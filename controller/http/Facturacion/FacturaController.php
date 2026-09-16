<?php

class FacturaController
{
    private FacturacionService $facturacion;
    private AuthService $authService;
    private ConfiguracionService $config;

    public function __construct(
        FacturacionService $facturacion,
        AuthService $authService,
        ConfiguracionService $config
    ) {
        $this->facturacion = $facturacion;
        $this->authService = $authService;
        $this->config      = $config;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $query = ApiRequest::query();
            $idClienteActor = (($actor['tipo'] ?? '') === 'cliente' && $actor['cliente'])
                ? (int) $actor['cliente']->getIdCliente()
                : 0;
            if ($idClienteActor < 1 && $actor['usuario']) {
                $this->authService->asegurarPermiso($actor['usuario'], 'facturas.ver', 'No puede consultar facturas');
            }
            if (!empty($query['id'])) {
                $factura = $this->facturacion->buscarFactura((int) $query['id']);
                if ($idClienteActor > 0 && $factura->getIdCliente() !== $idClienteActor) {
                    throw new AppException('No puede ver esta factura', HTTP_FORBIDDEN);
                }
                ApiResponse::ok($this->facturacion->serializarFactura($factura));
                return;
            }
            if ($idClienteActor > 0) {
                $query['id_cliente'] = $idClienteActor;
            } elseif (!empty($query['preview_orden'])) {
                ApiResponse::ok($this->facturacion->previewOrden((int) $query['preview_orden']));
                return;
            } elseif (!empty($query['ordenes_facturables'])) {
                ApiResponse::ok($this->facturacion->ordenesFacturablesComoArray());
                return;
            }
            $facturas = $this->facturacion->listarFacturas([
                'estado'       => $query['estado'] ?? null,
                'tipo_factura' => $query['tipo_factura'] ?? $query['tipo'] ?? null,
                'id_cliente'   => $query['id_cliente'] ?? null,
            ]);
            $data = array_map(function (Factura $f) {
                return $this->facturacion->serializarFactura($f);
            }, $facturas);
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function crear(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'facturas.crear', 'No puede emitir facturas');
            $body = ApiRequest::jsonBody();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['actor'] = $actor['usuario'];
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $tipo = $body['tipo_factura'] ?? $body['tipo'] ?? '';
            if (!empty($body['id_orden']) || $tipo === Factura::TIPO_ORDEN || $tipo === 'orden') {
                $factura = $this->facturacion->emitirDesdeOrden($body);
            } else {
                $factura = $this->facturacion->emitirVentaDirecta($body);
            }
            ApiResponse::ok($this->facturacion->serializarFactura($factura), 'Factura emitida', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'facturas.pagar', 'No puede actualizar facturas');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_factura'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de factura requerido', HTTP_BAD_REQUEST);
            }
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $body['actor'] = $actor['usuario'];
            $body['token'] = ApiRequest::bearerToken();
            $factura = $this->facturacion->actualizarEstado($id, $body);
            ApiResponse::ok($this->facturacion->serializarFactura($factura), 'Factura actualizada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function anular(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso($actor['usuario'], 'facturas.anular', 'No puede anular facturas');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_factura'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de factura requerido', HTTP_BAD_REQUEST);
            }
            $factura = $this->facturacion->anular($id, [
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'actor'      => $actor['usuario'],
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
            ]);
            ApiResponse::ok($this->facturacion->serializarFactura($factura), 'Factura anulada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function pdf(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $id = (int) (ApiRequest::query()['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de factura requerido', HTTP_BAD_REQUEST);
            }
            $idClienteActor = (($actor['tipo'] ?? '') === 'cliente' && $actor['cliente'])
                ? (int) $actor['cliente']->getIdCliente()
                : 0;
            if ($idClienteActor < 1) {
                if (!$actor['usuario']) {
                    throw new AppException('No autorizado', HTTP_FORBIDDEN);
                }
                $this->authService->asegurarPermiso($actor['usuario'], 'facturas.ver', 'No puede consultar facturas');
            }
            $factura = $this->facturacion->buscarFactura($id);
            if ($idClienteActor > 0 && $factura->getIdCliente() !== $idClienteActor) {
                throw new AppException('No puede ver esta factura', HTTP_FORBIDDEN);
            }
            $mapa = $this->config->mapa();
            $bin = (new FacturaPdf())->generar($factura->toArray(), [
                'nombre'     => $mapa[Configuracion::EMPRESA_NOMBRE] ?? 'Taller El Paisa',
                'nit'        => $mapa[Configuracion::EMPRESA_NIT] ?? '',
                'direccion'  => $mapa[Configuracion::EMPRESA_DIRECCION] ?? '',
                'telefono'   => $mapa[Configuracion::EMPRESA_TELEFONO] ?? '',
                'ciudad'     => 'Cumaribo, Vichada',
                'logo_ruta'  => $this->config->archivoLogo(),
            ]);
            $nombre = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($factura->toArray()['numero'] ?? ('FAC-' . $id)));
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nombre . '.pdf"');
            header('Content-Length: ' . strlen($bin));
            header('Cache-Control: private, max-age=0, must-revalidate');
            echo $bin;
            exit;
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
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
