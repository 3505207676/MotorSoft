<?php

require_once __DIR__ . '/../../models/Caja/Cuenta.php';
require_once __DIR__ . '/../../models/Caja/SesionCaja.php';
require_once __DIR__ . '/../../models/Caja/ConceptoFinanciero.php';
require_once __DIR__ . '/../../models/Caja/TransaccionCaja.php';
require_once __DIR__ . '/../../models/Facturacion/Factura.php';
require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../../repositories/Caja/CuentaRepository.php';
require_once __DIR__ . '/../../repositories/Caja/SesionCajaRepository.php';
require_once __DIR__ . '/../../repositories/Caja/ConceptoFinancieroRepository.php';
require_once __DIR__ . '/../../repositories/Caja/TransaccionCajaRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class CajaService
{
    private const MSG_SIN_CAJA = 'No hay sesión de caja activa. Abra caja para registrar dinero.';

    private Database $db;
    private CuentaRepository $cuentas;
    private SesionCajaRepository $sesiones;
    private ConceptoFinancieroRepository $conceptos;
    private TransaccionCajaRepository $transacciones;
    private AuthService $auth;

    public function __construct(
        Database $db,
        CuentaRepository $cuentas,
        SesionCajaRepository $sesiones,
        ConceptoFinancieroRepository $conceptos,
        TransaccionCajaRepository $transacciones,
        AuthService $auth
    ) {
        $this->db            = $db;
        $this->cuentas       = $cuentas;
        $this->sesiones      = $sesiones;
        $this->conceptos     = $conceptos;
        $this->transacciones = $transacciones;
        $this->auth          = $auth;
    }

    public function sembrarBase(int $createdBy): void
    {
        if ($createdBy < 1) {
            return;
        }
        if (!$this->cuentas->buscarPorNombre('Caja Principal')) {
            $this->cuentas->guardar(new Cuenta('Caja Principal', 'Efectivo', $createdBy, 0));
        }
        if (!$this->cuentas->buscarPorNombre('Banco')) {
            $this->cuentas->guardar(new Cuenta('Banco', 'Banco', $createdBy, 0));
        }
        $catalogo = [
            [ConceptoFinanciero::PAGO_ORDEN, ConceptoFinanciero::INGRESO, 'Cobro de factura de orden de servicio'],
            [ConceptoFinanciero::VENTA_MOSTRADOR, ConceptoFinanciero::INGRESO, 'Cobro de venta de mostrador'],
            [ConceptoFinanciero::ANULACION, ConceptoFinanciero::EGRESO, 'Reverso de cobro por factura anulada'],
            [ConceptoFinanciero::AJUSTE, ConceptoFinanciero::INGRESO, 'Ajuste manual de caja'],
            [ConceptoFinanciero::GASTO, ConceptoFinanciero::EGRESO, 'Gasto operativo del taller'],
            [ConceptoFinanciero::PAGO_NOMINA, ConceptoFinanciero::EGRESO, 'Pago de salarios y nómina'],
        ];
        foreach ($catalogo as $fila) {
            if (!$this->conceptos->buscarPorNombre($fila[0])) {
                $this->conceptos->guardar(new ConceptoFinanciero($fila[0], $fila[1], $fila[2], $createdBy));
            }
        }
    }

    /** @return Cuenta[] */
    public function listarCuentas(): array
    {
        return $this->cuentas->listar();
    }

    /** @return Cuenta[] */
    public function cuentasParaAbrir(): array
    {
        return array_values(array_filter(
            $this->cuentas->listar(),
            static fn (Cuenta $c) => $c->isActiva() && $c->esEfectivo()
        ));
    }

    public function guardarCuenta(array $data, Usuario $actor): Cuenta
    {
        if (!$actor->puede('cuentas.crear')) {
            throw new AppException('No tiene permiso para gestionar cuentas', HTTP_FORBIDDEN);
        }
        $id = (int) ($data['id_cuenta'] ?? $data['id'] ?? 0);
        if ($id > 0) {
            $cuenta = $this->cuentas->buscarPorId($id);
            if (!$cuenta) {
                throw new AppException('Cuenta no encontrada', HTTP_NOT_FOUND);
            }
            if (isset($data['nombre'])) {
                $cuenta->setNombre((string) $data['nombre']);
            }
            if (isset($data['tipo'])) {
                $cuenta->setTipo((string) $data['tipo']);
            }
            if (isset($data['estado'])) {
                $cuenta->setEstado((string) $data['estado']);
            }
            $cuenta->tocarUpdatedAt((int) $actor->getIdUsuario());
            return $this->cuentas->guardar($cuenta);
        }
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $tipo   = trim((string) ($data['tipo'] ?? 'Efectivo'));
        if ($nombre === '') {
            throw new AppException('El nombre de la cuenta es requerido', HTTP_BAD_REQUEST);
        }
        if ($this->cuentas->buscarPorNombre($nombre)) {
            throw new AppException('Ya existe una cuenta con ese nombre', HTTP_BAD_REQUEST);
        }
        $cuenta = new Cuenta(
            $nombre,
            $tipo !== '' ? $tipo : 'Efectivo',
            (int) $actor->getIdUsuario(),
            (float) ($data['saldo_actual'] ?? $data['saldo_inicial'] ?? 0)
        );
        if (!empty($data['estado'])) {
            $cuenta->setEstado((string) $data['estado']);
        }
        return $this->cuentas->guardar($cuenta);
    }

    /** @return ConceptoFinanciero[] */
    public function listarConceptos(): array
    {
        return $this->conceptos->listar();
    }

    /** @return SesionCaja[] */
    public function listarSesiones(): array
    {
        return $this->sesiones->listar();
    }

    public function sesionActiva(int $idUsuario): ?SesionCaja
    {
        return $this->sesiones->activaPorUsuario($idUsuario);
    }

    public function sesionOperable(Usuario $usuario): ?SesionCaja
    {
        $propia = $this->sesiones->activaPorUsuario((int) $usuario->getIdUsuario());
        if ($propia && $propia->isActiva()) {
            return $propia;
        }
        if (
            $usuario->puedeOperarCaja()
            || $usuario->puede('facturas.pagar')
            || $usuario->puede('contabilidad.ver')
        ) {
            $taller = $this->sesiones->activaTaller();
            if ($taller && $taller->isActiva()) {
                return $taller;
            }
        }
        return null;
    }

    public function exigirSesionCaja(Usuario $usuario): SesionCaja
    {
        $sesion = $this->sesionOperable($usuario);
        if (!$sesion || !$sesion->isActiva()) {
            throw new AppException(self::MSG_SIN_CAJA, HTTP_BAD_REQUEST);
        }
        return $sesion;
    }

    public function abrir(array $data, Usuario $actor): SesionCaja
    {
        if (!$actor->puedeOperarCaja()) {
            throw new AppException('No tiene permiso para abrir caja', HTTP_FORBIDDEN);
        }
        $idUsuario = (int) $actor->getIdUsuario();
        if ($this->sesiones->activaPorUsuario($idUsuario)) {
            throw new AppException('Ya tiene una sesión de caja abierta', HTTP_BAD_REQUEST);
        }
        $idCuenta = (int) ($data['id_cuenta'] ?? 0);
        $cuenta = $this->cuentas->buscarPorId($idCuenta);
        if (!$cuenta || !$cuenta->isActiva()) {
            throw new AppException('Seleccione una cuenta activa', HTTP_BAD_REQUEST);
        }
        if (!$cuenta->esEfectivo()) {
            throw new AppException('La caja física solo se abre sobre una cuenta de efectivo', HTTP_BAD_REQUEST);
        }
        if ($this->sesiones->activaPorCuenta($idCuenta)) {
            throw new AppException('Esa cuenta ya tiene una sesión de caja abierta', HTTP_BAD_REQUEST);
        }
        $monto = (float) ($data['monto_apertura'] ?? $cuenta->getSaldoActual());
        if ($monto < 0) {
            throw new AppException('El monto de apertura no puede ser negativo', HTTP_BAD_REQUEST);
        }
        $saldoAntes = round($cuenta->getSaldoActual(), 2);
        $monto = round($monto, 2);

        $this->db->beginTransaction();
        try {
            $sesion = new SesionCaja($idCuenta, $idUsuario, $monto);
            $guardada = $this->sesiones->guardar($sesion);
            if (abs($monto - $saldoAntes) >= 0.01) {
                $cuenta->setSaldoActual($monto);
                $cuenta->tocarUpdatedAt($idUsuario);
                $this->cuentas->guardar($cuenta);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'CREATE',
            'tablaAfectada'     => 'Sesiones_Caja',
            'registroId'        => (int) $guardada->getIdCaja(),
            'valoresAnteriores' => ['saldo_cuenta' => $saldoAntes],
            'valoresNuevos'     => array_merge($guardada->toArray(), ['saldo_cuenta' => $monto]),
            'ipAddress'         => $data['ip'] ?? null,
        ]);

        return $this->sesiones->buscarPorId((int) $guardada->getIdCaja()) ?: $guardada;
    }

    public function cerrar(array $data, Usuario $actor): SesionCaja
    {
        $idCaja = (int) ($data['id_caja'] ?? 0);
        $sesion = $idCaja > 0
            ? $this->sesiones->buscarPorId($idCaja)
            : $this->sesionOperable($actor);
        if (!$sesion || !$sesion->isActiva()) {
            throw new AppException('No hay sesión de caja activa para cerrar', HTTP_BAD_REQUEST);
        }
        $esDuenio = $sesion->getIdUsuario() === (int) $actor->getIdUsuario();
        if (!$esDuenio && !$actor->puede('caja.cerrar') && !$actor->puede('contabilidad.ver')) {
            throw new AppException('Solo el responsable de la caja o un supervisor pueden cerrarla', HTTP_FORBIDDEN);
        }
        if (!array_key_exists('monto_real', $data) && !array_key_exists('monto', $data)) {
            throw new AppException('Debe informar el monto real en caja para cerrar', HTTP_BAD_REQUEST);
        }
        $montoReal = (float) ($data['monto_real'] ?? $data['monto'] ?? 0);
        if ($montoReal < 0) {
            $montoReal = 0.0;
        }
        $ingresos = $this->transacciones->sumaPorCaja((int) $sesion->getIdCaja(), ConceptoFinanciero::INGRESO);
        $egresos  = $this->transacciones->sumaPorCaja((int) $sesion->getIdCaja(), ConceptoFinanciero::EGRESO);
        $sistema  = round($sesion->getMontoApertura() + $ingresos - $egresos, 2);
        $cuenta = $this->cuentas->buscarPorId($sesion->getIdCuenta());
        $saldoAntesCierre = $cuenta ? $cuenta->getSaldoActual() : null;

        $this->db->beginTransaction();
        try {
            $sesion->cerrar($montoReal, $sistema);
            $guardada = $this->sesiones->guardar($sesion);
            if ($cuenta) {
                $cuenta->setSaldoActual($montoReal);
                $cuenta->tocarUpdatedAt((int) $actor->getIdUsuario());
                $this->cuentas->guardar($cuenta);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Sesiones_Caja',
            'registroId'        => (int) $guardada->getIdCaja(),
            'valoresAnteriores' => ['monto_sistema' => $sistema, 'saldo_cuenta' => $saldoAntesCierre],
            'valoresNuevos'     => $guardada->toArray(),
            'ipAddress'         => $data['ip'] ?? null,
        ]);

        return $this->sesiones->buscarPorId((int) $guardada->getIdCaja()) ?: $guardada;
    }

    /**
     * @return TransaccionCaja[]
     */
    public function listarTransacciones(array $filtros = []): array
    {
        return $this->transacciones->listar($filtros);
    }

    public function registrarMovimiento(array $data, Usuario $actor): TransaccionCaja
    {
        $sesion = $this->exigirSesionCaja($actor);
        $concepto = $this->resolverConcepto($data);
        $tipo = trim((string) ($data['tipo'] ?? $concepto->getTipo()));
        if ($tipo !== ConceptoFinanciero::INGRESO && $tipo !== ConceptoFinanciero::EGRESO) {
            throw new AppException('El tipo debe ser Ingreso o Egreso', HTTP_BAD_REQUEST);
        }
        if (strcasecmp($tipo, $concepto->getTipo()) !== 0) {
            throw new AppException('El tipo de movimiento no coincide con el concepto', HTTP_BAD_REQUEST);
        }
        $monto = round(abs((float) ($data['monto'] ?? 0)), 2);
        if ($monto <= 0) {
            throw new AppException('El monto debe ser mayor a cero', HTTP_BAD_REQUEST);
        }

        $cuenta = $this->resolverCuentaMovimiento($sesion, $data);
        if (!$cuenta || !$cuenta->isActiva()) {
            throw new AppException('La cuenta destino no está disponible', HTTP_BAD_REQUEST);
        }
        $afectaCaja = $this->afectaCajaFisica($sesion, $cuenta, $data);

        $tx = new TransaccionCaja(
            (int) $concepto->getIdConcepto(),
            $monto,
            $tipo,
            (int) $actor->getIdUsuario(),
            (int) $cuenta->getIdCuenta()
        );
        $tx->setIdCaja((int) $sesion->getIdCaja());
        if (!empty($data['id_factura'])) {
            $tx->setIdFactura((int) $data['id_factura']);
        }
        $tx->setConcepto($concepto);
        $tx->setCuenta($cuenta);

        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }
        try {
            $cuenta->aplicarMovimiento($tipo, $monto);
            $cuenta->tocarUpdatedAt((int) $actor->getIdUsuario());
            $this->cuentas->guardar($cuenta);
            if ($afectaCaja) {
                $sesion->aplicarMovimiento($tipo, $monto);
                $this->sesiones->guardar($sesion);
            }
            $this->transacciones->guardar($tx);
            if ($propia) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($propia) {
                $this->db->rollback();
            }
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Transacciones_Caja',
            'registroId'    => (int) $tx->getIdTransaccion(),
            'valoresNuevos' => $tx->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return $tx;
    }

    public function registrarCobroFactura(Factura $factura, Usuario $actor, array $contexto = []): TransaccionCaja
    {
        if ($this->netoCobradoFactura((int) $factura->getIdFactura()) > 0.009) {
            throw new AppException('Esta factura ya tiene un cobro registrado', HTTP_BAD_REQUEST);
        }
        $metodo = Factura::normalizarMetodo($factura->getMetodoPago());
        $nombre = $factura->getTipoFactura() === Factura::TIPO_MOSTRADOR
            ? ConceptoFinanciero::VENTA_MOSTRADOR
            : ConceptoFinanciero::PAGO_ORDEN;
        $cuenta = $this->cuentaDestinoMetodo($metodo, $actor);
        $sesion = $this->exigirSesionCaja($actor);
        return $this->registrarMovimiento(array_merge($contexto, [
            'concepto'           => $nombre,
            'tipo'               => ConceptoFinanciero::INGRESO,
            'monto'              => $factura->getTotal(),
            'id_factura'         => $factura->getIdFactura(),
            'id_cuenta'          => $cuenta->getIdCuenta(),
            'afecta_caja_fisica' => $cuenta->esEfectivo()
                && (int) $cuenta->getIdCuenta() === (int) $sesion->getIdCuenta(),
        ]), $actor);
    }

    public function registrarAnulacionFactura(Factura $factura, Usuario $actor, array $contexto = []): ?TransaccionCaja
    {
        $neto = $this->netoCobradoFactura((int) $factura->getIdFactura());
        if ($neto <= 0.009) {
            return null;
        }
        $origen = $this->ultimoCobroFactura((int) $factura->getIdFactura());
        $idCuenta = $origen ? $origen->getIdCuenta() : null;
        $sesion = $this->exigirSesionCaja($actor);
        $afectaCaja = $idCuenta && (int) $idCuenta === (int) $sesion->getIdCuenta();
        return $this->registrarMovimiento(array_merge($contexto, [
            'concepto'           => ConceptoFinanciero::ANULACION,
            'tipo'               => ConceptoFinanciero::EGRESO,
            'monto'              => $neto,
            'id_factura'         => $factura->getIdFactura(),
            'id_cuenta'          => $idCuenta,
            'afecta_caja_fisica' => $afectaCaja,
        ]), $actor);
    }

    public function registrarPagoNomina(float $monto, Usuario $actor, array $contexto = []): TransaccionCaja
    {
        $this->sembrarBase((int) $actor->getIdUsuario());
        return $this->registrarMovimiento(array_merge($contexto, [
            'concepto' => ConceptoFinanciero::PAGO_NOMINA,
            'tipo'     => ConceptoFinanciero::EGRESO,
            'monto'    => $monto,
        ]), $actor);
    }

    public function resumen(Usuario $actor): array
    {
        $this->sembrarBase((int) $actor->getIdUsuario());
        $cuentas = array_map(static fn (Cuenta $c) => $c->toArray(), $this->cuentas->listar());
        $conceptos = array_map(static fn (ConceptoFinanciero $c) => $c->toArray(), $this->conceptos->listar());
        $sesiones = array_map(static fn (SesionCaja $s) => $s->toArray(), $this->sesiones->listar());
        $transacciones = array_map(static fn (TransaccionCaja $t) => $t->toArray(), $this->transacciones->listar());
        $ingresos = 0.0;
        $egresos = 0.0;
        foreach ($transacciones as $t) {
            if (($t['tipo'] ?? '') === ConceptoFinanciero::INGRESO) {
                $ingresos += (float) $t['monto'];
            } else {
                $egresos += (float) $t['monto'];
            }
        }
        $sesion = $this->sesionOperable($actor);
        $turnoIngresos = 0.0;
        $turnoEgresos = 0.0;
        if ($sesion && $sesion->getIdCaja()) {
            $turnoIngresos = $this->transacciones->sumaPorCaja((int) $sesion->getIdCaja(), ConceptoFinanciero::INGRESO);
            $turnoEgresos = $this->transacciones->sumaPorCaja((int) $sesion->getIdCaja(), ConceptoFinanciero::EGRESO);
        }
        return [
            'cuentas'        => $cuentas,
            'conceptos'      => $conceptos,
            'sesiones'       => $sesiones,
            'transacciones'  => $transacciones,
            'sesion_activa'  => $sesion ? $sesion->toArray() : null,
            'puede_operar'   => $actor->puedeOperarCaja(),
            'es_admin'       => $actor->esAdministrador(),
            'puede_cuentas'  => $actor->puede('cuentas.crear'),
            'totales'        => [
                'ingresos'      => $turnoIngresos,
                'egresos'       => $turnoEgresos,
                'balance'       => $turnoIngresos - $turnoEgresos,
                'saldo_cuentas' => array_sum(array_column($cuentas, 'saldo_actual')),
                'ambito'        => $sesion ? 'turno' : 'sin_turno',
                'historico_ingresos' => $ingresos,
                'historico_egresos'  => $egresos,
            ],
        ];
    }

    public function netoCobradoFactura(int $idFactura): float
    {
        if ($idFactura < 1) {
            return 0.0;
        }
        $neto = 0.0;
        foreach ($this->transacciones->listarPorFactura($idFactura) as $tx) {
            if ($tx->getTipo() === ConceptoFinanciero::INGRESO) {
                $neto += $tx->getMonto();
            } else {
                $neto -= $tx->getMonto();
            }
        }
        return round($neto, 2);
    }

    private function ultimoCobroFactura(int $idFactura): ?TransaccionCaja
    {
        $ultimo = null;
        foreach ($this->transacciones->listarPorFactura($idFactura) as $tx) {
            if ($tx->getTipo() === ConceptoFinanciero::INGRESO) {
                $ultimo = $tx;
            }
        }
        return $ultimo;
    }

    private function cuentaDestinoMetodo(string $metodo, Usuario $actor): Cuenta
    {
        $this->sembrarBase((int) $actor->getIdUsuario());
        if ($metodo === 'Efectivo') {
            $sesion = $this->exigirSesionCaja($actor);
            $cuenta = $this->cuentas->buscarPorId($sesion->getIdCuenta());
            if (!$cuenta || !$cuenta->isActiva()) {
                throw new AppException('La cuenta de efectivo de la caja no está disponible', HTTP_BAD_REQUEST);
            }
            return $cuenta;
        }
        $banco = $this->cuentas->buscarPorNombre('Banco');
        if (!$banco || !$banco->isActiva()) {
            throw new AppException('No hay cuenta Banco activa para registrar tarjeta o transferencia', HTTP_BAD_REQUEST);
        }
        return $banco;
    }

    private function resolverCuentaMovimiento(SesionCaja $sesion, array $data): ?Cuenta
    {
        $id = (int) ($data['id_cuenta'] ?? 0);
        if ($id > 0) {
            return $this->cuentas->buscarPorId($id);
        }
        return $this->cuentas->buscarPorId($sesion->getIdCuenta());
    }

    private function afectaCajaFisica(SesionCaja $sesion, Cuenta $cuenta, array $data): bool
    {
        if (array_key_exists('afecta_caja_fisica', $data)) {
            return (bool) $data['afecta_caja_fisica'];
        }
        return $cuenta->esEfectivo() && (int) $cuenta->getIdCuenta() === (int) $sesion->getIdCuenta();
    }

    private function resolverConcepto(array $data): ConceptoFinanciero
    {
        $id = (int) ($data['id_concepto'] ?? 0);
        if ($id > 0) {
            $concepto = $this->conceptos->buscarPorId($id);
        } else {
            $nombre = trim((string) ($data['concepto'] ?? $data['nombre_concepto'] ?? ''));
            $concepto = $nombre !== '' ? $this->conceptos->buscarPorNombre($nombre) : null;
        }
        if (!$concepto) {
            throw new AppException('Concepto financiero no válido', HTTP_BAD_REQUEST);
        }
        return $concepto;
    }
}
