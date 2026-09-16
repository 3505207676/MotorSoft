<?php

/**
 * Contrato laboral. Tabla Contratos. Sin acceso a BD.
 */
class Contrato
{
    public const VIGENTE    = 'Vigente';
    public const FINALIZADO = 'Finalizado';

    private ?int $idContrato;
    private int $idUsuario;
    private float $salarioBase;
    private float $porcentajeComision;
    private string $fechaIngreso;
    private ?string $fechaRetiro;
    private string $estadoContrato;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    private ?string $usuarioNombre;
    private ?array $usuario;

    public function __construct(
        int $idUsuario,
        float $salarioBase,
        int $createdBy,
        float $porcentajeComision = 0.0,
        ?string $fechaIngreso = null
    ) {
        $this->idContrato          = null;
        $this->idUsuario           = $idUsuario;
        $this->salarioBase         = round($salarioBase, 2);
        $this->porcentajeComision  = round($porcentajeComision, 2);
        $this->fechaIngreso        = $fechaIngreso ?: date('Y-m-d');
        $this->fechaRetiro         = null;
        $this->estadoContrato      = self::VIGENTE;
        $this->createdBy           = $createdBy;
        $this->updatedBy           = null;
        $this->createdAt           = new DateTime();
        $this->updatedAt           = null;
        $this->deletedAt           = null;
        $this->usuarioNombre       = null;
        $this->usuario             = null;
    }

    public static function fromArray(array $fila): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idContrato         = isset($fila['id_contrato']) ? (int) $fila['id_contrato'] : null;
        $c->idUsuario          = (int) ($fila['id_usuario'] ?? 0);
        $c->salarioBase        = (float) ($fila['salario_base'] ?? 0);
        $comision = $fila['porcentaje_comision'] ?? $fila['porcentaje_comicion'] ?? 0;
        $c->porcentajeComision = (float) $comision;
        $c->fechaIngreso       = substr((string) ($fila['fecha_ingreso'] ?? date('Y-m-d')), 0, 10);
        $c->fechaRetiro        = !empty($fila['fecha_retiro']) ? substr((string) $fila['fecha_retiro'], 0, 10) : null;
        $c->estadoContrato     = $fila['estado_contrato'] ?? self::VIGENTE;
        $c->createdBy          = (int) ($fila['created_by'] ?? 0);
        $c->updatedBy          = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $c->createdAt          = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->updatedAt          = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $c->deletedAt          = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $c->usuarioNombre      = $fila['usuario_nombre'] ?? null;
        $c->usuario            = null;
        return $c;
    }

    public function assignId(int $id): void { $this->idContrato = $id; }
    public function getIdContrato(): ?int { return $this->idContrato; }
    public function getIdUsuario(): int { return $this->idUsuario; }
    public function setIdUsuario(int $id): void { $this->idUsuario = $id; }
    public function getSalarioBase(): float { return $this->salarioBase; }
    public function setSalarioBase(float $v): void { $this->salarioBase = round($v, 2); }
    public function getPorcentajeComision(): float { return $this->porcentajeComision; }
    public function setPorcentajeComision(float $v): void { $this->porcentajeComision = round($v, 2); }
    public function getFechaIngreso(): string { return $this->fechaIngreso; }
    public function setFechaIngreso(string $f): void { $this->fechaIngreso = substr($f, 0, 10); }
    public function getFechaRetiro(): ?string { return $this->fechaRetiro; }
    public function setFechaRetiro(?string $f): void { $this->fechaRetiro = $f ? substr($f, 0, 10) : null; }
    public function getEstadoContrato(): string { return $this->estadoContrato; }
    public function setEstadoContrato(string $e): void { $this->estadoContrato = $e; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }
    public function setUsuarioNombre(?string $n): void { $this->usuarioNombre = $n; }

    public function isVigente(): bool
    {
        return strcasecmp($this->estadoContrato, self::VIGENTE) === 0 && $this->deletedAt === null;
    }

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

    public function finalizar(?string $fechaRetiro, ?int $updatedBy): void
    {
        $this->estadoContrato = self::FINALIZADO;
        $this->fechaRetiro    = $fechaRetiro ?: date('Y-m-d');
        $this->tocarUpdatedAt($updatedBy);
    }

    public function toArray(): array
    {
        return [
            'id_contrato'          => $this->idContrato,
            'id_usuario'           => $this->idUsuario,
            'usuario_nombre'       => $this->usuarioNombre,
            'salario_base'         => $this->salarioBase,
            'porcentaje_comision'  => $this->porcentajeComision,
            'porcentaje_comicion'  => $this->porcentajeComision,
            'fecha_ingreso'        => $this->fechaIngreso,
            'fecha_retiro'         => $this->fechaRetiro,
            'estado_contrato'      => $this->estadoContrato,
        ];
    }
}
