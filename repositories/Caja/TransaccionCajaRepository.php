<?php

require_once __DIR__ . '/../../models/Caja/TransaccionCaja.php';
require_once __DIR__ . '/ConceptoFinancieroRepository.php';
require_once __DIR__ . '/CuentaRepository.php';

class TransaccionCajaRepository
{
    private Database $db;
    private ConceptoFinancieroRepository $conceptos;
    private CuentaRepository $cuentas;

    public function __construct(Database $db, ConceptoFinancieroRepository $conceptos, CuentaRepository $cuentas)
    {
        $this->db        = $db;
        $this->conceptos = $conceptos;
        $this->cuentas   = $cuentas;
        $this->asegurarIdCaja();
    }

    public function asegurarIdCaja(): void
    {
        $fila = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c',
            [':t' => 'Transacciones_Caja', ':c' => 'id_caja']
        )->fetch();
        if ((int) ($fila['total'] ?? 0) > 0) {
            return;
        }
        $this->db->query('ALTER TABLE Transacciones_Caja ADD COLUMN id_caja INT NULL');
    }

    public function guardar(TransaccionCaja $tx): TransaccionCaja
    {
        $this->db->query(
            'INSERT INTO Transacciones_Caja (
                id_concepto, id_factura, id_cuenta, id_movimiento, id_caja, monto, tipo, fecha, created_at, created_by
             ) VALUES (:co, :f, :cu, :m, :caja, :mo, :t, :fe, :ca, :cb)',
            [
                ':co'   => $tx->getIdConcepto(),
                ':f'    => $tx->getIdFactura(),
                ':cu'   => $tx->getIdCuenta(),
                ':m'    => $tx->getIdMovimiento(),
                ':caja' => $tx->getIdCaja(),
                ':mo'   => $tx->getMonto(),
                ':t'    => $tx->getTipo(),
                ':fe'   => $tx->getFecha()->format('Y-m-d H:i:s'),
                ':ca'   => $tx->getFecha()->format('Y-m-d H:i:s'),
                ':cb'   => $tx->getCreatedBy(),
            ]
        );
        $tx->assignId((int) $this->db->lastInsertId());
        return $tx;
    }

    /** @return TransaccionCaja[] */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT * FROM Transacciones_Caja WHERE deleted_at IS NULL';
        $params = [];
        if (!empty($filtros['tipo'])) {
            $sql .= ' AND tipo = :tipo';
            $params[':tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['id_cuenta'])) {
            $sql .= ' AND id_cuenta = :cu';
            $params[':cu'] = (int) $filtros['id_cuenta'];
        }
        if (!empty($filtros['id_caja'])) {
            $sql .= ' AND id_caja = :caja';
            $params[':caja'] = (int) $filtros['id_caja'];
        }
        $sql .= ' ORDER BY fecha DESC, id_transaccion DESC';
        $lista = [];
        foreach ($this->db->query($sql, $params)->fetchAll() as $fila) {
            $concepto = $this->conceptos->buscarPorId((int) $fila['id_concepto']);
            $cuenta   = !empty($fila['id_cuenta']) ? $this->cuentas->buscarPorId((int) $fila['id_cuenta']) : null;
            $lista[]  = TransaccionCaja::fromArray($fila, $concepto, $cuenta);
        }
        return $lista;
    }

    public function sumaPorCaja(int $idCaja, string $tipo): float
    {
        $fila = $this->db->query(
            'SELECT COALESCE(SUM(t.monto), 0) AS total
             FROM Transacciones_Caja t
             INNER JOIN Sesiones_Caja s ON s.id_caja = t.id_caja
             WHERE t.id_caja = :c AND t.tipo = :t AND t.deleted_at IS NULL
               AND t.id_cuenta = s.id_cuenta',
            [':c' => $idCaja, ':t' => $tipo]
        )->fetch();
        return (float) ($fila['total'] ?? 0);
    }

    /** @return TransaccionCaja[] */
    public function listarPorFactura(int $idFactura): array
    {
        if ($idFactura < 1) {
            return [];
        }
        $lista = [];
        $filas = $this->db->query(
            'SELECT * FROM Transacciones_Caja
             WHERE id_factura = :f AND deleted_at IS NULL
             ORDER BY fecha ASC, id_transaccion ASC',
            [':f' => $idFactura]
        )->fetchAll();
        foreach ($filas as $fila) {
            $concepto = $this->conceptos->buscarPorId((int) $fila['id_concepto']);
            $cuenta   = !empty($fila['id_cuenta']) ? $this->cuentas->buscarPorId((int) $fila['id_cuenta']) : null;
            $lista[]  = TransaccionCaja::fromArray($fila, $concepto, $cuenta);
        }
        return $lista;
    }
}
