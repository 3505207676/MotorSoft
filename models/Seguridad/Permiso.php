<?php

/**
 * Entidad de dominio: Permiso
 * No consulta la base de datos. La persistencia vive en PermisoRepository.
 */
class Permiso
{
    private ?int $idPermiso;
    private string $nombre;
    private string $slug;
    private string $modulo;
    private string $descripcion;
    private ?DateTime $createdAt;

    public function __construct(
        string $nombre,
        string $slug,
        string $modulo,
        string $descripcion = '',
        ?int $idPermiso = null
    ) {
        $this->idPermiso   = $idPermiso;
        $this->nombre      = $nombre;
        $this->slug        = $slug;
        $this->modulo      = $modulo;
        $this->descripcion = $descripcion;
        $this->createdAt   = new DateTime();
    }

    public static function fromArray(array $fila): self
    {
        $permiso = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $permiso->idPermiso   = isset($fila['id_permiso']) ? (int) $fila['id_permiso'] : null;
        $permiso->nombre      = $fila['nombre'] ?? '';
        $permiso->slug        = $fila['slug'] ?? '';
        $permiso->modulo      = $fila['modulo'] ?? '';
        $permiso->descripcion = $fila['descripcion'] ?? '';
        $permiso->createdAt   = !empty($fila['created_at'])
            ? new DateTime($fila['created_at'])
            : null;

        return $permiso;
    }

    /**
     * Filtra una colección YA cargada. No toca la BD.
     *
     * @param Permiso[] $permisos
     * @return Permiso[]
     */
    public static function scopePorModulo(array $permisos, string $modulo): array
    {
        $modulo = strtolower($modulo);
        return array_values(array_filter(
            $permisos,
            static function (Permiso $permiso) use ($modulo) {
                return strtolower($permiso->getModulo()) === $modulo;
            }
        ));
    }

    public function getIdPermiso(): ?int
    {
        return $this->idPermiso;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void
    {
        $this->nombre = $nombre;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getModulo(): string
    {
        return $this->modulo;
    }

    public function setModulo(string $modulo): void
    {
        $this->modulo = $modulo;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id_permiso'  => $this->idPermiso,
            'nombre'      => $this->nombre,
            'slug'        => $this->slug,
            'modulo'      => $this->modulo,
            'descripcion' => $this->descripcion,
        ];
    }
}
