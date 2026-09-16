<?php

/**
 * Registro de eventos WhatsApp (verify, inbound, simulado, error).
 * Vive en uploads/whatsapp/ y no se sirve por Apache.
 */
class BuzonWhatsApp
{
    private const MAX = 40;

    private string $dir;

    public function __construct(?string $raizUploads = null)
    {
        $raiz = $raizUploads ?: dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'uploads';
        $this->dir = $raiz . DIRECTORY_SEPARATOR . 'whatsapp';
    }

    /**
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    public function guardar(string $tipo, bool $ok, string $detalle = '', array $extra = []): array
    {
        $this->asegurarDir();
        $meta = array_merge($extra, [
            'id'      => date('YmdHis') . '_' . bin2hex(random_bytes(3)),
            'fecha'   => date('Y-m-d H:i:s'),
            'tipo'    => $tipo,
            'ok'      => $ok,
            'detalle' => $detalle,
        ]);
        $lista = $this->leerIndice();
        array_unshift($lista, $meta);
        $lista = array_slice($lista, 0, self::MAX);
        $json = json_encode($lista, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (@file_put_contents($this->rutaIndice(), $json, LOCK_EX) === false) {
            throw new AppException('No se pudo guardar el registro de WhatsApp', HTTP_INTERNAL_ERROR);
        }
        return $meta;
    }

    /** @return array<int,array<string,mixed>> */
    public function listar(int $limite = 20): array
    {
        $limite = max(1, min(40, $limite));
        return array_slice($this->leerIndice(), 0, $limite);
    }

    private function asegurarDir(): void
    {
        if (!is_dir($this->dir) && !mkdir($this->dir, 0755, true) && !is_dir($this->dir)) {
            throw new AppException('No se pudo crear el registro de WhatsApp', HTTP_INTERNAL_ERROR);
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
        $data = json_decode((string) file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }

    private function rutaIndice(): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . 'index.json';
    }
}
