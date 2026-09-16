<?php

require_once __DIR__ . '/ConceptoFinanciero.php';
require_once __DIR__ . '/Cuenta.php';

class TransaccionCaja
{
    private ?int $idTransaccion;
    private int $idConcepto;
    private ?int $idFactura;
    private ?int $idCuenta;
    private ?int $idMovimiento;
    private ?int $idCaja;
    private float $monto;
    private string $tipo;
    private DateTime $fecha;
    private int $createdBy;
    private ?ConceptoFinanciero $concepto;
    private ?Cuenta $cuenta;

    public function __construct(int $idConcepto, float $monto, string $tipo, int $createdBy, ?int $idCuenta = null)
    {
        $this->idTransaccion = null;
        $this->idConcepto    = $idConcepto;
        $this->idFactura     = null;
        $this->idCuenta      = $idCuenta;
        $this->idMovimiento  = null;
        $this->idCaja        = null;
        $this->monto         = round($monto, 2);
        $this->tipo          = $tipo;
        $this->fecha         = new DateTime();
        $this->createdBy     = $createdBy;
        $this->concepto      = null;
        $this->cuenta        = null;
    }

    public static function fromArray(array $fila, ?ConceptoFinanciero $concepto = null, ?Cuenta $cuenta = null): self
    {
        $t = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $t->idTransaccion = isset($fila['id_transaccion']) ? (int) $fila['id_transaccion'] : null;
        $t->idConcepto    = (int) ($fila['id_concepto'] ?? 0);
        $t->idFactura     = isset($fila['id_factura']) ? (int) $fila['id_factura'] : null;
        $t->idCuenta      = isset($fila['id_cuenta']) ? (int) $fila['id_cuenta'] : null;
        $t->idMovimiento  = isset($fila['id_movimiento']) ? (int) $fila['id_movimiento'] : null;
        $t->idCaja        = isset($fila['id_caja']) ? (int) $fila['id_caja'] : null;
        $t->monto         = (float) ($fila['monto'] ?? 0);
        $t->tipo          = $fila['tipo'] ?? '';
        $t->fecha         = !empty($fila['fecha']) ? new DateTime($fila['fecha']) : new DateTime();
        $t->createdBy     = (int) ($fila['created_by'] ?? 0);
        $t->concepto      = $concepto;
        $t->cuenta        = $cuenta;
        return $t;
    }

    public function setIdFactura(?int $id): void { $this->idFactura = $id; }
    public function setIdCaja(?int $id): void { $this->idCaja = $id; }
    public function setIdCuenta(?int $id): void { $this->idCuenta = $id; }
    public function setConcepto(?ConceptoFinanciero $concepto): void { $this->concepto = $concepto; }
    public function setCuenta(?Cuenta $cuenta): void { $this->cuenta = $cuenta; }
    public function getIdTransaccion(): ?int { return $this->idTransaccion; }
    public function assignId(int $id): void { $this->idTransaccion = $id; }
    public function getIdConcepto(): int { return $this->idConcepto; }
    public function getIdFactura(): ?int { return $this->idFactura; }
    public function getIdCuenta(): ?int { return $this->idCuenta; }
    public function getIdMovimiento(): ?int { return $this->idMovimiento; }
    public function getIdCaja(): ?int { return $this->idCaja; }
    public function getMonto(): float { return $this->monto; }
    public function getTipo(): string { return $this->tipo; }
    public function getFecha(): DateTime { return $this->fecha; }
    public function getCreatedBy(): int { return $this->createdBy; }

    public function toArray(): array
    {
        return [
            'id_transaccion' => $this->idTransaccion,
            'id_concepto'    => $this->idConcepto,
            'concepto'       => $this->concepto ? $this->concepto->getNombre() : null,
            'id_factura'     => $this->idFactura,
            'id_cuenta'      => $this->idCuenta,
            'cuenta'         => $this->cuenta ? $this->cuenta->getNombre() : null,
            'id_caja'        => $this->idCaja,
            'id_movimiento'  => $this->idMovimiento,
            'monto'          => $this->monto,
            'tipo'           => $this->tipo,
            'fecha'          => $this->fecha->format('Y-m-d H:i:s'),
            'created_by'     => $this->createdBy,
        ];
    }
}
