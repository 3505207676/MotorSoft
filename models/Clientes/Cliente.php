<?php

require_once __DIR__ . '/Vehiculo.php';

/**
 * Dueño de los vehículos del taller.
 * Tabla: Clientes. No consulta la base de datos.
 *
 * guardar / actualizar / eliminar / buscarPorDoc viven en
 * ClienteRepository + ClienteService.
 */
class Cliente
{
    private ?int $idCliente;
    private string $nombre;
    private string $documento;
    private ?string $email;
    private string $telefono;
    private ?string $preferenciaContacto;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    /** @var Vehiculo[] */
    private array $vehiculos = [];

    public function __construct(
        string $nombre,
        string $documento,
        string $telefono,
        int $createdBy,
        ?string $email = null,
        ?string $preferenciaContacto = null,
        ?int $idCliente = null
    ) {
        $this->idCliente            = $idCliente;
        $this->nombre               = $nombre;
        $this->documento            = trim($documento);
        $this->telefono             = $telefono;
        $this->email                = $email ? strtolower(trim($email)) : null;
        $this->preferenciaContacto  = $preferenciaContacto;
        $this->createdBy            = $createdBy;
        $this->estado               = 'Activo';
        $this->updatedBy            = null;
        $this->createdAt            = new DateTime();
        $this->updatedAt            = null;
        $this->deletedAt            = null;
    }

    public static function fromArray(array $fila, array $vehiculos = []): self
    {
        $cliente = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $cliente->idCliente           = isset($fila['id_cliente']) ? (int) $fila['id_cliente'] : null;
        $cliente->nombre              = $fila['nombre'] ?? '';
        $cliente->documento           = (string) ($fila['documento'] ?? '');
        $cliente->email               = $fila['email'] ?? null;
        $cliente->telefono            = (string) ($fila['telefono'] ?? '');
        $cliente->preferenciaContacto = $fila['preferencia_contacto'] ?? null;
        $cliente->estado              = $fila['estado'] ?? 'Activo';
        $cliente->createdBy           = (int) ($fila['created_by'] ?? 0);
        $cliente->updatedBy           = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $cliente->createdAt           = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $cliente->updatedAt           = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $cliente->deletedAt           = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $cliente->vehiculos           = $vehiculos;

        return $cliente;
    }

    public function isActivo(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    /**
     * Filtra una colección YA cargada por documento. No toca la BD.
     *
     * @param Cliente[] $clientes
     */
    public static function buscarPorDoc(array $clientes, string $documento): ?self
    {
        $documento = trim($documento);
        foreach ($clientes as $cliente) {
            if ($cliente->getDocumento() === $documento) {
                return $cliente;
            }
        }
        return null;
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

    public function getIdCliente(): ?int
    {
        return $this->idCliente;
    }

    public function assignId(int $id): void
    {
        $this->idCliente = $id;
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

    public function getDocumento(): string
    {
        return $this->documento;
    }

    public function setDocumento(string $documento): void
    {
        $this->documento = trim($documento);
        $this->tocarUpdatedAt();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email ? strtolower(trim($email)) : null;
        $this->tocarUpdatedAt();
    }

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): void
    {
        $this->telefono = $telefono;
        $this->tocarUpdatedAt();
    }

    public function getPreferenciaContacto(): ?string
    {
        return $this->preferenciaContacto;
    }

    public function setPreferenciaContacto(?string $preferencia): void
    {
        $this->preferenciaContacto = $preferencia;
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

    /** @return Vehiculo[] */
    public function getVehiculos(): array
    {
        return $this->vehiculos;
    }

    /** @param Vehiculo[] $vehiculos */
    public function setVehiculos(array $vehiculos): void
    {
        $this->vehiculos = $vehiculos;
    }

    public function toArray(): array
    {
        return [
            'id_cliente'            => $this->idCliente,
            'nombre'                => $this->nombre,
            'documento'             => $this->documento,
            'email'                 => $this->email,
            'telefono'              => $this->telefono,
            'preferencia_contacto'  => $this->preferenciaContacto,
            'estado'                => $this->estado,
            'created_at'            => $this->createdAt->format('Y-m-d H:i:s'),
            'deleted_at'            => $this->deletedAt ? $this->deletedAt->format('Y-m-d H:i:s') : null,
            'vehiculos'             => array_map(static function (Vehiculo $v) {
                return $v->toArray();
            }, $this->vehiculos),
        ];
    }
}
