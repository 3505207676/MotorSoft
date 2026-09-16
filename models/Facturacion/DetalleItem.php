<?php

require_once __DIR__ . '/../Inventario/Producto.php';

/**
 * Línea polimórfica de productos (venta u OT). Tabla: Detalle_Items.
 */
class DetalleItem
{
    public const TIPO_VENTA = 'Venta';
    public const TIPO_ORDEN = 'Orden_Servicio';

    private ?int $idItems;
    private int $itemableId;
    private string $itemableType;
    private int $idProducto;
    private int $cantidad;
    private float $precioUnitario;
    private float $descuento;
    private int $createdBy;
    private ?Producto $producto;

    public function __construct(
        int $itemableId,
        string $itemableType,
        int $idProducto,
        int $cantidad,
        float $precioUnitario,
        int $createdBy,
        float $descuento = 0
    ) {
        $this->idItems        = null;
        $this->itemableId     = $itemableId;
        $this->itemableType   = $itemableType;
        $this->idProducto     = $idProducto;
        $this->cantidad       = max(1, $cantidad);
        $this->precioUnitario = round($precioUnitario, 2);
        $this->descuento      = round(max(0, $descuento), 2);
        $this->createdBy      = $createdBy;
        $this->producto       = null;
    }

    public static function fromArray(array $fila, ?Producto $producto = null): self
    {
        $d = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $d->idItems        = isset($fila['id_items']) ? (int) $fila['id_items'] : null;
        $d->itemableId     = (int) ($fila['itemable_id'] ?? 0);
        $d->itemableType   = $fila['itemable_type'] ?? '';
        $d->idProducto     = (int) ($fila['id_producto'] ?? 0);
        $d->cantidad       = (int) ($fila['cantidad'] ?? 1);
        $d->precioUnitario = (float) ($fila['precio_unitario'] ?? 0);
        $d->descuento      = (float) ($fila['descuento'] ?? 0);
        $d->createdBy      = (int) ($fila['created_by'] ?? 0);
        $d->producto       = $producto;
        return $d;
    }

    public function subtotal(): float
    {
        return round(($this->precioUnitario * $this->cantidad) - $this->descuento, 2);
    }

    public function getIdItems(): ?int { return $this->idItems; }
    public function assignId(int $id): void { $this->idItems = $id; }
    public function getItemableId(): int { return $this->itemableId; }
    public function setItemableId(int $id): void { $this->itemableId = $id; }
    public function getItemableType(): string { return $this->itemableType; }
    public function getIdProducto(): int { return $this->idProducto; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getPrecioUnitario(): float { return $this->precioUnitario; }
    public function getDescuento(): float { return $this->descuento; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getProducto(): ?Producto { return $this->producto; }
    public function setProducto(Producto $producto): void { $this->producto = $producto; }

    public function toArray(): array
    {
        return [
            'id_items'        => $this->idItems,
            'itemable_id'     => $this->itemableId,
            'itemable_type'   => $this->itemableType,
            'id_producto'     => $this->idProducto,
            'producto'        => $this->producto ? $this->producto->getNombre() : null,
            'referencia'      => $this->producto ? $this->producto->getReferencia() : null,
            'cantidad'        => $this->cantidad,
            'precio_unitario' => $this->precioUnitario,
            'descuento'       => $this->descuento,
            'subtotal'        => $this->subtotal(),
        ];
    }
}
