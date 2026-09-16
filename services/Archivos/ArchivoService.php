<?php

class ArchivoService
{
    private string $raiz;

    public function __construct(?string $raiz = null)
    {
        $this->raiz = $raiz ?: dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';
    }

    /**
     * @param array<string,mixed> $file
     * @return array{nombre_original:string,nombre_sistema:string,ruta:string,extension:string,peso_bytes:int,mime:string}
     */
    public function guardar(array $file, string $carpeta): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new AppException('No se pudo leer el archivo', HTTP_BAD_REQUEST);
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new AppException('Archivo inválido', HTTP_BAD_REQUEST);
        }
        $peso = (int) ($file['size'] ?? 0);
        if ($peso < 1) {
            throw new AppException('El archivo está vacío', HTTP_BAD_REQUEST);
        }
        if ($peso > UPLOAD_MAX_SIZE) {
            throw new AppException('El archivo supera el límite de 5 MB', HTTP_BAD_REQUEST);
        }

        $original = $this->nombreSeguro((string) ($file['name'] ?? 'archivo'));
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, UPLOAD_ALLOWED_TYPES, true)) {
            throw new AppException('Solo se permiten imágenes JPG/PNG/GIF o PDF', HTTP_BAD_REQUEST);
        }

        $mime = $this->mimeReal($tmp);
        $this->validarMime($ext, $mime);

        $carpeta = preg_replace('/[^a-z0-9_-]/i', '', $carpeta) ?: 'general';
        $dir = $this->raiz . DIRECTORY_SEPARATOR . $carpeta;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new AppException('No se pudo crear la carpeta de archivos', HTTP_INTERNAL_ERROR);
        }

        $sistema = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destino = $dir . DIRECTORY_SEPARATOR . $sistema;
        if (!move_uploaded_file($tmp, $destino)) {
            throw new AppException('No se pudo guardar el archivo', HTTP_INTERNAL_ERROR);
        }

        $relativa = $carpeta . '/' . $sistema;
        return [
            'nombre_original' => $original,
            'nombre_sistema'  => $sistema,
            'ruta'            => $relativa,
            'extension'       => $ext,
            'peso_bytes'      => $peso,
            'mime'            => $mime,
        ];
    }

    public function absoluto(string $rutaRelativa): string
    {
        $limpia = str_replace(['\\', '..'], ['/', ''], $rutaRelativa);
        $limpia = ltrim($limpia, '/');
        return $this->raiz . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $limpia);
    }

    public function eliminar(string $rutaRelativa): void
    {
        $abs = $this->absoluto($rutaRelativa);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    public function mimeDeRuta(string $abs): string
    {
        if (!is_file($abs)) {
            return 'application/octet-stream';
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($abs) ?: 'application/octet-stream';
    }

    private function nombreSeguro(string $nombre): string
    {
        $base = basename(str_replace(["\0", '\\'], '', $nombre));
        $base = preg_replace('/[^\p{L}\p{N}\.\-\_ ]+/u', '', $base) ?: 'archivo';
        return mb_substr($base, 0, 180);
    }

    private function mimeReal(string $tmp): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmp) ?: '';
    }

    private function validarMime(string $ext, string $mime): void
    {
        $ok = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'pdf'  => ['application/pdf'],
        ];
        $permitidos = $ok[$ext] ?? [];
        if ($permitidos && !in_array($mime, $permitidos, true)) {
            throw new AppException('El tipo de archivo no coincide con la extensión', HTTP_BAD_REQUEST);
        }
    }
}
