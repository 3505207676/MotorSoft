<?php

/**
 * Slot de agenda. Tabla Agenda_Disponible. Sin acceso a BD.
 */
class Horario
{
    public const DISPONIBLE = 'Disponible';
    public const OCUPADO    = 'Ocupado';
    public const CANCELADO  = 'Cancelado';

    private ?int $idHorario;
    private int $idUsuario;
    private string $fecha;
    private string $horaInicio;
    private int $capacidad;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?string $mecanicoNombre;
    private int $ocupacion;

    public function __construct(int $idUsuario, string $fecha, string $horaInicio, int $createdBy, int $capacidad = 1)
    {
        $this->idHorario      = null;
        $this->idUsuario      = $idUsuario;
        $this->fecha          = $fecha;
        $this->horaInicio     = $this->normalizarHora($horaInicio);
        $this->capacidad      = max(1, $capacidad);
        $this->estado         = self::DISPONIBLE;
        $this->createdBy      = $createdBy;
        $this->updatedBy      = null;
        $this->createdAt      = new DateTime();
        $this->updatedAt      = null;
        $this->mecanicoNombre = null;
        $this->ocupacion      = 0;
    }

    public static function fromArray(array $fila): self
    {
        $h = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $h->idHorario      = isset($fila['id_horario']) ? (int) $fila['id_horario'] : null;
        $h->idUsuario      = (int) ($fila['id_usuario'] ?? 0);
        $h->fecha          = substr((string) ($fila['fecha'] ?? ''), 0, 10);
        $h->horaInicio     = self::formatearHora($fila['hora_inicio'] ?? '08:00:00');
        $h->capacidad      = max(1, (int) ($fila['capacidad'] ?? 1));
        $h->estado         = $fila['estado'] ?? self::DISPONIBLE;
        $h->createdBy      = (int) ($fila['created_by'] ?? 0);
        $h->updatedBy      = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $h->createdAt      = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $h->updatedAt      = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $h->mecanicoNombre = $fila['mecanico_nombre'] ?? null;
        $h->ocupacion      = (int) ($fila['ocupacion'] ?? 0);
        return $h;
    }

    public static function formatearHora($hora): string
    {
        $s = substr((string) $hora, 0, 8);
        if (preg_match('/^\d{2}:\d{2}$/', $s)) {
            return $s . ':00';
        }
        return $s !== '' ? $s : '08:00:00';
    }

    private function normalizarHora(string $hora): string
    {
        return self::formatearHora($hora);
    }

    public function assignId(int $id): void { $this->idHorario = $id; }
    public function getIdHorario(): ?int { return $this->idHorario; }
    public function getIdUsuario(): int { return $this->idUsuario; }
    public function getFecha(): string { return $this->fecha; }
    public function getHoraInicio(): string { return $this->horaInicio; }
    public function horaCorta(): string { return substr($this->horaInicio, 0, 5); }
    public function getCapacidad(): int { return $this->capacidad; }
    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $e): void { $this->estado = $e; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function setOcupacion(int $n): void { $this->ocupacion = $n; }
    public function getOcupacion(): int { return $this->ocupacion; }

    public function isDisponible(): bool
    {
        return !$this->isCancelado() && $this->ocupacion < $this->capacidad;
    }

    public function isCancelado(): bool
    {
        return strcasecmp($this->estado, self::CANCELADO) === 0;
    }

    public function tocarUpdatedAt(?int $updatedBy): void
    {
        $this->updatedAt = new DateTime();
        $this->updatedBy = $updatedBy;
    }

    public function toArray(): array
    {
        $libre = max(0, $this->capacidad - $this->ocupacion);
        if ($this->isCancelado()) {
            $estadoVista = self::CANCELADO;
        } elseif ($libre < 1) {
            $estadoVista = self::OCUPADO;
        } else {
            $estadoVista = self::DISPONIBLE;
        }
        return [
            'id_horario'      => $this->idHorario,
            'id_usuario'      => $this->idUsuario,
            'mecanico_nombre' => $this->mecanicoNombre,
            'fecha'           => $this->fecha,
            'hora_inicio'     => $this->horaCorta(),
            'capacidad'       => $this->capacidad,
            'ocupacion'       => $this->ocupacion,
            'cupos_libres'    => $libre,
            'estado'          => $estadoVista,
        ];
    }
}
