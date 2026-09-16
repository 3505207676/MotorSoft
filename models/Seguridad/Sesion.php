<?php

require_once __DIR__ . '/Usuario.php';

/**
 * Entidad de dominio: Sesion
 * Relación: Sesion pertenece a Usuario y es el contexto de LogAuditoria.
 * No consulta la base de datos.
 */
class Sesion
{
    private ?int $idSesion;
    private int $idUsuario;
    private string $token;
    private DateTime $fechaInicio;
    private DateTime $fechaExpiracion;
    private ?string $ipAddress;
    private ?DateTime $deletedAt;
    private ?Usuario $usuario;

    public function __construct(
        int $idUsuario,
        string $token,
        DateTime $fechaExpiracion,
        ?string $ipAddress = null,
        ?int $idSesion = null
    ) {
        $this->idSesion         = $idSesion;
        $this->idUsuario        = $idUsuario;
        $this->token            = $token;
        $this->fechaInicio      = new DateTime();
        $this->fechaExpiracion  = $fechaExpiracion;
        $this->ipAddress        = $ipAddress;
        $this->deletedAt        = null;
        $this->usuario          = null;
    }

    public static function fromArray(array $fila, ?Usuario $usuario = null): self
    {
        $sesion = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $sesion->idSesion  = isset($fila['id_sesiones']) ? (int) $fila['id_sesiones'] : null;
        $sesion->idUsuario = (int) ($fila['id_usuario'] ?? 0);
        $sesion->token     = $fila['token'] ?? '';
        $sesion->fechaInicio = !empty($fila['fecha_inicio'])
            ? new DateTime($fila['fecha_inicio'])
            : new DateTime();
        $sesion->fechaExpiracion = !empty($fila['fecha_expiracion'])
            ? new DateTime($fila['fecha_expiracion'])
            : new DateTime();
        $sesion->ipAddress = $fila['ip_address'] ?? null;
        $sesion->deletedAt = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $sesion->usuario   = $usuario;

        return $sesion;
    }

    public function isActiva(): bool
    {
        if ($this->deletedAt !== null) {
            return false;
        }
        return $this->fechaExpiracion > new DateTime();
    }

    public function invalidar(): void
    {
        $this->deletedAt = new DateTime();
    }

    public function getIdSesion(): ?int
    {
        return $this->idSesion;
    }

    public function assignId(int $idSesion): void
    {
        $this->idSesion = $idSesion;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getFechaInicio(): DateTime
    {
        return $this->fechaInicio;
    }

    public function getFechaExpiracion(): DateTime
    {
        return $this->fechaExpiracion;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
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
            'id_sesion'         => $this->idSesion,
            'id_usuario'        => $this->idUsuario,
            'token'             => $this->token,
            'fecha_inicio'      => $this->fechaInicio->format('Y-m-d H:i:s'),
            'fecha_expiracion'  => $this->fechaExpiracion->format('Y-m-d H:i:s'),
            'ip_address'        => $this->ipAddress,
            'activa'            => $this->isActiva(),
        ];
    }
}

if (!class_exists('Sesiones')) {
    class_alias('Sesion', 'Sesiones');
}
