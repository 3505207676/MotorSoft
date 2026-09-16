<?php

/**
 * Línea de nómina (devengo o deducción). Tabla Nomina_Rol. Sin acceso a BD.
 */
class NominaRol
{
    public const DEVENGO    = 'Devengo';
    public const DEDUCCION  = 'Deduccion';

    private ?int $idNominaRol;
    private int $idNomina;
    private string $tipoConcepto;
    private string $descripcion;
    private float $valor;
    private int $createdBy;
    private DateTime $createdAt;

    public function __construct(int $idNomina, string $tipoConcepto, string $descripcion, float $valor, int $createdBy)
    {
        $this->idNominaRol  = null;
        $this->idNomina     = $idNomina;
        $this->tipoConcepto = $tipoConcepto;
        $this->descripcion  = $descripcion;
        $this->valor        = round($valor, 2);
        $this->createdBy    = $createdBy;
        $this->createdAt    = new DateTime();
    }

    public static function fromArray(array $fila): self
    {
        $n = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $n->idNominaRol  = isset($fila['id_nomina_rol']) ? (int) $fila['id_nomina_rol'] : null;
        $n->idNomina     = (int) ($fila['id_nomina'] ?? 0);
        $n->tipoConcepto = $fila['tipo_concepto'] ?? self::DEVENGO;
        $n->descripcion  = $fila['descripcion'] ?? '';
        $n->valor        = (float) ($fila['valor'] ?? 0);
        $n->createdBy    = (int) ($fila['created_by'] ?? 0);
        $n->createdAt    = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        return $n;
    }

    public function assignId(int $id): void { $this->idNominaRol = $id; }
    public function setIdNomina(int $id): void { $this->idNomina = $id; }
    public function getIdNominaRol(): ?int { return $this->idNominaRol; }
    public function getIdNomina(): int { return $this->idNomina; }
    public function getTipoConcepto(): string { return $this->tipoConcepto; }
    public function getDescripcion(): string { return $this->descripcion; }
    public function getValor(): float { return $this->valor; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    public function esDevengo(): bool
    {
        return strcasecmp($this->tipoConcepto, self::DEVENGO) === 0;
    }

    public function toArray(): array
    {
        return [
            'id_nomina_rol' => $this->idNominaRol,
            'id_nomina'     => $this->idNomina,
            'tipo_concepto' => $this->tipoConcepto,
            'descripcion'   => $this->descripcion,
            'valor'         => $this->valor,
        ];
    }
}
