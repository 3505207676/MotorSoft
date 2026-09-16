<?php

require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../../models/Seguridad/Permiso.php';
require_once __DIR__ . '/../../repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/RolRepository.php';
require_once __DIR__ . '/AuthService.php';
require_once __DIR__ . '/RolPermisoSchema.php';
require_once __DIR__ . '/../Correo/CorreoService.php';
require_once __DIR__ . '/../../config/catalogo_roles.php';

class UserService
{
    private UsuarioRepository $usuarios;
    private RolRepository $roles;
    private AuthService $auth;
    private ?CorreoService $correo;

    private ?RolPermisoSchema $schema;

    public function __construct(
        UsuarioRepository $usuarios,
        RolRepository $roles,
        AuthService $auth,
        ?CorreoService $correo = null,
        ?RolPermisoSchema $schema = null
    ) {
        $this->usuarios = $usuarios;
        $this->roles    = $roles;
        $this->auth     = $auth;
        $this->correo   = $correo;
        $this->schema   = $schema;
    }

    /** @return Usuario[] */
    public function listarUsuarios(): array
    {
        return $this->usuarios->listar();
    }

    public function buscarPorId(int $id): Usuario
    {
        $usuario = $this->usuarios->buscarPorId($id);
        if (!$usuario) {
            throw new AppException('Usuario no encontrado', HTTP_NOT_FOUND);
        }
        return $usuario;
    }

    /**
     * @param array{
     *   nombre:string,correo?:string,email?:string,documento:string,
     *   password?:string,telefono?:string,id_rol:int,estado?:string,
     *   created_by?:int,token?:string
     * } $data
     */
    public function registrarUsuario(array $data): Usuario
    {
        $nombre    = trim((string) ($data['nombre'] ?? ''));
        $correo    = strtolower(trim((string) ($data['correo'] ?? $data['email'] ?? '')));
        $documento = trim((string) ($data['documento'] ?? ''));
        $password  = (string) ($data['password'] ?? '');
        $idRol     = (int) ($data['id_rol'] ?? 0);

        if ($idRol < 1 && !empty($data['rol'])) {
            $rolPorNombre = $this->roles->buscarPorNombre((string) $data['rol']);
            $idRol = $rolPorNombre ? (int) $rolPorNombre->getIdRol() : 0;
        }

        if ($nombre === '' || $correo === '' || $documento === '' || $idRol < 1) {
            throw new AppException('nombre, correo, documento e id_rol son requeridos', HTTP_BAD_REQUEST);
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new AppException('Correo no válido', HTTP_BAD_REQUEST);
        }
        $invitar = $password === '';
        if (!$invitar && strlen($password) < 6) {
            throw new AppException('El password debe tener al menos 6 caracteres', HTTP_BAD_REQUEST);
        }
        if ($invitar && !$this->correo) {
            throw new AppException(
                'Sin contraseña hay que enviar un correo de activación. Asigne una clave temporal o revise el servicio de correo.',
                HTTP_BAD_REQUEST
            );
        }
        $telefono = isset($data['telefono']) ? preg_replace('/\D+/', '', (string) $data['telefono']) : '';
        if ($telefono === '' || strlen($telefono) < 7 || strlen($telefono) > 15) {
            throw new AppException('El teléfono es obligatorio y debe tener entre 7 y 15 dígitos', HTTP_BAD_REQUEST);
        }
        if (!preg_match('/^\d{5,15}$/', $documento)) {
            throw new AppException('El documento debe tener entre 5 y 15 dígitos', HTTP_BAD_REQUEST);
        }
        if (!preg_match('/^[\p{L}\s]{2,80}$/u', $nombre)) {
            throw new AppException('El nombre solo admite letras', HTTP_BAD_REQUEST);
        }
        if ($this->usuarios->correoExiste($correo)) {
            throw new AppException('El correo ya está registrado', HTTP_BAD_REQUEST);
        }
        if ($this->usuarios->documentoExiste($documento)) {
            throw new AppException('El documento ya está registrado', HTTP_BAD_REQUEST);
        }

        $rol = $this->roles->buscarPorId($idRol);
        if (!$rol || !$rol->isActivo()) {
            throw new AppException('Rol no válido', HTTP_BAD_REQUEST);
        }
        if (!empty($data['actor']) && $data['actor'] instanceof Usuario) {
            $this->asegurarAsignacionRol($data['actor'], $rol);
        }

        $hash = $invitar
            ? password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT)
            : password_hash($password, PASSWORD_DEFAULT);

        $usuario = new Usuario(
            $nombre,
            $correo,
            $documento,
            $hash,
            $telefono !== '' ? $telefono : null
        );
        $usuario->setRol($rol);
        if (!empty($data['estado'])) {
            $usuario->setEstado((string) $data['estado']);
        }
        if (!empty($data['created_by'])) {
            $usuario->setCreatedBy((int) $data['created_by']);
        }

        $guardado = $this->usuarios->guardar($usuario);

        $this->auth->registrarLog([
            'token'         => $data['token'] ?? null,
            'accion'        => 'CREATE',
            'tablaAfectada' => 'Usuarios',
            'registroId'    => (int) $guardado->getIdUsuario(),
            'valoresNuevos' => $guardado->toPublicArray(),
            'ipAddress'     => $data['ip'] ?? null,
            'userAgent'     => $data['user_agent'] ?? null,
        ]);

        if ($invitar) {
            $envio = $this->enviarEnlaceAcceso((int) $guardado->getIdUsuario(), 'invitacion');
            $guardado = $envio['usuario'];
        }

        return $guardado;
    }

    /**
     * @return array{usuario:Usuario,correo:array<string,mixed>}
     */
    public function enviarEnlaceAcceso(int $id, string $motivo = 'invitacion'): array
    {
        if (!$this->correo) {
            throw new AppException('Servicio de correo no disponible', HTTP_INTERNAL_ERROR);
        }
        $usuario = $this->buscarPorId($id);
        if (!$usuario->isActivo() && strcasecmp($usuario->getEstado(), 'Inactivo') === 0) {
            throw new AppException('Reactive al usuario antes de enviarle un enlace', HTTP_BAD_REQUEST);
        }
        $horas = $motivo === 'recuperacion' ? 2 : 48;
        $token = $usuario->generarToken($horas);
        $this->usuarios->guardar($usuario);
        $envio = $this->correo->enviarEnlaceCuenta($usuario, $token, $motivo, $horas);
        return [
            'usuario' => $this->usuarios->buscarPorId($id) ?: $usuario,
            'correo'  => $envio,
        ];
    }

    /** @param array<string,mixed>|null $correo */
    public function toPublicConCorreo(Usuario $usuario, ?array $correo = null): array
    {
        $data = $usuario->toPublicArray();
        $envio = $correo ?: ($this->correo ? $this->correo->ultimoEnvio() : null);
        if (is_array($envio) && !empty($envio['url'])) {
            $data['correo_canal'] = (string) ($envio['canal'] ?? 'local');
            $data['url_activacion'] = (string) $envio['url'];
        }
        return $data;
    }

    public function actualizarUsuario(int $id, array $data): Usuario
    {
        $usuario = $this->buscarPorId($id);
        $antes   = $usuario->toPublicArray();

        if (isset($data['nombre'])) {
            $nombre = trim((string) $data['nombre']);
            if (!preg_match('/^[\p{L}\s]{2,80}$/u', $nombre)) {
                throw new AppException('El nombre solo admite letras (mínimo 2)', HTTP_BAD_REQUEST);
            }
            $usuario->setNombre($nombre);
        }
        if (isset($data['correo']) || isset($data['email'])) {
            $correo = strtolower(trim((string) ($data['correo'] ?? $data['email'])));
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new AppException('Correo no válido', HTTP_BAD_REQUEST);
            }
            if ($this->usuarios->correoExiste($correo, $id)) {
                throw new AppException('El correo ya está registrado', HTTP_BAD_REQUEST);
            }
            $usuario->setCorreo($correo);
        }
        if (array_key_exists('telefono', $data)) {
            $telefono = $data['telefono'] !== null ? preg_replace('/\D+/', '', (string) $data['telefono']) : '';
            if ($telefono === '' || strlen($telefono) < 7 || strlen($telefono) > 15) {
                throw new AppException('El teléfono es obligatorio y debe tener entre 7 y 15 dígitos', HTTP_BAD_REQUEST);
            }
            $usuario->setTelefono($telefono);
        }
        if (isset($data['documento'])) {
            $documento = trim((string) $data['documento']);
            if (!preg_match('/^\d{5,15}$/', $documento)) {
                throw new AppException('El documento debe tener entre 5 y 15 dígitos', HTTP_BAD_REQUEST);
            }
            if ($this->usuarios->documentoExiste($documento, $id)) {
                throw new AppException('El documento ya está registrado', HTTP_BAD_REQUEST);
            }
            $usuario->setDocumento($documento);
        }
        if (isset($data['estado'])) {
            $usuario->setEstado((string) $data['estado']);
        }
        if (!empty($data['password'])) {
            if (strlen((string) $data['password']) < 6) {
                throw new AppException('El password debe tener al menos 6 caracteres', HTTP_BAD_REQUEST);
            }
            $usuario->cambiarPassword((string) $data['password']);
        }
        $actorRol = (!empty($data['actor']) && $data['actor'] instanceof Usuario) ? $data['actor'] : null;
        if (!empty($data['id_rol'])) {
            $this->aplicarRol($usuario, (int) $data['id_rol'], $actorRol);
        } elseif (!empty($data['rol']) && is_string($data['rol'])) {
            $rol = $this->roles->buscarPorNombre($data['rol']);
            if (!$rol) {
                throw new AppException('Rol no válido', HTTP_BAD_REQUEST);
            }
            $this->aplicarRol($usuario, (int) $rol->getIdRol(), $actorRol);
        }

        $usuario->tocarUpdatedAt(isset($data['updated_by']) ? (int) $data['updated_by'] : null);
        $guardado = $this->usuarios->guardar($usuario);

        $this->auth->registrarLog([
            'token'              => $data['token'] ?? null,
            'accion'             => 'UPDATE',
            'tablaAfectada'      => 'Usuarios',
            'registroId'         => $id,
            'valoresAnteriores'  => $antes,
            'valoresNuevos'      => $guardado->toPublicArray(),
            'ipAddress'          => $data['ip'] ?? null,
        ]);

        return $guardado;
    }

    public function eliminarUsuario(int $id, array $contexto = []): void
    {
        $usuario = $this->buscarPorId($id);
        $antes   = $usuario->toPublicArray();

        if ($usuario->tieneRol('Administrador')) {
            $admins = 0;
            foreach ($this->usuarios->listar() as $otro) {
                if ($otro->tieneRol('Administrador') && $otro->getIdUsuario() !== $id) {
                    $admins++;
                }
            }
            if ($admins === 0) {
                throw new AppException('No se puede eliminar al último administrador', HTTP_BAD_REQUEST);
            }
        }

        $usuario->marcarEliminado();
        $this->usuarios->guardar($usuario);

        $this->auth->registrarLog([
            'token'             => $contexto['token'] ?? null,
            'accion'            => 'DELETE',
            'tablaAfectada'     => 'Usuarios',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'ipAddress'         => $contexto['ip'] ?? null,
        ]);
    }

    /** @return Rol[] */
    public function listarRoles(): array
    {
        return $this->roles->listar(true);
    }

    /** @return array{roles:array,catalogo:array} */
    public function listarRolesPanel(): array
    {
        return [
            'roles'    => array_map(static fn (Rol $rol) => $rol->toArray(), $this->listarRoles()),
            'catalogo' => array_map(static fn (Permiso $p) => $p->toArray(), $this->roles->listarPermisos()),
        ];
    }

    /**
     * @param string[] $slugs
     */
    public function guardarPermisosRol(int $idRol, array $slugs, array $contexto, Usuario $actor): Rol
    {
        $this->exigirGestionRoles($actor);
        $rol = $this->roles->buscarPorId($idRol, true);
        if (!$rol) {
            throw new AppException('Rol no encontrado', HTTP_NOT_FOUND);
        }
        if ($this->claveRol($rol->getNombre()) === 'admin') {
            throw new AppException('El rol Administrador tiene acceso total y no se recorta desde la matriz', HTTP_BAD_REQUEST);
        }

        $permitidos = catalogo_todos_los_slugs();
        $limpios = [];
        foreach ($slugs as $slug) {
            $slug = trim((string) $slug);
            if ($slug !== '' && in_array($slug, $permitidos, true)) {
                $limpios[] = $slug;
            }
        }
        $limpios = array_values(array_unique($limpios));
        if (in_array('roles.gestionar', $limpios, true)) {
            throw new AppException('Solo el Administrador puede gestionar roles. No asigne ese permiso a otro rol.', HTTP_BAD_REQUEST);
        }
        if (!$limpios) {
            throw new AppException('El rol debe conservar al menos un permiso', HTTP_BAD_REQUEST);
        }

        $antes = $rol->toArray();
        $this->schema()->aplicarSlugs($idRol, $limpios, (int) $actor->getIdUsuario());
        $this->schema()->marcarMatrizManual($idRol, true);
        $guardado = $this->roles->buscarPorId($idRol, true);
        if (!$guardado) {
            throw new AppException('No se pudo guardar la matriz del rol', HTTP_INTERNAL_ERROR);
        }

        $this->auth->registrarLog([
            'token'             => $contexto['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Permisos_Rol',
            'registroId'        => $idRol,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $guardado->toArray(),
            'ipAddress'         => $contexto['ip'] ?? null,
        ]);

        return $guardado;
    }

    public function restaurarPermisosRol(int $idRol, array $contexto, Usuario $actor): Rol
    {
        $this->exigirGestionRoles($actor);
        $rol = $this->roles->buscarPorId($idRol, true);
        if (!$rol) {
            throw new AppException('Rol no encontrado', HTTP_NOT_FOUND);
        }
        $antes = $rol->toArray();
        $this->schema()->restaurarRolDesdeCatalogo((string) $rol->getNombre(), (int) $actor->getIdUsuario());
        $guardado = $this->roles->buscarPorId($idRol, true);
        if (!$guardado) {
            throw new AppException('No se pudo restaurar la matriz del rol', HTTP_INTERNAL_ERROR);
        }

        $this->auth->registrarLog([
            'token'             => $contexto['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Permisos_Rol',
            'registroId'        => $idRol,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $guardado->toArray(),
            'ipAddress'         => $contexto['ip'] ?? null,
        ]);

        return $guardado;
    }

    private function exigirGestionRoles(Usuario $actor): void
    {
        if ($actor->esGerente()) {
            throw new AppException('El gerente no puede modificar la matriz de roles', HTTP_FORBIDDEN);
        }
        if (!$actor->puede('roles.gestionar')) {
            throw new AppException('No puede gestionar roles', HTTP_FORBIDDEN);
        }
    }

    private function schema(): RolPermisoSchema
    {
        if (!$this->schema) {
            throw new AppException('Esquema de roles no disponible', HTTP_INTERNAL_ERROR);
        }
        return $this->schema;
    }

    private function claveRol(string $nombre): string
    {
        $clave = strtolower(trim($nombre));
        $clave = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clave);
        if (strpos($clave, 'admin') !== false) {
            return 'admin';
        }
        if (strpos($clave, 'gerent') !== false) {
            return 'gerente';
        }
        return $clave;
    }

    public function cambiarRolUsuario(int $id, int $idRol, array $contexto = []): Usuario
    {
        $usuario = $this->buscarPorId($id);
        $antes   = $usuario->getRol() ? $usuario->getRol()->toArray() : null;
        $actorRol = (!empty($contexto['actor']) && $contexto['actor'] instanceof Usuario) ? $contexto['actor'] : null;
        $this->aplicarRol($usuario, $idRol, $actorRol);
        $usuario->tocarUpdatedAt($contexto['updated_by'] ?? null);
        $guardado = $this->usuarios->guardar($usuario);

        $this->auth->registrarLog([
            'token'             => $contexto['token'] ?? null,
            'accion'            => 'UPDATE',
            'tablaAfectada'     => 'Usuarios',
            'registroId'        => $id,
            'valoresAnteriores' => $antes,
            'valoresNuevos'     => $guardado->getRol() ? $guardado->getRol()->toArray() : null,
            'ipAddress'         => $contexto['ip'] ?? null,
        ]);

        return $guardado;
    }

    private function aplicarRol(Usuario $usuario, int $idRol, ?Usuario $actor = null): void
    {
        $rol = $this->roles->buscarPorId($idRol);
        if (!$rol || !$rol->isActivo()) {
            throw new AppException('Rol no válido', HTTP_BAD_REQUEST);
        }
        if ($actor) {
            $this->asegurarAsignacionRol($actor, $rol);
        }
        $usuario->setRol($rol);
    }

    private function asegurarAsignacionRol(Usuario $actor, Rol $rol): void
    {
        if ($actor->esAdministrador()) {
            return;
        }
        $clave = strtolower(trim($rol->getNombre()));
        $clave = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clave);
        if (strpos($clave, 'admin') !== false) {
            throw new AppException('Solo un administrador puede asignar el rol Administrador', HTTP_FORBIDDEN);
        }
    }
}
