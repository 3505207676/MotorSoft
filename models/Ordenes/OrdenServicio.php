<?php

require_once __DIR__ . '/DetalleServicio.php';
require_once __DIR__ . '/../Seguridad/Usuario.php';

/**
 * Orden de trabajo (padre del módulo).
 * Tabla: Orden_Servicio.
 *
 * Relación de negocio:
 *   OrdenServicio -> Usuario (mecánico asignado, id_usuario)
 *   OrdenServicio -> Vehiculo (id_vehiculo; el cliente llega por el vehículo)
 *   OrdenServicio -> DetalleServicio[] -> Servicio
 *
 * El SQL no guarda "precio" en la cabecera: el total se calcula con los detalles
 * (totalGeneral). El estado sí existe en BD y gobierna el flujo del taller.
 *
 * listarPorEstado / listarPorUsuario filtran colecciones ya cargadas.
 * La persistencia está en OrdenServicioRepository.
 */
class OrdenServicio
{
    public const ESTADOS = [
        'Pendiente',
        'Diagnóstico',
        'En Proceso',
        'Pendiente Pago',
        'Completada',
        'Cancelada',
    ];

    private ?int $idOrden;
    private int $idVehiculo;
    private int $idUsuario;
    private DateTime $fechaIngreso;
    private ?DateTime $fechaSalida;
    private ?string $descripcion;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    /** @var DetalleServicio[] */
    private array $detalles = [];
    private ?Usuario $usuario;

    public function __construct(
        int $idVehiculo,
        int $idUsuario,
        int $createdBy,
        ?string $descripcion = null,
        ?DateTime $fechaIngreso = null,
        ?int $idOrden = null
    ) {
        $this->idOrden      = $idOrden;
        $this->idVehiculo   = $idVehiculo;
        $this->idUsuario    = $idUsuario;
        $this->createdBy    = $createdBy;
        $this->descripcion  = $descripcion;
        $this->fechaIngreso = $fechaIngreso ?: new DateTime();
        $this->fechaSalida  = null;
        $this->estado       = 'Pendiente';
        $this->updatedBy    = null;
        $this->createdAt    = new DateTime();
        $this->updatedAt    = null;
        $this->deletedAt    = null;
        $this->usuario      = null;
    }

    public static function fromArray(array $fila, array $detalles = [], ?Usuario $usuario = null): self
    {
        $orden = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $orden->idOrden     = isset($fila['id_orden']) ? (int) $fila['id_orden'] : null;
        $orden->idVehiculo  = (int) ($fila['id_vehiculo'] ?? 0);
        $orden->idUsuario   = (int) ($fila['id_usuario'] ?? 0);
        $orden->fechaIngreso = !empty($fila['fecha_ingreso'])
            ? new DateTime($fila['fecha_ingreso'])
            : new DateTime();
        $orden->fechaSalida = !empty($fila['fecha_salida']) ? new DateTime($fila['fecha_salida']) : null;
        $orden->descripcion = $fila['descripcion'] ?? null;
        $orden->estado      = $fila['estado'] ?? 'Pendiente';
        $orden->createdBy   = (int) ($fila['created_by'] ?? 0);
        $orden->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $orden->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $orden->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $orden->deletedAt   = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $orden->detalles    = $detalles;
        $orden->usuario     = $usuario;

        return $orden;
    }

    /**
     * @param OrdenServicio[] $ordenes
     * @return OrdenServicio[]
     */
    public static function listarPorEstado(array $ordenes, string $estado): array
    {
        $estado = strtolower($estado);
        return array_values(array_filter(
            $ordenes,
            static function (OrdenServicio $orden) use ($estado) {
                return strtolower($orden->getEstado()) === $estado;
            }
        ));
    }

    /**
     * @param OrdenServicio[] $ordenes
     * @return OrdenServicio[]
     */
    public static function listarPorUsuario(array $ordenes, int $idUsuario): array
    {
        return array_values(array_filter(
            $ordenes,
            static function (OrdenServicio $orden) use ($idUsuario) {
                return $orden->getIdUsuario() === $idUsuario;
            }
        ));
    }

    public function totalServicios(): int
    {
        return count($this->detalles);
    }

    public function totalGeneral(): float
    {
        $total = 0.0;
        foreach ($this->detalles as $detalle) {
            $total += $detalle->getSubtotal();
        }
        return round($total, 2);
    }

    /** Alias de dominio para el "precio" de la OT (no es columna SQL). */
    public function getPrecio(): float
    {
        return $this->totalGeneral();
    }

    public function cambiarEstado(string $nuevoEstado, ?int $updatedBy = null): void
    {
        if (!in_array($nuevoEstado, self::ESTADOS, true)) {
            throw new AppException('Estado de orden no válido', HTTP_BAD_REQUEST);
        }

        $this->estado = $nuevoEstado;
        $this->tocarUpdatedAt($updatedBy);

        if (in_array($nuevoEstado, ['Completada', 'Cancelada'], true) && $this->fechaSalida === null) {
            $this->fechaSalida = new DateTime();
        }
        if (!in_array($nuevoEstado, ['Completada', 'Cancelada'], true)) {
            $this->fechaSalida = null;
        }
    }

    public function agregarDetalle(DetalleServicio $detalle): void
    {
        if ($this->idOrden !== null) {
            $detalle->setIdOrden($this->idOrden);
        }
        $this->detalles[] = $detalle;
    }

    public function reemplazarDetalles(array $detalles): void
    {
        $this->detalles = [];
        foreach ($detalles as $detalle) {
            $this->agregarDetalle($detalle);
        }
    }

    public function marcarEliminado(?int $updatedBy = null): void
    {
        $this->deletedAt = new DateTime();
        $this->tocarUpdatedAt($updatedBy);
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getIdOrden(): ?int
    {
        return $this->idOrden;
    }

    public function assignId(int $id): void
    {
        $this->idOrden = $id;
        foreach ($this->detalles as $detalle) {
            $detalle->setIdOrden($id);
        }
    }

    public function getIdVehiculo(): int
    {
        return $this->idVehiculo;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function setIdUsuario(int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
        $this->tocarUpdatedAt();
    }

    public function getFechaIngreso(): DateTime
    {
        return $this->fechaIngreso;
    }

    public function getFechaSalida(): ?DateTime
    {
        return $this->fechaSalida;
    }

    public function setFechaSalida(?DateTime $fecha): void
    {
        $this->fechaSalida = $fecha;
        $this->tocarUpdatedAt();
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
        $this->tocarUpdatedAt();
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /** @return DetalleServicio[] */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario): void
    {
        $this->usuario   = $usuario;
        $this->idUsuario = (int) $usuario->getIdUsuario();
    }

    public function toArray(): array
    {
        return [
            'id_orden'        => $this->idOrden,
            'id_vehiculo'     => $this->idVehiculo,
            'id_usuario'      => $this->idUsuario,
            'fecha_ingreso'   => $this->fechaIngreso->format('Y-m-d H:i:s'),
            'fecha_salida'    => $this->fechaSalida ? $this->fechaSalida->format('Y-m-d H:i:s') : null,
            'descripcion'     => $this->descripcion,
            'estado'          => $this->estado,
            'precio'          => $this->totalGeneral(),
            'total_servicios' => $this->totalServicios(),
            'total_general'   => $this->totalGeneral(),
            'mecanico'        => $this->usuario ? $this->usuario->toPublicArray() : null,
            'detalles'        => array_map(static function (DetalleServicio $detalle) {
                return $detalle->toArray();
            }, $this->detalles),
            'created_at'      => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'      => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
        ];
    }
}
