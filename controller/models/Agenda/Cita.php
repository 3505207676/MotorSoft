<?php

require_once __DIR__ . '/Horario.php';

/**
 * Cita de taller. Tabla Citas. Sin acceso a BD.
 */
class Cita
{
    public const PROGRAMADA = 'Programada';
    public const CONFIRMADA = 'Confirmada';
    public const COMPLETADA = 'Completada';
    public const CANCELADA  = 'Cancelada';

    private ?int $idCita;
    private int $idCliente;
    private int $idVehiculo;
    private ?int $idOrden;
    private int $idServicio;
    private int $idHorario;
    private ?string $motivo;
    private string $estadoCita;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    private ?Horario $horario;
    private ?string $clienteNombre;
    private ?string $vehiculoPlaca = null;
    private ?string $vehiculoTipo = null;
    private ?string $vehiculoLabel = null;
    private ?string $servicioNombre;
    private ?string $mecanicoNombre;

    public function __construct(
        int $idCliente,
        int $idVehiculo,
        int $idServicio,
        int $idHorario,
        int $createdBy,
        ?string $motivo = null
    ) {
        $this->idCita          = null;
        $this->idCliente       = $idCliente;
        $this->idVehiculo      = $idVehiculo;
        $this->idOrden         = null;
        $this->idServicio      = $idServicio;
        $this->idHorario       = $idHorario;
        $this->motivo          = $motivo;
        $this->estadoCita      = self::PROGRAMADA;
        $this->createdBy       = $createdBy;
        $this->updatedBy       = null;
        $this->createdAt       = new DateTime();
        $this->updatedAt       = null;
        $this->deletedAt       = null;
        $this->horario         = null;
        $this->clienteNombre   = null;
        $this->vehiculoPlaca   = null;
        $this->vehiculoLabel   = null;
        $this->servicioNombre  = null;
        $this->mecanicoNombre  = null;
    }

    public static function fromArray(array $fila, ?Horario $horario = null): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idCita         = isset($fila['id_cita']) ? (int) $fila['id_cita'] : null;
        $c->idCliente      = (int) ($fila['id_cliente'] ?? 0);
        $c->idVehiculo     = (int) ($fila['id_vehiculo'] ?? 0);
        $c->idOrden        = !empty($fila['id_orden']) ? (int) $fila['id_orden'] : null;
        $c->idServicio     = (int) ($fila['id_servicio'] ?? 0);
        $c->idHorario      = (int) ($fila['id_horario'] ?? 0);
        $c->motivo         = $fila['motivo'] ?? null;
        $c->estadoCita     = $fila['estado_cita'] ?? self::PROGRAMADA;
        $c->createdBy      = (int) ($fila['created_by'] ?? 0);
        $c->updatedBy      = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $c->createdAt      = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->updatedAt      = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $c->deletedAt      = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $c->horario        = $horario;
        $c->clienteNombre  = $fila['cliente_nombre'] ?? null;
        $c->vehiculoPlaca  = $fila['vehiculo_placa'] ?? null;
        $c->vehiculoTipo   = $fila['vehiculo_tipo'] ?? null;
        $marca = trim(($fila['vehiculo_marca'] ?? '') . ' ' . ($fila['vehiculo_modelo'] ?? ''));
        $c->vehiculoLabel  = trim(($fila['vehiculo_placa'] ?? '') . ($marca !== '' ? ' — ' . $marca : ''));
        $c->servicioNombre = $fila['servicio_nombre'] ?? null;
        $c->mecanicoNombre = $fila['mecanico_nombre'] ?? ($horario ? $horario->toArray()['mecanico_nombre'] : null);
        return $c;
    }

    public function assignId(int $id): void { $this->idCita = $id; }
    public function getIdCita(): ?int { return $this->idCita; }
    public function getIdCliente(): int { return $this->idCliente; }
    public function getIdVehiculo(): int { return $this->idVehiculo; }
    public function setIdVehiculo(int $id): void { $this->idVehiculo = $id; }
    public function getIdOrden(): ?int { return $this->idOrden; }
    public function setIdOrden(?int $id): void { $this->idOrden = ($id !== null && $id > 0) ? $id : null; }
    public function tieneOrden(): bool { return $this->idOrden !== null && $this->idOrden > 0; }
    public function getIdServicio(): int { return $this->idServicio; }
    public function setIdServicio(int $id): void { $this->idServicio = $id; }
    public function getIdHorario(): int { return $this->idHorario; }
    public function setIdHorario(int $id): void { $this->idHorario = $id; }
    public function getMotivo(): ?string { return $this->motivo; }
    public function setMotivo(?string $m): void { $this->motivo = $m; }
    public function getEstadoCita(): string { return $this->estadoCita; }
    public function setEstadoCita(string $e): void { $this->estadoCita = $e; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }
    public function getHorario(): ?Horario { return $this->horario; }
    public function setHorario(?Horario $h): void { $this->horario = $h; }

    public function isCancelada(): bool
    {
        return strcasecmp($this->estadoCita, self::CANCELADA) === 0;
    }

    public function isActiva(): bool
    {
        return !$this->isCancelada() && $this->deletedAt === null
            && strcasecmp($this->estadoCita, self::COMPLETADA) !== 0;
    }

    /** Citas que todavía reservan el cupo del horario. */
    public function ocupaCupo(): bool
    {
        return $this->isActiva();
    }

    /** @return string[] */
    public static function estadosQueOcupanCupo(): array
    {
        return [self::PROGRAMADA, self::CONFIRMADA];
    }

    public function tocarUpdatedAt(?int $updatedBy): void
    {
        $this->updatedAt = new DateTime();
        $this->updatedBy = $updatedBy;
    }

    public function marcarEliminado(?int $updatedBy): void
    {
        $this->deletedAt = new DateTime();
        $this->estadoCita = self::CANCELADA;
        $this->tocarUpdatedAt($updatedBy);
    }

    public function toArray(): array
    {
        $horarioArr = $this->horario ? $this->horario->toArray() : null;
        return [
            'id_cita'          => $this->idCita,
            'id_cliente'       => $this->idCliente,
            'cliente_nombre'   => $this->clienteNombre,
            'id_vehiculo'      => $this->idVehiculo,
            'vehiculo_placa'   => $this->vehiculoPlaca,
            'vehiculo_tipo'    => $this->vehiculoTipo,
            'vehiculo_label'   => $this->vehiculoLabel,
            'id_servicio'      => $this->idServicio,
            'servicio_nombre'  => $this->servicioNombre,
            'id_horario'       => $this->idHorario,
            'id_orden'         => $this->idOrden,
            'motivo'           => $this->motivo,
            'estado_cita'      => $this->estadoCita,
            'mecanico_nombre'  => $this->mecanicoNombre,
            'horario'          => $horarioArr,
            'fecha'            => $horarioArr['fecha'] ?? null,
            'hora_inicio'      => $horarioArr['hora_inicio'] ?? null,
        ];
    }
}
