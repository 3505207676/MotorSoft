<?php

require_once __DIR__ . '/../../models/Ordenes/OrdenServicio.php';
require_once __DIR__ . '/DetalleServicioRepository.php';
require_once __DIR__ . '/../Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../Clientes/VehiculoRepository.php';

class OrdenServicioRepository
{
    private Database $db;
    private DetalleServicioRepository $detalles;
    private UsuarioRepository $usuarios;
    private VehiculoRepository $vehiculos;

    public function __construct(
        Database $db,
        DetalleServicioRepository $detalles,
        UsuarioRepository $usuarios,
        VehiculoRepository $vehiculos
    ) {
        $this->db        = $db;
        $this->detalles  = $detalles;
        $this->usuarios  = $usuarios;
        $this->vehiculos = $vehiculos;
    }

    public function vehiculoExiste(int $idVehiculo): bool
    {
        $vehiculo = $this->vehiculos->buscarPorId($idVehiculo);
        return $vehiculo !== null && $vehiculo->isActivo();
    }

    public function obtenerVehiculoConCliente(int $idVehiculo): ?array
    {
        $sql = 'SELECT
                    v.id_vehiculo, v.placa, v.marca, v.modelo, v.`año` AS anio, v.tipo, v.estado AS vehiculo_estado,
                    c.id_cliente, c.nombre AS cliente_nombre, c.documento AS cliente_documento,
                    c.telefono AS cliente_telefono, c.email AS cliente_email
                FROM Vehiculo v
                INNER JOIN Clientes c ON c.id_cliente = v.id_cliente
                WHERE v.id_vehiculo = :id
                  AND v.deleted_at IS NULL
                LIMIT 1';
        $stmt = $this->db->query($sql, [':id' => $idVehiculo]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function buscarPorId(int $id, bool $conDetalles = true): ?OrdenServicio
    {
        $stmt = $this->db->query(
            'SELECT * FROM Orden_Servicio WHERE id_orden = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }
        return $this->hydrate($fila, $conDetalles);
    }

    /** @return OrdenServicio[] */
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT * FROM Orden_Servicio WHERE deleted_at IS NULL';
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= ' AND estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['estados']) && is_array($filtros['estados'])) {
            $keys = [];
            foreach (array_values($filtros['estados']) as $i => $estado) {
                $key = ':est' . $i;
                $keys[] = $key;
                $params[$key] = $estado;
            }
            if ($keys) {
                $sql .= ' AND estado IN (' . implode(', ', $keys) . ')';
            }
        }
        if (!empty($filtros['id_usuario'])) {
            $sql .= ' AND id_usuario = :id_usuario';
            $params[':id_usuario'] = (int) $filtros['id_usuario'];
        }
        if (!empty($filtros['id_vehiculo'])) {
            $sql .= ' AND id_vehiculo = :id_vehiculo';
            $params[':id_vehiculo'] = (int) $filtros['id_vehiculo'];
        }
        if (!empty($filtros['id_cliente'])) {
            $sql .= ' AND id_vehiculo IN (SELECT id_vehiculo FROM Vehiculo WHERE id_cliente = :id_cli AND deleted_at IS NULL)';
            $params[':id_cli'] = (int) $filtros['id_cliente'];
        }
        if (!empty($filtros['desde'])) {
            $sql .= ' AND fecha_ingreso >= :desde';
            $params[':desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $sql .= ' AND fecha_ingreso < DATE_ADD(:hasta, INTERVAL 1 DAY)';
            $params[':hasta'] = $filtros['hasta'];
        }

        $sql .= ' ORDER BY fecha_ingreso DESC';
        $stmt = $this->db->query($sql, $params);
        $filas = $stmt->fetchAll();

        $ids = [];
        foreach ($filas as $fila) {
            $ids[] = (int) $fila['id_orden'];
        }
        $detallesPorOrden = $this->detalles->listarPorOrdenes($ids);

        $ordenes = [];
        foreach ($filas as $fila) {
            $id = (int) $fila['id_orden'];
            $usuario = $this->usuarios->buscarPorId((int) $fila['id_usuario'], true);
            $ordenes[] = OrdenServicio::fromArray($fila, $detallesPorOrden[$id] ?? [], $usuario);
        }
        return $ordenes;
    }

    public function guardarCabecera(OrdenServicio $orden): OrdenServicio
    {
        if ($orden->getIdOrden() === null) {
            $sql = 'INSERT INTO Orden_Servicio (
                        id_vehiculo, id_usuario, fecha_ingreso, fecha_salida,
                        descripcion, estado, created_at, created_by
                    ) VALUES (
                        :id_vehiculo, :id_usuario, :fecha_ingreso, :fecha_salida,
                        :descripcion, :estado, :created_at, :created_by
                    )';
            $this->db->query($sql, [
                ':id_vehiculo'   => $orden->getIdVehiculo(),
                ':id_usuario'    => $orden->getIdUsuario(),
                ':fecha_ingreso' => $orden->getFechaIngreso()->format('Y-m-d H:i:s'),
                ':fecha_salida'  => $orden->getFechaSalida()
                    ? $orden->getFechaSalida()->format('Y-m-d H:i:s')
                    : null,
                ':descripcion'   => $orden->getDescripcion(),
                ':estado'        => $orden->getEstado(),
                ':created_at'    => $orden->getCreatedAt()->format('Y-m-d H:i:s'),
                ':created_by'    => $orden->getCreatedBy(),
            ]);
            $orden->assignId((int) $this->db->lastInsertId());
            return $orden;
        }

        $sql = 'UPDATE Orden_Servicio SET
                    id_vehiculo = :id_vehiculo,
                    id_usuario = :id_usuario,
                    fecha_ingreso = :fecha_ingreso,
                    fecha_salida = :fecha_salida,
                    descripcion = :descripcion,
                    estado = :estado,
                    updated_at = :updated_at,
                    updated_by = :updated_by,
                    deleted_at = :deleted_at
                WHERE id_orden = :id';
        $this->db->query($sql, [
            ':id_vehiculo'   => $orden->getIdVehiculo(),
            ':id_usuario'    => $orden->getIdUsuario(),
            ':fecha_ingreso' => $orden->getFechaIngreso()->format('Y-m-d H:i:s'),
            ':fecha_salida'  => $orden->getFechaSalida()
                ? $orden->getFechaSalida()->format('Y-m-d H:i:s')
                : null,
            ':descripcion'   => $orden->getDescripcion(),
            ':estado'        => $orden->getEstado(),
            ':updated_at'    => $orden->getUpdatedAt()
                ? $orden->getUpdatedAt()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s'),
            ':updated_by'    => $orden->getUpdatedBy(),
            ':deleted_at'    => $orden->getDeletedAt()
                ? $orden->getDeletedAt()->format('Y-m-d H:i:s')
                : null,
            ':id'            => $orden->getIdOrden(),
        ]);
        return $orden;
    }

    private function hydrate(array $fila, bool $conDetalles): OrdenServicio
    {
        $detalles = $conDetalles
            ? $this->detalles->listarPorOrden((int) $fila['id_orden'])
            : [];
        $usuario = $this->usuarios->buscarPorId((int) $fila['id_usuario'], true);
        return OrdenServicio::fromArray($fila, $detalles, $usuario);
    }
}
