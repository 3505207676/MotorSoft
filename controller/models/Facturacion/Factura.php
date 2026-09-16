<?php

require_once __DIR__ . '/DetalleFactura.php';
require_once __DIR__ . '/../Clientes/Cliente.php';

/**
 * Documento de cobro. Tabla: Facturas. El número visible se arma con el prefijo.
 */
class Factura
{
    public const TIPO_ORDEN     = 'Orden de servicio';
    public const TIPO_MOSTRADOR = 'Venta mostrador';

    public const PENDIENTE = 'Pendiente';
    public const PAGADA    = 'Pagada';
    public const ANULADA   = 'Anulada';

    public const METODO_EFECTIVO      = 'Efectivo';
    public const METODO_TARJETA       = 'Tarjeta';
    public const METODO_TRANSFERENCIA = 'Transferencia';

    private ?int $idFactura;
    private int $idCliente;
    private DateTime $fechaEmision;
    private float $subtotal;
    private float $iva;
    private float $total;
    private string $metodoPago;
    private string $estado;
    private string $tipoFactura;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    /** @var DetalleFactura[] */
    private array $detalles = [];
    private ?Cliente $cliente;
    private string $prefijo = 'FAC';

    public function __construct(
        int $idCliente,
        float $subtotal,
        float $iva,
        float $total,
        string $metodoPago,
        string $tipoFactura,
        int $createdBy,
        string $estado = self::PENDIENTE
    ) {
        $this->idFactura    = null;
        $this->idCliente    = $idCliente;
        $this->fechaEmision = new DateTime();
        $this->subtotal     = round($subtotal, 2);
        $this->iva          = round($iva, 2);
        $this->total        = round($total, 2);
        $this->metodoPago   = $metodoPago;
        $this->tipoFactura  = $tipoFactura;
        $this->estado       = $estado;
        $this->createdBy    = $createdBy;
        $this->updatedBy    = null;
        $this->createdAt    = new DateTime();
        $this->updatedAt    = null;
        $this->deletedAt    = null;
        $this->cliente      = null;
    }

    public static function fromArray(array $fila, array $detalles = [], ?Cliente $cliente = null): self
    {
        $f = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $f->idFactura    = isset($fila['id_factura']) ? (int) $fila['id_factura'] : null;
        $f->idCliente    = (int) ($fila['id_cliente'] ?? 0);
        $f->fechaEmision = !empty($fila['fecha_emision']) ? new DateTime($fila['fecha_emision']) : new DateTime();
        $f->subtotal     = (float) ($fila['subtotal'] ?? 0);
        $f->iva          = (float) ($fila['IVA'] ?? $fila['iva'] ?? 0);
        $f->total        = (float) ($fila['total'] ?? 0);
        $f->metodoPago   = $fila['metodo_pago'] ?? '';
        $f->estado       = $fila['estado'] ?? self::PENDIENTE;
        $f->tipoFactura  = $fila['tipo_factura'] ?? '';
        $f->createdBy    = (int) ($fila['created_by'] ?? 0);
        $f->updatedBy    = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $f->createdAt    = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $f->updatedAt    = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $f->deletedAt    = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $f->detalles     = $detalles;
        $f->cliente      = $cliente;
        return $f;
    }

    public static function normalizarMetodo(?string $metodo): string
    {
        $valor = mb_strtolower(trim((string) $metodo));
        if ($valor === '' || $valor === 'efectivo' || $valor === 'cash') {
            return self::METODO_EFECTIVO;
        }
        if (in_array($valor, ['tarjeta', 'card', 'datafono', 'datáfono'], true)) {
            return self::METODO_TARJETA;
        }
        if (in_array($valor, ['transferencia', 'nequi', 'daviplata', 'consignacion', 'consignación'], true)) {
            return self::METODO_TRANSFERENCIA;
        }
        throw new AppException('El método de pago debe ser Efectivo, Tarjeta o Transferencia', HTTP_BAD_REQUEST);
    }

    public function numero(): string
    {
        $id = $this->idFactura ? str_pad((string) $this->idFactura, 4, '0', STR_PAD_LEFT) : '----';
        return $this->prefijo . '-' . $id;
    }

    public function setPrefijo(string $prefijo): void
    {
        $this->prefijo = $prefijo !== '' ? $prefijo : 'FAC';
    }

    public function tasaIva(): float
    {
        if ($this->subtotal <= 0) {
            return 0;
        }
        return round(($this->iva * 100) / $this->subtotal, 2);
    }

    public function isAnulada(): bool
    {
        return $this->estado === self::ANULADA || $this->deletedAt !== null;
    }

    public function marcarPagada(?int $updatedBy = null): void
    {
        if ($this->isAnulada()) {
            throw new AppException('No se puede pagar una factura anulada', HTTP_BAD_REQUEST);
        }
        $this->estado = self::PAGADA;
        $this->tocarUpdatedAt($updatedBy);
    }

    public function anular(?int $updatedBy = null): void
    {
        if ($this->isAnulada()) {
            throw new AppException('La factura ya está anulada', HTTP_BAD_REQUEST);
        }
        $this->estado = self::ANULADA;
        $this->tocarUpdatedAt($updatedBy);
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getIdFactura(): ?int { return $this->idFactura; }
    public function assignId(int $id): void { $this->idFactura = $id; }
    public function getIdCliente(): int { return $this->idCliente; }
    public function getFechaEmision(): DateTime { return $this->fechaEmision; }
    public function getSubtotal(): float { return $this->subtotal; }
    public function getIva(): float { return $this->iva; }
    public function getTotal(): float { return $this->total; }
    public function getMetodoPago(): string { return $this->metodoPago; }
    public function setMetodoPago(string $metodo): void { $this->metodoPago = $metodo; $this->tocarUpdatedAt(); }
    public function getEstado(): string { return $this->estado; }
    public function getTipoFactura(): string { return $this->tipoFactura; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }
    /** @return DetalleFactura[] */
    public function getDetalles(): array { return $this->detalles; }
    public function setDetalles(array $detalles): void { $this->detalles = $detalles; }
    public function agregarDetalle(DetalleFactura $detalle): void { $this->detalles[] = $detalle; }
    public function getCliente(): ?Cliente { return $this->cliente; }
    public function setCliente(Cliente $cliente): void { $this->cliente = $cliente; }

    public function toArray(): array
    {
        return [
            'id_factura'     => $this->idFactura,
            'numero'         => $this->numero(),
            'id_cliente'     => $this->idCliente,
            'cliente'        => $this->cliente ? $this->cliente->getNombre() : null,
            'cliente_obj'    => $this->cliente ? $this->cliente->toArray() : null,
            'fecha_emision'  => $this->fechaEmision->format('Y-m-d H:i:s'),
            'subtotal'       => $this->subtotal,
            'IVA'            => $this->iva,
            'iva'            => $this->iva,
            'tasa_iva'       => $this->tasaIva(),
            'total'          => $this->total,
            'metodo_pago'    => $this->metodoPago,
            'estado'         => $this->estado,
            'tipo_factura'   => $this->tipoFactura,
            'detalles'       => array_map(static function (DetalleFactura $d) {
                return $d->toArray();
            }, $this->detalles),
        ];
    }
}
