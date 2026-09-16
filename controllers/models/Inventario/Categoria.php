<?php

/**
 * Estantería del almacén. Tabla: Categorias. Sin acceso a BD.
 */
class Categoria
{
    private ?int $idCategoria;
    private string $nombre;
    private string $descripcion;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    public function __construct(string $nombre, int $createdBy, string $descripcion = '', ?int $idCategoria = null)
    {
        $this->idCategoria = $idCategoria;
        $this->nombre      = trim($nombre);
        $this->descripcion = $descripcion !== '' ? $descripcion : $this->nombre;
        $this->createdBy   = $createdBy;
        $this->estado      = 'Activo';
        $this->updatedBy   = null;
        $this->createdAt   = new DateTime();
        $this->updatedAt   = null;
        $this->deletedAt   = null;
    }

    public static function fromArray(array $fila): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idCategoria = isset($fila['id_categoria']) ? (int) $fila['id_categoria'] : null;
        $c->nombre      = $fila['nombre'] ?? '';
        $c->descripcion = $fila['descripcion'] ?? '';
        $c->estado      = $fila['estado'] ?? 'Activo';
        $c->createdBy   = (int) ($fila['created_by'] ?? 0);
        $c->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $c->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $c->deletedAt   = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        return $c;
    }

    public function isActiva(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    public function marcarEliminado(?int $updatedBy = null): void
    {
        $this->deletedAt = new DateTime();
        $this->estado    = 'Inactivo';
        $this->tocarUpdatedAt($updatedBy);
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getIdCategoria(): ?int { return $this->idCategoria; }
    public function assignId(int $id): void { $this->idCategoria = $id; }
    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): void { $this->nombre = trim($nombre); $this->tocarUpdatedAt(); }
    public function getDescripcion(): string { return $this->descripcion; }
    public function setDescripcion(string $descripcion): void { $this->descripcion = $descripcion; $this->tocarUpdatedAt(); }
    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $estado): void { $this->estado = $estado; $this->tocarUpdatedAt(); }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }

    public function toArray(): array
    {
        return [
            'id_categoria' => $this->idCategoria,
            'nombre'       => $this->nombre,
            'descripcion'  => $this->descripcion,
            'estado'       => $this->estado,
        ];
    }
}
