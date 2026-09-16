<?php

class ApiRequest
{
    private static ?string $rawBody = null;

    public static function jsonBody(): array
    {
        if (self::$rawBody === null) {
            self::$rawBody = (string) file_get_contents('php://input');
        }
        $data = json_decode(self::$rawBody, true);
        if (is_array($data)) {
            return $data;
        }
        return $_POST ?: [];
    }

    public static function query(): array
    {
        return $_GET;
    }

    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? $_SERVER['Authorization']
            ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }
        $body = self::jsonBody();
        if (!empty($body['token'])) {
            return (string) $body['token'];
        }
        if (!empty($_GET['token'])) {
            return (string) $_GET['token'];
        }
        return null;
    }

    public static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }

    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function requireMethod(array $allowed): void
    {
        if (!in_array(self::method(), $allowed, true)) {
            throw new AppException('Método no permitido', HTTP_BAD_REQUEST);
        }
    }
}
