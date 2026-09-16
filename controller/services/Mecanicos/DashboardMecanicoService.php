<?php

require_once __DIR__ . '/../../repositories/Dashboard/DashboardRepository.php';

/**
 * Resumen del panel mecánico: solo KPIs y listas de ese usuario.
 */
class DashboardMecanicoService
{
    private DashboardRepository $repo;
    private ChatService $chat;
    private ConfiguracionService $config;

    public function __construct(
        DashboardRepository $repo,
        ChatService $chat,
        ConfiguracionService $config
    ) {
        $this->repo   = $repo;
        $this->chat   = $chat;
        $this->config = $config;
    }

    /** @param array<string,mixed> $actor */
    public function resumen(array $actor): array
    {
        /** @var Usuario $usuario */
        $usuario = $actor['usuario'];
        $id = (int) $usuario->getIdUsuario();
        $conteo = $this->repo->conteoOrdenesMecanico($id);
        $chatNoLeidos = 0;
        try {
            $chatNoLeidos = $this->chat->noLeidos($actor);
        } catch (Throwable $e) {
            $chatNoLeidos = 0;
        }
        $mapa = $this->config->mapaPublico();

        return [
            'kpis' => [
                'ordenes_asignadas' => $conteo['total'],
                'en_proceso'        => $conteo['en_proceso'],
                'pendientes'        => $conteo['pendiente'],
                'completadas'       => $conteo['completadas'],
                'chat_no_leidos'    => $chatNoLeidos,
                'stock_bajo'        => $usuario->puede('inventario.ver') ? $this->repo->stockBajo() : 0,
                'citas_hoy'         => $this->repo->citasHoyMecanico($id),
            ],
            'ordenes_recientes' => $this->repo->ordenesRecientesMecanico($id, 5),
            'citas_hoy_lista'   => $this->repo->citasDelDiaMecanico($id, 8),
            'taller'            => [
                'nombre' => (string) ($mapa[Configuracion::EMPRESA_NOMBRE] ?? 'Taller El Paisa'),
            ],
        ];
    }
}
