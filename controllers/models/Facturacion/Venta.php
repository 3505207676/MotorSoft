<?php

require_once __DIR__ . '/DetalleItem.php';
require_once __DIR__ . '/../Clientes/Cliente.php';

/**
 * Venta de mostrador. Tabla: Ventas.
 */
class Venta
{
    private ?int $idVenta;
    private int $idCliente;
    private DateTime $fecha;
    private float $total;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    /** @var DetalleItem[] */
    private array $items = [];
    private ?Cliente $cliente;

    public function __construct(int $idCliente, float $total, int $createdBy)
    {
        $this->idVenta    = null;
        $this->idCliente  = $idCliente;
        $this->fecha      = new DateTime();
        $this->total      = round($total, 2);
        $this->createdBy  = $createdBy;
        $this->updatedBy  = null;
        $this->createdAt  = new DateTime();
        $this->updatedAt  = null;
        $this->deletedAt  = null;
        $this->cliente    = null;
    }

    public static function fromArray(array $fila, array $items = [], ?Cliente $cliente = null): self
    {
        $v = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $v->idVenta    = isset($fila['id_venta']) ? (int) $fila['id_venta'] : null;
        $v->idCliente  = (int) ($fila['id_cliente'] ?? 0);
        $v->fecha      = !empty($fila['fecha']) ? new DateTime($fila['fecha']) : new DateTime();
        $v->total      = (float) ($fila['total'] ?? 0);
        $v->createdBy  = (int) ($fila['created_by'] ?? 0);
        $v->updatedBy  = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $v->createdAt  = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $v->updatedAt  = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $v->deletedAt  = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $v->items      = $items;
        $v->cliente    = $cliente;
        return $v;
    }

    public function getIdVenta(): ?int { return $this->idVenta; }
    public function assignId(int $id): void { $this->idVenta = $id; }
    public function getIdCliente(): int { return $this->idCliente; }
    public function getFecha(): DateTime { return $this->fecha; }
    public function getTotal(): float { return $this->total; }
    public function setTotal(float $total): void { $this->total = round($total, 2); }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }
    /** @return DetalleItem[] */
    public function getItems(): array { return $this->items; }
    public function setItems(array $items): void { $this->items = $items; }
    public function getCliente(): ?Cliente { return $this->cliente; }
    public function setCliente(Cliente $cliente): void { $this->cliente = $cliente; }

    public function toArray(): array
    {
        return [
            'id_venta'   => $this->idVenta,
            'id_cliente' => $this->idCliente,
            'cliente'    => $this->cliente ? $this->cliente->getNombre() : null,
            'fecha'      => $this->fecha->format('Y-m-d H:i:s'),
            'total'      => $this->total,
            'items'      => array_map(static function (DetalleItem $item) {
                return $item->toArray();
            }, $this->items),
        ];
    }
}
