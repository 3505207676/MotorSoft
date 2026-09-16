<?php

/**
 * Línea de factura: servicio, producto, orden o venta. Tabla: Detalles_Factura.
 */
class DetalleFactura
{
    public const REF_PRODUCTO = 'Producto';
    public const REF_SERVICIO = 'Servicio';
    public const REF_ORDEN    = 'Orden_Servicio';
    public const REF_VENTA    = 'Venta';

    private ?int $idDetalleFactura;
    private int $idFactura;
    private string $tipoReferencia;
    private int $idReferencia;
    private ?string $descripcion;
    private float $monto;
    private int $createdBy;
    private DateTime $createdAt;

    public function __construct(
        int $idFactura,
        string $tipoReferencia,
        int $idReferencia,
        float $monto,
        int $createdBy,
        ?string $descripcion = null
    ) {
        $this->idDetalleFactura = null;
        $this->idFactura        = $idFactura;
        $this->tipoReferencia   = $tipoReferencia;
        $this->idReferencia     = $idReferencia;
        $this->monto            = round($monto, 2);
        $this->createdBy        = $createdBy;
        $this->descripcion      = $descripcion;
        $this->createdAt        = new DateTime();
    }

    public static function fromArray(array $fila): self
    {
        $d = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $d->idDetalleFactura = isset($fila['id_detalle_factura']) ? (int) $fila['id_detalle_factura'] : null;
        $d->idFactura        = (int) ($fila['id_factura'] ?? 0);
        $d->tipoReferencia   = $fila['tipo_referencia'] ?? '';
        $d->idReferencia     = (int) ($fila['id_referencia'] ?? 0);
        $d->descripcion      = $fila['descripcion'] ?? null;
        $d->monto            = (float) ($fila['monto'] ?? 0);
        $d->createdBy        = (int) ($fila['created_by'] ?? 0);
        $d->createdAt        = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        return $d;
    }

    public function esLineaCobro(): bool
    {
        return $this->monto > 0;
    }

    public function getIdDetalleFactura(): ?int { return $this->idDetalleFactura; }
    public function assignId(int $id): void { $this->idDetalleFactura = $id; }
    public function getIdFactura(): int { return $this->idFactura; }
    public function setIdFactura(int $id): void { $this->idFactura = $id; }
    public function getTipoReferencia(): string { return $this->tipoReferencia; }
    public function getIdReferencia(): int { return $this->idReferencia; }
    public function getDescripcion(): ?string { return $this->descripcion; }
    public function getMonto(): float { return $this->monto; }
    public function getCreatedBy(): int { return $this->createdBy; }

    public function toArray(): array
    {
        return [
            'id_detalle_factura' => $this->idDetalleFactura,
            'id_factura'         => $this->idFactura,
            'tipo_referencia'    => $this->tipoReferencia,
            'id_referencia'      => $this->idReferencia,
            'descripcion'        => $this->descripcion,
            'monto'              => $this->monto,
        ];
    }
}
