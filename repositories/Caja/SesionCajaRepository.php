<?php

require_once __DIR__ . '/../../models/Caja/SesionCaja.php';
require_once __DIR__ . '/CuentaRepository.php';
require_once __DIR__ . '/../Seguridad/UsuarioRepository.php';

class SesionCajaRepository
{
    private Database $db;
    private CuentaRepository $cuentas;
    private UsuarioRepository $usuarios;

    public function __construct(Database $db, CuentaRepository $cuentas, UsuarioRepository $usuarios)
    {
        $this->db       = $db;
        $this->cuentas  = $cuentas;
        $this->usuarios = $usuarios;
    }

    public function buscarPorId(int $id): ?SesionCaja
    {
        $stmt = $this->db->query('SELECT * FROM Sesiones_Caja WHERE id_caja = :id LIMIT 1', [':id' => $id]);
        return $this->hydrate($stmt->fetch() ?: null);
    }

    public function activaPorUsuario(int $idUsuario): ?SesionCaja
    {
        $stmt = $this->db->query(
            'SELECT * FROM Sesiones_Caja WHERE id_usuario = :u AND estado_sesion = :e AND fecha_cierra IS NULL
             ORDER BY id_caja DESC LIMIT 1',
            [':u' => $idUsuario, ':e' => SesionCaja::ACTIVA]
        );
        return $this->hydrate($stmt->fetch() ?: null);
    }

    public function activaPorCuenta(int $idCuenta): ?SesionCaja
    {
        $stmt = $this->db->query(
            'SELECT * FROM Sesiones_Caja WHERE id_cuenta = :c AND estado_sesion = :e AND fecha_cierra IS NULL
             ORDER BY id_caja DESC LIMIT 1',
            [':c' => $idCuenta, ':e' => SesionCaja::ACTIVA]
        );
        return $this->hydrate($stmt->fetch() ?: null);
    }

    public function activaTaller(): ?SesionCaja
    {
        $stmt = $this->db->query(
            'SELECT * FROM Sesiones_Caja WHERE estado_sesion = :e AND fecha_cierra IS NULL
             ORDER BY id_caja DESC LIMIT 1',
            [':e' => SesionCaja::ACTIVA]
        );
        return $this->hydrate($stmt->fetch() ?: null);
    }

    /** @return SesionCaja[] */
    public function listar(): array
    {
        $stmt = $this->db->query('SELECT * FROM Sesiones_Caja ORDER BY fecha_apertura DESC, id_caja DESC');
        $lista = [];
        foreach ($stmt->fetchAll() as $fila) {
            $sesion = $this->hydrate($fila);
            if ($sesion) {
                $lista[] = $sesion;
            }
        }
        return $lista;
    }

    public function guardar(SesionCaja $sesion): SesionCaja
    {
        if ($sesion->getIdCaja() === null) {
            $this->db->query(
                'INSERT INTO Sesiones_Caja (
                    id_cuenta, id_usuario, fecha_apertura, fecha_cierra,
                    monto_apertura, monto_sistema, monto_real, diferencia, estado_sesion
                 ) VALUES (:c, :u, :fa, :fc, :ma, :ms, :mr, :d, :e)',
                [
                    ':c'  => $sesion->getIdCuenta(),
                    ':u'  => $sesion->getIdUsuario(),
                    ':fa' => $sesion->getFechaApertura()->format('Y-m-d H:i:s'),
                    ':fc' => $sesion->getFechaCierra() ? $sesion->getFechaCierra()->format('Y-m-d H:i:s') : null,
                    ':ma' => $sesion->getMontoApertura(),
                    ':ms' => $sesion->getMontoSistema(),
                    ':mr' => $sesion->getMontoReal(),
                    ':d'  => $sesion->getDiferencia(),
                    ':e'  => $sesion->getEstadoSesion(),
                ]
            );
            $sesion->assignId((int) $this->db->lastInsertId());
            return $sesion;
        }
        $this->db->query(
            'UPDATE Sesiones_Caja SET
                fecha_cierra = :fc, monto_sistema = :ms, monto_real = :mr,
                diferencia = :d, estado_sesion = :e
             WHERE id_caja = :id',
            [
                ':fc' => $sesion->getFechaCierra() ? $sesion->getFechaCierra()->format('Y-m-d H:i:s') : null,
                ':ms' => $sesion->getMontoSistema(),
                ':mr' => $sesion->getMontoReal(),
                ':d'  => $sesion->getDiferencia(),
                ':e'  => $sesion->getEstadoSesion(),
                ':id' => $sesion->getIdCaja(),
            ]
        );
        return $sesion;
    }

    private function hydrate(?array $fila): ?SesionCaja
    {
        if (!$fila) {
            return null;
        }
        $usuario = !empty($fila['id_usuario']) ? $this->usuarios->buscarPorId((int) $fila['id_usuario']) : null;
        $cuenta  = !empty($fila['id_cuenta']) ? $this->cuentas->buscarPorId((int) $fila['id_cuenta']) : null;
        return SesionCaja::fromArray($fila, $usuario, $cuenta);
    }
}
