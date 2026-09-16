<?php

require_once __DIR__ . '/CanalChatInterface.php';
require_once __DIR__ . '/CanalInterno.php';
require_once __DIR__ . '/../../../models/Configuracion/Configuracion.php';
require_once __DIR__ . '/../../../repositories/Chat/MensajeRepository.php';
require_once __DIR__ . '/../../../repositories/Chat/ConversacionRepository.php';

/**
 * Adaptador de WhatsApp Cloud API (Meta).
 * Si no hay token/phone_id, no finge el envío: avisa que falta configurar.
 */
class CanalWhatsApp implements CanalChatInterface
{
    public const GRAPH_VERSION = 'v22.0';

    private MensajeRepository $mensajes;
    private ConversacionRepository $conversaciones;
    private ConfiguracionService $config;

    public function __construct(
        MensajeRepository $mensajes,
        ConversacionRepository $conversaciones,
        ConfiguracionService $config
    ) {
        $this->mensajes       = $mensajes;
        $this->conversaciones = $conversaciones;
        $this->config         = $config;
    }

    public function codigo(): string
    {
        return Conversacion::CANAL_WHATSAPP;
    }

    public function estaConfigurado(): bool
    {
        $creds = $this->credenciales();
        return $creds['activo'] && $creds['token'] !== '' && $creds['phone_id'] !== '';
    }

    /**
     * Estado local, sin llamar a Graph.
     * @return array<string,mixed>
     */
    public function resumen(): array
    {
        $creds = $this->credenciales();
        $smtp = $this->estaConfigurado();
        $https = $this->webhookEsHttps();
        $error = null;
        if (!$creds['activo']) {
            $error = 'Canal apagado. Puede simular mensajes y usar hilos en Chat; Meta no envía ni recibe.';
        } elseif ($creds['token'] === '' || $creds['phone_id'] === '') {
            $error = 'Faltan token y Phone Number ID. El chat WhatsApp funciona en local; el celular del cliente no.';
        } elseif (!$https) {
            $error = 'Meta solo acepta webhook HTTPS público. En local use un túnel o simule un mensaje entrante.';
        }
        return [
            'ok'              => $smtp && $https,
            'configurado'     => $smtp,
            'activo'          => $creds['activo'],
            'tiene_token'     => $creds['token'] !== '',
            'tiene_phone_id'  => $creds['phone_id'] !== '',
            'tiene_verify'    => $creds['verify'] !== '',
            'graph_version'   => self::GRAPH_VERSION,
            'webhook_local'   => $this->urlWebhookLocal(),
            'webhook_publico' => $this->urlWebhookPublico(),
            'https'           => $https,
            'error'           => $error,
        ];
    }

    /**
     * Comprueba token + Phone Number ID contra Graph, sin enviar mensajes.
     * @return array{ok:bool,configurado:bool,activo:bool,tiene_token:bool,tiene_phone_id:bool,graph_version:string,numero:?string,nombre:?string,calidad:?string,error:?string}
     */
    public function diagnosticar(): array
    {
        $creds = $this->credenciales();
        $base = [
            'ok'            => false,
            'configurado'   => false,
            'activo'        => $creds['activo'],
            'tiene_token'   => $creds['token'] !== '',
            'tiene_phone_id'=> $creds['phone_id'] !== '',
            'tiene_verify'  => $creds['verify'] !== '',
            'graph_version' => self::GRAPH_VERSION,
            'numero'        => null,
            'nombre'        => null,
            'calidad'       => null,
            'webhook_local' => $this->urlWebhookLocal(),
            'webhook_publico'=> $this->urlWebhookPublico(),
            'https'         => $this->webhookEsHttps(),
            'error'         => null,
        ];
        if (!$creds['activo']) {
            $base['error'] = 'El canal WhatsApp está apagado. Actívelo en Configuración.';
            return $base;
        }
        if ($creds['token'] === '' || $creds['phone_id'] === '') {
            $base['error'] = 'Faltan el token permanente y/o el Phone Number ID de Meta.';
            return $base;
        }
        $base['configurado'] = true;
        $url = 'https://graph.facebook.com/' . self::GRAPH_VERSION . '/' . rawurlencode($creds['phone_id'])
            . '?fields=' . rawurlencode('id,display_phone_number,verified_name,quality_rating');
        try {
            $json = $this->httpJson('GET', $url, $creds['token']);
        } catch (AppException $e) {
            $base['error'] = $e->getMessage();
            return $base;
        }
        $base['ok']     = true;
        $base['numero'] = isset($json['display_phone_number']) ? (string) $json['display_phone_number'] : null;
        $base['nombre'] = isset($json['verified_name']) ? (string) $json['verified_name'] : null;
        $base['calidad']= isset($json['quality_rating']) ? (string) $json['quality_rating'] : null;
        return $base;
    }

    public function enviar(Conversacion $conversacion, Mensaje $borrador, array $actor): array
    {
        $destino = $this->normalizarDestino($conversacion->getTelefono());
        if ($destino === '') {
            throw new AppException(
                'Este contacto no tiene un teléfono válido para WhatsApp. Agréguelo en la ficha del cliente o proveedor.',
                HTTP_BAD_REQUEST
            );
        }

        if (!$this->estaConfigurado()) {
            $borrador->setIdExterno('local:' . bin2hex(random_bytes(8)));
            $guardado = $this->mensajes->guardar($borrador);
            $idConv = (int) $conversacion->getIdConversacion();
            if ($idConv > 0) {
                $this->conversaciones->tocarUltimoMensaje($idConv, $guardado->getFechaHora());
            }
            return ['mensaje' => $guardado, 'id_externo' => (string) $guardado->getIdExterno(), 'local' => true];
        }

        $creds   = $this->credenciales();
        $url     = 'https://graph.facebook.com/' . self::GRAPH_VERSION . '/' . rawurlencode($creds['phone_id']) . '/messages';
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $destino,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $borrador->getContenido(),
            ],
        ];

        $respuesta = $this->httpJson('POST', $url, $creds['token'], $payload);
        $wamid = $respuesta['messages'][0]['id'] ?? null;
        if (!is_string($wamid) || $wamid === '') {
            throw new AppException(
                $this->mensajeErrorMeta($respuesta['error'] ?? ['message' => 'Meta no devolvió un id de mensaje']),
                HTTP_BAD_REQUEST
            );
        }

        $borrador->setIdExterno($wamid);
        $guardado = $this->mensajes->guardar($borrador);
        $idConv = (int) $conversacion->getIdConversacion();
        if ($idConv > 0) {
            $this->conversaciones->tocarUltimoMensaje($idConv, $guardado->getFechaHora());
        }
        return ['mensaje' => $guardado, 'id_externo' => $wamid];
    }

    public function tokenVerificacion(): string
    {
        return $this->credenciales()['verify'];
    }

    public function urlWebhookLocal(): string
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $root = preg_replace('#/controllers/api/.*$#', '', $script) ?? '';
        $root = preg_replace('#/views/.*$#', '', $root) ?? $root;
        $path = rtrim($root, '/') . '/controllers/api/chat/whatsapp-webhook.php';
        $path = str_replace(' ', '%20', $path);
        return $scheme . '://' . $host . $path;
    }

    public function urlWebhookPublico(): string
    {
        $mapa = $this->config->mapa();
        $base = rtrim(trim((string) ($mapa[Configuracion::APP_URL_PUBLICA] ?? '')), '/');
        if ($base === '') {
            return $this->urlWebhookLocal();
        }
        return $base . '/controllers/api/chat/whatsapp-webhook.php';
    }

    public function webhookEsHttps(): bool
    {
        return (bool) preg_match('#^https://#i', $this->urlWebhookPublico());
    }

    public static function normalizarTelefono(?string $telefono): string
    {
        $solo = preg_replace('/\D+/', '', (string) $telefono) ?? '';
        if ($solo === '') {
            return '';
        }
        if (str_starts_with($solo, '00')) {
            $solo = substr($solo, 2);
        }
        if (strlen($solo) === 10 && $solo[0] === '3') {
            return '57' . $solo;
        }
        return $solo;
    }

    private function normalizarDestino(?string $telefono): string
    {
        return self::normalizarTelefono($telefono);
    }

    /** @return array{activo:bool,token:string,phone_id:string,waba_id:string,verify:string} */
    private function credenciales(): array
    {
        $mapa = $this->config->mapa();
        return [
            'activo'   => !empty($mapa[Configuracion::WHATSAPP_ACTIVO]),
            'token'    => trim((string) ($mapa[Configuracion::WHATSAPP_TOKEN] ?? '')),
            'phone_id' => trim((string) ($mapa[Configuracion::WHATSAPP_PHONE_ID] ?? '')),
            'waba_id'  => trim((string) ($mapa[Configuracion::WHATSAPP_WABA_ID] ?? '')),
            'verify'   => trim((string) ($mapa[Configuracion::WHATSAPP_VERIFY_TOKEN] ?? '')),
        ];
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return array<string,mixed>
     */
    private function httpJson(string $method, string $url, string $token, ?array $payload = null): array
    {
        $cuerpo = null;
        if ($payload !== null) {
            $cuerpo = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($cuerpo === false) {
                throw new AppException('No se pudo armar el mensaje de WhatsApp', HTTP_BAD_REQUEST);
            }
        }

        if (function_exists('curl_init')) {
            return $this->httpJsonCurl($method, $url, $token, $cuerpo);
        }
        return $this->httpJsonStream($method, $url, $token, $cuerpo);
    }

    private function httpJsonCurl(string $method, string $url, string $token, ?string $cuerpo): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        $ca = $this->rutaCertificados();
        if ($ca !== null) {
            $opts[CURLOPT_CAINFO] = $ca;
        }
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $cuerpo ?? '{}';
        } else {
            $opts[CURLOPT_HTTPGET] = true;
        }
        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) {
            throw new AppException('No se pudo contactar la API de WhatsApp: ' . $err, HTTP_BAD_REQUEST);
        }
        return $this->decodificarRespuesta((string) $raw, $code);
    }

    private function httpJsonStream(string $method, string $url, string $token, ?string $cuerpo): array
    {
        $headers = "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n";
        $http = [
            'method'  => strtoupper($method),
            'header'  => $headers,
            'timeout' => 20,
            'ignore_errors' => true,
        ];
        if ($cuerpo !== null) {
            $http['content'] = $cuerpo;
        }
        $ctx = stream_context_create(['http' => $http]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new AppException('No se pudo contactar la API de WhatsApp desde este servidor.', HTTP_BAD_REQUEST);
        }
        $code = 0;
        foreach ($http_response_header ?? [] as $linea) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $linea, $m)) {
                $code = (int) $m[1];
            }
        }
        return $this->decodificarRespuesta($raw, $code);
    }

    /** @return array<string,mixed> */
    private function decodificarRespuesta(string $raw, int $code): array
    {
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new AppException('Respuesta inválida de WhatsApp (HTTP ' . $code . ')', HTTP_BAD_REQUEST);
        }
        if ($code >= 400 || isset($json['error'])) {
            throw new AppException($this->mensajeErrorMeta($json['error'] ?? ['message' => 'HTTP ' . $code]), HTTP_BAD_REQUEST);
        }
        return $json;
    }

    /** @param array<string,mixed> $error */
    private function mensajeErrorMeta(array $error): string
    {
        $code = (int) ($error['code'] ?? 0);
        $sub  = (int) ($error['error_subcode'] ?? 0);
        $msg  = trim((string) ($error['message'] ?? ''));
        if ($code === 190) {
            return 'El token de WhatsApp expiró o es inválido. Genere uno permanente en Meta y péguelo en Configuración.';
        }
        if ($code === 100 || $code === 33) {
            return 'Phone Number ID incorrecto o la app no tiene permiso sobre ese número.';
        }
        if ($code === 10 || $code === 200) {
            return 'El token no tiene permiso whatsapp_business_messaging. Revise los permisos de la app en Meta.';
        }
        if ($code === 131047 || $sub === 131047) {
            return 'Pasaron más de 24 horas desde el último mensaje del cliente. En WhatsApp hay que usar una plantilla aprobada, o esperar a que la persona escriba de nuevo.';
        }
        if ($code === 131026 || $sub === 131026) {
            return 'Ese número no tiene WhatsApp o no puede recibir mensajes de este negocio.';
        }
        if ($code === 131048 || $sub === 131048) {
            return 'Meta limitó el envío por calidad o spam. Revise el estado del número en Business Manager.';
        }
        if ($msg !== '') {
            return 'WhatsApp rechazó el envío: ' . $msg;
        }
        return 'WhatsApp rechazó el envío (código ' . $code . ').';
    }

    private function rutaCertificados(): ?string
    {
        $candidatos = [
            (string) ini_get('curl.cainfo'),
            (string) ini_get('openssl.cafile'),
            'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt',
            'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
        ];
        foreach ($candidatos as $ruta) {
            if ($ruta !== '' && is_file($ruta)) {
                return $ruta;
            }
        }
        return null;
    }
}
