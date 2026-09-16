<?php

/**
 * Contacto comercial del taller (repuestos, insumos). Tabla: Proveedores.
 */
class Proveedor
{
    public const ACTIVO   = 'Activo';
    public const INACTIVO = 'Inactivo';

    private ?int $idProveedor;
    private string $nombre;
    private ?string $nit;
    private ?string $email;
    private string $telefono;
    private ?string $direccion;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;

    public function __construct(string $nombre, string $telefono, int $createdBy, ?string $nit = null, ?string $email = null)
    {
        $this->idProveedor = null;
        $this->nombre      = trim($nombre);
        $this->telefono    = trim($telefono);
        $this->nit         = $nit !== null && trim($nit) !== '' ? trim($nit) : null;
        $this->email       = $email ? strtolower(trim($email)) : null;
        $this->direccion   = null;
        $this->estado      = self::ACTIVO;
        $this->createdBy   = $createdBy;
        $this->updatedBy   = null;
        $this->createdAt   = new DateTime();
        $this->updatedAt   = null;
        $this->deletedAt   = null;
    }

    public static function fromArray(array $fila): self
    {
        $p = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $p->idProveedor = isset($fila['id_proveedor']) ? (int) $fila['id_proveedor'] : null;
        $p->nombre      = (string) ($fila['nombre'] ?? '');
        $p->nit         = $fila['nit'] ?? null;
        $p->email       = $fila['email'] ?? null;
        $p->telefono    = (string) ($fila['telefono'] ?? '');
        $p->direccion   = $fila['direccion'] ?? null;
        $p->estado      = $fila['estado'] ?? self::ACTIVO;
        $p->createdBy   = (int) ($fila['created_by'] ?? 0);
        $p->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $p->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $p->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $p->deletedAt   = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        return $p;
    }

    public function isActivo(): bool
    {
        return strcasecmp($this->estado, self::ACTIVO) === 0 && $this->deletedAt === null;
    }

    public function assignId(int $id): void { $this->idProveedor = $id; }
    public function getIdProveedor(): ?int { return $this->idProveedor; }
    public function getNombre(): string { return $this->nombre; }
    public function getNit(): ?string { return $this->nit; }
    public function getEmail(): ?string { return $this->email; }
    public function getTelefono(): string { return $this->telefono; }
    public function getDireccion(): ?string { return $this->direccion; }
    public function getEstado(): string { return $this->estado; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }

    public function toArray(): array
    {
        return [
            'id_proveedor' => $this->idProveedor,
            'nombre'       => $this->nombre,
            'nit'          => $this->nit,
            'email'        => $this->email,
            'telefono'     => $this->telefono,
            'direccion'    => $this->direccion,
            'estado'       => $this->estado,
        ];
    }
}
