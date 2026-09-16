<?php

/**
 * Hilo de chat. Un mismo contacto puede tener un hilo Interno y otro WhatsApp.
 * Tabla: Conversaciones.
 */
class Conversacion
{
    public const CANAL_INTERNO   = 'Interno';
    public const CANAL_WHATSAPP  = 'WhatsApp';

    public const TIPO_CLIENTE    = 'Cliente';
    public const TIPO_USUARIO    = 'Usuario';
    public const TIPO_PROVEEDOR  = 'Proveedor';

    private ?int $idConversacion;
    private string $canal;
    private string $tipoContacto;
    private int $idContacto;
    /** Segundo participante en chats Usuario-Usuario. 0 en Cliente/Proveedor. */
    private int $idPar;
    private ?string $telefono;
    private ?string $titulo;
    private ?DateTime $ultimoMensajeAt;
    private int $createdBy;
    private DateTime $createdAt;
    private ?DateTime $deletedAt;

    public function __construct(string $canal, string $tipoContacto, int $idContacto, int $createdBy, ?string $telefono = null, ?string $titulo = null, int $idPar = 0)
    {
        $this->idConversacion  = null;
        $this->canal           = $canal;
        $this->tipoContacto    = $tipoContacto;
        $this->idContacto      = $idContacto;
        $this->idPar           = $idPar;
        $this->telefono        = $telefono;
        $this->titulo          = $titulo;
        $this->ultimoMensajeAt = null;
        $this->createdBy       = $createdBy;
        $this->createdAt       = new DateTime();
        $this->deletedAt       = null;
    }

    public static function fromArray(array $fila): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idConversacion  = isset($fila['id_conversacion']) ? (int) $fila['id_conversacion'] : null;
        $c->canal           = $fila['canal'] ?? self::CANAL_INTERNO;
        $c->tipoContacto    = $fila['tipo_contacto'] ?? self::TIPO_CLIENTE;
        $c->idContacto      = (int) ($fila['id_contacto'] ?? 0);
        $c->idPar           = (int) ($fila['id_par'] ?? 0);
        $c->telefono        = $fila['telefono'] ?? null;
        $c->titulo          = $fila['titulo'] ?? null;
        $c->ultimoMensajeAt = !empty($fila['ultimo_mensaje_at']) ? new DateTime($fila['ultimo_mensaje_at']) : null;
        $c->createdBy       = (int) ($fila['created_by'] ?? 0);
        $c->createdAt       = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->deletedAt       = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        return $c;
    }

    public static function canales(): array
    {
        return [self::CANAL_INTERNO, self::CANAL_WHATSAPP];
    }

    public static function tiposContacto(): array
    {
        return [self::TIPO_CLIENTE, self::TIPO_USUARIO, self::TIPO_PROVEEDOR];
    }

    /**
     * Identidad canónica de un chat entre dos usuarios: (menor, mayor).
     * @return array{0:int,1:int}
     */
    public static function parUsuarios(int $a, int $b): array
    {
        if ($a < 1 || $b < 1 || $a === $b) {
            throw new AppException('Seleccione otro usuario del taller', HTTP_BAD_REQUEST);
        }
        return $a < $b ? [$a, $b] : [$b, $a];
    }

    public function assignId(int $id): void { $this->idConversacion = $id; }
    public function getIdConversacion(): ?int { return $this->idConversacion; }
    public function getCanal(): string { return $this->canal; }
    public function getTipoContacto(): string { return $this->tipoContacto; }
    public function getIdContacto(): int { return $this->idContacto; }
    public function getIdPar(): int { return $this->idPar; }
    public function setIdPar(int $id): void { $this->idPar = $id; }

    public function idOtroUsuario(int $idYo): int
    {
        if (strcasecmp($this->tipoContacto, self::TIPO_USUARIO) !== 0) {
            return $this->idContacto;
        }
        if ($this->idContacto === $idYo) {
            return $this->idPar;
        }
        return $this->idContacto;
    }
    public function getTelefono(): ?string { return $this->telefono; }
    public function setTelefono(?string $telefono): void { $this->telefono = $telefono; }
    public function getTitulo(): ?string { return $this->titulo; }
    public function setTitulo(?string $titulo): void { $this->titulo = $titulo; }
    public function getUltimoMensajeAt(): ?DateTime { return $this->ultimoMensajeAt; }
    public function setUltimoMensajeAt(?DateTime $at): void { $this->ultimoMensajeAt = $at; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    public function esCliente(): bool
    {
        return strcasecmp($this->tipoContacto, self::TIPO_CLIENTE) === 0;
    }

    public function toArray(): array
    {
        return [
            'id_conversacion'   => $this->idConversacion,
            'canal'             => $this->canal,
            'tipo_contacto'     => $this->tipoContacto,
            'id_contacto'       => $this->idContacto,
            'id_par'            => $this->idPar,
            'id_cliente'        => $this->esCliente() ? $this->idContacto : 0,
            'telefono'          => $this->telefono,
            'titulo'            => $this->titulo,
            'ultimo_mensaje_at' => $this->ultimoMensajeAt ? $this->ultimoMensajeAt->format('Y-m-d H:i:s') : null,
        ];
    }
}
