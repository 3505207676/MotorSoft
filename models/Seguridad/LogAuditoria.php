<?php

require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/Sesion.php';

/**
 * Entidad de dominio: LogAuditoria
 * Tabla SQL: Logs_Actoria
 * Relación: pertenece a Usuario y a Sesion (el contexto del evento).
 * No consulta la base de datos.
 */
class LogAuditoria
{
    private ?int $idLog;
    private int $idUsuario;
    private int $idSesion;
    private string $accion;
    private string $tablaAfectada;
    private int $registroId;
    /** @var array|string|null */
    private $valoresAnteriores;
    /** @var array|string|null */
    private $valoresNuevos;
    private ?string $ipAddress;
    private ?string $userAgent;
    private DateTime $fecha;
    private ?Usuario $usuario;
    private ?Sesion $sesion;
    private ?string $usuarioNombre;

    public function __construct(
        int $idUsuario,
        int $idSesion,
        string $accion,
        string $tablaAfectada,
        int $registroId,
        $valoresAnteriores = null,
        $valoresNuevos = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ) {
        $this->idLog              = null;
        $this->idUsuario          = $idUsuario;
        $this->idSesion           = $idSesion;
        $this->accion             = $accion;
        $this->tablaAfectada      = $tablaAfectada;
        $this->registroId         = $registroId;
        $this->valoresAnteriores  = $valoresAnteriores;
        $this->valoresNuevos      = $valoresNuevos;
        $this->ipAddress          = $ipAddress;
        $this->userAgent          = $userAgent;
        $this->fecha              = new DateTime();
        $this->usuario            = null;
        $this->sesion             = null;
        $this->usuarioNombre      = null;
    }

    public static function fromArray(array $fila, ?Usuario $usuario = null, ?Sesion $sesion = null): self
    {
        $log = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();

        $log->idLog     = isset($fila['id_log']) ? (int) $fila['id_log'] : null;
        $log->idUsuario = (int) ($fila['id_usuario'] ?? 0);
        $log->idSesion  = (int) ($fila['id_sesiones'] ?? $fila['id_sesion'] ?? 0);
        $log->accion    = $fila['accion'] ?? '';
        $log->tablaAfectada = $fila['tabla_afectada'] ?? '';
        $log->registroId    = (int) ($fila['registro_id'] ?? 0);
        $log->valoresAnteriores = self::decodeJson($fila['valores_anteriores'] ?? null);
        $log->valoresNuevos     = self::decodeJson($fila['valores_nuevos'] ?? null);
        $log->ipAddress  = $fila['ip_address'] ?? null;
        $log->userAgent  = $fila['user_agent'] ?? null;
        $log->fecha      = !empty($fila['fecha']) ? new DateTime($fila['fecha']) : new DateTime();
        $log->usuario    = $usuario;
        $log->sesion     = $sesion;
        $log->usuarioNombre = $fila['usuario_nombre'] ?? ($usuario ? $usuario->getNombre() : null);

        return $log;
    }

    /**
     * Filtra una colección YA cargada por tabla. No toca la BD.
     *
     * @param LogAuditoria[] $logs
     * @return LogAuditoria[]
     */
    public static function scopePorTabla(array $logs, string $tabla): array
    {
        $tabla = strtolower($tabla);
        return array_values(array_filter(
            $logs,
            static function (LogAuditoria $log) use ($tabla) {
                return strtolower($log->getTablaAfectada()) === $tabla;
            }
        ));
    }

    /**
     * Filtra una colección YA cargada por usuario. No toca la BD.
     *
     * @param LogAuditoria[] $logs
     * @return LogAuditoria[]
     */
    public static function scopePorUsuario(array $logs, int $idUsuario): array
    {
        return array_values(array_filter(
            $logs,
            static function (LogAuditoria $log) use ($idUsuario) {
                return $log->getIdUsuario() === $idUsuario;
            }
        ));
    }

    private static function decodeJson($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_array($valor)) {
            return $valor;
        }
        $decoded = json_decode((string) $valor, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $valor;
    }

    public function getIdLog(): ?int
    {
        return $this->idLog;
    }

    public function assignId(int $idLog): void
    {
        $this->idLog = $idLog;
    }

    public function getIdUsuario(): int
    {
        return $this->idUsuario;
    }

    public function getIdSesion(): int
    {
        return $this->idSesion;
    }

    public function getAccion(): string
    {
        return $this->accion;
    }

    public function getTablaAfectada(): string
    {
        return $this->tablaAfectada;
    }

    public function getRegistroId(): int
    {
        return $this->registroId;
    }

    public function getValoresAnteriores()
    {
        return $this->valoresAnteriores;
    }

    public function getValoresNuevos()
    {
        return $this->valoresNuevos;
    }

    public function getValoresAnterioresJson(): ?string
    {
        if ($this->valoresAnteriores === null) {
            return null;
        }
        return is_string($this->valoresAnteriores)
            ? $this->valoresAnteriores
            : json_encode($this->valoresAnteriores, JSON_UNESCAPED_UNICODE);
    }

    public function getValoresNuevosJson(): ?string
    {
        if ($this->valoresNuevos === null) {
            return null;
        }
        return is_string($this->valoresNuevos)
            ? $this->valoresNuevos
            : json_encode($this->valoresNuevos, JSON_UNESCAPED_UNICODE);
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getFecha(): DateTime
    {
        return $this->fecha;
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

    public function getSesion(): ?Sesion
    {
        return $this->sesion;
    }

    public function setSesion(Sesion $sesion): void
    {
        $this->sesion   = $sesion;
        $this->idSesion = (int) $sesion->getIdSesion();
    }

    public function toArray(): array
    {
        return [
            'id_log'              => $this->idLog,
            'id_usuario'          => $this->idUsuario,
            'usuario_nombre'      => $this->usuarioNombre ?? ($this->usuario ? $this->usuario->getNombre() : null),
            'id_sesion'           => $this->idSesion,
            'accion'              => $this->accion,
            'tabla_afectada'      => $this->tablaAfectada,
            'registro_id'         => $this->registroId,
            'valores_anteriores'  => $this->valoresAnteriores,
            'valores_nuevos'      => $this->valoresNuevos,
            'ip_address'          => $this->ipAddress,
            'user_agent'          => $this->userAgent,
            'fecha'               => $this->fecha->format('Y-m-d H:i:s'),
        ];
    }
}

if (!class_exists('LogActoria')) {
    class_alias('LogAuditoria', 'LogActoria');
}
