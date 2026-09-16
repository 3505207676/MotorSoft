<?php

class UserController
{
    private UserService $userService;
    private AuthService $authService;

    public function __construct(UserService $userService, AuthService $authService)
    {
        $this->userService = $userService;
        $this->authService = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->exigirSesion();
            $usuarios = $this->userService->listarUsuarios();
            $data = array_map(static function (Usuario $usuario) {
                return $usuario->toPublicArray();
            }, $usuarios);
            ApiResponse::ok($data);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function mostrar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->exigirSesion();
            $id = (int) ($_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de usuario requerido', HTTP_BAD_REQUEST);
            }
            $usuario = $this->userService->buscarPorId($id);
            ApiResponse::ok($usuario->toPublicArray());
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function registrar(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('usuarios.crear', null, 'No puede registrar usuarios');
            $body = ApiRequest::jsonBody();
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $body['user_agent'] = ApiRequest::userAgent();
            $body['created_by'] = $actor['usuario']->getIdUsuario();
            $body['actor'] = $actor['usuario'];
            $usuario = $this->userService->registrarUsuario($body);
            ApiResponse::ok($this->userService->toPublicConCorreo($usuario), 'Usuario creado exitosamente', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_usuario'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de usuario requerido', HTTP_BAD_REQUEST);
            }
            $esPropio = (int) $actor['usuario']->getIdUsuario() === $id;
            if (!$esPropio) {
                $this->authService->asegurarPermiso($actor['usuario'], 'usuarios.editar', 'No puede editar usuarios');
            }
            $body['token'] = ApiRequest::bearerToken();
            $body['ip'] = ApiRequest::ip();
            $body['updated_by'] = $actor['usuario']->getIdUsuario();
            $body['actor'] = $actor['usuario'];
            if ($esPropio && !$actor['usuario']->puede('usuarios.asignar_rol')) {
                unset($body['id_rol'], $body['rol']);
            }
            $usuario = $this->userService->actualizarUsuario($id, $body);
            ApiResponse::ok($usuario->toPublicArray(), 'Usuario actualizado exitosamente');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $this->authService->exigirPermiso('usuarios.eliminar', null, 'No puede eliminar usuarios');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_usuario'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de usuario requerido', HTTP_BAD_REQUEST);
            }
            $this->userService->eliminarUsuario($id, [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ]);
            ApiResponse::ok(null, 'Usuario eliminado exitosamente');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function asignarRol(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT']);
            $actor = $this->authService->exigirPermiso('usuarios.asignar_rol', null, 'No puede asignar roles');
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_usuario'] ?? 0);
            $idRol = (int) ($body['id_rol'] ?? 0);
            if ($id < 1 || $idRol < 1) {
                throw new AppException('id_usuario e id_rol son requeridos', HTTP_BAD_REQUEST);
            }
            $usuario = $this->userService->cambiarRolUsuario($id, $idRol, [
                'token'      => ApiRequest::bearerToken(),
                'ip'         => ApiRequest::ip(),
                'updated_by' => $actor['usuario']->getIdUsuario(),
                'actor'      => $actor['usuario'],
            ]);
            ApiResponse::ok($usuario->toPublicArray(), 'Rol asignado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function enviarEnlace(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $this->authService->exigirAlgunPermiso(
                ['usuarios.crear', 'usuarios.editar'],
                null,
                'No puede enviar enlaces de acceso'
            );
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id'] ?? $body['id_usuario'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('ID de usuario requerido', HTTP_BAD_REQUEST);
            }
            $envio = $this->userService->enviarEnlaceAcceso($id, 'invitacion');
            $canal = (string) ($envio['correo']['canal'] ?? 'local');
            $mensaje = $canal === 'smtp'
                ? 'Enlace enviado al correo del usuario'
                : 'Sin SMTP: el enlace quedó en el buzón de Configuración y se puede copiar';
            ApiResponse::ok(
                $this->userService->toPublicConCorreo($envio['usuario'], $envio['correo']),
                $mensaje
            );
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function listarRoles(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $this->exigirSesion();
            ApiResponse::ok($this->userService->listarRolesPanel());
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizarPermisos(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->authService->exigirPermiso('roles.gestionar', null, 'No puede gestionar roles');
            $body = ApiRequest::jsonBody();
            $idRol = (int) ($body['id_rol'] ?? $body['id'] ?? 0);
            $slugs = $body['slugs'] ?? $body['permisos'] ?? [];
            if ($idRol < 1) {
                throw new AppException('ID de rol requerido', HTTP_BAD_REQUEST);
            }
            if (!is_array($slugs)) {
                throw new AppException('La lista de permisos no es válida', HTTP_BAD_REQUEST);
            }
            $rol = $this->userService->guardarPermisosRol($idRol, $slugs, [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ], $actor['usuario']);
            ApiResponse::ok($rol->toArray(), 'Matriz de permisos guardada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function restaurarPermisos(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->authService->exigirPermiso('roles.gestionar', null, 'No puede gestionar roles');
            $body = ApiRequest::jsonBody();
            $idRol = (int) ($body['id_rol'] ?? $body['id'] ?? 0);
            if ($idRol < 1) {
                throw new AppException('ID de rol requerido', HTTP_BAD_REQUEST);
            }
            $rol = $this->userService->restaurarPermisosRol($idRol, [
                'token' => ApiRequest::bearerToken(),
                'ip'    => ApiRequest::ip(),
            ], $actor['usuario']);
            ApiResponse::ok($rol->toArray(), 'Matriz restaurada al catálogo del sistema');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /** @return array{usuario:Usuario,sesion:Sesion} */
    private function exigirSesion(): array
    {
        $token = ApiRequest::bearerToken();
        if (!$token) {
            throw new AppException('Token requerido', HTTP_UNAUTHORIZED);
        }
        return $this->authService->validarToken($token);
    }

}
