<?php

/**
 * Catálogo de mano de obra / intervenciones.
 * Tabla: Servicios. No consulta la base de datos.
 */
class Servicio
{
    private ?int $idServicio;
    private ?string $tipo;
    private string $nombre;
    private ?string $descripcion;
    private float $precio;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    public function __construct(
        string $nombre,
        float $precio,
        int $createdBy,
        ?string $tipo = null,
        ?string $descripcion = null,
        ?int $idServicio = null
    ) {
        $this->idServicio  = $idServicio;
        $this->tipo        = $tipo;
        $this->nombre      = $nombre;
        $this->descripcion = $descripcion;
        $this->precio      = round($precio, 2);
        $this->estado      = 'Activo';
        $this->createdBy   = $createdBy;
        $this->updatedBy   = null;
        $this->createdAt   = new DateTime();
        $this->updatedAt   = null;
        $this->deletedAt   = null;
    }

    public static function fromArray(array $fila): self
    {
        $servicio = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $servicio->idServicio  = isset($fila['id_servicio']) ? (int) $fila['id_servicio'] : null;
        $servicio->tipo        = $fila['tipo'] ?? null;
        $servicio->nombre      = $fila['nombre'] ?? '';
        $servicio->descripcion = $fila['descripcion'] ?? null;
        $servicio->precio      = (float) ($fila['precio'] ?? 0);
        $servicio->estado      = $fila['estado'] ?? 'Activo';
        $servicio->createdBy   = (int) ($fila['created_by'] ?? 0);
        $servicio->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $servicio->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $servicio->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $servicio->deletedAt   = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;

        return $servicio;
    }

    public function isActivo(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    public function desactivar(): void
    {
        $this->estado    = 'Inactivo';
        $this->updatedAt = new DateTime();
    }

    public function marcarEliminado(): void
    {
        $this->deletedAt = new DateTime();
        $this->desactivar();
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getIdServicio(): ?int
    {
        return $this->idServicio;
    }

    public function assignId(int $id): void
    {
        $this->idServicio = $id;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function setTipo(?string $tipo): void
    {
        $this->tipo = $tipo;
        $this->tocarUpdatedAt();
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
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

    public function getPrecio(): float
    {
        return $this->precio;
    }

    public function setPrecio(float $precio): void
    {
        $this->precio = round($precio, 2);
        $this->tocarUpdatedAt();
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
        $this->tocarUpdatedAt();
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

    public function toArray(): array
    {
        return [
            'id_servicio' => $this->idServicio,
            'tipo'        => $this->tipo,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio'      => $this->precio,
            'estado'      => $this->estado,
            'created_at'  => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'  => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
        ];
    }
}
