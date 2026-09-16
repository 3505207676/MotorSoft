<?php

require_once __DIR__ . '/../../repositories/Dashboard/DashboardRepository.php';

class DashboardService
{
    private DashboardRepository $repo;
    private ChatService $chat;
    private ConfiguracionService $config;
    private AuthService $auth;

    public function __construct(
        DashboardRepository $repo,
        ChatService $chat,
        ConfiguracionService $config,
        AuthService $auth
    ) {
        $this->repo   = $repo;
        $this->chat   = $chat;
        $this->config = $config;
        $this->auth   = $auth;
    }

    /** @param array<string,mixed> $actor */
    public function resumen(array $actor, string $periodo = 'week'): array
    {
        $periodo = in_array($periodo, ['week', 'month', 'year'], true) ? $periodo : 'week';
        /** @var Usuario $usuario */
        $usuario = $actor['usuario'];
        $verFinanzas = $usuario->puede('contabilidad.ver');
        $verAuditoria = $usuario->puede('auditoria.ver');
        $conteo = $this->repo->conteoOrdenes();
        $ingresos = $verFinanzas ? $this->repo->ingresosHoyAyer() : ['hoy' => 0.0, 'ayer' => 0.0];
        $alertas = $this->alertas($actor);
        $stockBajo = $alertas['stock_bajo'];
        $citasHoy = $alertas['citas_hoy'];
        $factPend = $alertas['facturas_pendientes'];
        $chatNoLeidos = $alertas['chat_no_leidos'];
        $mapa = $this->config->mapaPublico();

        return [
            'vista' => [
                'finanzas'           => $verFinanzas,
                'auditoria'          => $verAuditoria,
                'configurar_taller'  => $usuario->puede('configuracion.editar'),
            ],
            'kpis' => [
                'vehiculos_taller'    => $this->repo->vehiculosEnTaller(),
                'ordenes_activas'     => $conteo['activas'],
                'ingresos_dia'        => $verFinanzas ? $ingresos['hoy'] : null,
                'ingresos_ayer'       => $verFinanzas ? $ingresos['ayer'] : null,
                'stock_bajo'          => $stockBajo,
                'citas_hoy'           => $citasHoy,
                'facturas_pendientes' => $factPend,
                'chat_no_leidos'      => $chatNoLeidos,
                'detalle_ordenes'     => $this->detalleOrdenes($conteo),
                'detalle_ingresos'    => $verFinanzas ? $this->detalleIngresos($ingresos['hoy'], $ingresos['ayer']) : null,
            ],
            'ordenes_recientes' => $this->repo->ordenesRecientes(8),
            'actividad'         => $verAuditoria
                ? array_map([$this, 'mapActividad'], $this->repo->actividad(8))
                : [],
            'citas_hoy_lista'   => $this->repo->citasDelDia(8),
            'mecanicos'         => $this->repo->mecanicos(),
            'grafico'           => $verFinanzas
                ? $this->armarGrafico($periodo, $this->repo->ingresosSerie($periodo))
                : ['periodo' => $periodo, 'etiquetas' => [], 'valores' => []],
            'notificaciones'    => $alertas['notificaciones'],
            'taller'            => [
                'nombre'    => (string) ($mapa[Configuracion::EMPRESA_NOMBRE] ?? 'Taller El Paisa'),
                'nit'       => (string) ($mapa[Configuracion::EMPRESA_NIT] ?? ''),
                'direccion' => (string) ($mapa[Configuracion::EMPRESA_DIRECCION] ?? ''),
                'telefono'  => (string) ($mapa[Configuracion::EMPRESA_TELEFONO] ?? ''),
            ],
        ];
    }

    /**
     * Alertas livianas para la campana de cualquier pantalla del personal.
     *
     * @param array<string,mixed> $actor
     * @return array<string,mixed>
     */
    public function alertas(array $actor): array
    {
        /** @var Usuario $usuario */
        $usuario = $actor['usuario'];
        $stock = $this->repo->stockBajo();
        $citas = $this->repo->citasHoy();
        $facturas = $this->repo->facturasPendientes();
        $chat = 0;
        try {
            $chat = $this->chat->noLeidos($actor);
        } catch (Throwable $e) {
            $chat = 0;
        }

        return [
            'stock_bajo'          => $stock,
            'citas_hoy'           => $citas,
            'facturas_pendientes' => $facturas,
            'chat_no_leidos'      => $chat,
            'notificaciones'      => $this->armarNotificaciones(
                $usuario->puedeAlguno(['inventario.ver', 'inventario.stock_bajo']) ? $stock : 0,
                $usuario->puede('agenda.ver') ? $citas : 0,
                $usuario->puede('facturas.ver') ? $facturas : 0,
                $usuario->puede('chat.ver') ? $chat : 0
            ),
        ];
    }

    /** @param array<string,mixed> $conteo */
    private function detalleOrdenes(array $conteo): string
    {
        $partes = [];
        if ($conteo['en_proceso'] > 0) {
            $partes[] = $conteo['en_proceso'] . ' en proceso';
        }
        if ($conteo['pendiente'] > 0) {
            $partes[] = $conteo['pendiente'] . ' pendientes';
        }
        if ($conteo['pendiente_pago'] > 0) {
            $partes[] = $conteo['pendiente_pago'] . ' por cobrar';
        }
        return $partes ? implode(' · ', $partes) : 'Sin órdenes abiertas';
    }

    private function detalleIngresos(float $hoy, float $ayer): string
    {
        if ($ayer <= 0 && $hoy <= 0) {
            return 'Sin cobros registrados hoy';
        }
        if ($ayer <= 0) {
            return 'Sin cobros ayer para comparar';
        }
        $delta = (($hoy - $ayer) / $ayer) * 100;
        $signo = $delta >= 0 ? '+' : '';
        return $signo . number_format($delta, 0, ',', '.') . '% vs ayer';
    }

    /** @param array<string,mixed> $row */
    private function mapActividad(array $row): array
    {
        $tabla = (string) $row['tabla_afectada'];
        $accion = strtoupper((string) $row['accion']);
        $id = (int) $row['registro_id'];
        $quien = (string) $row['usuario_nombre'];
        $texto = $quien . ' · ' . $accion . ' · ' . $tabla . ($id ? ' #' . $id : '');
        $icono = 'fa-history';
        $cls = 'bg-primary';
        if (stripos($tabla, 'Orden') !== false) {
            $icono = 'fa-clipboard-list';
            $cls = 'bg-primary';
            $texto = $quien . ' actualizó la orden #' . $id;
            if ($accion === 'CREATE') {
                $texto = $quien . ' creó la orden #' . $id;
            }
        } elseif (stripos($tabla, 'Factura') !== false) {
            $icono = 'fa-file-invoice-dollar';
            $cls = 'bg-success';
            $texto = $quien . ' registró la factura #' . $id;
        } elseif (stripos($tabla, 'Cita') !== false || stripos($tabla, 'Agenda') !== false) {
            $icono = 'fa-calendar';
            $cls = 'bg-info';
            $texto = $quien . ' gestionó una cita #' . $id;
        } elseif (stripos($tabla, 'Mensaje') !== false || stripos($tabla, 'Convers') !== false) {
            $icono = 'fa-comment';
            $cls = 'bg-success';
            $texto = $quien . ' envió un mensaje';
        } elseif (stripos($tabla, 'Producto') !== false || stripos($tabla, 'Stock') !== false) {
            $icono = 'fa-boxes';
            $cls = 'bg-warning';
            $texto = $quien . ' actualizó inventario';
        }
        return [
            'icono'  => $icono,
            'cls'    => $cls,
            'texto'  => $texto,
            'tiempo' => $row['fecha'],
        ];
    }

    /** @param array<int,array{clave:string,total:float}> $serie */
    private function armarGrafico(string $periodo, array $serie): array
    {
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $etiquetas = [];
        $valores = [];
        foreach ($serie as $i => $punto) {
            $valores[] = $punto['total'];
            if ($periodo === 'year') {
                $mes = (int) substr($punto['clave'], 5, 2);
                $etiquetas[] = $meses[max(1, $mes) - 1] ?? $punto['clave'];
            } elseif ($periodo === 'month') {
                $etiquetas[] = 'Sem ' . ($i + 1);
            } else {
                $ts = strtotime($punto['clave'] . ' 12:00:00');
                $etiquetas[] = $ts ? $dias[(int) date('w', $ts)] : $punto['clave'];
            }
        }
        return [
            'periodo'   => $periodo,
            'etiquetas' => $etiquetas,
            'valores'   => $valores,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function armarNotificaciones(int $stock, int $citas, int $facturas, int $chat): array
    {
        $items = [];
        if ($stock > 0) {
            $items[] = [
                'tipo' => 'stock',
                'texto' => $stock . ' producto(s) con stock bajo',
                'href' => '../inventario/index.php',
            ];
        }
        if ($citas > 0) {
            $items[] = [
                'tipo' => 'citas',
                'texto' => $citas . ' cita(s) programada(s) hoy',
                'href' => '../agenda/index.php#citas',
            ];
        }
        if ($facturas > 0) {
            $items[] = [
                'tipo' => 'facturas',
                'texto' => $facturas . ' factura(s) pendiente(s) de cobro',
                'href' => '../facturacion/index.php?estado=Pendiente',
            ];
        }
        if ($chat > 0) {
            $items[] = [
                'tipo' => 'chat',
                'texto' => $chat . ' mensaje(s) sin leer',
                'href' => '../chat/index-trabajadores.php',
            ];
        }
        return $items;
    }
}
