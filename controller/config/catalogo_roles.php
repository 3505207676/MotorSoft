<?php

/**
 * Catálogo de permisos y mapa por rol.
 * Parte de los procesos de la matriz crud(grupo / proceso / tablas).
 * Cliente no es un rol de Usuarios: entra por portal propio.
 */

function catalogo_permisos(): array
{
    return [
        ['Registrar cliente', 'clientes.crear', 'clientes', 'Alta de cliente en recepción'],
        ['Consultar clientes', 'clientes.ver', 'clientes', 'Listar y ver ficha de cliente'],
        ['Editar cliente', 'clientes.editar', 'clientes', 'Actualizar datos de cliente'],
        ['Eliminar cliente', 'clientes.eliminar', 'clientes', 'Baja lógica de cliente'],
        ['Gestionar clientes', 'clientes.gestionar', 'clientes', 'Alias legado de alta y edición'],

        ['Registrar vehículo', 'vehiculos.crear', 'vehiculos', 'Alta de vehículo asociado a cliente'],
        ['Consultar vehículos', 'vehiculos.ver', 'vehiculos', 'Listar y ver vehículos'],
        ['Editar vehículo', 'vehiculos.editar', 'vehiculos', 'Actualizar datos de vehículo'],
        ['Eliminar vehículo', 'vehiculos.eliminar', 'vehiculos', 'Baja lógica de vehículo'],

        ['Crear orden de servicio', 'ordenes.crear', 'ordenes', 'Abrir OT desde recepción o gerencia'],
        ['Consultar órdenes', 'ordenes.ver', 'ordenes', 'Ver órdenes y su detalle'],
        ['Listar órdenes', 'ordenes.listar', 'ordenes', 'Alias legado de consulta'],
        ['Cambiar estado de orden', 'ordenes.cambiar_estado', 'ordenes', 'Avanzar o devolver el flujo de la OT'],
        ['Agregar servicios a orden', 'ordenes.agregar_servicios', 'ordenes', 'Cargar mano de obra en la OT'],
        ['Agregar productos a orden', 'ordenes.agregar_productos', 'ordenes', 'Cargar repuestos en la OT'],
        ['Cerrar orden', 'ordenes.cerrar', 'ordenes', 'Marcar OT lista para facturar'],
        ['Anular orden', 'ordenes.anular', 'ordenes', 'Anular o eliminar una OT'],

        ['Crear servicio', 'servicios.crear', 'servicios', 'Alta en el catálogo de servicios'],
        ['Consultar servicios', 'servicios.ver', 'servicios', 'Ver catálogo de servicios'],
        ['Editar servicio', 'servicios.editar', 'servicios', 'Actualizar precio o datos del servicio'],
        ['Eliminar servicio', 'servicios.eliminar', 'servicios', 'Desactivar servicio del catálogo'],

        ['Consultar inventario', 'inventario.ver', 'inventario', 'Ver productos y existencias'],
        ['Crear producto', 'inventario.crear', 'inventario', 'Alta de producto'],
        ['Editar producto', 'inventario.editar', 'inventario', 'Actualizar producto'],
        ['Eliminar producto', 'inventario.eliminar', 'inventario', 'Baja de producto'],
        ['Ajustar stock', 'inventario.ajustar', 'inventario', 'Entradas, salidas y ajustes'],
        ['Ver stock bajo', 'inventario.stock_bajo', 'inventario', 'Alertas de mínimo'],
        ['Gestionar categorías', 'inventario.categorias', 'inventario', 'Alta y baja de categorías'],

        ['Emitir factura', 'facturas.crear', 'facturacion', 'Facturar OT o venta'],
        ['Consultar facturas', 'facturas.ver', 'facturacion', 'Ver facturas emitidas'],
        ['Registrar pago', 'facturas.pagar', 'facturacion', 'Cobrar y marcar factura pagada'],
        ['Anular factura', 'facturas.anular', 'facturacion', 'Anular factura y reversar caja'],

        ['Registrar venta', 'ventas.crear', 'ventas', 'Venta de mostrador'],
        ['Consultar ventas', 'ventas.ver', 'ventas', 'Ver ventas de mostrador'],

        ['Consultar agenda', 'agenda.ver', 'agenda', 'Ver horarios y citas'],
        ['Gestionar horarios', 'agenda.horarios', 'agenda', 'Crear o cancelar cupos'],
        ['Crear cita', 'citas.crear', 'agenda', 'Agendar cita'],
        ['Confirmar cita', 'citas.confirmar', 'agenda', 'Confirmar o atender cita'],
        ['Cancelar cita', 'citas.cancelar', 'agenda', 'Cancelar cita'],
        ['Convertir cita en orden', 'citas.convertir_orden', 'agenda', 'Pasar cita a OT'],

        ['Consultar usuarios', 'usuarios.ver', 'usuarios', 'Ver personal del taller'],
        ['Listar usuarios', 'usuarios.listar', 'usuarios', 'Alias legado de consulta'],
        ['Crear usuario', 'usuarios.crear', 'usuarios', 'Alta de trabajador'],
        ['Editar usuario', 'usuarios.editar', 'usuarios', 'Actualizar ficha de trabajador'],
        ['Eliminar usuario', 'usuarios.eliminar', 'usuarios', 'Baja de trabajador'],
        ['Asignar rol', 'usuarios.asignar_rol', 'usuarios', 'Cambiar el rol de un usuario'],
        ['Gestionar roles y permisos', 'roles.gestionar', 'usuarios', 'Definir qué puede cada rol'],

        ['Consultar especialidades', 'especialidades.ver', 'especialidades', 'Ver especialidades y asignaciones'],
        ['Gestionar especialidades', 'especialidades.gestionar', 'especialidades', 'Alta y asignación de especialidades'],

        ['Subir adjuntos', 'adjuntos.crear', 'adjuntos', 'Cargar evidencias a una OT'],
        ['Ver adjuntos', 'adjuntos.ver', 'adjuntos', 'Consultar evidencias'],

        ['Ver conversaciones', 'chat.ver', 'mensajeria', 'Abrir hilos de chat'],
        ['Enviar mensajes', 'chat.enviar', 'mensajeria', 'Escribir en chat interno o WhatsApp'],

        ['Consultar auditoría', 'auditoria.ver', 'auditoria', 'Ver logs de actividad'],
        ['Reportes de órdenes', 'reportes.ordenes', 'auditoria', 'Indicadores de taller'],
        ['Reportes de nómina', 'reportes.nomina', 'auditoria', 'Indicadores de personal'],

        ['Ver contabilidad', 'contabilidad.ver', 'contabilidad', 'Cuentas, sesiones y resumen financiero'],
        ['Crear cuentas', 'cuentas.crear', 'contabilidad', 'Plan de cuentas'],
        ['Gestionar conceptos', 'conceptos.gestionar', 'contabilidad', 'Conceptos de ingreso y egreso'],
        ['Consultar caja', 'caja.ver', 'caja', 'Ver la propia sesión de caja'],
        ['Abrir caja', 'caja.abrir', 'caja', 'Abrir turno de caja'],
        ['Registrar movimiento de caja', 'caja.transaccion', 'caja', 'Ingresos y egresos de turno'],
        ['Cerrar caja', 'caja.cerrar', 'caja', 'Cuadre y cierre de turno'],

        ['Consultar nóminas', 'nominas.ver', 'nomina', 'Ver liquidaciones'],
        ['Registrar nómina', 'nominas.crear', 'nomina', 'Liquidar y pagar nómina'],
        ['Consultar contratos', 'contratos.ver', 'nomina', 'Ver contratos laborales'],
        ['Gestionar contratos', 'contratos.gestionar', 'nomina', 'Alta, edición y baja de contratos'],

        ['Ver configuración', 'configuracion.ver', 'configuracion', 'Leer datos del taller'],
        ['Editar configuración', 'configuracion.editar', 'configuracion', 'IVA, empresa, WhatsApp y secretos'],
    ];
}

function catalogo_roles_base(): array
{
    return [
        'Administrador' => 'Acceso total: operación, personal, roles y configuración del sistema.',
        'Gerente'       => 'Dirección operativa, financiera y de personal. Sin roles ni secretos del sistema.',
        'Recepcionista' => 'Clientes, citas, órdenes, facturación y caja del día.',
        'Mecánico'      => 'Órdenes asignadas, inventario de consulta, agenda propia y chat.',
    ];
}

function catalogo_slugs_restringidos_gerente(): array
{
    return [
        'roles.gestionar',
        'usuarios.eliminar',
        'configuracion.editar',
    ];
}

/** @return string[] */
function catalogo_todos_los_slugs(): array
{
    return array_values(array_map(static function (array $p) {
        return $p[1];
    }, catalogo_permisos()));
}

/** @return string[] */
function catalogo_slugs_por_rol(string $nombre): array
{
    $clave = strtolower(trim($nombre));
    $clave = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $clave);

    $todos = catalogo_todos_los_slugs();

    if (strpos($clave, 'admin') !== false) {
        return $todos;
    }

    if (strpos($clave, 'gerent') !== false) {
        return array_values(array_diff($todos, catalogo_slugs_restringidos_gerente()));
    }

    if (strpos($clave, 'recep') !== false) {
        return [
            'clientes.crear', 'clientes.ver', 'clientes.editar', 'clientes.gestionar',
            'vehiculos.crear', 'vehiculos.ver', 'vehiculos.editar',
            'ordenes.crear', 'ordenes.ver', 'ordenes.listar',
            'ordenes.agregar_servicios', 'ordenes.agregar_productos',
            'servicios.ver',
            'inventario.ver', 'inventario.stock_bajo',
            'facturas.crear', 'facturas.ver', 'facturas.pagar',
            'ventas.crear', 'ventas.ver',
            'agenda.ver', 'agenda.horarios', 'citas.crear', 'citas.confirmar', 'citas.cancelar', 'citas.convertir_orden',
            'especialidades.ver',
            'adjuntos.crear', 'adjuntos.ver',
            'chat.ver', 'chat.enviar',
            'caja.ver', 'caja.abrir', 'caja.transaccion', 'caja.cerrar',
        ];
    }

    if (strpos($clave, 'mecanic') !== false) {
        return [
            'clientes.ver',
            'vehiculos.ver',
            'ordenes.ver', 'ordenes.listar', 'ordenes.cambiar_estado',
            'ordenes.agregar_servicios', 'ordenes.agregar_productos', 'ordenes.cerrar',
            'servicios.ver',
            'inventario.ver', 'inventario.stock_bajo',
            'agenda.ver', 'citas.confirmar',
            'especialidades.ver',
            'adjuntos.crear', 'adjuntos.ver',
            'chat.ver', 'chat.enviar',
            'configuracion.ver',
        ];
    }

    return [];
}