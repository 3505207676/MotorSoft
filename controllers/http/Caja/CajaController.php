<?php

class CajaController
{
    private CajaService $caja;
    private AuthService $authService;

    public function __construct(CajaService $caja, AuthService $authService)
    {
        $this->caja        = $caja;
        $this->authService = $authService;
    }

    public function resumen(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->authService->exigirPermiso('contabilidad.ver', null, 'No puede ver la contabilidad');
            ApiResponse::ok($this->caja->resumen($actor['usuario']));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function activa(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->caja->sembrarBase((int) $actor['usuario']->getIdUsuario());
            $sesion = $this->caja->sesionOperable($actor['usuario']);
            $cuentas = [];
            if ($actor['usuario']->puedeOperarCaja()) {
                $cuentas = array_map(static fn (Cuenta $c) => $c->toArray(), $this->caja->cuentasParaAbrir());
            }
            $payload = $sesion ? $sesion->toArray() : null;
            if (is_array($payload)) {
                $payload['es_propia'] = (int) $sesion->getIdUsuario() === (int) $actor['usuario']->getIdUsuario();
            }
            ApiResponse::ok([
                'sesion'       => $payload,
                'puede_operar' => $actor['usuario']->puedeOperarCaja(),
                'cuentas'      => $cuentas,
            ]);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function listarCuentas(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->authService->exigirAlgunPermiso(
                ['caja.abrir', 'contabilidad.ver'],
                null,
                'No puede consultar cuentas'
            );
            $this->caja->sembrarBase((int) $actor['usuario']->getIdUsuario());
            ApiResponse::ok(array_map(static fn (Cuenta $c) => $c->toArray(), $this->caja->listarCuentas()));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function guardarCuenta(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->authService->exigirPermiso('cuentas.crear', null, 'No puede gestionar cuentas');
            $cuenta = $this->caja->guardarCuenta(ApiRequest::jsonBody(), $actor['usuario']);
            ApiResponse::ok($cuenta->toArray(), 'Cuenta guardada', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function listarConceptos(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->authService->exigirAlgunPermiso(
                ['caja.ver', 'caja.transaccion', 'facturas.pagar', 'contabilidad.ver'],
                null,
                'No puede consultar conceptos'
            );
            $this->caja->sembrarBase((int) $actor['usuario']->getIdUsuario());
            ApiResponse::ok(array_map(
                static fn (ConceptoFinanciero $c) => $c->toArray(),
                $this->caja->listarConceptos()
            ));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function listarSesiones(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->authService->exigirAlgunPermiso(
                ['caja.ver', 'contabilidad.ver'],
                null,
                'No puede consultar sesiones de caja'
            );
            $sesiones = $this->caja->listarSesiones();
            if (!$actor['usuario']->puede('contabilidad.ver')) {
                $id = (int) $actor['usuario']->getIdUsuario();
                $sesiones = array_values(array_filter(
                    $sesiones,
                    static fn (SesionCaja $s) => (int) $s->getIdUsuario() === $id
                ));
            }
            ApiResponse::ok(array_map(static fn (SesionCaja $s) => $s->toArray(), $sesiones));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function abrir(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('caja.abrir', null, 'No puede abrir caja');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $sesion = $this->caja->abrir($body, $actor['usuario']);
            ApiResponse::ok($sesion->toArray(), 'Caja abierta', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function cerrar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT']);
            $actor = $this->authService->exigirPermiso('caja.cerrar', null, 'No puede cerrar caja');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $sesion = $this->caja->cerrar($body, $actor['usuario']);
            ApiResponse::ok($sesion->toArray(), 'Caja cerrada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function listarTransacciones(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->authService->exigirAlgunPermiso(
                ['caja.ver', 'contabilidad.ver'],
                null,
                'No puede consultar movimientos de caja'
            );
            $query = ApiRequest::query();
            $filtros = [
                'tipo'      => $query['tipo'] ?? null,
                'id_cuenta' => $query['id_cuenta'] ?? null,
                'id_caja'   => $query['id_caja'] ?? null,
            ];
            if (!$actor['usuario']->puede('contabilidad.ver')) {
                $propia = $this->caja->sesionOperable($actor['usuario']);
                $filtros['id_caja'] = $propia ? (int) $propia->getIdCaja() : -1;
            }
            $data = array_map(
                static fn (TransaccionCaja $t) => $t->toArray(),
                $this->caja->listarTransacciones($filtros)
            );
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function registrarTransaccion(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('caja.transaccion', null, 'No puede registrar movimientos de caja');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $tx = $this->caja->registrarMovimiento($body, $actor['usuario']);
            ApiResponse::ok($tx->toArray(), 'Movimiento registrado', HTTP_CREATED);
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
