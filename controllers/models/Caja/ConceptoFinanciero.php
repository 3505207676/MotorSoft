<?php

class ConceptoFinanciero
{
    public const INGRESO = 'Ingreso';
    public const EGRESO  = 'Egreso';

    public const PAGO_ORDEN     = 'Pago de orden de servicio';
    public const VENTA_MOSTRADOR = 'Venta de mostrador';
    public const ANULACION      = 'Anulación de factura';
    public const AJUSTE         = 'Ajuste de caja';
    public const GASTO          = 'Gasto operativo';
    public const PAGO_NOMINA    = 'Pago de nómina';

    private ?int $idConcepto;
    private string $nombre;
    private string $tipo;
    private string $descripcion;
    private int $createdBy;
    private DateTime $createdAt;
    private ?DateTime $deletedAt;

    public function __construct(string $nombre, string $tipo, string $descripcion, int $createdBy)
    {
        $this->idConcepto  = null;
        $this->nombre      = trim($nombre);
        $this->tipo        = $tipo;
        $this->descripcion = $descripcion;
        $this->createdBy   = $createdBy;
        $this->createdAt   = new DateTime();
        $this->deletedAt   = null;
    }

    public static function fromArray(array $fila): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idConcepto  = isset($fila['id_concepto']) ? (int) $fila['id_concepto'] : null;
        $c->nombre      = $fila['nombre'] ?? '';
        $c->tipo        = $fila['tipo'] ?? '';
        $c->descripcion = $fila['descripcion'] ?? '';
        $c->createdBy   = (int) ($fila['created_by'] ?? 0);
        $c->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->deletedAt   = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        return $c;
    }

    public function getIdConcepto(): ?int { return $this->idConcepto; }
    public function assignId(int $id): void { $this->idConcepto = $id; }
    public function getNombre(): string { return $this->nombre; }
    public function getTipo(): string { return $this->tipo; }
    public function getDescripcion(): string { return $this->descripcion; }
    public function getCreatedBy(): int { return $this->createdBy; }

    public function toArray(): array
    {
        return [
            'id_concepto' => $this->idConcepto,
            'nombre'      => $this->nombre,
            'tipo'        => $this->tipo,
            'descripcion' => $this->descripcion,
        ];
    }
}
