<?php

class Adjunto
{
    public const CLIENTE      = 'cliente';
    public const CONVERSACION = 'conversacion';
    public const MENSAJE      = 'mensaje';
    public const ORDEN        = 'orden';
    public const FACTURA      = 'factura';

    public const TIPOS = [
        self::CLIENTE,
        self::CONVERSACION,
        self::MENSAJE,
        self::ORDEN,
        self::FACTURA,
    ];

    private ?int $idAdjunto;
    private string $entidadTipo;
    private int $entidadId;
    private ?int $idUsuario;
    private ?int $idCliente;
    private ?string $nombreOriginal;
    private string $nombreSistema;
    private string $ruta;
    private string $extension;
    private int $pesoBytes;
    private DateTime $fechaSubida;
    private ?DateTime $deletedAt;

    public static function crear(
        string $tipo,
        int $entidadId,
        array $archivo,
        ?int $idUsuario,
        ?int $idCliente
    ): self {
        $a = new self();
        $a->idAdjunto      = null;
        $a->entidadTipo    = $tipo;
        $a->entidadId      = $entidadId;
        $a->idUsuario      = $idUsuario;
        $a->idCliente      = $idCliente;
        $a->nombreOriginal = (string) ($archivo['nombre_original'] ?? 'archivo');
        $a->nombreSistema  = (string) ($archivo['nombre_sistema'] ?? '');
        $a->ruta           = (string) ($archivo['ruta'] ?? '');
        $a->extension      = (string) ($archivo['extension'] ?? '');
        $a->pesoBytes      = (int) ($archivo['peso_bytes'] ?? 0);
        $a->fechaSubida    = new DateTime();
        $a->deletedAt      = null;
        return $a;
    }

    public static function fromArray(array $fila): self
    {
        $a = new self();
        $a->idAdjunto       = isset($fila['id_adjunto']) ? (int) $fila['id_adjunto'] : null;
        $a->entidadTipo     = (string) ($fila['entidad_tipo'] ?? '');
        $a->entidadId       = (int) ($fila['entidad_id'] ?? 0);
        $a->idUsuario       = isset($fila['id_usuario']) && $fila['id_usuario'] !== '' ? (int) $fila['id_usuario'] : null;
        $a->idCliente       = isset($fila['id_cliente']) && $fila['id_cliente'] !== '' ? (int) $fila['id_cliente'] : null;
        $a->nombreOriginal  = $fila['nombre_original'] ?? null;
        $a->nombreSistema   = (string) ($fila['nombre_sistema'] ?? '');
        $a->ruta            = (string) ($fila['ruta'] ?? '');
        $a->extension       = (string) ($fila['extension'] ?? '');
        $a->pesoBytes       = (int) ($fila['peso_bytes'] ?? 0);
        $a->fechaSubida     = !empty($fila['fecha_subida']) ? new DateTime($fila['fecha_subida']) : new DateTime();
        $a->deletedAt       = !empty($fila['deleted_at']) ? new DateTime($fila['deleted_at']) : null;
        return $a;
    }

    public function assignId(int $id): void
    {
        $this->idAdjunto = $id;
    }

    public function getIdAdjunto(): ?int { return $this->idAdjunto; }
    public function getEntidadTipo(): string { return $this->entidadTipo; }
    public function getEntidadId(): int { return $this->entidadId; }
    public function getIdUsuario(): ?int { return $this->idUsuario; }
    public function getIdCliente(): ?int { return $this->idCliente; }
    public function getNombreOriginal(): string { return (string) $this->nombreOriginal; }
    public function getNombreSistema(): string { return $this->nombreSistema; }
    public function getRuta(): string { return $this->ruta; }
    public function getExtension(): string { return $this->extension; }
    public function getPesoBytes(): int { return $this->pesoBytes; }
    public function getFechaSubida(): DateTime { return $this->fechaSubida; }
    public function getDeletedAt(): ?DateTime { return $this->deletedAt; }

    public function setEntidad(string $tipo, int $id): void
    {
        $this->entidadTipo = $tipo;
        $this->entidadId = $id;
    }

    public function marcarEliminado(): void
    {
        $this->deletedAt = new DateTime();
    }

    public function esImagen(): bool
    {
        return in_array(strtolower($this->extension), ['jpg', 'jpeg', 'png', 'gif'], true);
    }

    public function toArray(): array
    {
        $id = (int) $this->idAdjunto;
        return [
            'id_adjunto'      => $this->idAdjunto,
            'entidad_tipo'    => $this->entidadTipo,
            'entidad_id'      => $this->entidadId,
            'nombre_original' => $this->nombreOriginal,
            'extension'       => $this->extension,
            'peso_bytes'      => $this->pesoBytes,
            'es_imagen'       => $this->esImagen(),
            'fecha_subida'    => $this->fechaSubida->format('Y-m-d H:i:s'),
            'url'             => $id > 0 ? '/controllers/api/adjuntos/ver.php?id=' . $id : null,
        ];
    }
}
