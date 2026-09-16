<?php

/**
 * Cantidad física de un producto en el almacén. Tabla: Stock. Sin BD.
 */
class Stock
{
    private ?int $idStock;
    private int $idProducto;
    private int $cantidad;
    private int $stockMinimo;
    private float $precioCompra;
    private ?string $ubicacion;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        int $idProducto,
        int $createdBy,
        int $cantidad = 0,
        int $stockMinimo = 10,
        float $precioCompra = 0,
        ?string $ubicacion = null,
        ?int $idStock = null
    ) {
        $this->idStock      = $idStock;
        $this->idProducto   = $idProducto;
        $this->cantidad     = max(0, $cantidad);
        $this->stockMinimo  = max(0, $stockMinimo);
        $this->precioCompra = round($precioCompra, 2);
        $this->ubicacion    = $ubicacion;
        $this->createdBy    = $createdBy;
        $this->updatedBy    = null;
        $this->createdAt    = new DateTime();
        $this->updatedAt    = null;
    }

    public static function fromArray(array $fila): self
    {
        $s = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $s->idStock      = isset($fila['id_stock']) ? (int) $fila['id_stock'] : null;
        $s->idProducto   = (int) ($fila['id_producto'] ?? 0);
        $s->cantidad     = (int) ($fila['cantidad'] ?? 0);
        $s->stockMinimo  = (int) ($fila['stock_minimo'] ?? 10);
        $s->precioCompra = (float) ($fila['precio_compra'] ?? 0);
        $s->ubicacion    = $fila['ubicacion'] ?? null;
        $s->createdBy    = (int) ($fila['created_by'] ?? 0);
        $s->updatedBy    = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $s->createdAt    = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $s->updatedAt    = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        return $s;
    }

    public function estaBajoMinimo(): bool
    {
        return $this->cantidad <= $this->stockMinimo;
    }

    public function entrar(int $unidades): void
    {
        $this->cantidad += max(0, $unidades);
        $this->tocarUpdatedAt();
    }

    public function salir(int $unidades): void
    {
        $unidades = max(0, $unidades);
        if ($unidades > $this->cantidad) {
            throw new AppException('Stock insuficiente', HTTP_BAD_REQUEST);
        }
        $this->cantidad -= $unidades;
        $this->tocarUpdatedAt();
    }

    public function ajustar(int $nuevaCantidad): void
    {
        $this->cantidad = max(0, $nuevaCantidad);
        $this->tocarUpdatedAt();
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getIdStock(): ?int { return $this->idStock; }
    public function assignId(int $id): void { $this->idStock = $id; }
    public function getIdProducto(): int { return $this->idProducto; }
    public function setIdProducto(int $id): void { $this->idProducto = $id; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getStockMinimo(): int { return $this->stockMinimo; }
    public function setStockMinimo(int $minimo): void { $this->stockMinimo = max(0, $minimo); $this->tocarUpdatedAt(); }
    public function getPrecioCompra(): float { return $this->precioCompra; }
    public function setPrecioCompra(float $precio): void { $this->precioCompra = round($precio, 2); $this->tocarUpdatedAt(); }
    public function getUbicacion(): ?string { return $this->ubicacion; }
    public function setUbicacion(?string $ubicacion): void { $this->ubicacion = $ubicacion; $this->tocarUpdatedAt(); }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    public function toArray(): array
    {
        return [
            'id_stock'      => $this->idStock,
            'id_producto'   => $this->idProducto,
            'cantidad'      => $this->cantidad,
            'stock_minimo'  => $this->stockMinimo,
            'precio_compra' => $this->precioCompra,
            'ubicacion'     => $this->ubicacion,
            'stock_bajo'    => $this->estaBajoMinimo(),
        ];
    }
}
