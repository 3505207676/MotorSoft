<?php

/**
 * Cliente SMTP mínimo (AUTH LOGIN + STARTTLS/SSL). Sin dependencias.
 */
class SmtpCliente
{
    /**
     * @param array{
     *   host:string,puerto:int,usuario:string,password:string,
     *   cifrado:string,remitente:string,remitente_nombre:string
     * } $cfg
     */
    public function enviar(array $cfg, string $para, string $asunto, string $html): void
    {
        $host = trim($cfg['host']);
        $puerto = (int) $cfg['puerto'];
        $cifrado = strtolower(trim($cfg['cifrado'] ?? 'tls'));
        $usuario = (string) $cfg['usuario'];
        $clave = (string) $cfg['password'];
        $from = trim($cfg['remitente']);
        $fromNom = trim($cfg['remitente_nombre']);
        if ($host === '' || $puerto < 1 || $from === '' || $para === '') {
            throw new AppException('Faltan datos SMTP (host, puerto o remitente)', HTTP_BAD_REQUEST);
        }

        $prefijo = $cifrado === 'ssl' ? 'ssl://' : '';
        $remote = $prefijo . $host . ':' . $puerto;
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        if (!is_resource($fp)) {
            throw new AppException('No se pudo conectar al servidor de correo: ' . ($errstr ?: 'error ' . $errno), HTTP_INTERNAL_ERROR);
        }
        stream_set_timeout($fp, 20);

        try {
            $this->esperar($fp, 220);
            $ehlo = $this->ehloHost();
            $this->comando($fp, 'EHLO ' . $ehlo, 250);
            if ($cifrado === 'tls') {
                $this->comando($fp, 'STARTTLS', 220);
                $ok = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($ok !== true) {
                    throw new AppException('No se pudo negociar TLS con el servidor de correo', HTTP_INTERNAL_ERROR);
                }
                $this->comando($fp, 'EHLO ' . $ehlo, 250);
            }
            if ($usuario !== '') {
                $this->comando($fp, 'AUTH LOGIN', 334);
                $this->comando($fp, base64_encode($usuario), 334);
                $this->comando($fp, base64_encode($clave), 235);
            }
            $this->comando($fp, 'MAIL FROM:<' . $from . '>', 250);
            $this->comando($fp, 'RCPT TO:<' . $para . '>', 250);
            $this->comando($fp, 'DATA', 354);
            $cuerpo = $this->armarMime($from, $fromNom, $para, $asunto, $html);
            fwrite($fp, $this->dotStuff($cuerpo) . "\r\n.\r\n");
            $this->esperar($fp, 250);
            $this->comando($fp, 'QUIT', 221);
        } finally {
            fclose($fp);
        }
    }

    private function ehloHost(): string
    {
        $h = gethostname();
        return $h !== false && $h !== '' ? $h : 'localhost';
    }

    private function comando($fp, string $linea, int $codigo): void
    {
        fwrite($fp, $linea . "\r\n");
        $this->esperar($fp, $codigo);
    }

    private function esperar($fp, int $codigo): void
    {
        $buf = '';
        while (!feof($fp)) {
            $linea = fgets($fp, 8192);
            if ($linea === false) {
                break;
            }
            $buf .= $linea;
            if (preg_match('/^\d{3} /', $linea)) {
                break;
            }
        }
        $recibido = (int) substr($buf, 0, 3);
        if ($recibido !== $codigo) {
            $msg = trim(preg_replace('/^\d{3}[- ]/', '', $buf));
            throw new AppException(
                $msg !== '' ? 'SMTP: ' . $msg : 'El servidor de correo rechazó la operación',
                HTTP_INTERNAL_ERROR
            );
        }
    }

    private function armarMime(string $from, string $fromNom, string $para, string $asunto, string $html): string
    {
        $fromHeader = $fromNom !== ''
            ? $this->rfc2047($fromNom) . ' <' . $from . '>'
            : $from;
        $texto = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
        $boundary = 'b' . bin2hex(random_bytes(8));
        $headers = [
            'From: ' . $fromHeader,
            'To: ' . $para,
            'Reply-To: ' . $from,
            'Subject: ' . $this->rfc2047($asunto),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@motorsoft>',
        ];
        $htmlPart = preg_match('/^\s*<(!DOCTYPE|html)/i', $html)
            ? $html
            : '<!DOCTYPE html><html lang="es"><body style="font-family:Segoe UI,Arial,sans-serif;color:#1e293b">'
                . $html . '</body></html>';
        return implode("\r\n", $headers) . "\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
            . $texto . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $htmlPart . "\r\n"
            . '--' . $boundary . '--';
    }

    private function rfc2047(string $texto): string
    {
        if (preg_match('/^[\x20-\x7e]*$/', $texto)) {
            return $texto;
        }
        return '=?UTF-8?B?' . base64_encode($texto) . '?=';
    }

    private function dotStuff(string $cuerpo): string
    {
        $cuerpo = str_replace(["\r\n", "\r"], "\n", $cuerpo);
        $lineas = explode("\n", $cuerpo);
        foreach ($lineas as $i => $linea) {
            if (isset($linea[0]) && $linea[0] === '.') {
                $lineas[$i] = '.' . $linea;
            }
        }
        return implode("\r\n", $lineas);
    }
}
