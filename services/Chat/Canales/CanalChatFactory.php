<?php

require_once __DIR__ . '/CanalChatInterface.php';
require_once __DIR__ . '/CanalInterno.php';
require_once __DIR__ . '/CanalWhatsApp.php';

/**
 * Punto único para resolver un canal. Añadir Telegram/SMS = nueva clase + un case aquí.
 */
class CanalChatFactory
{
    private CanalInterno $interno;
    private CanalWhatsApp $whatsapp;

    public function __construct(CanalInterno $interno, CanalWhatsApp $whatsapp)
    {
        $this->interno  = $interno;
        $this->whatsapp = $whatsapp;
    }

    public function obtener(string $codigo): CanalChatInterface
    {
        $clave = strtolower(trim($codigo));
        if ($clave === 'whatsapp') {
            return $this->whatsapp;
        }
        if ($clave === 'interno' || $clave === '') {
            return $this->interno;
        }
        throw new AppException('Canal de chat no soportado: ' . $codigo, HTTP_BAD_REQUEST);
    }

    public function whatsapp(): CanalWhatsApp
    {
        return $this->whatsapp;
    }

    /** @return array<int,array{codigo:string,nombre:string,configurado:bool}> */
    public function disponibles(): array
    {
        return [
            [
                'codigo'      => Conversacion::CANAL_INTERNO,
                'nombre'      => 'Chat interno',
                'configurado' => true,
            ],
            [
                'codigo'      => Conversacion::CANAL_WHATSAPP,
                'nombre'      => 'WhatsApp',
                'configurado' => $this->whatsapp->estaConfigurado(),
                'conectado'   => $this->whatsapp->estaConfigurado(),
            ],
        ];
    }
}
