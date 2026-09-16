<?php

require_once __DIR__ . '/../../models/Configuracion/Configuracion.php';
require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../Configuracion/ConfiguracionService.php';
require_once __DIR__ . '/SmtpCliente.php';
require_once __DIR__ . '/BuzonCorreo.php';

class CorreoService
{
    private ConfiguracionService $config;
    private SmtpCliente $smtp;
    private BuzonCorreo $buzon;
    private ?array $ultimoEnvio = null;

    public function __construct(
        ConfiguracionService $config,
        ?SmtpCliente $smtp = null,
        ?BuzonCorreo $buzon = null
    ) {
        $this->config = $config;
        $this->smtp   = $smtp ?: new SmtpCliente();
        $this->buzon  = $buzon ?: new BuzonCorreo();
    }

    /** Puede entregar: SMTP si está listo, si no el buzón interno. */
    public function estaListo(): bool
    {
        return true;
    }

    public function smtpListo(): bool
    {
        $c = $this->credenciales();
        return $c['activo'] && $c['host'] !== '' && $c['remitente'] !== '' && $c['password'] !== '';
    }

    /**
     * @return array{
     *   ok:bool,configurado:bool,activo:bool,host:string,canal:string,
     *   smtp_listo:bool,url_sugerida:string,error:?string
     * }
     */
    public function diagnosticar(): array
    {
        $c = $this->credenciales();
        $smtp = $this->smtpListo();
        $out = [
            'ok'           => true,
            'configurado'  => $smtp,
            'activo'       => $c['activo'],
            'host'         => $c['host'],
            'canal'        => $smtp ? 'smtp' : 'local',
            'smtp_listo'   => $smtp,
            'url_sugerida' => $this->urlBaseSugerida(),
            'error'        => null,
        ];
        if ($smtp) {
            return $out;
        }
        if ($c['activo'] && ($c['host'] === '' || $c['remitente'] === '' || $c['password'] === '')) {
            $out['error'] = 'SMTP incompleto. Las invitaciones se guardan en el buzón interno hasta completar host, remitente y contraseña.';
            return $out;
        }
        $out['error'] = 'Sin SMTP. El taller guarda los correos en el buzón interno (Configuración) y puede copiar el enlace.';
        return $out;
    }

    /** @return array<string,mixed>|null */
    public function ultimoEnvio(): ?array
    {
        return $this->ultimoEnvio;
    }

    /** @return array<int,array<string,mixed>> */
    public function listarBuzon(int $limite = 20): array
    {
        return $this->buzon->listar($limite);
    }

    /** @return array<string,mixed>|null */
    public function leerBuzon(string $id): ?array
    {
        return $this->buzon->obtener($id);
    }

    public function enviarPrueba(string $destino): array
    {
        $para = strtolower(trim($destino));
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            throw new AppException('Indique un correo de prueba válido', HTTP_BAD_REQUEST);
        }
        $taller = $this->nombreTaller();
        $html = $this->envolver(
            'Prueba de correo',
            '<p>La conexión de correo de <strong>' . $this->e($taller) . '</strong> funciona.</p>'
            . '<p>Ya puede enviar invitaciones y recuperación de contraseña'
            . ($this->smtpListo() ? ' por SMTP.' : ' desde el buzón interno (copie el enlace si hace falta).')
            . '</p>'
        );
        $envio = $this->entregar($para, 'Prueba de correo — ' . $taller, $html);
        return [
            'ok'       => true,
            'destino'  => $para,
            'canal'    => $envio['canal'],
            'id_buzon' => $envio['id'],
        ];
    }

    /**
     * @return array{ok:bool,destino:string,canal:string,url:string,id_buzon:string}
     */
    public function enviarEnlaceCuenta(Usuario $usuario, string $token, string $motivo, int $horas): array
    {
        $url = $this->urlActivacion($token);
        $taller = $this->nombreTaller();
        $nombre = $this->e($usuario->getNombre());
        $esRecupera = $motivo === 'recuperacion';
        $asunto = $esRecupera
            ? 'Restablecer contraseña — ' . $taller
            : 'Active su cuenta — ' . $taller;
        $intro = $esRecupera
            ? 'Recibimos una solicitud para restablecer su contraseña.'
            : 'Le crearon una cuenta de personal en el taller.';
        $cta = $esRecupera ? 'Restablecer contraseña' : 'Definir contraseña';
        $cuerpo = '<p>Hola <strong>' . $nombre . '</strong>,</p>'
            . '<p>' . $intro . '</p>'
            . '<p>El enlace vale <strong>' . (int) $horas . ' horas</strong>.</p>'
            . '<p style="margin:24px 0">'
            . '<a href="' . $this->e($url) . '" style="background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 18px;border-radius:6px;display:inline-block">'
            . $this->e($cta) . '</a></p>'
            . '<p style="word-break:break-all;font-size:12px;color:#64748b">' . $this->e($url) . '</p>'
            . '<p>Si no esperaba este mensaje, ignórelo. ' . $this->e($taller) . ' no envía la contraseña por correo.</p>';
        $envio = $this->entregar($usuario->getCorreo(), $asunto, $this->envolver($cta, $cuerpo));
        $this->ultimoEnvio = [
            'ok'       => true,
            'destino'  => $usuario->getCorreo(),
            'canal'    => $envio['canal'],
            'url'      => $url,
            'id_buzon' => $envio['id'],
        ];
        return $this->ultimoEnvio;
    }

    public function enviarAvisoContrato(Usuario $empleado, string $fechaIngreso): void
    {
        $taller = $this->nombreTaller();
        $cuerpo = '<p>Hola <strong>' . $this->e($empleado->getNombre()) . '</strong>,</p>'
            . '<p>El taller registró su contrato laboral con fecha de ingreso <strong>'
            . $this->e($fechaIngreso) . '</strong>.</p>'
            . '<p>Por privacidad no incluimos salario ni comisión en este correo. Al entrar al sistema podrá ver el detalle con su usuario.</p>';
        try {
            $this->entregar(
                $empleado->getCorreo(),
                'Contrato registrado — ' . $taller,
                $this->envolver('Contrato registrado', $cuerpo)
            );
        } catch (Throwable $e) {
            error_log('Correo contrato: ' . $e->getMessage());
        }
    }

    public function urlActivacion(string $token): string
    {
        return $this->urlPublica('views/auth/activar.php?token=' . rawurlencode($token));
    }

    public function urlPublica(string $ruta): string
    {
        return rtrim($this->urlBase(), '/') . '/' . ltrim($ruta, '/');
    }

    public function urlBaseSugerida(): string
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $root = preg_replace('#/controllers/api/.*$#', '', $script) ?? '';
        $root = preg_replace('#/views/.*$#', '', $root) ?? $root;
        return $scheme . '://' . $host . rtrim($root, '/');
    }

    private function urlBase(): string
    {
        $mapa = $this->config->mapa();
        $base = rtrim(trim((string) ($mapa[Configuracion::APP_URL_PUBLICA] ?? '')), '/');
        return $base !== '' ? $base : $this->urlBaseSugerida();
    }

    /**
     * @return array{id:string,canal:string}
     */
    private function entregar(string $para, string $asunto, string $html): array
    {
        $para = strtolower(trim($para));
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            throw new AppException('El destinatario de correo no es válido', HTTP_BAD_REQUEST);
        }
        if ($this->smtpListo()) {
            try {
                $this->smtp->enviar($this->credenciales(), $para, $asunto, $html);
                $meta = $this->buzon->guardar($para, $asunto, $html, 'smtp');
                $this->ultimoEnvio = ['canal' => 'smtp', 'id' => $meta['id'], 'destino' => $para];
                return ['id' => $meta['id'], 'canal' => 'smtp'];
            } catch (Throwable $e) {
                try {
                    $this->buzon->guardar($para, $asunto, $html, 'local', $e->getMessage());
                } catch (Throwable $ignored) {
                }
                throw $e instanceof AppException
                    ? $e
                    : new AppException('No se pudo enviar el correo: ' . $e->getMessage(), HTTP_INTERNAL_ERROR);
            }
        }
        $meta = $this->buzon->guardar($para, $asunto, $html, 'local');
        $this->ultimoEnvio = ['canal' => 'local', 'id' => $meta['id'], 'destino' => $para];
        return ['id' => $meta['id'], 'canal' => 'local'];
    }

    private function envolver(string $titulo, string $cuerpoHtml): string
    {
        $taller = $this->e($this->nombreTaller());
        $tituloE = $this->e($titulo);
        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>' . $tituloE . '</title></head>'
            . '<body style="margin:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;color:#1e293b">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:24px 12px">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="560" cellspacing="0" cellpadding="0" style="max-width:560px;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e2e8f0">'
            . '<tr><td style="background:#1d4ed8;color:#fff;padding:18px 24px;font-size:18px;font-weight:700">' . $taller . '</td></tr>'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.5">' . $cuerpoHtml . '</td></tr>'
            . '<tr><td style="padding:12px 24px 20px;font-size:12px;color:#64748b">Taller El Paisa · Cumaribo, Vichada<br>Este mensaje es interno de MotorSoft.</td></tr>'
            . '</table></td></tr></table></body></html>';
    }

    private function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    private function nombreTaller(): string
    {
        $mapa = $this->config->mapa();
        $n = trim((string) ($mapa[Configuracion::EMPRESA_NOMBRE] ?? ''));
        return $n !== '' ? $n : 'Taller El Paisa';
    }

    /** @return array<string,mixed> */
    private function credenciales(): array
    {
        $m = $this->config->mapa();
        $remitente = trim((string) ($m[Configuracion::CORREO_REMITENTE] ?? ''));
        $usuario = trim((string) ($m[Configuracion::CORREO_USUARIO] ?? ''));
        if ($remitente === '') {
            $remitente = $usuario;
        }
        return [
            'activo'            => !empty($m[Configuracion::CORREO_ACTIVO]),
            'host'              => trim((string) ($m[Configuracion::CORREO_HOST] ?? '')),
            'puerto'            => (int) ($m[Configuracion::CORREO_PUERTO] ?? 587),
            'usuario'           => $usuario,
            'password'          => (string) ($m[Configuracion::CORREO_PASSWORD] ?? ''),
            'cifrado'           => strtolower(trim((string) ($m[Configuracion::CORREO_CIFRADO] ?? 'tls'))) ?: 'tls',
            'remitente'         => $remitente,
            'remitente_nombre'  => trim((string) ($m[Configuracion::CORREO_REMITENTE_NOM] ?? $this->nombreTaller())),
        ];
    }
}
