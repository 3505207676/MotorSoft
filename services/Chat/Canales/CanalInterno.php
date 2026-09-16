<?php

require_once __DIR__ . '/CanalChatInterface.php';
require_once __DIR__ . '/../../../repositories/Chat/MensajeRepository.php';
require_once __DIR__ . '/../../../repositories/Chat/ConversacionRepository.php';

class CanalInterno implements CanalChatInterface
{
    private MensajeRepository $mensajes;
    private ConversacionRepository $conversaciones;

    public function __construct(MensajeRepository $mensajes, ConversacionRepository $conversaciones)
    {
        $this->mensajes        = $mensajes;
        $this->conversaciones  = $conversaciones;
    }

    public function codigo(): string
    {
        return Conversacion::CANAL_INTERNO;
    }

    public function estaConfigurado(): bool
    {
        return true;
    }

    public function enviar(Conversacion $conversacion, Mensaje $borrador, array $actor): array
    {
        $guardado = $this->mensajes->guardar($borrador);
        $idConv = (int) $conversacion->getIdConversacion();
        if ($idConv > 0) {
            $this->conversaciones->tocarUltimoMensaje($idConv, $guardado->getFechaHora());
        }
        return ['mensaje' => $guardado, 'id_externo' => null];
    }
}
