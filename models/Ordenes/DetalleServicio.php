<?php

require_once __DIR__ . '/Servicio.php';

/**
 * Línea de una orden: qué servicio se hizo, cuántas veces y a qué precio.
 * Tabla: Detalles_Servicios. No consulta la base de datos.
 *
 * guardar / eliminar / consultar viven en DetalleServicioRepository.
 */
class DetalleServicio
{
    private ?int $idDetalle;
    private int $idOrden;
    private int $idServicio;
    private float $precio;
    private int $cantidad;
    private float $subtotal;
    private int $createdBy;
    private DateTime $createdAt;
    private ?Servicio $servicio;

    public function __construct(
        int $idOrden,
        int $idServicio,
        float $precio,
        int $cantidad,
        int $createdBy,
        ?int $idDetalle = null
    ) {
        $this->idDetalle  = $idDetalle;
        $this->idOrden    = $idOrden;
        $this->idServicio = $idServicio;
        $this->precio     = round($precio, 2);
        $this->cantidad   = max(1, $cantidad);
        $this->createdBy  = $createdBy;
        $this->createdAt  = new DateTime();
        $this->servicio   = null;
        $this->calcularSubtotal();
    }

    public static function fromArray(array $fila, ?Servicio $servicio = null): self
    {
        $detalle = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $detalle->idDetalle  = isset($fila['id_detalle']) ? (int) $fila['id_detalle'] : null;
        $detalle->idOrden    = (int) ($fila['id_orden'] ?? 0);
        $detalle->idServicio = (int) ($fila['id_servicio'] ?? 0);
        $detalle->precio     = (float) ($fila['precio'] ?? 0);
        $detalle->cantidad   = (int) ($fila['cantidad'] ?? 1);
        $detalle->subtotal   = (float) ($fila['subtotal'] ?? 0);
        $detalle->createdBy  = (int) ($fila['created_by'] ?? 0);
        $detalle->createdAt  = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $detalle->servicio   = $servicio;

        if ($detalle->subtotal <= 0) {
            $detalle->calcularSubtotal();
        }

        return $detalle;
    }

    public function calcularSubtotal(): float
    {
        $this->subtotal = round($this->precio * $this->cantidad, 2);
        return $this->subtotal;
    }

    public function getIdDetalle(): ?int
    {
        return $this->idDetalle;
    }

    public function assignId(int $id): void
    {
        $this->idDetalle = $id;
    }

    public function getIdOrden(): int
    {
        return $this->idOrden;
    }

    public function setIdOrden(int $idOrden): void
    {
        $this->idOrden = $idOrden;
    }

    public function getIdServicio(): int
    {
        return $this->idServicio;
    }

    public function getPrecio(): float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): void
    {
        $this->precio = round($precio, 2);
        $this->calcularSubtotal();
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function setCantidad(int $cantidad): void
    {
        $this->cantidad = max(1, $cantidad);
        $this->calcularSubtotal();
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getServicio(): ?Servicio
    {
        return $this->servicio;
    }

    public function setServicio(Servicio $servicio): void
    {
        $this->servicio   = $servicio;
        $this->idServicio = (int) $servicio->getIdServicio();
        if ($this->precio <= 0) {
            $this->setPrecio($servicio->getPrecio());
        }
    }

    public function toArray(): array
    {
        return [
            'id_detalle'  => $this->idDetalle,
            'id_orden'    => $this->idOrden,
            'id_servicio' => $this->idServicio,
            'precio'      => $this->precio,
            'cantidad'    => $this->cantidad,
            'subtotal'    => $this->subtotal,
            'servicio'    => $this->servicio ? $this->servicio->toArray() : null,
        ];
    }
}
