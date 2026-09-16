<?php

require_once __DIR__ . '/../../services/Correo/CorreoService.php';

class ConfiguracionController
{
    private ConfiguracionService $configService;
    private AuthService $authService;
    private ?CorreoService $correo;

    public function __construct(
        ConfiguracionService $configService,
        AuthService $authService,
        ?CorreoService $correo = null
    ) {
        $this->configService = $configService;
        $this->authService   = $authService;
        $this->correo        = $correo;
    }

    public function obtener(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->configService->sembrarSiVacio((int) $actor['usuario']->getIdUsuario());
            $puedeVer = $actor['usuario']->puedeAlguno(['configuracion.ver', 'configuracion.editar']);
            if (!$puedeVer) {
                ApiResponse::ok([
                    'mapa'         => $this->configService->mapaOperativo(),
                    'es_admin'     => false,
                    'puede_editar' => false,
                    'items'        => [],
                ]);
                return;
            }
            ApiResponse::ok([
                'mapa'     => $this->configService->mapaPublico(),
                'es_admin' => $actor['usuario']->esAdministrador(),
                'puede_editar' => $actor['usuario']->puede('configuracion.editar'),
                'diagnostico_correo' => $this->correo ? $this->correo->diagnosticar() : null,
                'buzon' => $this->correo ? $this->correo->listarBuzon(12) : [],
                'items'    => array_map(function (Configuracion $c) {
                    $arr = $c->toArray();
                    if (in_array($c->getClave(), Configuracion::clavesSecretas(), true)) {
                        $arr['definido'] = trim((string) $arr['valor']) !== '';
                        $arr['valor'] = '';
                        $arr['valor_nativo'] = '';
                    }
                    return $arr;
                }, $this->configService->listar()),
            ]);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function actualizar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'PUT', 'PATCH']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso(
                $actor['usuario'],
                'configuracion.editar',
                'Solo el administrador puede cambiar la configuración'
            );
            $body = ApiRequest::jsonBody();
            $this->configService->actualizar($body, (int) $actor['usuario']->getIdUsuario());
            ApiResponse::ok($this->configService->mapaPublico(), 'Configuración actualizada');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function probarCorreo(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarPermiso(
                $actor['usuario'],
                'configuracion.editar',
                'Solo quien edita configuración puede probar el correo'
            );
            if (!$this->correo) {
                throw new AppException('Servicio de correo no disponible', HTTP_INTERNAL_ERROR);
            }
            $body = ApiRequest::jsonBody();
            $destino = trim((string) ($body['destino'] ?? $body['correo'] ?? ''));
            if ($destino === '') {
                $mapa = $this->configService->mapa();
                $destino = (string) ($mapa[Configuracion::CORREO_USUARIO] ?? $mapa[Configuracion::CORREO_REMITENTE] ?? '');
            }
            $envio = $this->correo->enviarPrueba($destino);
            $mensaje = (($envio['canal'] ?? '') === 'smtp')
                ? 'Correo de prueba enviado'
                : 'Prueba guardada en el buzón interno (aún no hay SMTP)';
            ApiResponse::ok($envio, $mensaje);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function logo(): void
    {
        try {
            $actor = $this->exigirSesion();
            $method = ApiRequest::method();
            $archivos = new ArchivoService();

            if ($method === 'GET') {
                $this->authService->asegurarPermiso(
                    $actor['usuario'],
                    'configuracion.ver',
                    'No puede ver la configuración'
                );
                $rel = $this->configService->rutaLogo();
                if ($rel === '') {
                    throw new AppException('No hay logo cargado', HTTP_NOT_FOUND);
                }
                $abs = $archivos->absoluto($rel);
                if (!is_file($abs)) {
                    throw new AppException('No hay logo cargado', HTTP_NOT_FOUND);
                }
                $mime = $archivos->mimeDeRuta($abs) ?: 'image/jpeg';
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($abs));
                header('Cache-Control: private, max-age=0, must-revalidate');
                readfile($abs);
                exit;
            }

            $this->authService->asegurarPermiso(
                $actor['usuario'],
                'configuracion.editar',
                'Solo el administrador puede cambiar el logo'
            );

            if ($method === 'DELETE') {
                $anterior = $this->configService->quitarLogo((int) $actor['usuario']->getIdUsuario());
                if ($anterior !== '') {
                    $archivos->eliminar($anterior);
                }
                ApiResponse::ok(['empresa_logo_set' => false], 'Logo quitado');
                return;
            }

            ApiRequest::requireMethod(['POST']);
            if (empty($_FILES['logo'])) {
                throw new AppException('Seleccione una imagen JPG o PNG', HTTP_BAD_REQUEST);
            }
            $info = $archivos->guardar($_FILES['logo'], 'logo');
            $ext = strtolower((string) ($info['extension'] ?? ''));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                $archivos->eliminar($info['ruta']);
                throw new AppException('El logo debe ser JPG o PNG', HTTP_BAD_REQUEST);
            }
            $anterior = $this->configService->guardarLogo($info['ruta'], (int) $actor['usuario']->getIdUsuario());
            if ($anterior !== '' && $anterior !== $info['ruta']) {
                $archivos->eliminar($anterior);
            }
            ApiResponse::ok(['empresa_logo_set' => true], 'Logo actualizado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function buzon(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirSesion();
            $this->authService->asegurarAlgunPermiso(
                $actor['usuario'],
                ['configuracion.ver', 'configuracion.editar'],
                'No puede ver la configuración'
            );
            if (!$this->correo) {
                throw new AppException('Servicio de correo no disponible', HTTP_INTERNAL_ERROR);
            }
            $id = trim((string) ($_GET['id'] ?? ''));
            if ($id !== '') {
                $item = $this->correo->leerBuzon($id);
                if (!$item) {
                    throw new AppException('Correo no encontrado en el buzón', HTTP_NOT_FOUND);
                }
                ApiResponse::ok($item);
                return;
            }
            ApiResponse::ok($this->correo->listarBuzon((int) ($_GET['limite'] ?? 20)));
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
