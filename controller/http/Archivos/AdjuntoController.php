<?php

class AdjuntoController
{
    private AdjuntoService $adjuntos;
    private AuthService $authService;

    public function __construct(AdjuntoService $adjuntos, AuthService $authService)
    {
        $this->adjuntos    = $adjuntos;
        $this->authService = $authService;
    }

    public function listar(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $q = ApiRequest::query();
            $tipo = (string) ($q['entidad_tipo'] ?? $q['tipo'] ?? '');
            $id = (int) ($q['entidad_id'] ?? $q['id_entidad'] ?? 0);
            $lista = $this->adjuntos->listar($actor, $tipo, $id);
            ApiResponse::ok(array_map(static fn (Adjunto $a) => $a->toArray(), $lista));
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function subir(): void
    {
        try {
            ApiRequest::requireMethod(['POST']);
            $actor = $this->exigirActor();
            $actor['token'] = ApiRequest::bearerToken();
            $actor['ip'] = ApiRequest::ip();
            if (empty($_FILES['archivo']) || !is_array($_FILES['archivo'])) {
                throw new AppException('Seleccione un archivo', HTTP_BAD_REQUEST);
            }
            $tipo = (string) ($_POST['entidad_tipo'] ?? $_POST['tipo'] ?? '');
            $id = (int) ($_POST['entidad_id'] ?? $_POST['id_entidad'] ?? 0);
            $adj = $this->adjuntos->subir($actor, $_FILES['archivo'], $tipo, $id);
            ApiResponse::ok($adj->toArray(), 'Archivo subido', HTTP_CREATED);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function ver(): void
    {
        try {
            ApiRequest::requireMethod(['GET']);
            $actor = $this->exigirActor();
            $id = (int) (ApiRequest::query()['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('Archivo requerido', HTTP_BAD_REQUEST);
            }
            $this->adjuntos->servir($actor, $id);
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    public function eliminar(): void
    {
        try {
            ApiRequest::requireMethod(['POST', 'DELETE']);
            $actor = $this->exigirActor();
            $body = ApiRequest::jsonBody();
            $id = (int) ($body['id_adjunto'] ?? $body['id'] ?? $_GET['id'] ?? 0);
            if ($id < 1) {
                throw new AppException('Archivo requerido', HTTP_BAD_REQUEST);
            }
            $this->adjuntos->eliminar($actor, $id);
            ApiResponse::ok(null, 'Archivo eliminado');
        } catch (Throwable $e) {
            ApiResponse::fromException($e);
        }
    }

    /** @return array{tipo:string,usuario:?Usuario,sesion:?Sesion,cliente:?Cliente} */
    private function exigirActor(): array
    {
        return $this->authService->resolverActor(ApiRequest::bearerToken());
    }
}
