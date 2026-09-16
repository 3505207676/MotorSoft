<?php

require_once __DIR__ . '/../../models/RRHH/Nomina.php';
require_once __DIR__ . '/../../models/Facturacion/Factura.php';
require_once __DIR__ . '/../Facturacion/FacturacionService.php';

class ReportesService
{
    private const ESTADOS_CERRADOS = ['Completada', 'Cancelada', 'Finalizada'];

    private OrdenesService $ordenes;
    private NominaRepository $nominas;
    private FacturacionService $facturacion;
    private AuthService $auth;

    public function __construct(
        OrdenesService $ordenes,
        NominaRepository $nominas,
        FacturacionService $facturacion,
        AuthService $auth
    ) {
        $this->ordenes     = $ordenes;
        $this->nominas     = $nominas;
        $this->facturacion = $facturacion;
        $this->auth        = $auth;
    }

    /** @return array<string,mixed> */
    public function ordenes(array $actor, array $query = []): array
    {
        $this->exigir($actor, 'reportes.ordenes');
        $rango = $this->rango($query);
        $lista = [];
        foreach ($this->ordenes->listarOrdenesComoArray([
            'desde' => $rango['desde'],
            'hasta' => $rango['hasta'],
        ]) as $orden) {
            $lista[] = $this->filaOrden($orden);
        }

        $porEstado = [];
        $valor = 0.0;
        $valorCompletadas = 0.0;
        foreach ($lista as $fila) {
            $estado = (string) ($fila['estado'] ?? '');
            $porEstado[$estado] = (int) ($porEstado[$estado] ?? 0) + 1;
            $total = (float) ($fila['total_general'] ?? 0);
            $valor += $total;
            if (in_array($estado, ['Completada', 'Finalizada'], true)) {
                $valorCompletadas += $total;
            }
        }
        $activas = 0;
        foreach ($porEstado as $estado => $n) {
            if (!in_array((string) $estado, self::ESTADOS_CERRADOS, true)) {
                $activas += $n;
            }
        }

        return [
            'periodo' => $rango,
            'resumen' => [
                'total'              => count($lista),
                'activas'            => $activas,
                'pendiente'          => (int) ($porEstado['Pendiente'] ?? 0) + (int) ($porEstado['Diagnóstico'] ?? 0),
                'en_proceso'         => (int) ($porEstado['En Proceso'] ?? 0) + (int) ($porEstado['En Progreso'] ?? 0),
                'pendiente_pago'     => (int) ($porEstado['Pendiente Pago'] ?? 0),
                'completadas'        => (int) ($porEstado['Completada'] ?? 0) + (int) ($porEstado['Finalizada'] ?? 0),
                'canceladas'         => (int) ($porEstado['Cancelada'] ?? 0),
                'valor_total'        => round($valor, 2),
                'valor_completadas'  => round($valorCompletadas, 2),
                'por_estado'         => $porEstado,
            ],
            'ordenes' => $lista,
        ];
    }

    /** @return array<string,mixed> */
    public function facturacion(array $actor, array $query = []): array
    {
        $this->exigir($actor, 'reportes.ordenes');
        $rango = $this->rango($query);
        $facturas = $this->facturacion->listarFacturas([
            'desde'        => $rango['desde'],
            'hasta'        => $rango['hasta'],
            'con_detalles' => false,
        ]);

        $filas = [];
        $emitidas = 0;
        $pagadas = 0;
        $pendientes = 0;
        $anuladas = 0;
        $cobrado = 0.0;
        $porCobrar = 0.0;
        $porMetodo = [];
        $porTipo = [];

        foreach ($facturas as $factura) {
            $arr = $factura->toArray();
            $fila = [
                'id_factura'    => $arr['id_factura'] ?? null,
                'numero'        => $arr['numero'] ?? null,
                'cliente'       => $arr['cliente'] ?? null,
                'fecha_emision' => $arr['fecha_emision'] ?? null,
                'subtotal'      => (float) ($arr['subtotal'] ?? 0),
                'iva'           => (float) ($arr['iva'] ?? $arr['IVA'] ?? 0),
                'total'         => (float) ($arr['total'] ?? 0),
                'metodo_pago'   => $arr['metodo_pago'] ?? '',
                'estado'        => $arr['estado'] ?? '',
                'tipo_factura'  => $arr['tipo_factura'] ?? '',
            ];
            $filas[] = $fila;
            $emitidas++;
            $estado = (string) ($fila['estado'] ?? '');
            $total = (float) ($fila['total'] ?? 0);
            $tipo = (string) ($fila['tipo_factura'] ?? '—');
            $porTipo[$tipo] = (int) ($porTipo[$tipo] ?? 0) + 1;

            if ($estado === Factura::PAGADA) {
                $pagadas++;
                $cobrado += $total;
                $metodo = (string) ($fila['metodo_pago'] ?? '—');
                $porMetodo[$metodo] = (float) ($porMetodo[$metodo] ?? 0) + $total;
            } elseif ($estado === Factura::ANULADA) {
                $anuladas++;
            } else {
                $pendientes++;
                $porCobrar += $total;
            }
        }

        ksort($porMetodo, SORT_NATURAL | SORT_FLAG_CASE);
        ksort($porTipo, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'periodo'   => $rango,
            'resumen'   => [
                'emitidas'    => $emitidas,
                'pagadas'     => $pagadas,
                'pendientes'  => $pendientes,
                'anuladas'    => $anuladas,
                'cobrado'     => round($cobrado, 2),
                'por_cobrar'  => round($porCobrar, 2),
                'por_metodo'  => $porMetodo,
                'por_tipo'    => $porTipo,
            ],
            'facturas'  => $filas,
        ];
    }

    /** @return array<string,mixed> */
    public function nomina(array $actor, array $query = []): array
    {
        $this->exigir($actor, 'reportes.nomina');
        $rango = $this->rango($query);
        $lista = $this->nominas->listar([
            'desde' => $rango['desde'],
            'hasta' => $rango['hasta'],
        ]);
        $filas = [];
        $total = 0.0;
        foreach ($lista as $nomina) {
            $fila = $nomina->toArray();
            unset($fila['detalle'], $fila['contrato']);
            $filas[] = $fila;
            $total += (float) ($fila['total_neto'] ?? 0);
        }

        return [
            'periodo' => $rango,
            'resumen' => [
                'pagos'     => count($filas),
                'total'     => round($total, 2),
                'promedio'  => count($filas) > 0 ? round($total / count($filas), 2) : 0.0,
            ],
            'nominas' => $filas,
        ];
    }

    /**
     * @param array<string,mixed> $query
     * @return array{desde:string,hasta:string}
     */
    public function rango(array $query): array
    {
        $desde = trim((string) ($query['desde'] ?? ''));
        $hasta = trim((string) ($query['hasta'] ?? ''));
        if (!$this->esFecha($desde)) {
            $desde = date('Y-m-01');
        }
        if (!$this->esFecha($hasta)) {
            $hasta = date('Y-m-d');
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $inicio = new DateTime($desde);
        $fin = new DateTime($hasta);
        if ($inicio->diff($fin)->days > 730) {
            throw new AppException('El rango no puede superar dos años', HTTP_BAD_REQUEST);
        }
        return ['desde' => $desde, 'hasta' => $hasta];
    }

    /** @param array<string,mixed> $orden */
    private function filaOrden(array $orden): array
    {
        $mecanico = $orden['mecanico'] ?? null;
        return [
            'id_orden'         => $orden['id_orden'] ?? null,
            'estado'           => $orden['estado'] ?? '',
            'cliente_nombre'   => $orden['cliente_nombre'] ?? null,
            'placa'            => $orden['placa'] ?? null,
            'vehiculo_label'   => $orden['vehiculo_label'] ?? null,
            'fecha_ingreso'    => $orden['fecha_ingreso'] ?? null,
            'fecha_salida'     => $orden['fecha_salida'] ?? null,
            'total_general'    => (float) ($orden['total_general'] ?? $orden['precio'] ?? 0),
            'mecanico_nombre'  => is_array($mecanico) ? ($mecanico['nombre'] ?? null) : null,
        ];
    }

    private function esFecha(string $valor): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return false;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $valor);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $valor;
    }

    private function exigir(array $actor, string $permiso): void
    {
        if (($actor['tipo'] ?? '') !== 'usuario' || !$actor['usuario']) {
            throw new AppException('Solo el personal del taller puede ver reportes', HTTP_FORBIDDEN);
        }
        $this->auth->asegurarPermiso($actor['usuario'], $permiso, 'No puede consultar este reporte');
    }
}
