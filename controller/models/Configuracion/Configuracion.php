<?php

/**
 * Ajuste del taller (IVA, datos fiscales, prefijo de factura). Tabla: Configuracion.
 */
class Configuracion
{
    public const IVA_ACTIVO        = 'iva_activo';
    public const IVA_PORCENTAJE    = 'iva_porcentaje';
    public const EMPRESA_NOMBRE    = 'empresa_nombre';
    public const EMPRESA_NIT       = 'empresa_nit';
    public const EMPRESA_DIRECCION = 'empresa_direccion';
        public const EMPRESA_TELEFONO  = 'empresa_telefono';
    public const EMPRESA_LOGO      = 'empresa_logo';
    public const FACTURA_PREFIJO   = 'factura_prefijo';
    public const MONEDA            = 'moneda';
    public const WHATSAPP_ACTIVO       = 'whatsapp_activo';
    public const WHATSAPP_TOKEN        = 'whatsapp_token';
    public const WHATSAPP_PHONE_ID     = 'whatsapp_phone_id';
    public const WHATSAPP_WABA_ID      = 'whatsapp_waba_id';
    public const WHATSAPP_VERIFY_TOKEN = 'whatsapp_verify_token';
    public const CORREO_ACTIVO         = 'correo_activo';
    public const CORREO_HOST           = 'correo_host';
    public const CORREO_PUERTO         = 'correo_puerto';
    public const CORREO_USUARIO        = 'correo_usuario';
    public const CORREO_PASSWORD       = 'correo_password';
    public const CORREO_CIFRADO        = 'correo_cifrado';
    public const CORREO_REMITENTE      = 'correo_remitente';
    public const CORREO_REMITENTE_NOM  = 'correo_remitente_nombre';
    public const APP_URL_PUBLICA       = 'app_url_publica';

    /** @return string[] */
    public static function clavesSecretas(): array
    {
        return [self::WHATSAPP_TOKEN, self::CORREO_PASSWORD];
    }

    private ?int $idConfig;
    private string $clave;
    private string $valor;
    private string $tipo;
    private string $descripcion;
    private int $createdBy;
    private ?int $updatedBy;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(string $clave, string $valor, int $createdBy, string $tipo = 'string', string $descripcion = '')
    {
        $this->idConfig    = null;
        $this->clave       = trim($clave);
        $this->valor       = $valor;
        $this->tipo        = $tipo;
        $this->descripcion = $descripcion;
        $this->createdBy   = $createdBy;
        $this->updatedBy   = null;
        $this->createdAt   = new DateTime();
        $this->updatedAt   = null;
    }

    public static function fromArray(array $fila): self
    {
        $c = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
        $c->idConfig    = isset($fila['id_config']) ? (int) $fila['id_config'] : null;
        $c->clave       = $fila['clave'] ?? '';
        $c->valor       = (string) ($fila['valor'] ?? '');
        $c->tipo        = $fila['tipo'] ?? 'string';
        $c->descripcion = $fila['descripcion'] ?? '';
        $c->createdBy   = (int) ($fila['created_by'] ?? 0);
        $c->updatedBy   = isset($fila['updated_by']) ? (int) $fila['updated_by'] : null;
        $c->createdAt   = !empty($fila['created_at']) ? new DateTime($fila['created_at']) : new DateTime();
        $c->updatedAt   = !empty($fila['updated_at']) ? new DateTime($fila['updated_at']) : null;
        return $c;
    }

    public function valorNativo()
    {
        if ($this->tipo === 'bool') {
            return $this->valor === '1' || strtolower($this->valor) === 'true';
        }
        if ($this->tipo === 'number') {
            return is_numeric($this->valor) ? (float) $this->valor : 0.0;
        }
        return $this->valor;
    }

    public function setValor(string $valor, ?int $updatedBy = null): void
    {
        $this->valor      = $valor;
        $this->updatedAt  = new DateTime();
        if ($updatedBy !== null) {
            $this->updatedBy = $updatedBy;
        }
    }

    public function getIdConfig(): ?int { return $this->idConfig; }
    public function assignId(int $id): void { $this->idConfig = $id; }
    public function getClave(): string { return $this->clave; }
    public function getValor(): string { return $this->valor; }
    public function getTipo(): string { return $this->tipo; }
    public function getDescripcion(): string { return $this->descripcion; }
    public function getCreatedBy(): int { return $this->createdBy; }
    public function getUpdatedBy(): ?int { return $this->updatedBy; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }
    public function getUpdatedAt(): ?DateTime { return $this->updatedAt; }

    public function toArray(): array
    {
        return [
            'id_config'   => $this->idConfig,
            'clave'       => $this->clave,
            'valor'       => $this->valor,
            'valor_nativo'=> $this->valorNativo(),
            'tipo'        => $this->tipo,
            'descripcion' => $this->descripcion,
        ];
    }
}
