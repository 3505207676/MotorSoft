<?php

/**
 * Convierte un log técnico (tabla + JSON) en una frase y cambios legibles.
 */
class AuditoriaPresentador
{
    /** @return array<string,mixed> */
    public static function presentar(LogAuditoria $log): array
    {
        $base = $log->toArray();
        $accion = strtoupper($log->getAccion());
        $modulo = in_array($accion, ['LOGIN', 'LOGOUT'], true)
            ? ['clave' => 'acceso', 'etiqueta' => 'Acceso']
            : self::modulo($log->getTablaAfectada());
        $antes = self::plano(is_array($base['valores_anteriores'] ?? null) ? $base['valores_anteriores'] : []);
        $despues = self::plano(is_array($base['valores_nuevos'] ?? null) ? $base['valores_nuevos'] : []);
        $sujeto = self::sujeto($log->getTablaAfectada(), $despues, $antes);
        $verbo = self::verbo($log->getAccion(), $log->getTablaAfectada(), $despues);
                        $cambios = self::cambios($log->getAccion(), $antes, $despues);

        return array_merge($base, [
            'modulo'           => $modulo['clave'],
            'modulo_etiqueta'  => $modulo['etiqueta'],
            'accion_etiqueta'  => $verbo,
            'sujeto'           => $sujeto,
            'resumen'          => self::resumen($log->getAccion(), $log->getTablaAfectada(), $verbo, $sujeto, $despues, $cambios),
            'cambios'          => $cambios,
        ]);
    }

    /** @return array{clave:string,etiqueta:string} */
    public static function modulo(string $tabla): array
    {
        $t = strtolower(trim($tabla));
        $mapa = self::mapaTablas();
        return $mapa[$t] ?? ['clave' => 'otros', 'etiqueta' => $tabla !== '' ? $tabla : 'Sistema'];
    }

    /** @return array<int,array{id:string,etiqueta:string}> */
    public static function catalogoModulos(): array
    {
        $out = ['acceso' => 'Acceso'];
        foreach (self::mapaTablas() as $mod) {
            $out[$mod['clave']] = $mod['etiqueta'];
        }
        $out['otros'] = 'Otros';
        asort($out, SORT_NATURAL | SORT_FLAG_CASE);
        $lista = [];
        foreach ($out as $id => $etiqueta) {
            $lista[] = ['id' => $id, 'etiqueta' => $etiqueta];
        }
        return $lista;
    }

    /** @return string[] */
    public static function tablasDeModulo(string $clave): array
    {
        $clave = strtolower(trim($clave));
        $tablas = [];
        foreach (self::mapaTablas() as $tabla => $mod) {
            if ($mod['clave'] === $clave) {
                $tablas[] = $tabla;
            }
        }
        return $tablas;
    }

    /** @return string[] */
    public static function tablasConocidas(): array
    {
        return array_keys(self::mapaTablas());
    }

    /** @return array<string,array{clave:string,etiqueta:string}> */
    private static function mapaTablas(): array
    {
        return [
            'usuarios'            => ['clave' => 'personal', 'etiqueta' => 'Personal'],
            'sesiones'            => ['clave' => 'acceso', 'etiqueta' => 'Acceso'],
            'clientes'            => ['clave' => 'clientes', 'etiqueta' => 'Clientes'],
            'vehiculos'           => ['clave' => 'vehiculos', 'etiqueta' => 'Vehículos'],
            'vehiculo'            => ['clave' => 'vehiculos', 'etiqueta' => 'Vehículos'],
            'ordenes'             => ['clave' => 'ordenes', 'etiqueta' => 'Órdenes'],
            'ordenes_servicio'    => ['clave' => 'ordenes', 'etiqueta' => 'Órdenes'],
            'orden_servicio'      => ['clave' => 'ordenes', 'etiqueta' => 'Órdenes'],
            'servicios'           => ['clave' => 'servicios', 'etiqueta' => 'Servicios'],
            'facturas'            => ['clave' => 'facturacion', 'etiqueta' => 'Facturación'],
            'ventas'              => ['clave' => 'facturacion', 'etiqueta' => 'Facturación'],
            'detalle_items'       => ['clave' => 'facturacion', 'etiqueta' => 'Facturación'],
            'detalle_factura'     => ['clave' => 'facturacion', 'etiqueta' => 'Facturación'],
            'productos'           => ['clave' => 'inventario', 'etiqueta' => 'Inventario'],
            'stock'               => ['clave' => 'inventario', 'etiqueta' => 'Inventario'],
            'movimientos_stock'   => ['clave' => 'inventario', 'etiqueta' => 'Inventario'],
            'categorias'          => ['clave' => 'inventario', 'etiqueta' => 'Inventario'],
            'contratos'           => ['clave' => 'rrhh', 'etiqueta' => 'Contratos y nómina'],
            'nominas'             => ['clave' => 'rrhh', 'etiqueta' => 'Contratos y nómina'],
            'citas'               => ['clave' => 'agenda', 'etiqueta' => 'Agenda'],
            'agenda_disponible'   => ['clave' => 'agenda', 'etiqueta' => 'Agenda'],
            'configuracion'       => ['clave' => 'configuracion', 'etiqueta' => 'Configuración'],
            'transacciones_caja'  => ['clave' => 'caja', 'etiqueta' => 'Caja'],
            'sesiones_caja'       => ['clave' => 'caja', 'etiqueta' => 'Caja'],
            'cuentas'             => ['clave' => 'caja', 'etiqueta' => 'Caja'],
            'mensajes'            => ['clave' => 'chat', 'etiqueta' => 'Chat'],
            'conversaciones'      => ['clave' => 'chat', 'etiqueta' => 'Chat'],
            'proveedores'         => ['clave' => 'chat', 'etiqueta' => 'Chat'],
            'adjuntos'            => ['clave' => 'archivos', 'etiqueta' => 'Archivos'],
        ];
    }

    /**
     * @param array<string,mixed> $despues
     */
    private static function verbo(string $accion, string $tabla, array $despues): string
    {
        $a = strtoupper(trim($accion));
        $t = strtolower($tabla);
        if ($a === 'LOGIN') {
            return 'Inició sesión';
        }
        if ($a === 'LOGOUT') {
            return 'Cerró sesión';
        }
        if ($t === 'sesiones_caja' && $a === 'CREATE') {
            return 'Abrió caja';
        }
        if ($t === 'sesiones_caja' && $a === 'UPDATE') {
            return 'Cerró caja';
        }
        if ($t === 'configuracion') {
            return 'Cambió configuración';
        }
        return match ($a) {
            'CREATE' => 'Registró',
            'UPDATE' => 'Actualizó',
            'DELETE' => 'Eliminó',
            default  => $accion !== '' ? $accion : 'Actividad',
        };
    }

    /**
     * @param array<string,mixed> $despues
     * @param array<string,mixed> $antes
     */
    private static function sujeto(string $tabla, array $despues, array $antes): string
    {
        $datos = $despues !== [] ? $despues : $antes;
        $t = strtolower($tabla);
        $candidatos = [
            'nombre', 'nombre_completo', 'cliente', 'cliente_nombre', 'usuario_nombre',
            'placa', 'numero', 'codigo', 'correo', 'email', 'concepto', 'cuenta',
            'periodo_pago', 'descripcion', 'nombre_original', 'titulo',
        ];
        foreach ($candidatos as $clave) {
            $valor = self::texto($datos[$clave] ?? null);
            if ($valor !== '') {
                return $valor;
            }
        }
        if (!empty($datos['rol']) && is_string($datos['rol'])) {
            return (string) $datos['rol'];
        }
        if ($t === 'configuracion') {
            return 'datos del taller';
        }
        return '';
    }

    /**
     * @param array<string,mixed> $despues
     * @param array<int,array{campo:string,antes:string,despues:string}> $cambios
     */
    private static function resumen(
        string $accion,
        string $tabla,
        string $verbo,
        string $sujeto,
        array $despues,
        array $cambios
    ): string {
        $a = strtoupper(trim($accion));
        $t = strtolower($tabla);
        if ($a === 'LOGIN') {
            $rol = self::texto($despues['rol'] ?? '');
            return $rol !== '' ? 'Inició sesión como ' . $rol : 'Inició sesión';
        }
        if ($a === 'LOGOUT') {
            return 'Cerró la sesión';
        }
        if ($t === 'sesiones_caja' && $a === 'CREATE') {
            $cuenta = self::texto($despues['cuenta'] ?? $despues['nombre'] ?? '');
            $monto = self::dinero($despues['monto_apertura'] ?? null);
            $partes = ['Abrió caja'];
            if ($cuenta !== '') {
                $partes[] = 'en ' . $cuenta;
            }
            if ($monto !== '') {
                $partes[] = 'con ' . $monto;
            }
            return implode(' ', $partes);
        }
        if ($t === 'sesiones_caja' && $a === 'UPDATE') {
            $real = self::dinero($despues['monto_real'] ?? null);
            return $real !== '' ? 'Cerró caja. Efectivo contado: ' . $real : 'Cerró caja';
        }
        if ($t === 'transacciones_caja') {
            $tipo = strtolower((string) ($despues['tipo'] ?? ''));
            $monto = self::dinero($despues['monto'] ?? null);
            $concepto = self::texto($despues['concepto'] ?? $despues['nombre'] ?? $sujeto);
            $verboTx = $tipo === 'egreso' ? 'Registró un egreso' : 'Registró un ingreso';
            $cola = array_filter([$concepto, $monto]);
            return $cola ? $verboTx . ': ' . implode(' · ', $cola) : $verboTx;
        }
        if ($t === 'configuracion') {
            $n = count($cambios);
            return $n > 0
                ? 'Cambió la configuración del taller (' . $n . ' ajuste' . ($n === 1 ? '' : 's') . ')'
                : 'Cambió la configuración del taller';
        }
        $entidad = self::entidad($tabla);
        if ($sujeto !== '') {
            if (mb_strlen($sujeto) > 70) {
                $sujeto = mb_substr($sujeto, 0, 67) . '…';
            }
            return $verbo . ' ' . $entidad . ' ' . $sujeto;
        }
        return $verbo . ' ' . $entidad;
    }

    private static function entidad(string $tabla): string
    {
        $t = strtolower($tabla);
        return match ($t) {
            'usuarios' => 'al usuario',
            'clientes' => 'al cliente',
            'vehiculos', 'vehiculo' => 'el vehículo',
            'ordenes', 'ordenes_servicio', 'orden_servicio' => 'la orden',
            'servicios' => 'el servicio',
            'facturas' => 'la factura',
            'ventas' => 'la venta',
            'detalle_items' => 'un ítem de venta',
            'productos' => 'el producto',
            'contratos' => 'el contrato de',
            'nominas' => 'la nómina de',
            'citas' => 'la cita',
            'agenda_disponible' => 'el horario',
            'cuentas' => 'la cuenta',
            'mensajes' => 'un mensaje',
            'proveedores' => 'el contacto',
            'adjuntos' => 'un archivo',
            default => 'un registro',
        };
    }

    /**
     * @param array<string,mixed> $antes
     * @param array<string,mixed> $despues
     * @return array<int,array{campo:string,antes:string,despues:string}>
     */
    private static function cambios(string $accion, array $antes, array $despues): array
    {
        $a = strtoupper(trim($accion));
        if (in_array($a, ['CREATE', 'LOGIN', 'LOGOUT'], true)) {
            return [];
        }
        $claves = array_unique(array_merge(array_keys($antes), array_keys($despues)));
        $out = [];
        foreach ($claves as $clave) {
            if (self::omitirCampo($clave)) {
                continue;
            }
            $va = self::textoVisible($clave, $antes[$clave] ?? null);
            $vd = self::textoVisible($clave, $despues[$clave] ?? null);
            if ($va === $vd) {
                continue;
            }
            $out[] = [
                'campo'   => self::etiquetaCampo($clave),
                'antes'   => $va !== '' ? $va : '—',
                'despues' => $vd !== '' ? $vd : '—',
            ];
        }
        return $out;
    }

    /** @return array<string,mixed> */
    private static function plano(array $datos): array
    {
        $out = [];
        foreach ($datos as $clave => $valor) {
            $k = (string) $clave;
            if (self::omitirCampo($k)) {
                continue;
            }
            if (is_array($valor)) {
                if (isset($valor['nombre']) && is_scalar($valor['nombre'])) {
                    $out[$k] = $valor['nombre'];
                    continue;
                }
                if (array_is_list($valor)) {
                    $out[$k] = count($valor) . ' ítem' . (count($valor) === 1 ? '' : 's');
                    continue;
                }
                continue;
            }
            $out[$k] = $valor;
        }
        return $out;
    }

    private static function omitirCampo(string $clave): bool
    {
        $k = strtolower($clave);
        if (preg_match('/^(id_|password|token|hash)/', $k) || preg_match('/(_id|_hash|_token)$/', $k)) {
            return true;
        }
        return in_array($k, [
            'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by',
            'intentos_login', 'user_agent', 'cliente_obj', 'vehiculos', 'detalles',
            'mecanico', 'rol_obj', 'password_hash', 'invitacion_pendiente',
        ], true);
    }

    private static function etiquetaCampo(string $clave): string
    {
        $mapa = [
            'nombre' => 'Nombre',
            'correo' => 'Correo',
            'email' => 'Correo',
            'documento' => 'Documento',
            'telefono' => 'Teléfono',
            'estado' => 'Estado',
            'placa' => 'Placa',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'rol' => 'Rol',
            'descripcion' => 'Descripción',
            'numero' => 'Número',
            'total' => 'Total',
            'subtotal' => 'Subtotal',
            'iva' => 'IVA',
            'monto' => 'Monto',
            'monto_apertura' => 'Monto de apertura',
            'monto_real' => 'Efectivo físico',
            'monto_sistema' => 'Saldo en sistema',
            'diferencia' => 'Diferencia',
            'salario_base' => 'Salario',
            'porcentaje_comision' => 'Comisión',
            'periodo_pago' => 'Periodo',
            'metodo_pago' => 'Método de pago',
            'tipo' => 'Tipo',
            'concepto' => 'Concepto',
            'cuenta' => 'Cuenta',
            'saldo_actual' => 'Saldo',
            'fecha_ingreso' => 'Fecha de ingreso',
            'fecha_salida' => 'Fecha de salida',
            'precio' => 'Precio',
            'cliente' => 'Cliente',
        ];
        $k = strtolower($clave);
        if (isset($mapa[$k])) {
            return $mapa[$k];
        }
        return ucfirst(str_replace('_', ' ', $clave));
    }

    private static function textoVisible(string $clave, $valor): string
    {
        $k = strtolower($clave);
        if ($valor === null || $valor === '') {
            return '';
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if (preg_match('/(monto|total|salario|precio|saldo|iva|subtotal|diferencia)/', $k) && is_numeric($valor)) {
            return self::dinero($valor);
        }
        return self::texto($valor);
    }

    private static function texto($valor): string
    {
        if ($valor === null || $valor === '' || $valor === []) {
            return '';
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if (is_array($valor)) {
            if (isset($valor['nombre'])) {
                return trim((string) $valor['nombre']);
            }
            return '';
        }
        return trim((string) $valor);
    }

    private static function dinero($valor): string
    {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return '';
        }
        return '$' . number_format((float) $valor, 0, ',', '.');
    }
}
