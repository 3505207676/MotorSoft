<?php

require_once __DIR__ . '/NominaRol.php';
require_once __DIR__ . '/Contrato.php';

/**
 * Pago de nómina. Tabla Nominas. Sin acceso a BD.
 */
class Nomina
{
    private ?int $idNomina;
    private int $idContrato;
    private string $periodoPago;
    private string $fechaPago;
    private float $totalNeto;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    /** @var NominaRol[] */
    private array $detalle = [];
    private ?Contrato $contrato;
    private ?string $usuarioNombre;

    public function __construct(int $idContrato, string $periodoPago, string $fechaPago, float $totalNeto, int $createdBy)
    {
        $this->idNomina       = null;
        $this->idContrato     = $idContrato;
        $this->periodoPago    = $periodoPago;
        $this->fechaPago      = $fechaPago;
        $this->totalNeto      = round($totalNeto, 2);
        $this->createdBy      = $createdBy;
        $this->updatedBy      = null;
        $this->createdAt      = new DateTime();
        $this->updatedAt      = null;
        $this->deletedAt      = null;
        $this->detalle        = [];
        $this->contrato       = null;
        $this->usuarioNombre  = null;
    }

    public static function fromArray(array $fila, array $detalle = [], ?Contrato $contrato = null): self
    {
        $n = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $n->idNomina      = isset($fila['id_nomina']) ? (int) $fila['id_nomina'] : null;
        $n->idContrato    = (int) ($fila['id_contrato'] ?? 0);
        $n->periodoPago   = $fila['periodo_pago'] ?? '';
        $n->fechaPago     = substr((string) ($fila['fecha_pago'] ?? date('Y-m-d')), 0, 10);
        $n->totalNeto     = (float) ($fila['total_neto'] ?? 0);
        $n->createdBy     = (int) ($fila['created_by'] ?? 0);
        $n->updatedBy     = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $n->createdAt     = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $n->updatedAt     = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $n->deletedAt     = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $n->detalle       = $detalle;
        $n->contrato      = $contrato;
        $n->usuarioNombre = $fila['usuario_nombre'] ?? ($contrato ? $contrato->toArray()['usuario_nombre'] : null);
        return $n;
    }

    public function assignId(int $id): void { $this->idNomina = $id; }
    public function getIdNomina(): ?int { return $this->idNomina; }
    public function getIdContrato(): int { return $this->idContrato; }
    public function getPeriodoPago(): string { return $this->periodoPago; }
    public function getFechaPago(): string { return $this->fechaPago; }
    public function getTotalNeto(): float { return $this->totalNeto; }
    public function setTotalNeto(float $v): void { $this->totalNeto = round($v, 2); }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }
    /** @return NominaRol[] */
    public function getDetalle(): array { return $this->detalle; }
    /** @param NominaRol[] $detalle */
    public function setDetalle(array $detalle): void { $this->detalle = $detalle; }
    public function getContrato(): ?Contrato { return $this->contrato; }
    public function setContrato(?Contrato $c): void { $this->contrato = $c; }

    public function tocarUpdatedAt(?int $updatedBy): void
    {
        $this->updatedAt = new DateTime();
        $this->updatedBy = $updatedBy;
    }

    public function marcarEliminado(?int $updatedBy): void
    {
        $this->deletedAt = new DateTime();
        $this->tocarUpdatedAt($updatedBy);
    }

    public function recalcularTotal(): float
    {
        $neto = 0.0;
        foreach ($this->detalle as $linea) {
            $neto += $linea->esDevengo() ? $linea->getValor() : -$linea->getValor();
        }
        $this->totalNeto = round($neto, 2);
        return $this->totalNeto;
    }

    public function toArray(): array
    {
        return [
            'id_nomina'      => $this->idNomina,
            'id_contrato'    => $this->idContrato,
            'periodo_pago'   => $this->periodoPago,
            'fecha_pago'     => $this->fechaPago,
            'total_neto'     => $this->totalNeto,
            'usuario_nombre' => $this->usuarioNombre,
            'contrato'       => $this->contrato ? $this->contrato->toArray() : null,
            'detalle'        => array_map(static fn (NominaRol $l) => $l->toArray(), $this->detalle),
        ];
    }
}
