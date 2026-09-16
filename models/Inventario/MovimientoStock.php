<?php

/**
 * Kardex: entrada, salida o ajuste. Tabla: Movimientos.
 * referencia_documento es el id de OT, factura o 0 si es movimiento de almacén.
 */
class MovimientoStock
{
    public const ENTRADA = 'ENTRADA';
    public const SALIDA  = 'SALIDA';
    public const AJUSTE  = 'AJUSTE';

    private ?int $idMovimiento;
    private int $idProducto;
    private string $tipoMovimiento;
    private int $cantidad;
    private ?int $cantidadAntes;
    private ?int $cantidadDespues;
    private ?string $motivo;
    private DateTime $fechaHora;
    private int $referenciaDocumento;
    private int $createdBy;

    public function __construct(
        int $idProducto,
        string $tipoMovimiento,
        int $createdBy,
        int $referenciaDocumento = 0,
        int $cantidad = 0,
        ?int $cantidadAntes = null,
        ?int $cantidadDespues = null,
        ?string $motivo = null
    ) {
        $tipo = strtoupper($tipoMovimiento);
        if (!in_array($tipo, [self::ENTRADA, self::SALIDA, self::AJUSTE], true)) {
            throw new AppException('Tipo de movimiento no válido', HTTP_BAD_REQUEST);
        }
        $this->idMovimiento        = null;
        $this->idProducto          = $idProducto;
        $this->tipoMovimiento      = $tipo;
        $this->cantidad            = max(0, $cantidad);
        $this->cantidadAntes       = $cantidadAntes;
        $this->cantidadDespues     = $cantidadDespues;
        $this->motivo              = $motivo !== null && trim($motivo) !== '' ? trim($motivo) : null;
        $this->fechaHora           = new DateTime();
        $this->referenciaDocumento = $referenciaDocumento;
        $this->createdBy           = $createdBy;
    }

    public static function fromArray(array $fila): self
    {
        $m = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $m->idMovimiento        = isset($fila['id_movimiento']) ? (int) $fila['id_movimiento'] : null;
        $m->idProducto          = (int) ($fila['id_producto'] ?? 0);
        $m->tipoMovimiento      = $fila['tipo_movimiento'] ?? '';
        $m->cantidad            = (int) ($fila['cantidad'] ?? 0);
        $m->cantidadAntes       = isset($fila['cantidad_antes']) ? (int) $fila['cantidad_antes'] : null;
        $m->cantidadDespues     = isset($fila['cantidad_despues']) ? (int) $fila['cantidad_despues'] : null;
        $m->motivo              = isset($fila['motivo']) && $fila['motivo'] !== '' ? (string) $fila['motivo'] : null;
        $m->fechaHora           = !empty($fila['fecha_hora']) ? new DateTime($fila['fecha_hora']) : new DateTime();
        $m->referenciaDocumento = (int) ($fila['referencia_documento'] ?? 0);
        $m->createdBy           = (int) ($fila['created_by'] ?? 0);
        return $m;
    }

    public function getIdMovimiento(): ?int { return $this->idMovimiento; }
    public function assignId(int $id): void { $this->idMovimiento = $id; }
    public function getIdProducto(): int { return $this->idProducto; }
    public function getTipoMovimiento(): string { return $this->tipoMovimiento; }
    public function getCantidad(): int { return $this->cantidad; }
    public function getCantidadAntes(): ?int { return $this->cantidadAntes; }
    public function getCantidadDespues(): ?int { return $this->cantidadDespues; }
    public function getMotivo(): ?string { return $this->motivo; }
    public function getFechaHora(): DateTime { return $this->fechaHora; }
    public function getReferenciaDocumento(): int { return $this->referenciaDocumento; }
    public function getCreatedBy(): int { return $this->createdBy; }

    public function toArray(): array
    {
        return [
            'id_movimiento'         => $this->idMovimiento,
            'id_producto'           => $this->idProducto,
            'tipo_movimiento'       => $this->tipoMovimiento,
            'cantidad'              => $this->cantidad,
            'cantidad_antes'        => $this->cantidadAntes,
            'cantidad_despues'      => $this->cantidadDespues,
            'motivo'                => $this->motivo,
            'fecha_hora'            => $this->fechaHora->format('Y-m-d H:i:s'),
            'referencia_documento'  => $this->referenciaDocumento,
        ];
    }
}
