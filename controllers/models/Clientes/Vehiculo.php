<?php

/**
 * Auto que entra al taller. Pertenece a un Cliente (id_cliente).
 * Tabla: Vehiculo. No consulta la base de datos.
 */
class Vehiculo
{
    public const TIPOS = ['Moto', 'Automóvil', 'Camioneta', 'Camión', 'Otro'];

    private ?int $idVehiculo;
    private int $idCliente;
    private string $placa;
    private string $marca;
    private ?string $modelo;
    private ?int $anio;
    private string $tipo;
    private string $estado;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    private ?string $clienteNombre;

    public function __construct(
        int $idCliente,
        string $placa,
        string $marca,
        int $createdBy,
        ?string $modelo = null,
        ?int $anio = null,
        ?int $idVehiculo = null,
        string $tipo = 'Automóvil'
    ) {
        $this->idVehiculo    = $idVehiculo;
        $this->idCliente     = $idCliente;
        $this->placa         = self::normalizarPlaca($placa);
        $this->marca         = $marca;
        $this->modelo        = $modelo;
        $this->anio          = $anio;
        $this->tipo          = self::normalizarTipo($tipo, $this->placa);
        $this->createdBy     = $createdBy;
        $this->estado        = 'Activo';
        $this->updatedBy     = null;
        $this->createdAt     = new DateTime();
        $this->updatedAt     = null;
        $this->deletedAt     = null;
        $this->clienteNombre = null;
    }

    public static function fromArray(array $fila): self
    {
        $vehiculo = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $vehiculo->idVehiculo = isset($fila['id_vehiculo']) ? (int) $fila['id_vehiculo'] : null;
        $vehiculo->idCliente  = (int) ($fila['id_cliente'] ?? 0);
        $vehiculo->placa      = self::normalizarPlaca((string) ($fila['placa'] ?? ''));
        $vehiculo->marca      = $fila['marca'] ?? '';
        $vehiculo->modelo     = $fila['modelo'] ?? null;
        $anio = $fila['anio'] ?? $fila['año'] ?? null;
        $vehiculo->anio       = $anio !== null && $anio !== '' ? (int) $anio : null;
        $vehiculo->tipo       = self::normalizarTipo($fila['tipo'] ?? null, $vehiculo->placa);
        $vehiculo->estado     = $fila['estado'] ?? 'Activo';
        $vehiculo->createdBy  = (int) ($fila['created_by'] ?? 0);
        $vehiculo->updatedBy  = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $vehiculo->createdAt  = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $vehiculo->updatedAt  = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $vehiculo->deletedAt  = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $vehiculo->clienteNombre = $fila['cliente_nombre'] ?? null;

        return $vehiculo;
    }

    public function isActivo(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    /**
     * @param Vehiculo[] $vehiculos
     * @return Vehiculo[]
     */
    public static function listarPorCliente(array $vehiculos, int $idCliente): array
    {
        return array_values(array_filter(
            $vehiculos,
            static function (Vehiculo $vehiculo) use ($idCliente) {
                return $vehiculo->getIdCliente() === $idCliente;
            }
        ));
    }

    /**
     * @param Vehiculo[] $vehiculos
     */
    public static function buscarPorPlaca(array $vehiculos, string $placa): ?self
    {
        $placa = self::normalizarPlaca($placa);
        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->getPlaca() === $placa) {
                return $vehiculo;
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

    public function getIdVehiculo(): ?int
    {
        return $this->idVehiculo;
    }

    public function assignId(int $id): void
    {
        $this->idVehiculo = $id;
    }

    public function getIdCliente(): int
    {
        return $this->idCliente;
    }

    public function setIdCliente(int $idCliente): void
    {
        $this->idCliente = $idCliente;
        $this->tocarUpdatedAt();
    }

    public function getPlaca(): string
    {
        return $this->placa;
    }

    public function setPlaca(string $placa): void
    {
        $this->placa = self::normalizarPlaca($placa);
        $this->tocarUpdatedAt();
    }

    public static function normalizarPlaca(string $placa): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $placa));
    }

    public static function placaValida(string $placa): bool
    {
        $p = self::normalizarPlaca($placa);
        return (bool) preg_match('/^[A-Z]{3}[0-9]{3}$/', $p)
            || (bool) preg_match('/^[A-Z]{3}[0-9]{2}[A-Z]$/', $p);
    }

    public static function tipoDesdePlaca(string $placa): string
    {
        $p = self::normalizarPlaca($placa);
        return preg_match('/^[A-Z]{3}[0-9]{2}[A-Z]$/', $p) ? 'Moto' : 'Automóvil';
    }

    public static function normalizarTipo(?string $tipo, string $placa = ''): string
    {
        $t = trim((string) $tipo);
        foreach (self::TIPOS as $ok) {
            if (strcasecmp($ok, $t) === 0) {
                return $ok;
            }
        }
        return $placa !== '' ? self::tipoDesdePlaca($placa) : 'Automóvil';
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = self::normalizarTipo($tipo, $this->placa);
        $this->tocarUpdatedAt();
    }

    public function getMarca(): string
    {
        return $this->marca;
    }

    public function setMarca(string $marca): void
    {
        $this->marca = $marca;
        $this->tocarUpdatedAt();
    }

    public function getModelo(): ?string
    {
        return $this->modelo;
    }

    public function setModelo(?string $modelo): void
    {
        $this->modelo = $modelo;
        $this->tocarUpdatedAt();
    }

    public function getAnio(): ?int
    {
        return $this->anio;
    }

    public function setAnio(?int $anio): void
    {
        $this->anio = $anio;
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
            'id_vehiculo'     => $this->idVehiculo,
            'id_cliente'      => $this->idCliente,
            'placa'           => $this->placa,
            'marca'           => $this->marca,
            'modelo'          => $this->modelo,
            'anio'            => $this->anio,
            'tipo'            => $this->tipo,
            'estado'          => $this->estado,
            'cliente_nombre'  => $this->clienteNombre,
            'created_at'      => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
