<?php

require_once __DIR__ . '/Rol.php';

/**
 * Entidad de dominio: Usuario (padre del módulo de seguridad)
 * Relación: Usuario -> Rol -> Permiso[]
 * No consulta la base de datos.
 */
class Usuario
{
    private ?int $idUsuario;
    private string $nombre;
    private string $correo;
    private ?string $telefono;
    private string $documento;
    private string $estado;
    private string $passwordHash;
    private int $intentosLogin;
    private ?string $tokenRecuperacion;
    private ?DateTime $tokenExpiracion;
    private ?int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    private ?Rol $rol;

    public function __construct(
        string $nombre,
        string $correo,
        string $documento,
        string $passwordHash,
        ?string $telefono = null,
        ?int $idUsuario = null,
        ?Rol $rol = null
    ) {
        $this->idUsuario     = $idUsuario;
        $this->nombre        = $nombre;
        $this->correo        = strtolower(trim($correo));
        $this->telefono      = $telefono;
        $this->documento     = $documento;
        $this->passwordHash  = $passwordHash;
        $this->rol           = $rol;
        $this->estado        = 'Activo';
        $this->intentosLogin = 0;
        $this->createdAt     = new DateTime();
        $this->createdBy     = null;
        $this->updatedBy     = null;
        $this->tokenRecuperacion = null;
        $this->tokenExpiracion   = null;
        $this->updatedAt     = null;
        $this->deletedAt     = null;
    }

    public static function fromArray(array $fila, ?Rol $rol = null): self
    {
        $usuario = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $usuario->idUsuario    = isset($fila['id_usuario']) ? (int) $fila['id_usuario'] : null;
        $usuario->nombre       = $fila['nombre'] ?? '';
        $usuario->correo       = $fila['correo'] ?? '';
        $usuario->telefono     = $fila['telefono'] ?? null;
        $usuario->documento    = (string) ($fila['documento'] ?? '');
        $usuario->passwordHash = $fila['password_hash'] ?? '';
        $usuario->estado       = $fila['estado'] ?? 'Activo';
        $usuario->intentosLogin = (int) ($fila['intentos_login'] ?? 0);
        $usuario->createdBy    = isset($fila['created_by']) ? (int) $fila['created_by'] : null;
        $usuario->updatedBy    = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $usuario->tokenRecuperacion = $fila['token_recuperacion'] ?? null;
        $usuario->tokenExpiracion   = !empty($fila['token_expiracion'])
            ? new DateTime($fila['token_expiracion'])
            : null;
        $usuario->updatedAt = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $usuario->deletedAt = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $usuario->createdAt = !empty($fila['created_at'])
            ? new DateTime($fila['created_at'])
            : new DateTime();
        $usuario->rol = $rol;

        return $usuario;
    }

    public function isActivo(): bool
    {
        return strtolower($this->estado) === 'activo' && $this->deletedAt === null;
    }

    public function isBloqueado(): bool
    {
        return $this->intentosLogin >= 3 || strtolower($this->estado) === 'bloqueado';
    }

    public function incrementarIntentos(): void
    {
        $this->intentosLogin++;
        if ($this->intentosLogin >= 3) {
            $this->estado = 'Bloqueado';
        }
        $this->tocarUpdatedAt();
    }

    public function resetIntentos(): void
    {
        $this->intentosLogin = 0;
        if (strtolower($this->estado) === 'bloqueado') {
            $this->estado = 'Activo';
        }
        $this->tocarUpdatedAt();
    }

    /**
     * Genera un token de un uso. Devuelve el valor en claro; en BD se guarda el hash.
     */
    public function generarToken(int $horas = 24): string
    {
        $crudo = bin2hex(random_bytes(32));
        $this->tokenRecuperacion = self::hashToken($crudo);
        $this->tokenExpiracion   = (new DateTime())->modify('+' . max(1, $horas) . ' hours');
        $this->tocarUpdatedAt();
        return $crudo;
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function tokenRecuperacionEsValido(string $token): bool
    {
        if ($this->tokenRecuperacion === null || $this->tokenExpiracion === null) {
            return false;
        }
        if ($this->tokenExpiracion <= new DateTime()) {
            return false;
        }
        $entrada = self::hashToken($token);
        if (hash_equals($this->tokenRecuperacion, $entrada)) {
            return true;
        }
        return hash_equals($this->tokenRecuperacion, $token);
    }

    public function invitacionPendiente(): bool
    {
        return $this->tokenRecuperacion !== null
            && $this->tokenExpiracion !== null
            && $this->tokenExpiracion > new DateTime();
    }

    public function limpiarTokenRecuperacion(): void
    {
        $this->tokenRecuperacion = null;
        $this->tokenExpiracion   = null;
        $this->tocarUpdatedAt();
    }

    public function verificarPassword(string $passwordPlano): bool
    {
        return password_verify($passwordPlano, $this->passwordHash);
    }

    public function cambiarPassword(string $passwordPlano): void
    {
        $this->passwordHash = password_hash($passwordPlano, PASSWORD_DEFAULT);
        $this->limpiarTokenRecuperacion();
    }

    public function tieneRol(string $nombreRol): bool
    {
        if ($this->rol === null) {
            return false;
        }
        return strtolower($this->rol->getNombre()) === strtolower($nombreRol);
    }

    public function tienePermiso(string $slug): bool
    {
        return $this->rol !== null && $this->rol->tienePermiso($slug);
    }

    public function puede(string $slug): bool
    {
        if ($this->esAdministrador()) {
            return true;
        }
        return $this->tienePermiso($slug);
    }

    /** @return string[] */
    public function slugs(): array
    {
        if ($this->rol === null) {
            return [];
        }
        $lista = [];
        foreach ($this->rol->getPermisos() as $permiso) {
            $lista[] = $permiso->getSlug();
        }
        return array_values(array_unique($lista));
    }

    /** @param string[] $slugs */
    public function puedeAlguno(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->puede((string) $slug)) {
                return true;
            }
        }
        return false;
    }

    public function getIdUsuario(): ?int
    {
        return $this->idUsuario;
    }

    public function assignId(int $idUsuario): void
    {
        $this->idUsuario = $idUsuario;
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

    public function getCorreo(): string
    {
        return $this->correo;
    }

    public function setCorreo(string $correo): void
    {
        $this->correo = strtolower(trim($correo));
        $this->tocarUpdatedAt();
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): void
    {
        $this->telefono = $telefono;
        $this->tocarUpdatedAt();
    }

    public function getDocumento(): string
    {
        return $this->documento;
    }

    public function setDocumento(string $documento): void
    {
        $this->documento = $documento;
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

    public function getIntentosLogin(): int
    {
        return $this->intentosLogin;
    }

    public function getRol(): ?Rol
    {
        return $this->rol;
    }

    public function esAdministrador(): bool
    {
        return strpos($this->claveRol(), 'admin') !== false;
    }

    public function esGerente(): bool
    {
        return strpos($this->claveRol(), 'gerent') !== false;
    }

    public function esRecepcionista(): bool
    {
        return strpos($this->claveRol(), 'recep') !== false;
    }

    public function puedeOperarCaja(): bool
    {
        return $this->puede('caja.abrir');
    }

    public function esMecanico(): bool
    {
        return strpos($this->claveRol(), 'mecanic') !== false;
    }

    public function esPersonalOficina(): bool
    {
        return $this->esAdministrador() || $this->esGerente() || $this->esRecepcionista();
    }

    private function claveRol(): string
    {
        $nombre = $this->rol ? $this->rol->getNombre() : '';
        $clave = strtolower(trim($nombre));
        return str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clave);
    }

    public function puedeGestionarAgenda(): bool
    {
        return $this->esAdministrador() || $this->puedeOperarCaja() || $this->esMecanico();
    }

    public function setRol(Rol $rol): void
    {
        $this->rol = $rol;
        $this->tocarUpdatedAt();
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getTokenRecuperacion(): ?string
    {
        return $this->tokenRecuperacion;
    }

    public function getTokenExpiracion(): ?DateTime
    {
        return $this->tokenExpiracion;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?int $createdBy): void
    {
        $this->createdBy = $createdBy;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy(): ?int
    {
        return $this->updatedBy;
    }

    public function tocarUpdatedAt(?int $idUsuario = null): void
    {
        $this->updatedAt = new DateTime();
        if ($idUsuario !== null) {
            $this->updatedBy = $idUsuario;
        }
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function marcarEliminado(): void
    {
        $this->deletedAt = new DateTime();
        $this->estado    = 'Inactivo';
        $this->tocarUpdatedAt();
    }

    public function toPublicArray(): array
    {
        return [
            'id_usuario'     => $this->idUsuario,
            'nombre'         => $this->nombre,
            'correo'         => $this->correo,
            'telefono'       => $this->telefono,
            'documento'      => $this->documento,
            'estado'         => $this->estado,
            'rol'            => $this->rol ? $this->rol->toArray() : null,
            'permisos'       => $this->slugs(),
            'es_admin'       => $this->esAdministrador(),
            'es_gerente'     => $this->esGerente(),
            'es_mecanico'    => $this->esMecanico(),
            'es_recepcion'            => $this->esRecepcionista(),
            'invitacion_pendiente'    => $this->invitacionPendiente(),
        ];
    }
}
