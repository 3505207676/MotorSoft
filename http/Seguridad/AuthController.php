<?php

class AuthController
{
    private AuthService $authService;
    private UserService $userService;
    private ?CorreoService $correo;

    public function __construct(AuthService $authService, UserService $userService, ?CorreoService $correo = null)
    {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->correo      = $correo;
    }

    public function login(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $body = ApiRequest::jsonBody();
            $result = $this->authService->autenticar([
                'correo'    => $body['correo'] ?? $body['email'] ?? '',
                'documento' => $body['documento'] ?? '',
                'password'  => $body['password'] ?? '',
                'placa'     => $body['placa'] ?? '',
                'ip'        => ApiRequest::ip(),
            ]);

            $tipo = $result['tipo'] ?? 'usuario';
            $payload = [
                'tipo'        => $tipo,
                'rol'         => $result['rol'] ?? '',
                'es_mecanico' => !empty($result['es_mecanico']),
                'redirect'    => $result['redirect'] ?? AuthService::rutaPorRol($result['rol'] ?? ''),
                'token'       => $result['token'] ?? '',
            ];

            if ($tipo === 'cliente') {
                $payload['cliente']  = $result['cliente']->toArray();
                $payload['vehiculo'] = $result['vehiculo']->toArray();
            } else {
                $payload['usuario']    = $result['usuario']->toPublicArray();
                $payload['session_id'] = $result['sesion']->getIdSesion();
                $payload['expira']     = $result['sesion']->getFechaExpiracion()->format('Y-m-d H:i:s');
            }

            ApiResponse::ok($payload, 'Login exitoso');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function logout(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $token = ApiRequest::bearerToken();
            if (!$token) {
                throw new AppException('Token requerido', HTTP_UNAUTHORIZED);
            }
            $this->authService->cerrarSesion($token);
            ApiResponse::ok(null, 'Sesión cerrada exitosamente');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /**
     * Identidad del usuario autenticado a partir del token.
     * Equivale a "quién soy yo ahora" sin volver a pedir correo/clave.
     */
    public function me(): void
    {
        try {
            ApiRequest::requireMethod(['GET', 'POST']);
            $token = ApiRequest::bearerToken();
            if (!$token) {
                throw new AppException('Token requerido', HTTP_UNAUTHORIZED);
            }
            $actor = $this->authService->resolverActor($token);
            if (($actor['tipo'] ?? '') === 'cliente') {
                ApiResponse::ok([
                    'tipo'     => 'cliente',
                    'cliente'  => $actor['cliente']->toArray(),
                    'vehiculo' => $actor['vehiculo'] ? $actor['vehiculo']->toArray() : null,
                ]);
                return;
            }
            /** @var Usuario $usuario */
            $usuario = $actor['usuario'];
            /** @var Sesion $sesion */
            $sesion = $actor['sesion'];

            ApiResponse::ok([
                'tipo'    => 'usuario',
                'usuario' => $usuario->toPublicArray(),
                'sesion'  => [
                    'id_sesion'        => $sesion->getIdSesion(),
                    'fecha_expiracion' => $sesion->getFechaExpiracion()->format('Y-m-d H:i:s'),
                    'activa'           => $sesion->isActiva(),
                ],
            ]);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function recuperarPassword(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $body = ApiRequest::jsonBody();
            $email = strtolower(trim((string) ($body['correo'] ?? $body['email'] ?? '')));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new AppException('El correo es requerido', HTTP_BAD_REQUEST);
            }
            if (!$this->correo) {
                throw new AppException(
                    'El taller aún no puede enviar correos. Pida ayuda en recepción o administración.',
                    HTTP_BAD_REQUEST
                );
            }
            $documento = isset($body['documento']) ? (string) $body['documento'] : null;
            $inicio = $this->authService->iniciarRecuperacion($email, $documento, 2);
            if ($inicio) {
                $this->correo->enviarEnlaceCuenta($inicio['usuario'], $inicio['token'], 'recuperacion', 2);
            }
            ApiResponse::ok(
                ['enviado' => true],
                'Si los datos coinciden, enviamos un enlace a su correo'
            );
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function resetPassword(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $usuario = $this->authService->resetearPassword(ApiRequest::jsonBody());
            ApiResponse::ok($usuario->toPublicArray(), 'Contraseña actualizada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function register(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('usuarios.crear', null, 'No puede registrar usuarios');
            $body = ApiRequest::jsonBody();
            $body['ip'] = ApiRequest::ip();
            $body['user_agent'] = ApiRequest::userAgent();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['actor'] = $actor['usuario'];
            $usuario = $this->userService->registrarUsuario($body);
            ApiResponse::ok($this->userService->toPublicConCorreo($usuario), 'Usuario registrado', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }
}
