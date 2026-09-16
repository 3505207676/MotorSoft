<?php

class ChatController
{
    private ChatService $chat;
    private AuthService $authService;

    public function __construct(ChatService $chat, AuthService $authService)
    {
        $this->chat        = $chat;
        $this->authService = $authService;
    }

    public function conversaciones(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $this->exigirStaffPermiso($actor, 'chat.ver', 'No puede ver el chat');
            $q = ApiRequest::query();
            $conversaciones = $this->chat->conversaciones($actor, $q);
            $noLeidos = 0;
            foreach ($conversaciones as $conversacion) {
                $noLeidos += (int) ($conversacion['no_leidos'] ?? 0);
            }
            ApiResponse::ok([
                'conversaciones' => $conversaciones,
                'no_leidos'      => $noLeidos,
                'canales'        => $this->chat->canalesDisponibles(),
            ]);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function contactos(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $this->exigirStaffPermiso($actor, 'chat.ver', 'No puede consultar contactos');
            $q = ApiRequest::query();
            $tipo = (string) ($q['tipo_contacto'] ?? $q['tipo'] ?? Conversacion::TIPO_CLIENTE);
            $busqueda = (string) ($q['q'] ?? $q['busqueda'] ?? '');
            ApiResponse::ok([
                'contactos' => $this->chat->contactos($actor, $tipo, $busqueda),
                'canales'   => $this->chat->canalesDisponibles(),
            ]);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function crearContacto(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            $this->exigirStaffPermiso($actor, 'chat.enviar', 'No puede crear contactos desde el chat');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $tipo = (string) ($body['tipo_contacto'] ?? $body['tipo'] ?? Conversacion::TIPO_PROVEEDOR);
            if (strcasecmp($tipo, Conversacion::TIPO_PROVEEDOR) !== 0) {
                throw new AppException('Desde el chat solo se pueden crear proveedores. Clientes y usuarios tienen su propio módulo.', HTTP_BAD_REQUEST);
            }
            $contacto = $this->chat->crearProveedor($body, $actor);
            ApiResponse::ok($contacto, 'Proveedor creado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function abrir(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            if (($actor['tipo'] ?? '') === 'cliente') {
                ApiResponse::ok($this->chat->asegurarHiloCliente($actor));
                return;
            }
            $this->exigirStaffPermiso($actor, 'chat.enviar', 'No puede abrir conversaciones');
            $body = ApiRequest::jsonBody();
            ApiResponse::ok($this->chat->abrir($body, $actor));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $this->exigirStaffPermiso($actor, 'chat.ver', 'No puede ver mensajes');
            $q = ApiRequest::query();
            $ref = $this->refDesde($q);
            $data = $this->chat->mensajesDeConversacion($actor, $ref);
            $silencioso = !empty($q['silencioso']) || (string) ($q['marcar'] ?? '1') === '0';
            if (
                !$silencioso
                && (($actor['tipo'] ?? '') === 'usuario' || ($actor['tipo'] ?? '') === 'cliente')
            ) {
                try {
                    $this->chat->marcarLeido($actor, $ref);
                } catch (Throwable $e) {
                    // abrir un hilo vacío aún no persistido no debe fallar
                }
            }
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function enviar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            $this->exigirStaffPermiso($actor, 'chat.enviar', 'No puede enviar mensajes');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $msg = $this->chat->enviar($body, $actor);
            $arr = $msg->toArray();
            $arr['mio'] = true;
            $ext = (string) ($arr['id_externo'] ?? '');
            $arr['whatsapp_local'] = str_starts_with($ext, 'local:');
            ApiResponse::ok($arr, $arr['whatsapp_local'] ? 'Guardado en MotorSoft (aún no sale al celular)' : 'Mensaje enviado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function leer(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            $this->exigirStaffPermiso($actor, 'chat.ver', 'No puede marcar mensajes');
            $body = ApiRequest::jsonBody();
            $ref = $this->refDesde($body + ApiRequest::query());
            $this->chat->marcarLeido($actor, $ref);
            ApiResponse::ok(['no_leidos' => $this->chat->noLeidos($actor)], 'Mensajes marcados como leídos');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function probarWhatsApp(): void
    {
        try {
            $actor = $this->exigirActor();
            if (ApiRequest::method() === 'GET') {
                $this->exigirStaffPermiso($actor, 'chat.ver', 'No puede consultar WhatsApp');
                ApiResponse::ok($this->chat->estadoWhatsApp());
                return;
            }
            ApiRequest::requireMethod(['POST']);
            if (($actor['tipo'] ?? '') === 'usuario') {
                $this->authService->asegurarAlgunPermiso(
                    $actor['usuario'],
                    ['configuracion.editar', 'chat.enviar'],
                    'No puede probar WhatsApp'
                );
            }
            ApiResponse::ok($this->chat->probarWhatsApp());
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function simularWhatsApp(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            if (($actor['tipo'] ?? '') !== 'usuario') {
                throw new AppException('Solo el personal del taller puede simular WhatsApp', HTTP_FORBIDDEN);
            }
            $this->authService->asegurarPermiso(
                $actor['usuario'],
                'configuracion.editar',
                'Solo el administrador puede simular un mensaje de WhatsApp'
            );
            $body = ApiRequest::jsonBody();
            ApiResponse::ok($this->chat->simularWhatsApp($body, $actor), 'Mensaje de WhatsApp simulado. Ábralo en Chat.');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function webhookWhatsApp(): void
    {
        ini_set('display_errors', '0');
        try {
            if (ApiRequest::method() === 'GET') {
                $challenge = $this->chat->verificarWebhookWhatsApp(ApiRequest::query());
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                if ($challenge === null) {
                    http_response_code(403);
                    header('Content-Type: text/plain; charset=utf-8');
                    echo 'Forbidden';
                    exit;
                }
                http_response_code(200);
                header('Content-Type: text/plain; charset=utf-8');
                header('X-Content-Type-Options: nosniff');
                echo $challenge;
                exit;
            }
            ApiRequest::requireMethod(['POST']);
            $this->chat->recibirWebhookWhatsApp(ApiRequest::jsonBody());
            ApiResponse::ok(['recibido' => true]);
        } catch (Throwable $e) {
            error_log('WhatsApp webhook: ' . $e->getMessage());
            ApiResponse::ok(['recibido' => false]);
        }
    }

    /** @param array<string,mixed> $src */
    private function refDesde(array $src): array
    {
        return [
            'id_conversacion' => (int) ($src['id_conversacion'] ?? $src['conversacionId'] ?? 0),
            'id_cliente'      => (int) ($src['id_cliente'] ?? $src['cliente_id'] ?? 0),
            'tipo_contacto'   => (string) ($src['tipo_contacto'] ?? ''),
            'id_contacto'     => (int) ($src['id_contacto'] ?? 0),
            'canal'           => (string) ($src['canal'] ?? ''),
        ];
    }

    private function exigirActor(): array
    {
        return $this->authService->resolverActor(ApiRequest::bearerToken());
    }

    private function exigirStaffPermiso(array $actor, string $slug, string $mensaje): void
    {
        if (($actor['tipo'] ?? '') === 'cliente') {
            return;
        }
        if (($actor['tipo'] ?? '') !== 'usuario' || empty($actor['usuario'])) {
            throw new AppException('No autorizado', HTTP_FORBIDDEN);
        }
        $this->authService->asegurarPermiso($actor['usuario'], $slug, $mensaje);
    }
}
