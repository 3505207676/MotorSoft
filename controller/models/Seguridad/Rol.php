<?php

require_once __DIR__ . '/Permiso.php';

/**
 * Entidad de dominio: Rol
 * Un rol agrupa muchos Permiso. No consulta la base de datos.
 * Los permisos los inyecta el repositorio o el service.
 */
class Rol
{
    private ?int $idRol;
    private string $nombre;
    private string $descripcion;
    private string $estado;
    private DateTime $createdAt;
    private ?int $createdBy;
    private ?DateTime $updatedAt;
    private ?int $updatedBy;
    private ?DateTime $deletedAt;
    private bool $matrizManual = false;

    /** @var Permiso[] */
    private array $permisos = [];

    public function __construct(
        string $nombre,
        string $descripcion = '',
        ?int $createdBy = null,
        ?int $idRol = null,
        string $estado = 'Activo'
    ) {
        $this->idRol       = $idRol;
        $this->nombre      = $nombre;
        $this->descripcion = $descripcion;
        $this->estado      = $estado;
        $this->createdBy   = $createdBy;
        $this->createdAt   = new DateTime();
        $this->updatedAt   = null;
        $this->updatedBy   = null;
        $this->deletedAt   = null;
    }

    public static function fromArray(array $fila, array $permisos = []): self
    {
        $rol = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $rol->idRol       = isset($fila['id_rol']) ? (int) $fila['id_rol'] : null;
        $rol->nombre      = $fila['nombre'] ?? '';
        $rol->descripcion = $fila['descripcion'] ?? '';
        $rol->estado      = $fila['estado'] ?? 'Activo';
        $rol->createdBy   = isset($fila['created_by']) ? (int) $fila['created_by'] : null;
        $rol->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $rol->createdAt   = !empty($fila['created_at'])
            ? new DateTime($fila['created_at'])
            : new DateTime();
        $rol->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $rol->deletedAt    = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $rol->matrizManual = !empty($fila['matriz_manual']);
        $rol->permisos     = $permisos;

        return $rol;
    }

    public function isActivo(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    /** @param Permiso[] $permisos */
    public function setPermisos(array $permisos): void
    {
        $this->permisos = $permisos;
    }

    /** @return Permiso[] */
    public function getPermisos(): array
    {
        return $this->permisos;
    }

    public function agregarPermiso(Permiso $permiso): void
    {
        foreach ($this->permisos as $existente) {
            if ($existente->getSlug() === $permiso->getSlug()) {
                return;
            }
        }
        $this->permisos[] = $permiso;
    }

    public function tienePermiso(string $slugPermiso): bool
    {
        foreach ($this->permisos as $permiso) {
            if ($permiso->getSlug() === $slugPermiso) {
                return true;
            }
        }
        return false;
    }

    public function getIdRol(): ?int
    {
        return $this->idRol;
    }

    public function assignId(int $idRol): void
    {
        $this->idRol = $idRol;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function marcarActualizado(int $idUsuario): void
    {
        $this->updatedBy = $idUsuario;
        $this->updatedAt = new DateTime();
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function marcarEliminado(): void
    {
        $this->deletedAt = new DateTime();
    }

    public function isMatrizManual(): bool
    {
        return $this->matrizManual;
    }

    public function setMatrizManual(bool $manual): void
    {
        $this->matrizManual = $manual;
    }

    public function toArray(): array
    {
        return [
            'id_rol'         => $this->idRol,
            'nombre'         => $this->nombre,
            'descripcion'    => $this->descripcion,
            'estado'         => $this->estado,
            'matriz_manual'  => $this->matrizManual,
            'permisos'       => array_map(static function (Permiso $permiso) {
                return $permiso->toArray();
            }, $this->permisos),
        ];
    }
}
