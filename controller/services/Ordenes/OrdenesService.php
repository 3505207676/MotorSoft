<?php

require_once __DIR__ . '/../../models/Ordenes/OrdenServicio.php';
require_once __DIR__ . '/../../models/Ordenes/DetalleServicio.php';
require_once __DIR__ . '/../../models/Facturacion/DetalleItem.php';
require_once __DIR__ . '/../../repositories/Ordenes/OrdenServicioRepository.php';
require_once __DIR__ . '/../../repositories/Ordenes/DetalleServicioRepository.php';
require_once __DIR__ . '/../../repositories/Ordenes/ServicioRepository.php';
require_once __DIR__ . '/../../repositories/Facturacion/DetalleItemRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class OrdenesService
{
    private Database $db;
    private OrdenServicioRepository $ordenes;
    private DetalleServicioRepository $detalles;
    private ServicioRepository $servicios;
    private UsuarioRepository $usuarios;
    private AuthService $auth;
    private DetalleItemRepository $items;

    public function __construct(
        Database $db,
        OrdenServicioRepository $ordenes,
        DetalleServicioRepository $detalles,
        ServicioRepository $servicios,
        UsuarioRepository $usuarios,
        AuthService $auth,
        DetalleItemRepository $items
    ) {
        $this->db       = $db;
        $this->ordenes  = $ordenes;
        $this->detalles = $detalles;
        $this->servicios = $servicios;
        $this->usuarios = $usuarios;
        $this->auth     = $auth;
        $this->items    = $items;
    }

    /** @return OrdenServicio[] */
    public function listarOrdenes(array $filtros = []): array
    {
        return $this->ordenes->listar($filtros);
    }

    /** @return array[] */
    public function listarOrdenesComoArray(array $filtros = []): array
    {
        $lista = [];
        foreach ($this->ordenes->listar($filtros) as $orden) {
            $lista[] = $this->serializarOrden($orden);
        }
        return $lista;
    }

    public function serializarOrden(OrdenServicio $orden): array
    {
        $arr = $orden->toArray();
        $veh = $this->ordenes->obtenerVehiculoConCliente($orden->getIdVehiculo());
        $arr['vehiculo'] = $veh;
        $arr['id_cliente'] = $veh ? (int) ($veh['id_cliente'] ?? 0) : 0;
        $arr['cliente_nombre'] = $veh['cliente_nombre'] ?? null;
        $arr['placa'] = $veh['placa'] ?? null;
        $arr['vehiculo_tipo'] = $veh['tipo'] ?? null;
        $arr['vehiculo_label'] = $veh
            ? trim(($veh['marca'] ?? '') . ' ' . ($veh['modelo'] ?? ''))
            : null;
        $arr['cliente_telefono'] = $veh['cliente_telefono'] ?? null;
        $repuestos = [];
        $totalRep = 0.0;
        $idOrden = (int) ($orden->getIdOrden() ?? 0);
        if ($idOrden > 0) {
            foreach ($this->items->listarPorPadre(DetalleItem::TIPO_ORDEN, $idOrden) as $item) {
                $repuestos[] = $item->toArray();
                $totalRep += $item->subtotal();
            }
        }
        $arr['repuestos'] = $repuestos;
        $arr['total_repuestos'] = round($totalRep, 2);
        $arr['total_con_repuestos'] = round((float) ($arr['total_general'] ?? 0) + $totalRep, 2);
        return $arr;
    }

    public function registrarOrdenes(array $data): OrdenServicio
    {
        $idVehiculo = (int) ($data['id_vehiculo'] ?? $data['vehiculo_id'] ?? 0);
        $idUsuario  = (int) ($data['id_usuario'] ?? $data['mecanico_id'] ?? 0);
        $createdBy  = (int) ($data['created_by'] ?? 0);
        $lineas     = $data['detalles'] ?? $data['servicios'] ?? [];

        if ($idVehiculo < 1 || $idUsuario < 1 || $createdBy < 1) {
            throw new AppException('id_vehiculo, id_usuario (mecánico) y created_by son requeridos', HTTP_BAD_REQUEST);
        }
        if (!$this->ordenes->vehiculoExiste($idVehiculo)) {
            throw new AppException('El vehículo no existe', HTTP_BAD_REQUEST);
        }

        $mecanico = $this->usuarios->buscarPorId($idUsuario);
        if (!$mecanico || !$mecanico->isActivo()) {
            throw new AppException('El mecánico no existe o está inactivo', HTTP_BAD_REQUEST);
        }

        $orden = new OrdenServicio(
            $idVehiculo,
            $idUsuario,
            $createdBy,
            $data['descripcion'] ?? null,
            !empty($data['fecha_ingreso']) ? new DateTime($data['fecha_ingreso']) : null
        );
        $orden->setUsuario($mecanico);
        if (!empty($data['estado'])) {
            $orden->cambiarEstado((string) $data['estado'], $createdBy);
        }
        $fechaEntrega = $data['fecha_salida'] ?? $data['fecha_entrega'] ?? null;
        if (!empty($fechaEntrega)) {
            $orden->setFechaSalida(new DateTime((string) $fechaEntrega));
        }

        $this->ejecutarEnTransaccion(function () use ($orden, $lineas, $createdBy, $data): void {
            $this->ordenes->guardarCabecera($orden);
            $this->persistirLineas(
                $orden,
                is_array($lineas) ? $lineas : [],
                $createdBy,
                !empty($data['permitir_precio_manual'])
            );
        });

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Orden_Servicio',
            'registroId'    => (int) $orden->getIdOrden(),
            'valoresNuevos' => $orden->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return $this->ordenes->buscarPorId((int) $orden->getIdOrden(), true);
    }

    public function obtenerDetalleCompleto(int $id): array
    {
        $orden = $this->ordenes->buscarPorId($id, true);
        if (!$orden) {
            throw new AppException('Orden no encontrada', HTTP_NOT_FOUND);
        }

        return $this->serializarOrden($orden);
    }

    public function cambiarEstado(int $id, string $estado, array $contexto = []): OrdenServicio
    {
        $orden = $this->ordenes->buscarPorId($id, true);
        if (!$orden) {
            throw new AppException('Orden no encontrada', HTTP_NOT_FOUND);
        }
        $antes = $orden->getEstado();
        $orden->cambiarEstado($estado, $contexto['updated_by'] ?? null);
        $this->ordenes->guardarCabecera($orden);

        $this->auth->registrarLog([
            'token'             => $contexto['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Orden_Servicio',
            'registroId'        => $id,
            'valoresAnteriores' => ['estado' => $antes],
            'valoresNuevos'     => ['estado' => $orden->getEstado()],
            'ipAddress'         => $contexto['ip'] ?? null,
        ]);

        return $orden;
    }

    public function guardarOrden(array $data): OrdenServicio
    {
        $id = (int) ($data['id_orden'] ?? $data['id'] ?? 0);
        if ($id < 1) {
            return $this->registrarOrdenes($data);
        }

        $orden = $this->ordenes->buscarPorId($id, true);
        if (!$orden) {
            throw new AppException('Orden no encontrada', HTTP_NOT_FOUND);
        }
        $antes = $orden->toArray();

        if (isset($data['descripcion'])) {
            $orden->setDescripcion((string) $data['descripcion']);
        }
        if (!empty($data['id_usuario']) || !empty($data['mecanico_id'])) {
            $idUsuario = (int) ($data['id_usuario'] ?? $data['mecanico_id']);
            $mecanico = $this->usuarios->buscarPorId($idUsuario);
            if (!$mecanico || !$mecanico->isActivo()) {
                throw new AppException('El mecánico no existe o está inactivo', HTTP_BAD_REQUEST);
            }
            $orden->setUsuario($mecanico);
        }
        if (!empty($data['estado'])) {
            $orden->cambiarEstado((string) $data['estado'], $data['updated_by'] ?? null);
        } else {
            $orden->tocarUpdatedAt($data['updated_by'] ?? null);
        }
        if (array_key_exists('fecha_salida', $data) || array_key_exists('fecha_entrega', $data)) {
            $rawFecha = $data['fecha_salida'] ?? $data['fecha_entrega'];
            $orden->setFechaSalida($rawFecha ? new DateTime((string) $rawFecha) : null);
        }

        $createdBy = (int) ($data['updated_by'] ?? $data['created_by'] ?? $orden->getCreatedBy());
        $lineas = $data['detalles'] ?? $data['servicios'] ?? null;

        $this->ejecutarEnTransaccion(function () use ($orden, $lineas, $createdBy, $data): void {
            $this->ordenes->guardarCabecera($orden);
            if (is_array($lineas)) {
                $this->detalles->eliminarPorOrden((int) $orden->getIdOrden());
                $orden->reemplazarDetalles([]);
                $this->persistirLineas($orden, $lineas, $createdBy, !empty($data['permitir_precio_manual']));
            }
        });

        $actual = $this->ordenes->buscarPorId($id, true);
        $this->auth->registrarLog([
            'token'             => $data['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Orden_Servicio',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $actual ? $actual->toArray() : null,
            'ipAddress'         => $data['ip'] ?? null,
        ]);

        return $actual;
    }

    public function eliminarOrden(int $id, array $contexto = []): void
    {
        $orden = $this->ordenes->buscarPorId($id, false);
        if (!$orden) {
            throw new AppException('Orden no encontrada', HTTP_NOT_FOUND);
        }
        $orden->marcarEliminado($contexto['updated_by'] ?? null);
        $this->ordenes->guardarCabecera($orden);

        $this->auth->registrarLog([
            'token'         => $contexto['token'] ?? null,
            'accion'        => 'DELETE',
            'tablaAfectada' => 'Orden_Servicio',
            'registroId'    => $id,
            'ipAddress'     => $contexto['ip'] ?? null,
        ]);
    }

    private function ejecutarEnTransaccion(callable $fn): void
    {
        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }
        try {
            $fn();
            if ($propia) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($propia) {
                $this->db->rollback();
            }
            throw $e;
        }
    }

    private function persistirLineas(OrdenServicio $orden, array $lineas, int $createdBy, bool $permitirPrecioManual = false): void
    {
        foreach ($lineas as $linea) {
            $idServicio = (int) ($linea['id_servicio'] ?? $linea['servicio_id'] ?? 0);
            if ($idServicio < 1) {
                continue;
            }
            $catalogo = $this->servicios->buscarPorId($idServicio);
            if (!$catalogo || !$catalogo->isActivo()) {
                throw new AppException('Servicio de catálogo no válido: ' . $idServicio, HTTP_BAD_REQUEST);
            }

            $precio = $catalogo->getPrecio();
            if ($permitirPrecioManual && array_key_exists('precio', $linea)) {
                $precio = (float) $linea['precio'];
            }
            $cantidad = (int) ($linea['cantidad'] ?? 1);
            $detalle = new DetalleServicio(
                (int) $orden->getIdOrden(),
                $idServicio,
                $precio,
                $cantidad,
                $createdBy
            );
            $detalle->setServicio($catalogo);
            $this->detalles->guardar($detalle);
            $orden->agregarDetalle($detalle);
        }
    }
}
