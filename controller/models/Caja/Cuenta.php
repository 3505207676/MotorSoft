<?php

class Cuenta
{
    private ?int $idCuenta;
    private string $nombre;
    private string $tipo;
    private float $saldoActual;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    public function __construct(string $nombre, string $tipo, int $createdBy, float $saldoActual = 0)
    {
        $this->idCuenta     = null;
        $this->nombre       = trim($nombre);
        $this->tipo         = $tipo;
        $this->saldoActual  = round($saldoActual, 2);
        $this->estado       = 'Activa';
        $this->createdBy    = $createdBy;
        $this->updatedBy    = null;
        $this->createdAt    = new DateTime();
        $this->updatedAt    = null;
        $this->deletedAt    = null;
    }

    public static function fromArray(array $fila): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idCuenta    = isset($fila['id_cuenta']) ? (int) $fila['id_cuenta'] : null;
        $c->nombre      = $fila['nombre'] ?? '';
        $c->tipo        = $fila['tipo'] ?? '';
        $c->saldoActual = (float) ($fila['saldo_actual'] ?? 0);
        $c->estado      = $fila['estado'] ?? 'Activa';
        $c->createdBy   = (int) ($fila['created_by'] ?? 0);
        $c->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $c->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $c->deletedAt   = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        return $c;
    }

    public function isActiva(): bool
    {
        return strtolower($this->estado) === 'activa' && $this->deletedAt === null;
    }

    public function esEfectivo(): bool
    {
        return strtolower($this->tipo) === 'efectivo';
    }

    public function aplicarMovimiento(string $tipo, float $monto): void
    {
        $monto = round(abs($monto), 2);
        if (strtolower($tipo) === 'ingreso') {
            $this->saldoActual += $monto;
        } else {
            $this->saldoActual -= $monto;
        }
        $this->updatedAt = new DateTime();
    }

    public function marcarEliminado(?int $updatedBy = null): void
    {
        $this->deletedAt = new DateTime();
        $this->estado    = 'Inactiva';
        $this->updatedBy = $updatedBy;
        $this->updatedAt = new DateTime();
    }

    public function getIdCuenta(): ?int { return $this->idCuenta; }
    public function assignId(int $id): void { $this->idCuenta = $id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $n): void { $this->nombre = trim($n); }
    public function getTipo(): string { return $this->tipo; }
    public function setTipo(string $t): void { $this->tipo = $t; }
    public function getSaldoActual(): float { return $this->saldoActual; }
    public function setSaldoActual(float $saldo): void
    {
        $this->saldoActual = round($saldo, 2);
        $this->updatedAt = new DateTime();
    }
    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $e): void { $this->estado = $e; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function tocarUpdatedAt(?int $id = null): void
    {
        $this->updatedAt = new DateTime();
        if ($id !== null) {
            $this->updatedBy = $id;
        }
    }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }

    public function toArray(): array
    {
        return [
            'id_cuenta'    => $this->idCuenta,
            'nombre'       => $this->nombre,
            'tipo'         => $this->tipo,
            'saldo_actual' => $this->saldoActual,
            'estado'       => $this->estado,
        ];
    }
}
