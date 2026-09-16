<?php

/**
 * Repuestos cargados a una OT del mecánico.
 * Descuenta stock como parte de la orden, no como ajuste de inventario.
 */
class ConsumoMecanicoService
{
    private Database $db;
    private OrdenesService $ordenes;
    private DetalleItemRepository $items;
    private InventarioService $inventario;
    private AuthService $auth;

    public function __construct(
        Database $db,
        OrdenesService $ordenes,
        DetalleItemRepository $items,
        InventarioService $inventario,
        AuthService $auth
    ) {
        $this->db         = $db;
        $this->ordenes    = $ordenes;
        $this->items      = $items;
        $this->inventario = $inventario;
        $this->auth       = $auth;
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(Usuario $actor, int $idOrden): array
    {
        $this->exigirOrdenAsignada($actor, $idOrden);
        return $this->serializarItems($idOrden);
    }

    /** @param array<string,mixed> $data */
    public function registrar(Usuario $actor, array $data): array
    {
        $this->auth->asegurarPermiso($actor, 'ordenes.agregar_productos', 'No puede cargar repuestos a la orden');
        $idOrden = (int) ($data['id_orden'] ?? $data['orden_id'] ?? 0);
        $idProducto = (int) ($data['id_producto'] ?? $data['producto_id'] ?? 0);
        $cantidad = (int) ($data['cantidad'] ?? 0);
        if ($idOrden < 1 || $idProducto < 1 || $cantidad < 1) {
            throw new AppException('id_orden, id_producto y cantidad son requeridos', HTTP_BAD_REQUEST);
        }

        $detalle = $this->exigirOrdenAsignada($actor, $idOrden);
        $estado = (string) ($detalle['estado'] ?? '');
        if (in_array($estado, ['Completada', 'Cancelada', 'Pendiente Pago'], true)) {
            throw new AppException('No se pueden cargar repuestos a una orden cerrada', HTTP_BAD_REQUEST);
        }

        $producto = $this->inventario->buscarProducto($idProducto);
        $createdBy = (int) $actor->getIdUsuario();

        $this->db->beginTransaction();
        try {
            $item = new DetalleItem(
                $idOrden,
                DetalleItem::TIPO_ORDEN,
                $idProducto,
                $cantidad,
                (float) $producto->getPrecioUnitario(),
                $createdBy
            );
            $this->items->guardar($item);
            $this->inventario->registrarMovimiento([
                'id_producto'          => $idProducto,
                'tipo'                 => MovimientoStock::SALIDA,
                'cantidad'             => $cantidad,
                'created_by'           => $createdBy,
                'referencia_documento' => $idOrden,
                'motivo'               => 'Consumo en orden #' . $idOrden,
            ]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Detalle_Items',
            'registroId'    => (int) $item->getIdItems(),
            'valoresNuevos' => $item->toArray(),
            'ipAddress'     => $data['ip'] ?? null,
        ]);

        return [
            'consumo' => $item->toArray(),
            'items'   => $this->serializarItems($idOrden),
        ];
    }

    /** @return array<string,mixed> */
    private function exigirOrdenAsignada(Usuario $actor, int $idOrden): array
    {
        $detalle = $this->ordenes->obtenerDetalleCompleto($idOrden);
        if (
            $actor->esMecanico()
            && !$actor->esAdministrador()
            && (int) ($detalle['id_usuario'] ?? 0) !== (int) $actor->getIdUsuario()
        ) {
            throw new AppException('No puede operar esta orden', HTTP_FORBIDDEN);
        }
        return $detalle;
    }

    /** @return array<int,array<string,mixed>> */
    private function serializarItems(int $idOrden): array
    {
        return array_map(static function (DetalleItem $item) {
            return $item->toArray();
        }, $this->items->listarPorPadre(DetalleItem::TIPO_ORDEN, $idOrden));
    }
}
