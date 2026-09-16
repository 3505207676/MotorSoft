<?php

class ApiResponse
{
    public static function json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Vary: Authorization');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function ok($data = null, string $mensaje = 'OK', int $code = 200): void
    {
        $payload = [
            'success' => true,
            'mensaje' => $mensaje,
        ];
        if ($data !== null) {
            $payload['data'] = $data;
        }
        self::json($payload, $code);
    }

    public static function error(string $mensaje, int $code = 400, array $extra = []): void
    {
        self::json(array_merge([
            'success' => false,
            'mensaje' => $mensaje,
        ], $extra), $code);
    }

    public static function fromException(Throwable $e): void
    {
        $code = (int) $e->getCode();
        if ($code < 400 || $code > 599) {
            $code = HTTP_INTERNAL_ERROR;
        }
        $mensaje = $e instanceof AppException
            ? $e->getMessage()
            : 'Error interno del servidor';
        if (defined('ENTORNO') && ENTORNO === 'development' && !$e instanceof AppException) {
            $mensaje = $e->getMessage();
        }
        self::error($mensaje, $code);
    }
}
