<?php

require_once __DIR__ . '/../../../models/Chat/Conversacion.php';
require_once __DIR__ . '/../../../models/Chat/Mensaje.php';

interface CanalChatInterface
{
    public function codigo(): string;

    public function estaConfigurado(): bool;

    /**
     * Entrega el mensaje por el canal (interno = solo persistir; WhatsApp = API + persistir).
     *
     * @return array{mensaje:Mensaje, id_externo:?string}
     */
    public function enviar(Conversacion $conversacion, Mensaje $borrador, array $actor): array;
}
