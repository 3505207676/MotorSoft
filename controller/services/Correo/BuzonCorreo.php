<?php

/**
 * Copia local de correos enviados (o guardados cuando no hay SMTP).
 * Los HTML viven en uploads/correo/ y no se sirven por Apache (.htaccess).
 */
class BuzonCorreo
{
    private const MAX = 40;

    private string $dir;

    public function __construct(?string $raizUploads = null)
    {
        $raiz = $raizUploads ?: dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';
        $this->dir = $raiz . DIRECTORY_SEPARATOR . 'correo';
    }

    /**
     * @return array{id:string,fecha:string,para:string,asunto:string,canal:string,ok:bool,error:?string}
     */
    public function guardar(string $para, string $asunto, string $html, string $canal, ?string $error = null): array
    {
        $this->asegurarDir();
        $id = date('YmdHis') . '_' . bin2hex(random_bytes(4));
        $meta = [
            'id'     => $id,
            'fecha'  => date('Y-m-d H:i:s'),
            'para'   => $para,
            'asunto' => $asunto,
            'canal'  => $canal,
            'ok'     => $error === null,
            'error'  => $error,
        ];
        $htmlPath = $this->dir . DIRECTORY_SEPARATOR . $id . '.html';
        if (@file_put_contents($htmlPath, $html, LOCK_EX) === false) {
            throw new AppException('No se pudo guardar el correo en el buzón interno', HTTP_INTERNAL_ERROR);
        }
        $lista = $this->leerIndice();
        array_unshift($lista, $meta);
        $lista = array_slice($lista, 0, self::MAX);
        $this->escribirIndice($lista);
        $this->purgar($lista);
        return $meta;
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $limite = 20): array
    {
        $limite = max(1, min(40, $limite));
        return array_slice($this->leerIndice(), 0, $limite);
    }

    /** @return array<string,mixed>|null */
    public function obtener(string $id): ?array
    {
        $id = $this->idSeguro($id);
        if ($id === '') {
            return null;
        }
        $meta = null;
        foreach ($this->leerIndice() as $item) {
            if (($item['id'] ?? '') === $id) {
                $meta = $item;
                break;
            }
        }
        if ($meta === null) {
            return null;
        }
        $abs = $this->dir . DIRECTORY_SEPARATOR . $id . '.html';
        $meta['html'] = is_file($abs) ? (string) file_get_contents($abs) : '';
        return $meta;
    }

    private function asegurarDir(): void
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0755, true) && !is_dir($this->dir)) {
            throw new AppException('No se pudo crear el buzón de correo', HTTP_INTERNAL_ERROR);
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function leerIndice(): array
    {
        $this->asegurarDir();
        $path = $this->rutaIndice();
        if (!is_file($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<int,array<string,mixed>> $lista */
    private function escribirIndice(array $lista): void
    {
        $json = json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (@file_put_contents($this->rutaIndice(), $json, LOCK_EX) === false) {
            throw new AppException('No se pudo actualizar el buzón de correo', HTTP_INTERNAL_ERROR);
        }
    }

    /** @param array<int,array<string,mixed>> $vivos */
    private function purgar(array $vivos): void
    {
        $ok = [];
        foreach ($vivos as $item) {
            $id = $this->idSeguro((string) ($item['id'] ?? ''));
            if ($id !== '') {
                $ok[$id] = true;
            }
        }
        $files = glob($this->dir . DIRECTORY_SEPARATOR . '*.html') ?: [];
        foreach ($files as $file) {
            $id = $this->idSeguro((string) pathinfo($file, PATHINFO_FILENAME));
            if ($id === '' || isset($ok[$id])) {
                continue;
            }
            @unlink($file);
        }
    }

    private function rutaIndice(): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . 'index.json';
    }

    private function idSeguro(string $id): string
    {
        return preg_match('/^[a-zA-Z0-9_-]{8,40}$/', $id) ? $id : '';
    }
}
