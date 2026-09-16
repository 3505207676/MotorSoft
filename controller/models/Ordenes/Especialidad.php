<?php

require_once __DIR__ . '/../Seguridad/Usuario.php';

/**
 * Oficio del mecánico (frenos, electricidad, diagnóstico...).
 * Tabla: Especialidades. N:N con Usuario vía Usuario_Especialidad.
 * No consulta la base de datos.
 *
 * listar() filtra una colección ya cargada.
 * usuarios() devuelve los Usuario inyectados por el repositorio.
 */
class Especialidad
{
    private ?int $idEspecialidad;
    private string $nombreEspecialidad;
    private ?string $descripcion;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    /** @var Usuario[] */
    private array $usuarios = [];

    public function __construct(
        string $nombreEspecialidad,
        int $createdBy,
        ?string $descripcion = null,
        ?int $idEspecialidad = null
    ) {
        $this->idEspecialidad     = $idEspecialidad;
        $this->nombreEspecialidad = $nombreEspecialidad;
        $this->descripcion        = $descripcion;
        $this->estado             = 'Activo';
        $this->createdBy          = $createdBy;
        $this->updatedBy          = null;
        $this->createdAt          = new DateTime();
        $this->updatedAt          = null;
        $this->deletedAt          = null;
    }

    public static function fromArray(array $fila, array $usuarios = []): self
    {
        $esp = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $esp->idEspecialidad     = isset($fila['id_especialidad']) ? (int) $fila['id_especialidad'] : null;
        $esp->nombreEspecialidad = $fila['nombre_especialidad'] ?? '';
        $esp->descripcion        = $fila['descripcion'] ?? null;
        $esp->estado             = $fila['estado'] ?? 'Activo';
        $esp->createdBy          = (int) ($fila['created_by'] ?? 0);
        $esp->updatedBy          = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $esp->createdAt          = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $esp->updatedAt          = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $esp->deletedAt          = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $esp->usuarios           = $usuarios;

        return $esp;
    }

    public function isActiva(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    /**
     * @param Especialidad[] $especialidades
     * @return Especialidad[]
     */
    public static function listar(array $especialidades, ?string $estado = null): array
    {
        if ($estado === null) {
            return array_values($especialidades);
        }
        $estado = strtolower($estado);
        return array_values(array_filter(
            $especialidades,
            static function (Especialidad $item) use ($estado) {
                return strtolower($item->getEstado()) === $estado;
            }
        ));
    }

    /** @return Usuario[] */
    public function usuarios(): array
    {
        return $this->usuarios;
    }

    /** @param Usuario[] $usuarios */
    public function setUsuarios(array $usuarios): void
    {
        $this->usuarios = $usuarios;
    }

    public function getIdEspecialidad(): ?int
    {
        return $this->idEspecialidad;
    }

    public function getNombreEspecialidad(): string
    {
        return $this->nombreEspecialidad;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): int
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

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function assignId(int $id): void
    {
        $this->idEspecialidad = $id;
    }

    public function setNombreEspecialidad(string $nombre): void
    {
        $this->nombreEspecialidad = $nombre;
    }

    public function setDescripcion(?string $descripcion): void
    {
        $this->descripcion = $descripcion;
    }

    public function setEstado(string $estado): void
    {
        $this->estado = $estado;
    }

    public function tocarUpdatedAt(int $idUsuario): void
    {
        $this->updatedBy = $idUsuario;
        $this->updatedAt = new DateTime();
    }

    public function marcarEliminado(int $idUsuario): void
    {
        $this->deletedAt = new DateTime();
        $this->estado    = 'Inactivo';
        $this->tocarUpdatedAt($idUsuario);
    }

    public function toArray(): array
    {
        return [
            'id_especialidad'     => $this->idEspecialidad,
            'nombre_especialidad' => $this->nombreEspecialidad,
            'descripcion'         => $this->descripcion,
            'estado'              => $this->estado,
            'created_at'          => $this->createdAt->format('Y-m-d H:i:s'),
            'usuarios'            => array_map(static function (Usuario $usuario) {
                return $usuario->toPublicArray();
            }, $this->usuarios),
        ];
    }
}
