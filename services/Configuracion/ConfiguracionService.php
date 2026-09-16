<?php

require_once __DIR__ . '/../../models/Configuracion/Configuracion.php';
require_once __DIR__ . '/../../repositories/Configuracion/ConfiguracionRepository.php';
require_once __DIR__ . '/../Seguridad/AuthService.php';

class ConfiguracionService
{
    private ConfiguracionRepository $repo;
    private AuthService $auth;

    public function __construct(ConfiguracionRepository $repo, AuthService $auth)
    {
        $this->repo = $repo;
        $this->auth = $auth;
        $this->repo->asegurarTabla();
    }

    public function listar(): array
    {
        $this->sembrarSiVacio(1);
        $this->asegurarClaves(1);
        return $this->repo->listar();
    }

    public function mapa(): array
    {
        $this->asegurarClaves(1);
        $out = [];
        foreach ($this->repo->listar() as $item) {
            $out[$item->getClave()] = $item->valorNativo();
        }
        return array_merge($this->valoresPorDefecto(), $out);
    }

    public function mapaPublico(): array
    {
        $mapa = $this->mapa();
        foreach (Configuracion::clavesSecretas() as $clave) {
            $mapa[$clave . '_set'] = trim((string) ($mapa[$clave] ?? '')) !== '';
            $mapa[$clave] = '';
        }
        $mapa['empresa_logo_set'] = trim((string) ($mapa[Configuracion::EMPRESA_LOGO] ?? '')) !== '';
        $mapa[Configuracion::EMPRESA_LOGO] = '';
        return $mapa;
    }

    /** Datos que el taller usa al facturar, sin el panel de configuración. */
    public function mapaOperativo(): array
    {
        $mapa = $this->mapa();
        $claves = [
            Configuracion::IVA_ACTIVO,
            Configuracion::IVA_PORCENTAJE,
            Configuracion::EMPRESA_NOMBRE,
            Configuracion::EMPRESA_NIT,
            Configuracion::EMPRESA_DIRECCION,
            Configuracion::EMPRESA_TELEFONO,
            Configuracion::EMPRESA_LOGO,
            Configuracion::FACTURA_PREFIJO,
            Configuracion::MONEDA,
        ];
        $out = [];
        foreach ($claves as $clave) {
            $out[$clave] = $mapa[$clave] ?? null;
        }
        return $out;
    }

    public function secretoDefinido(string $clave): bool
    {
        $item = $this->repo->buscarPorClave($clave);
        return $item !== null && trim($item->getValor()) !== '';
    }

    public function whatsappTokenDefinido(): bool
    {
        return $this->secretoDefinido(Configuracion::WHATSAPP_TOKEN);
    }

    public function ivaActivo(): bool
    {
        $mapa = $this->mapa();
        return !empty($mapa[Configuracion::IVA_ACTIVO]);
    }

    public function ivaPorcentaje(): float
    {
        $mapa = $this->mapa();
        $pct = (float) ($mapa[Configuracion::IVA_PORCENTAJE] ?? 19);
        return max(0, min(100, $pct));
    }

    public function prefijoFactura(): string
    {
        $mapa = $this->mapa();
        $prefijo = trim((string) ($mapa[Configuracion::FACTURA_PREFIJO] ?? 'FAC'));
        return $prefijo !== '' ? $prefijo : 'FAC';
    }

    public function guardarLogo(string $rutaRelativa, int $actorId): string
    {
        $this->sembrarSiVacio($actorId);
        $this->asegurarClaves($actorId);
        $item = $this->repo->buscarPorClave(Configuracion::EMPRESA_LOGO);
        if (!$item) {
            throw new AppException('No se pudo guardar el logo', HTTP_INTERNAL_ERROR);
        }
        $anterior = trim($item->getValor());
        $item->setValor($rutaRelativa, $actorId);
        $this->repo->guardar($item);
        $this->auth->registrarLog([
            'id_usuario'    => $actorId,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Configuracion',
            'registroId'    => 0,
            'valoresNuevos' => ['empresa_logo' => 'actualizado'],
        ]);
        return $anterior;
    }

    public function quitarLogo(int $actorId): string
    {
        return $this->guardarLogo('', $actorId);
    }

    public function rutaLogo(): string
    {
        $mapa = $this->mapa();
        return trim((string) ($mapa[Configuracion::EMPRESA_LOGO] ?? ''));
    }

    /** Logo subido o el sello empaquetado de Taller El Paisa. */
    public function archivoLogo(): string
    {
        $rel = $this->rutaLogo();
        if ($rel !== '') {
            require_once __DIR__ . '/../Archivos/ArchivoService.php';
            $abs = (new ArchivoService())->absoluto($rel);
            if (is_file($abs)) {
                return $abs;
            }
        }
        $dir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        foreach (['logo-taller.jpg', 'logo-taller.png'] as $archivo) {
            $ruta = $dir . $archivo;
            if (is_file($ruta)) {
                return $ruta;
            }
        }
        return '';
    }
    public function calcularTotales(float $subtotal): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $activo   = $this->ivaActivo();
        $pct      = $this->ivaPorcentaje();
        $iva      = $activo ? round($subtotal * ($pct / 100), 2) : 0.0;
        return [
            'subtotal'        => $subtotal,
            'iva'             => $iva,
            'total'           => round($subtotal + $iva, 2),
            'iva_activo'      => $activo,
            'iva_porcentaje'  => $pct,
        ];
    }

    public function actualizar(array $valores, int $actorId): array
    {
        $this->sembrarSiVacio($actorId);
        $permitidas = array_keys($this->valoresPorDefecto());
        foreach ($valores as $clave => $valor) {
            if (!in_array($clave, $permitidas, true)) {
                continue;
            }
            $item = $this->repo->buscarPorClave($clave);
            if (!$item) {
                continue;
            }
            if ($clave === Configuracion::EMPRESA_LOGO) {
                continue;
            }
            if (in_array($clave, Configuracion::clavesSecretas(), true)) {
                $valor = trim((string) $valor);
                if ($valor === '' || str_starts_with($valor, '*') || str_starts_with($valor, '•')) {
                    continue;
                }
            }
            if ($item->getTipo() === 'bool') {
                $valor = (!empty($valor) && $valor !== '0' && $valor !== 'false') ? '1' : '0';
            } elseif ($item->getTipo() === 'number') {
                $valor = (string) (is_numeric($valor) ? $valor : 0);
            } else {
                $valor = trim((string) $valor);
            }
            $item->setValor($valor, $actorId);
            $this->repo->guardar($item);
        }
        $auditoria = $valores;
        foreach (Configuracion::clavesSecretas() as $secreto) {
            if (array_key_exists($secreto, $auditoria)) {
                $auditoria[$secreto] = trim((string) $auditoria[$secreto]) !== '' ? '***' : '';
            }
        }
        $this->auth->registrarLog([
            'id_usuario'    => $actorId,
            'accion'        => 'UPDATE',
            'tablaAfectada' => 'Configuracion',
            'registroId'    => 0,
            'valoresNuevos' => $auditoria,
        ]);
        return $this->mapa();
    }

    public function sembrarSiVacio(int $createdBy): void
    {
        if ($this->repo->listar()) {
            $this->asegurarClaves($createdBy);
            return;
        }
        foreach ($this->definiciones() as $def) {
            $item = new Configuracion($def['clave'], $def['valor'], $createdBy, $def['tipo'], $def['descripcion']);
            $this->repo->guardar($item);
        }
    }

    public function asegurarClaves(int $createdBy): void
    {
        $existentes = [];
        foreach ($this->repo->listar() as $item) {
            $existentes[$item->getClave()] = true;
        }
        foreach ($this->definiciones() as $def) {
            if (isset($existentes[$def['clave']])) {
                continue;
            }
            $item = new Configuracion($def['clave'], $def['valor'], $createdBy, $def['tipo'], $def['descripcion']);
            $this->repo->guardar($item);
        }
    }

    private function valoresPorDefecto(): array
    {
        $out = [];
        foreach ($this->definiciones() as $def) {
            $tmp = new Configuracion($def['clave'], $def['valor'], 0, $def['tipo']);
            $out[$def['clave']] = $tmp->valorNativo();
        }
        return $out;
    }

    private function definiciones(): array
    {
        return [
            ['clave' => Configuracion::IVA_ACTIVO, 'valor' => '1', 'tipo' => 'bool', 'descripcion' => 'Cobrar IVA en facturas y ventas'],
            ['clave' => Configuracion::IVA_PORCENTAJE, 'valor' => '19', 'tipo' => 'number', 'descripcion' => 'Porcentaje de IVA'],
            ['clave' => Configuracion::EMPRESA_NOMBRE, 'valor' => 'Taller El Paisa', 'tipo' => 'string', 'descripcion' => 'Razón social en la factura'],
            ['clave' => Configuracion::EMPRESA_NIT, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'NIT / documento de la empresa'],
            ['clave' => Configuracion::EMPRESA_DIRECCION, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Dirección del taller'],
            ['clave' => Configuracion::EMPRESA_TELEFONO, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Teléfono de contacto'],
            ['clave' => Configuracion::EMPRESA_LOGO, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Logo extra para el PDF; si está vacío se usa el sello del taller'],
            ['clave' => Configuracion::FACTURA_PREFIJO, 'valor' => 'FAC', 'tipo' => 'string', 'descripcion' => 'Prefijo del número de factura'],
            ['clave' => Configuracion::MONEDA, 'valor' => 'COP', 'tipo' => 'string', 'descripcion' => 'Moneda de facturación'],
            ['clave' => Configuracion::WHATSAPP_ACTIVO, 'valor' => '0', 'tipo' => 'bool', 'descripcion' => 'Enviar y recibir por WhatsApp Cloud API'],
            ['clave' => Configuracion::WHATSAPP_TOKEN, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Token permanente de Meta (WhatsApp Cloud API)'],
            ['clave' => Configuracion::WHATSAPP_PHONE_ID, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Phone Number ID de WhatsApp'],
            ['clave' => Configuracion::WHATSAPP_WABA_ID, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'WhatsApp Business Account ID (opcional)'],
            ['clave' => Configuracion::WHATSAPP_VERIFY_TOKEN, 'valor' => 'motorsoft-wa', 'tipo' => 'string', 'descripcion' => 'Token de verificación del webhook de Meta'],
            ['clave' => Configuracion::CORREO_ACTIVO, 'valor' => '0', 'tipo' => 'bool', 'descripcion' => 'Enviar invitaciones y recuperación por SMTP'],
            ['clave' => Configuracion::CORREO_HOST, 'valor' => 'smtp.gmail.com', 'tipo' => 'string', 'descripcion' => 'Servidor SMTP'],
            ['clave' => Configuracion::CORREO_PUERTO, 'valor' => '587', 'tipo' => 'number', 'descripcion' => 'Puerto SMTP (587 TLS o 465 SSL)'],
            ['clave' => Configuracion::CORREO_USUARIO, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Usuario SMTP'],
            ['clave' => Configuracion::CORREO_PASSWORD, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Contraseña o clave de aplicación SMTP'],
            ['clave' => Configuracion::CORREO_CIFRADO, 'valor' => 'tls', 'tipo' => 'string', 'descripcion' => 'Cifrado SMTP: tls, ssl o none'],
            ['clave' => Configuracion::CORREO_REMITENTE, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'Correo remitente visible'],
            ['clave' => Configuracion::CORREO_REMITENTE_NOM, 'valor' => 'Taller El Paisa', 'tipo' => 'string', 'descripcion' => 'Nombre del remitente'],
            ['clave' => Configuracion::APP_URL_PUBLICA, 'valor' => '', 'tipo' => 'string', 'descripcion' => 'URL pública de la app para enlaces de correo'],
        ];
    }
}
