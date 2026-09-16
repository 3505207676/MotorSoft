<?php

require_once __DIR__ . '/Conversacion.php';

/**
 * Mensaje de un hilo. Tabla Mensajes. Sin acceso a BD.
 */
class Mensaje
{
    public const TIPO_CLIENTE   = 'Cliente';
    public const TIPO_USUARIO   = 'Usuario';
    public const TIPO_PROVEEDOR = 'Proveedor';
    public const TIPO_SISTEMA   = 'Sistema';
    public const ENVIADO        = 'Enviado';
    public const LEIDO          = 'Leido';
    public const CANAL_INTERNO  = 'Interno';
    public const CANAL_WHATSAPP = 'WhatsApp';

    private ?int $idMensaje;
    private int $emisorId;
    private int $receptorId;
    private string $tipoEmisor;
    private string $contenido;
    private string $estado;
    private DateTime $fechaHora;
    private ?DateTime $updatedAt;
    private ?DateTime $deletedAt;
    private ?string $emisorNombre;
    private ?string $receptorNombre;
    private ?int $idConversacion;
    private string $canal;
    private ?string $idExterno;
    private ?string $tipoContacto;
    private ?int $idContacto;

    private ?int $idAdjunto = null;
    private ?array $adjunto = null;

    public function __construct(
        int $emisorId,
        int $receptorId,
        string $tipoEmisor,
        string $contenido,
        ?int $idConversacion = null,
        string $canal = self::CANAL_INTERNO,
        ?string $idExterno = null
    ) {
        $this->idMensaje       = null;
        $this->emisorId        = $emisorId;
        $this->receptorId      = $receptorId;
        $this->tipoEmisor      = $tipoEmisor;
        $this->contenido       = $contenido;
        $this->estado          = self::ENVIADO;
        $this->fechaHora       = new DateTime();
        $this->updatedAt       = null;
        $this->deletedAt       = null;
        $this->emisorNombre    = null;
        $this->receptorNombre  = null;
        $this->idConversacion  = $idConversacion;
        $this->canal           = $canal;
        $this->idExterno       = $idExterno;
        $this->tipoContacto    = null;
        $this->idContacto      = null;
        $this->idAdjunto       = null;
        $this->adjunto         = null;
    }

    public static function fromArray(array $fila): self
    {
        $m = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $m->idMensaje      = isset($fila['id_mensaje']) ? (int) $fila['id_mensaje'] : null;
        $m->emisorId       = (int) ($fila['emisor_id'] ?? 0);
        $m->receptorId     = (int) ($fila['receptor_id'] ?? 0);
        $m->tipoEmisor     = $fila['tipo_emisor'] ?? self::TIPO_USUARIO;
        $m->contenido      = (string) ($fila['contenido'] ?? '');
        $m->estado         = $fila['estado'] ?? self::ENVIADO;
        $m->fechaHora      = !empty($fila['fecha_hora']) ? new DateTime($fila['fecha_hora']) : new DateTime();
        $m->updatedAt      = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        $m->deletedAt      = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        $m->emisorNombre   = $fila['emisor_nombre'] ?? null;
        $m->receptorNombre = $fila['receptor_nombre'] ?? null;
        $m->idConversacion = isset($fila['id_conversacion']) && $fila['id_conversacion'] !== ''
            ? (int) $fila['id_conversacion']
            : null;
        $m->canal          = $fila['canal'] ?? self::CANAL_INTERNO;
        $m->idExterno      = $fila['id_externo'] ?? null;
        $m->tipoContacto   = $fila['tipo_contacto'] ?? null;
        $m->idContacto     = isset($fila['id_contacto']) ? (int) $fila['id_contacto'] : null;
        $m->idAdjunto      = isset($fila['id_adjunto']) && $fila['id_adjunto'] !== ''
            ? (int) $fila['id_adjunto']
            : null;
        if (!empty($fila['adjunto_nombre']) || !empty($fila['id_adjunto'])) {
            $m->adjunto = [
                'id_adjunto'      => $m->idAdjunto,
                'nombre_original' => $fila['adjunto_nombre'] ?? null,
                'extension'       => $fila['adjunto_ext'] ?? null,
                'es_imagen'       => in_array(strtolower((string) ($fila['adjunto_ext'] ?? '')), ['jpg', 'jpeg', 'png', 'gif'], true),
            ];
        }
        return $m;
    }

    public function assignId(int $id): void { $this->idMensaje = $id; }
    public function getIdMensaje(): ?int { return $this->idMensaje; }
    public function getEmisorId(): int { return $this->emisorId; }
    public function getReceptorId(): int { return $this->receptorId; }
    public function getTipoEmisor(): string { return $this->tipoEmisor; }
    public function getContenido(): string { return $this->contenido; }
    public function getEstado(): string { return $this->estado; }
    public function setEstado(string $e): void { $this->estado = $e; }
    public function getFechaHora(): DateTime { return $this->fechaHora; }
    public function getIdConversacion(): ?int { return $this->idConversacion; }
    public function setIdConversacion(?int $id): void { $this->idConversacion = $id; }
    public function getCanal(): string { return $this->canal; }
    public function getIdExterno(): ?string { return $this->idExterno; }
    public function setIdExterno(?string $id): void { $this->idExterno = $id; }
    public function getTipoContacto(): ?string { return $this->tipoContacto; }
    public function getIdContacto(): ?int { return $this->idContacto; }

    public function getIdAdjunto(): ?int { return $this->idAdjunto; }
    public function setIdAdjunto(?int $id): void { $this->idAdjunto = $id; }
    public function getAdjunto(): ?array { return $this->adjunto; }

    public function esDeCliente(): bool
    {
        return strcasecmp($this->tipoEmisor, self::TIPO_CLIENTE) === 0;
    }

    public function esMioStaff(int $idUsuario): bool
    {
        return strcasecmp($this->tipoEmisor, self::TIPO_USUARIO) === 0 && $this->emisorId === $idUsuario;
    }

    public function idCliente(): int
    {
        if ($this->tipoContacto && strcasecmp($this->tipoContacto, Conversacion::TIPO_CLIENTE) === 0) {
            return (int) $this->idContacto;
        }
        if ($this->esDeCliente()) {
            return $this->emisorId;
        }
        if (strcasecmp($this->tipoEmisor, self::TIPO_USUARIO) === 0 && (!$this->tipoContacto || strcasecmp($this->tipoContacto, Conversacion::TIPO_CLIENTE) === 0)) {
            return $this->receptorId;
        }
        return 0;
    }

    public function idUsuario(): int
    {
        return $this->esDeCliente() ? $this->receptorId : $this->emisorId;
    }

    public function marcarLeido(): void
    {
        $this->estado = self::LEIDO;
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id_mensaje'       => $this->idMensaje,
            'id_conversacion'  => $this->idConversacion,
            'emisor_id'        => $this->emisorId,
            'receptor_id'      => $this->receptorId,
            'tipo_emisor'      => $this->tipoEmisor,
            'contenido'        => $this->contenido,
            'estado'           => $this->estado,
            'fecha_hora'       => $this->fechaHora->format('Y-m-d H:i:s'),
            'emisor_nombre'    => $this->emisorNombre,
            'receptor_nombre'  => $this->receptorNombre,
            'id_cliente'       => $this->idCliente(),
            'id_usuario'       => $this->idUsuario(),
            'canal'            => $this->canal,
            'id_externo'       => $this->idExterno,
            'tipo_contacto'    => $this->tipoContacto,
            'id_contacto'      => $this->idContacto,
            'id_adjunto'       => $this->idAdjunto,
            'adjunto'          => $this->adjunto,
            'mio'              => false,
        ];
    }
}
