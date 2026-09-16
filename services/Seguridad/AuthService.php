<?php

require_once __DIR__ . '/../../core/ApiRequest.php';
require_once __DIR__ . '/../../models/Seguridad/Usuario.php';
require_once __DIR__ . '/../../models/Seguridad/Sesion.php';
require_once __DIR__ . '/../../models/Seguridad/LogAuditoria.php';
require_once __DIR__ . '/../../repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/SesionRepository.php';
require_once __DIR__ . '/../../repositories/Seguridad/LogAuditoriaRepository.php';
require_once __DIR__ . '/../../repositories/Clientes/ClienteRepository.php';
require_once __DIR__ . '/../../repositories/Clientes/VehiculoRepository.php';

class AuthService
{
    private UsuarioRepository $usuarios;
    private SesionRepository $sesiones;
    private LogAuditoriaRepository $logs;
    private ?ClienteRepository $clientes;
    private ?VehiculoRepository $vehiculos;

    public function __construct(
        UsuarioRepository $usuarios,
        SesionRepository $sesiones,
        LogAuditoriaRepository $logs,
        ?ClienteRepository $clientes = null,
        ?VehiculoRepository $vehiculos = null
    ) {
        $this->usuarios  = $usuarios;
        $this->sesiones  = $sesiones;
        $this->logs      = $logs;
        $this->clientes  = $clientes;
        $this->vehiculos = $vehiculos;
    }

    /**
     * @return array{
     *   tipo:string,
     *   rol:string,
     *   redirect:string,
     *   usuario?:Usuario,
     *   cliente?:Cliente,
     *   vehiculo?:Vehiculo,
     *   sesion?:Sesion,
     *   token:string
     * }
     */
    public function autenticar(array $creds): array
    {
        $correo    = strtolower(trim((string) ($creds['correo'] ?? $creds['email'] ?? '')));
        $documento = preg_replace('/\D+/', '', (string) ($creds['documento'] ?? ''));
        $password  = (string) ($creds['password'] ?? '');
        $placa     = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) ($creds['placa'] ?? '')));
        $ip        = $creds['ip'] ?? null;

        if ($correo === '' && $documento === '') {
            throw new AppException('Documento o correo son requeridos', HTTP_BAD_REQUEST);
        }

        $usuario = $correo !== ''
            ? $this->usuarios->buscarPorCorreo($correo)
            : $this->usuarios->buscarPorDocumento($documento);

        if ($usuario) {
            return $this->autenticarUsuario($usuario, $password, $ip);
        }

        if ($documento === '') {
            throw new AppException('Credenciales inválidas', HTTP_UNAUTHORIZED);
        }

        return $this->autenticarCliente($documento, $placa);
    }

    private function autenticarUsuario(Usuario $usuario, string $password, ?string $ip): array
    {
        if ($password === '') {
            throw new AppException('La contraseña es requerida', HTTP_BAD_REQUEST);
        }

        if ($usuario->isBloqueado()) {
            throw new AppException('Usuario bloqueado por intentos fallidos', HTTP_UNAUTHORIZED);
        }

        if (!$usuario->isActivo()) {
            throw new AppException('Usuario inactivo', HTTP_UNAUTHORIZED);
        }

        if (!$usuario->verificarPassword($password)) {
            if ($usuario->invitacionPendiente()) {
                throw new AppException('Aún no ha definido su contraseña. Revise el correo de invitación.', HTTP_UNAUTHORIZED);
            }
            $usuario->incrementarIntentos();
            $this->usuarios->guardar($usuario);
            throw new AppException('Credenciales inválidas', HTTP_UNAUTHORIZED);
        }

        $usuario->resetIntentos();
        if ($usuario->invitacionPendiente()) {
            $usuario->limpiarTokenRecuperacion();
        }
        $this->usuarios->guardar($usuario);

        $sesion = $this->crearSesion($usuario, $ip);
        $rol = $usuario->getRol() ? $usuario->getRol()->getNombre() : '';
        $esMecanico = $usuario->esMecanico();

        $this->registrarLog([
            'sesion'        => $sesion,
            'accion'        => 'LOGIN',
            'tablaAfectada' => 'Usuarios',
            'registroId'    => (int) $usuario->getIdUsuario(),
            'valoresNuevos' => ['correo' => $usuario->getCorreo(), 'rol' => $rol],
            'ipAddress'     => $ip,
        ]);

        return [
            'tipo'        => 'usuario',
            'rol'         => $rol,
            'es_mecanico' => $esMecanico,
            'redirect'    => $esMecanico
                ? '../dashboard/index-mecanicos.php'
                : self::rutaPorRol($rol),
            'usuario'     => $usuario,
            'sesion'      => $sesion,
            'token'       => $sesion->getToken(),
        ];
    }

    private function autenticarCliente(string $documento, string $placa): array
    {
        if ($this->clientes === null || $this->vehiculos === null) {
            throw new AppException('Credenciales inválidas', HTTP_UNAUTHORIZED);
        }
        if ($documento === '' || $placa === '') {
            throw new AppException('Si eres cliente, ingresa documento y placa', HTTP_BAD_REQUEST);
        }

        $cliente = $this->clientes->buscarPorDocumento($documento);
        if (!$cliente) {
            throw new AppException('Credenciales inválidas', HTTP_UNAUTHORIZED);
        }
        if (!$cliente->isActivo()) {
            throw new AppException('Cliente inactivo. Pide al taller que reactive tu cuenta.', HTTP_UNAUTHORIZED);
        }

        $vehiculo = $this->vehiculos->buscarPorPlaca($placa);
        if (!$vehiculo || !$vehiculo->isActivo() || $vehiculo->getIdCliente() !== $cliente->getIdCliente()) {
            throw new AppException('La placa no corresponde a este cliente', HTTP_UNAUTHORIZED);
        }

        $token = $this->emitirTokenCliente(
            (int) $cliente->getIdCliente(),
            (int) $vehiculo->getIdVehiculo()
        );

        return [
            'tipo'     => 'cliente',
            'rol'      => 'Cliente',
            'redirect' => self::rutaPorRol('Cliente'),
            'cliente'  => $cliente,
            'vehiculo' => $vehiculo,
            'token'    => $token,
        ];
    }

    private function emitirTokenCliente(int $idCliente, int $idVehiculo): string
    {
        $payload = json_encode([
            'c' => $idCliente,
            'v' => $idVehiculo,
            'e' => time() + (int) SESSION_LIFETIME,
        ], JSON_UNESCAPED_SLASHES);
        $cuerpo = rtrim(strtr(base64_encode((string) $payload), '+/', '-_'), '=');
        $firma  = hash_hmac('sha256', $cuerpo, CLIENT_TOKEN_SECRET);
        return 'cli_' . $cuerpo . '.' . $firma;
    }

    public function validarClienteToken(string $token): array
    {
        if (strpos($token, 'cli_') !== 0) {
            throw new AppException('Token de cliente inválido', HTTP_UNAUTHORIZED);
        }
        $raw = substr($token, 4);
        $partes = explode('.', $raw, 2);
        if (count($partes) !== 2) {
            throw new AppException('Token de cliente inválido', HTTP_UNAUTHORIZED);
        }
        [$cuerpo, $firma] = $partes;
        $esperada = hash_hmac('sha256', $cuerpo, CLIENT_TOKEN_SECRET);
        if (!hash_equals($esperada, $firma)) {
            throw new AppException('Token de cliente inválido', HTTP_UNAUTHORIZED);
        }
        $pad = strlen($cuerpo) % 4;
        $json = base64_decode(strtr($cuerpo, '-_', '+/') . ($pad ? str_repeat('=', 4 - $pad) : ''), true);
        $data = json_decode((string) $json, true);
        if (!is_array($data) || empty($data['c']) || empty($data['e']) || (int) $data['e'] < time()) {
            throw new AppException('Sesión de cliente expirada. Ingrese de nuevo.', HTTP_UNAUTHORIZED);
        }
        if ($this->clientes === null) {
            throw new AppException('Token de cliente inválido', HTTP_UNAUTHORIZED);
        }
        $cliente = $this->clientes->buscarPorId((int) $data['c']);
        if (!$cliente || !$cliente->isActivo()) {
            throw new AppException('Cliente no autorizado', HTTP_UNAUTHORIZED);
        }
        $vehiculo = null;
        if (!empty($data['v']) && $this->vehiculos !== null) {
            $vehiculo = $this->vehiculos->buscarPorId((int) $data['v']);
        }
        return ['cliente' => $cliente, 'vehiculo' => $vehiculo];
    }

    /**
     * @return array{tipo:string,usuario:?Usuario,sesion:?Sesion,cliente:?Cliente,vehiculo:?Vehiculo}
     */
    public function resolverActor(?string $token): array
    {
        if (!$token) {
            throw new AppException('Token requerido', HTTP_UNAUTHORIZED);
        }
        if (strpos($token, 'cli_') === 0) {
            $cli = $this->validarClienteToken($token);
            return [
                'tipo'     => 'cliente',
                'usuario'  => null,
                'sesion'   => null,
                'cliente'  => $cli['cliente'],
                'vehiculo' => $cli['vehiculo'] ?? null,
            ];
        }
        $staff = $this->validarToken($token);
        return [
            'tipo'     => 'usuario',
            'usuario'  => $staff['usuario'],
            'sesion'   => $staff['sesion'],
            'cliente'  => null,
            'vehiculo' => null,
        ];
    }

    public static function rutaPorRol(string $rol): string
    {
        $clave = strtolower(trim($rol));
        $clave = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clave);

        if ($clave === 'cliente') {
            return '../cliente/P_Inicio.php';
        }
        if (strpos($clave, 'mecanic') !== false) {
            return '../dashboard/index-mecanicos.php';
        }
        return '../dashboard/index-trabajadores.php';
    }

    public function crearSesion(Usuario $usuario, ?string $ipAddress = null): Sesion
    {
        if ($usuario->getIdUsuario() === null) {
            throw new AppException('No se puede crear sesión de un usuario no persistido', HTTP_BAD_REQUEST);
        }

        $token = bin2hex(random_bytes(32));
        $expira = (new DateTime())->modify('+' . (int) SESSION_LIFETIME . ' seconds');

        $sesion = new Sesion(
            (int) $usuario->getIdUsuario(),
            $token,
            $expira,
            $ipAddress
        );
        $sesion->setUsuario($usuario);

        return $this->sesiones->guardar($sesion);
    }

    public function cerrarSesion(string $token): void
    {
        if (strpos($token, 'cli_') === 0) {
            $this->validarClienteToken($token);
            return;
        }
        $sesion = $this->sesiones->buscarPorToken($token);
        if (!$sesion) {
            throw new AppException('Sesión no encontrada', HTTP_NOT_FOUND);
        }

        $sesion->invalidar();
        $this->sesiones->guardar($sesion);

        $this->registrarLog([
            'sesion'        => $sesion,
            'accion'        => 'LOGOUT',
            'tablaAfectada' => 'Sesiones',
            'registroId'    => (int) $sesion->getIdSesion(),
            'ipAddress'     => $sesion->getIpAddress(),
        ]);
    }

    public function validarToken(string $token): array
    {
        $sesion = $this->sesiones->buscarPorToken($token, true);
        if (!$sesion || !$sesion->isActiva()) {
            throw new AppException('Token inválido o sesión expirada', HTTP_UNAUTHORIZED);
        }

        $usuario = $sesion->getUsuario();
        if (!$usuario || !$usuario->isActivo()) {
            throw new AppException('Usuario no autorizado', HTTP_UNAUTHORIZED);
        }

        return [
            'usuario' => $usuario,
            'sesion'  => $sesion,
        ];
    }

    /**
     * @return array{tipo:string,usuario:Usuario,sesion:Sesion,cliente:null,vehiculo:null}
     */
    public function exigirUsuario(?string $token = null): array
    {
        $actor = $this->resolverActor($token ?: ApiRequest::bearerToken());
        if (($actor['tipo'] ?? '') !== 'usuario' || !$actor['usuario']) {
            throw new AppException('Solo el personal del taller puede hacer esto', HTTP_FORBIDDEN);
        }
        return $actor;
    }

    public function asegurarPermiso(Usuario $usuario, string $slug, string $mensaje = 'No tiene permiso para esta acción'): void
    {
        if (!$usuario->puede($slug)) {
            throw new AppException($mensaje, HTTP_FORBIDDEN);
        }
    }

    /**
     * @param string[] $slugs
     */
    public function asegurarAlgunPermiso(Usuario $usuario, array $slugs, string $mensaje = 'No tiene permiso para esta acción'): void
    {
        if (!$usuario->puedeAlguno($slugs)) {
            throw new AppException($mensaje, HTTP_FORBIDDEN);
        }
    }

    /**
     * @return array{tipo:string,usuario:Usuario,sesion:Sesion,cliente:null,vehiculo:null}
     */
    public function exigirPermiso(string $slug, ?string $token = null, string $mensaje = 'No tiene permiso para esta acción'): array
    {
        $actor = $this->exigirUsuario($token);
        $this->asegurarPermiso($actor['usuario'], $slug, $mensaje);
        return $actor;
    }

    /**
     * @param string[] $slugs
     * @return array{tipo:string,usuario:Usuario,sesion:Sesion,cliente:null,vehiculo:null}
     */
    public function exigirAlgunPermiso(array $slugs, ?string $token = null, string $mensaje = 'No tiene permiso para esta acción'): array
    {
        $actor = $this->exigirUsuario($token);
        $this->asegurarAlgunPermiso($actor['usuario'], $slugs, $mensaje);
        return $actor;
    }

    /**
     * Orquesta la recuperación. El token lo genera Usuario::generarToken()
     * (no se duplica la lógica criptográfica aquí).
     */
    public function generarTokenRecuperacion(string $email, ?string $documento = null, int $horas = 2): ?string
    {
        $usuario = $this->usuarios->buscarPorCorreo($email);
        if (!$this->identidadRecuperacionValida($usuario, $documento)) {
            return null;
        }

        $token = $usuario->generarToken($horas);
        $this->usuarios->guardar($usuario);
        return $token;
    }

    /**
     * @return array{usuario:Usuario,token:string}|null
     */
    public function iniciarRecuperacion(string $email, ?string $documento = null, int $horas = 2): ?array
    {
        $usuario = $this->usuarios->buscarPorCorreo($email);
        if (!$this->identidadRecuperacionValida($usuario, $documento)) {
            return null;
        }
        $token = $usuario->generarToken($horas);
        $this->usuarios->guardar($usuario);
        return ['usuario' => $usuario, 'token' => $token];
    }

    private function identidadRecuperacionValida(?Usuario $usuario, ?string $documento): bool
    {
        if (!$usuario || !$usuario->isActivo()) {
            return false;
        }
        $pedido = preg_replace('/\D+/', '', (string) $documento) ?? '';
        if ($pedido === '') {
            return true;
        }
        $real = preg_replace('/\D+/', '', $usuario->getDocumento()) ?? '';
        return $real !== '' && hash_equals($real, $pedido);
    }

    /**
     * @param array{token?:string,password?:string,password_confirm?:string} $data
     */
    public function resetearPassword(array $data): Usuario
    {
        $token    = (string) ($data['token'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $confirm  = $data['password_confirm'] ?? $password;

        if ($token === '' || $password === '') {
            throw new AppException('Token y password son requeridos', HTTP_BAD_REQUEST);
        }
        if (strlen($password) < 6) {
            throw new AppException('El password debe tener al menos 6 caracteres', HTTP_BAD_REQUEST);
        }
        if ($password !== $confirm) {
            throw new AppException('Las contraseñas no coinciden', HTTP_BAD_REQUEST);
        }

        $usuario = $this->usuarios->buscarPorTokenRecuperacion($token);
        if (!$usuario || !$usuario->tokenRecuperacionEsValido($token)) {
            throw new AppException('Token de recuperación inválido o expirado', HTTP_BAD_REQUEST);
        }

        $usuario->cambiarPassword($password);
        return $this->usuarios->guardar($usuario);
    }

    /**
     * @param array{
     *   sesion?:Sesion,
     *   token?:string,
     *   id_sesion?:int,
     *   id_usuario?:int,
     *   accion:string,
     *   tablaAfectada:string,
     *   registroId:int,
     *   valoresAnteriores?:mixed,
     *   valoresNuevos?:mixed,
     *   ipAddress?:string,
     *   userAgent?:string
     * } $data
     */
    public function registrarLog(array $data): ?LogAuditoria
    {
        $sesion = $data['sesion'] ?? null;
        if (!$sesion instanceof Sesion && !empty($data['token'])) {
            $sesion = $this->sesiones->buscarPorToken((string) $data['token']);
        }
        if (!$sesion instanceof Sesion && !empty($data['id_sesion'])) {
            $sesion = $this->sesiones->buscarPorId((int) $data['id_sesion']);
        }

        if (!$sesion instanceof Sesion || $sesion->getIdSesion() === null) {
            return null;
        }

        $idUsuario = (int) ($data['id_usuario'] ?? $sesion->getIdUsuario());

        $log = new LogAuditoria(
            $idUsuario,
            (int) $sesion->getIdSesion(),
            (string) ($data['accion'] ?? ''),
            (string) ($data['tablaAfectada'] ?? ''),
            (int) ($data['registroId'] ?? 0),
            $data['valoresAnteriores'] ?? null,
            $data['valoresNuevos'] ?? null,
            $data['ipAddress'] ?? $sesion->getIpAddress(),
            $data['userAgent'] ?? null
        );

        return $this->logs->guardar($log);
    }
}
