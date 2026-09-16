<?php
/**
 * Datos de prueba para MotorSoft / Taller El Paisa.
 * Abrir en el navegador (solo entorno development):
 *   /controllers/sql/seed_prueba.php
 *
 * Clave de todos los trabajadores de prueba: 123456
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/catalogo_roles.php';
require_once __DIR__ . '/../services/Seguridad/RolPermisoSchema.php';

header('Content-Type: text/html; charset=utf-8');

if (!defined('ENTORNO') || ENTORNO !== 'development') {
    http_response_code(403);
    echo 'El seeder solo corre en development.';
    exit;
}

function seedFetch(Database $db, string $sql, array $params = []): ?array
{
    $fila = $db->query($sql, $params)->fetch();
    return $fila ?: null;
}

function seedIdRol(Database $db, string $nombre): int
{
    $fila = seedFetch($db, 'SELECT id_rol FROM Roles WHERE nombre = :n AND deleted_at IS NULL LIMIT 1', [':n' => $nombre]);
    if ($fila) {
        return (int) $fila['id_rol'];
    }
    $db->query(
        'INSERT INTO Roles (nombre, descripcion, estado) VALUES (:n, :d, :e)',
        [':n' => $nombre, ':d' => 'Rol de prueba: ' . $nombre, ':e' => 'Activo']
    );
    return (int) $db->lastInsertId();
}

try {
    $db = getDB();
    $clave = '123456';
    $hash  = password_hash($clave, PASSWORD_DEFAULT);

    $db->query(
        'CREATE TABLE IF NOT EXISTS Configuracion (
            id_config INT PRIMARY KEY AUTO_INCREMENT,
            clave VARCHAR(80) NOT NULL UNIQUE,
            valor TEXT NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT \'string\',
            descripcion VARCHAR(250) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by INT NOT NULL,
            updated_at DATETIME NULL,
            updated_by INT NULL
        )'
    );

    $colCaja = seedFetch(
        $db,
        'SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c',
        [':t' => 'Transacciones_Caja', ':c' => 'id_caja']
    );
    if ((int) ($colCaja['total'] ?? 0) === 0) {
        $db->query('ALTER TABLE Transacciones_Caja ADD COLUMN id_caja INT NULL');
    }

    $db->beginTransaction();

    $idAdminRol = seedIdRol($db, 'Administrador');
    $idGerenteRol = seedIdRol($db, 'Gerente');
    $idMecaRol  = seedIdRol($db, 'Mecánico');
    $idRecepRol = seedIdRol($db, 'Recepcionista');

    $trabajadores = [
        [
            'rol' => $idAdminRol,
            'nombre' => 'Jhonnier Alvarez',
            'correo' => 'admin@tallerpaisa.com',
            'telefono' => '3001112233',
            'documento' => '1000000001',
        ],
        [
            'rol' => $idMecaRol,
            'nombre' => 'Pedro Ramirez',
            'correo' => 'mecanico@tallerpaisa.com',
            'telefono' => '3102223344',
            'documento' => '1000000002',
        ],
        [
            'rol' => $idRecepRol,
            'nombre' => 'Laura Vega',
            'correo' => 'recepcion@tallerpaisa.com',
            'telefono' => '3203334455',
            'documento' => '1000000003',
        ],
        [
            'rol' => $idGerenteRol,
            'nombre' => 'Carlos Mendoza',
            'correo' => 'gerente@tallerpaisa.com',
            'telefono' => '3004445566',
            'documento' => '1000000004',
        ],
    ];

    $idsUsuario = [];
    foreach ($trabajadores as $u) {
        $fila = seedFetch($db, 'SELECT id_usuario FROM Usuarios WHERE documento = :d LIMIT 1', [':d' => $u['documento']]);
        if ($fila) {
            $idsUsuario[] = (int) $fila['id_usuario'];
            $db->query(
                'UPDATE Usuarios SET password_hash = :h, estado = :e, deleted_at = NULL WHERE id_usuario = :id',
                [':h' => $hash, ':e' => 'Activo', ':id' => $fila['id_usuario']]
            );
            continue;
        }
        $db->query(
            'INSERT INTO Usuarios (id_rol, nombre, correo, telefono, documento, estado, password_hash)
             VALUES (:rol, :nombre, :correo, :tel, :doc, :estado, :hash)',
            [
                ':rol'    => $u['rol'],
                ':nombre' => $u['nombre'],
                ':correo' => $u['correo'],
                ':tel'    => $u['telefono'],
                ':doc'    => $u['documento'],
                ':estado' => 'Activo',
                ':hash'   => $hash,
            ]
        );
        $idsUsuario[] = (int) $db->lastInsertId();
    }

    $idAdmin = $idsUsuario[0];
    $idMeca  = $idsUsuario[1];
    $idRecep = $idsUsuario[2] ?? 0;
    $idGerente = $idsUsuario[3] ?? 0;

    $especialidades = ['Frenos', 'Electricidad', 'Motor'];
    $idsEsp = [];
    foreach ($especialidades as $nombreEsp) {
        $fila = seedFetch(
            $db,
            'SELECT id_especialidad FROM Especialidades WHERE nombre_especialidad = :n AND deleted_at IS NULL LIMIT 1',
            [':n' => $nombreEsp]
        );
        if ($fila) {
            $idsEsp[] = (int) $fila['id_especialidad'];
            continue;
        }
        $db->query(
            'INSERT INTO Especialidades (nombre_especialidad, descripcion, estado, created_by)
             VALUES (:n, :d, :e, :u)',
            [':n' => $nombreEsp, ':d' => 'Especialidad de prueba', ':e' => 'Activo', ':u' => $idAdmin]
        );
        $idsEsp[] = (int) $db->lastInsertId();
    }

    $existeUE = seedFetch(
        $db,
        'SELECT id_user_especialidad FROM Usuario_Especialidad
         WHERE id_usuario = :u AND id_especialidad = :e AND deleted_at IS NULL LIMIT 1',
        [':u' => $idMeca, ':e' => $idsEsp[2]]
    );
    if (!$existeUE) {
        $db->query(
            'INSERT INTO Usuario_Especialidad (id_usuario, id_especialidad, created_by) VALUES (:u, :e, :c)',
            [':u' => $idMeca, ':e' => $idsEsp[2], ':c' => $idAdmin]
        );
    }

    $clientes = [
        [
            'nombre' => 'Juan Perez Garcia',
            'documento' => '1234567890',
            'email' => 'juan.perez@email.com',
            'telefono' => '3001234567',
            'preferencia' => 'WhatsApp',
        ],
        [
            'nombre' => 'Maria Lopez Ruiz',
            'documento' => '9876543210',
            'email' => 'maria.lopez@email.com',
            'telefono' => '3109876543',
            'preferencia' => 'Llamada',
        ],
    ];
    $idsCliente = [];
    foreach ($clientes as $c) {
        $fila = seedFetch($db, 'SELECT id_cliente FROM Clientes WHERE documento = :d LIMIT 1', [':d' => $c['documento']]);
        if ($fila) {
            $idsCliente[] = (int) $fila['id_cliente'];
            continue;
        }
        $db->query(
            'INSERT INTO Clientes (nombre, documento, email, telefono, preferencia_contacto, estado, created_by)
             VALUES (:n, :d, :e, :t, :p, :es, :u)',
            [
                ':n' => $c['nombre'],
                ':d' => $c['documento'],
                ':e' => $c['email'],
                ':t' => $c['telefono'],
                ':p' => $c['preferencia'],
                ':es' => 'Activo',
                ':u' => $idAdmin,
            ]
        );
        $idsCliente[] = (int) $db->lastInsertId();
    }

    $mostrador = seedFetch($db, 'SELECT id_cliente FROM Clientes WHERE documento = :d LIMIT 1', [':d' => '2222222222']);
    if (!$mostrador) {
        $db->query(
            'INSERT INTO Clientes (nombre, documento, email, telefono, preferencia_contacto, estado, created_by)
             VALUES (:n, :d, :e, :t, :p, :es, :u)',
            [
                ':n'  => 'Consumidor Final',
                ':d'  => '2222222222',
                ':e'  => null,
                ':t'  => '0000000000',
                ':p'  => 'Presencial',
                ':es' => 'Activo',
                ':u'  => $idAdmin,
            ]
        );
    }

    $ajustes = [
        ['iva_activo', '1', 'bool', 'Cobrar IVA en facturas y ventas'],
        ['iva_porcentaje', '19', 'number', 'Porcentaje de IVA'],
        ['empresa_nombre', 'Taller El Paisa', 'string', 'Razón social en la factura'],
        ['empresa_nit', '900123456-1', 'string', 'NIT / documento de la empresa'],
        ['empresa_direccion', 'Cra 50 # 45-10, Medellín', 'string', 'Dirección del taller'],
        ['empresa_telefono', '6044440000', 'string', 'Teléfono de contacto'],
        ['factura_prefijo', 'FAC', 'string', 'Prefijo del número de factura'],
        ['moneda', 'COP', 'string', 'Moneda de facturación'],
    ];
    foreach ($ajustes as $aj) {
        $existeAj = seedFetch($db, 'SELECT id_config FROM Configuracion WHERE clave = :k LIMIT 1', [':k' => $aj[0]]);
        if ($existeAj) {
            continue;
        }
        $db->query(
            'INSERT INTO Configuracion (clave, valor, tipo, descripcion, created_by) VALUES (:k, :v, :t, :d, :u)',
            [':k' => $aj[0], ':v' => $aj[1], ':t' => $aj[2], ':d' => $aj[3], ':u' => $idAdmin]
        );
    }

    $vehiculos = [
        ['cliente' => $idsCliente[0], 'placa' => 'ABC123', 'marca' => 'Chevrolet', 'modelo' => 'Spark', 'anio' => 2018, 'tipo' => 'Automóvil'],
        ['cliente' => $idsCliente[1], 'placa' => 'XYZ789', 'marca' => 'Renault', 'modelo' => 'Logan', 'anio' => 2020, 'tipo' => 'Automóvil'],
        ['cliente' => $idsCliente[0], 'placa' => 'DEF456', 'marca' => 'Toyota', 'modelo' => 'Corolla', 'anio' => 2019, 'tipo' => 'Automóvil'],
    ];
    $idsVeh = [];
    foreach ($vehiculos as $v) {
        $fila = seedFetch($db, 'SELECT id_vehiculo FROM Vehiculo WHERE placa = :p LIMIT 1', [':p' => $v['placa']]);
        if ($fila) {
            $idsVeh[] = (int) $fila['id_vehiculo'];
            continue;
        }
        $db->query(
            'INSERT INTO Vehiculo (id_cliente, placa, marca, modelo, `año`, tipo, estado, created_by)
             VALUES (:c, :p, :m, :mo, :a, :t, :e, :u)',
            [
                ':c' => $v['cliente'],
                ':p' => $v['placa'],
                ':m' => $v['marca'],
                ':mo' => $v['modelo'],
                ':a' => $v['anio'],
                ':t' => $v['tipo'] ?? 'Automóvil',
                ':e' => 'Activo',
                ':u' => $idAdmin,
            ]
        );
        $idsVeh[] = (int) $db->lastInsertId();
    }

    $servicios = [
        ['Cambio de aceite', 'Mantenimiento', 80000],
        ['Diagnostico general', 'Diagnostico', 50000],
        ['Alineacion y balanceo', 'Suspension', 120000],
    ];
    $idsServ = [];
    foreach ($servicios as $s) {
        $fila = seedFetch($db, 'SELECT id_servicio FROM Servicios WHERE nombre = :n AND deleted_at IS NULL LIMIT 1', [':n' => $s[0]]);
        if ($fila) {
            $idsServ[] = (int) $fila['id_servicio'];
            continue;
        }
        $db->query(
            'INSERT INTO Servicios (tipo, nombre, descripcion, precio, estado, created_by)
             VALUES (:t, :n, :d, :p, :e, :u)',
            [
                ':t' => $s[1],
                ':n' => $s[0],
                ':d' => 'Servicio de prueba',
                ':p' => $s[2],
                ':e' => 'Activo',
                ':u' => $idAdmin,
            ]
        );
        $idsServ[] = (int) $db->lastInsertId();
    }

    $categoriasSeed = ['Filtros', 'Frenos', 'Lubricantes', 'Eléctricos', 'Llantas', 'Motor'];
    $idsCat = [];
    foreach ($categoriasSeed as $nombreCat) {
        $fila = seedFetch($db, 'SELECT id_categoria FROM Categorias WHERE nombre = :n AND deleted_at IS NULL LIMIT 1', [':n' => $nombreCat]);
        if ($fila) {
            $idsCat[$nombreCat] = (int) $fila['id_categoria'];
            continue;
        }
        $db->query(
            'INSERT INTO Categorias (nombre, descripcion, estado, created_by) VALUES (:n, :d, :e, :u)',
            [':n' => $nombreCat, ':d' => $nombreCat, ':e' => 'Activo', ':u' => $idAdmin]
        );
        $idsCat[$nombreCat] = (int) $db->lastInsertId();
    }

    $productosSeed = [
        ['FLT-001', 'Filtro de aceite', 'Filtros', 25000, 15, 5, 'Estante A-1'],
        ['FRN-010', 'Pastillas de freno delanteras', 'Frenos', 85000, 8, 4, 'Estante B-2'],
        ['LUB-020', 'Aceite 20W50 1L', 'Lubricantes', 32000, 3, 6, 'Estante A-3'],
        ['ELE-003', 'Batería 12V', 'Eléctricos', 280000, 4, 2, 'Estante C-1'],
    ];
    foreach ($productosSeed as $prod) {
        $fila = seedFetch($db, 'SELECT id_producto FROM Productos WHERE referencia = :r AND deleted_at IS NULL LIMIT 1', [':r' => $prod[0]]);
        if ($fila) {
            continue;
        }
        $db->query(
            'INSERT INTO Productos (id_categoria, nombre, referencia, precio_unitario, estado, created_by)
             VALUES (:id_cat, :nombre, :ref, :precio, :estado, :user)',
            [
                ':id_cat' => $idsCat[$prod[2]],
                ':nombre' => $prod[1],
                ':ref'    => $prod[0],
                ':precio' => $prod[3],
                ':estado' => 'Activo',
                ':user'   => $idAdmin,
            ]
        );
        $idProd = (int) $db->lastInsertId();
        $db->query(
            'INSERT INTO Stock (id_producto, cantidad, stock_minimo, precio_compra, ubicacion, created_by)
             VALUES (:id_prod, :cant, :minimo, :compra, :ubi, :user)',
            [
                ':id_prod' => $idProd,
                ':cant'    => $prod[4],
                ':minimo'  => $prod[5],
                ':compra'  => $prod[3],
                ':ubi'     => $prod[6],
                ':user'    => $idAdmin,
            ]
        );
        if ($prod[4] > 0) {
            $db->query(
                'INSERT INTO Movimientos (id_producto, tipo_movimiento, fecha_hora, referencia_documento, created_by)
                 VALUES (:id_prod, :tipo, NOW(), 0, :user)',
                [':id_prod' => $idProd, ':tipo' => 'ENTRADA', ':user' => $idAdmin]
            );
        }
    }

    $orden = seedFetch(
        $db,
        'SELECT id_orden FROM Orden_Servicio WHERE id_vehiculo = :v AND descripcion = :d AND deleted_at IS NULL LIMIT 1',
        [':v' => $idsVeh[0], ':d' => 'OT de prueba - ruido en frenos']
    );
    if (!$orden) {
        $db->query(
            'INSERT INTO Orden_Servicio (id_vehiculo, id_usuario, fecha_ingreso, descripcion, estado, created_by)
             VALUES (:v, :u, NOW(), :d, :e, :c)',
            [
                ':v' => $idsVeh[0],
                ':u' => $idMeca,
                ':d' => 'OT de prueba - ruido en frenos',
                ':e' => 'En Proceso',
                ':c' => $idAdmin,
            ]
        );
        $idOrden = (int) $db->lastInsertId();
        $db->query(
            'INSERT INTO Detalles_Servicios (id_orden, id_servicio, precio, cantidad, subtotal, created_by)
             VALUES (:o, :s, :precio, 1, :sub, :u)',
            [':o' => $idOrden, ':s' => $idsServ[1], ':precio' => 50000, ':sub' => 50000, ':u' => $idAdmin]
        );
        $db->query(
            'INSERT INTO Detalles_Servicios (id_orden, id_servicio, precio, cantidad, subtotal, created_by)
             VALUES (:o, :s, :precio, 1, :sub, :u)',
            [':o' => $idOrden, ':s' => $idsServ[0], ':precio' => 80000, ':sub' => 80000, ':u' => $idAdmin]
        );
    }

    $caja = seedFetch($db, 'SELECT id_cuenta FROM Cuentas WHERE nombre = :n AND deleted_at IS NULL LIMIT 1', [':n' => 'Caja Principal']);
    if (!$caja) {
        $db->query(
            'INSERT INTO Cuentas (nombre, tipo, saldo_actual, estado, created_by) VALUES (:n, :t, :s, :e, :u)',
            [':n' => 'Caja Principal', ':t' => 'Efectivo', ':s' => 0, ':e' => 'Activa', ':u' => $idAdmin]
        );
    }
    $banco = seedFetch($db, 'SELECT id_cuenta FROM Cuentas WHERE nombre = :n AND deleted_at IS NULL LIMIT 1', [':n' => 'Banco']);
    if (!$banco) {
        $db->query(
            'INSERT INTO Cuentas (nombre, tipo, saldo_actual, estado, created_by) VALUES (:n, :t, :s, :e, :u)',
            [':n' => 'Banco', ':t' => 'Banco', ':s' => 0, ':e' => 'Activa', ':u' => $idAdmin]
        );
    }
    $conceptosSeed = [
        ['Pago de orden de servicio', 'Ingreso', 'Cobro de factura de orden de servicio'],
        ['Venta de mostrador', 'Ingreso', 'Cobro de venta de mostrador'],
        ['Anulación de factura', 'Egreso', 'Reverso de cobro por factura anulada'],
        ['Ajuste de caja', 'Ingreso', 'Ajuste manual de caja'],
        ['Gasto operativo', 'Egreso', 'Gasto operativo del taller'],
        ['Pago de nómina', 'Egreso', 'Pago de salarios y nómina'],
    ];
    foreach ($conceptosSeed as $con) {
        $existe = seedFetch($db, 'SELECT id_concepto FROM Conceptos_Financieros WHERE nombre = :n AND deleted_at IS NULL LIMIT 1', [':n' => $con[0]]);
        if ($existe) {
            continue;
        }
        $db->query(
            'INSERT INTO Conceptos_Financieros (nombre, tipo, descripcion, created_by) VALUES (:n, :t, :d, :u)',
            [':n' => $con[0], ':t' => $con[1], ':d' => $con[2], ':u' => $idAdmin]
        );
    }

    $contratosSeed = [
        [$idMeca, 1800000, 0.05, '2024-01-15'],
        [$idRecep, 1600000, 0.00, '2023-06-01'],
        [$idGerente, 2500000, 0.00, '2022-03-01'],
    ];
    foreach ($contratosSeed as $ct) {
        if ((int) $ct[0] < 1) {
            continue;
        }
        $existeCt = seedFetch(
            $db,
            'SELECT id_contrato FROM Contratos WHERE id_usuario = :u AND estado_contrato = :e AND deleted_at IS NULL LIMIT 1',
            [':u' => $ct[0], ':e' => 'Vigente']
        );
        if ($existeCt) {
            continue;
        }
        $db->query(
            'INSERT INTO Contratos (id_usuario, salario_base, porcentaje_comicion, fecha_ingreso, estado_contrato, created_by)
             VALUES (:u, :s, :p, :f, :e, :c)',
            [':u' => $ct[0], ':s' => $ct[1], ':p' => $ct[2], ':f' => $ct[3], ':e' => 'Vigente', ':c' => $idAdmin]
        );
    }

    $horasSeed = ['08:00:00', '10:00:00', '14:00:00'];
    for ($d = 0; $d < 7; $d++) {
        $fechaH = date('Y-m-d', strtotime('+' . $d . ' day'));
        foreach ($horasSeed as $horaH) {
            $existeH = seedFetch(
                $db,
                'SELECT id_horario FROM Agenda_Disponible
                 WHERE id_usuario = :u AND fecha = :f AND hora_inicio = :h AND estado <> :canc LIMIT 1',
                [':u' => $idMeca, ':f' => $fechaH, ':h' => $horaH, ':canc' => 'Cancelado']
            );
            if ($existeH) {
                continue;
            }
            $db->query(
                'INSERT INTO Agenda_Disponible (id_usuario, fecha, hora_inicio, capacidad, estado, created_by)
                 VALUES (:usr, :fec, :hor, :cap, :est, :cre)',
                [
                    ':usr' => $idMeca,
                    ':fec' => $fechaH,
                    ':hor' => $horaH,
                    ':cap' => 1,
                    ':est' => 'Disponible',
                    ':cre' => $idAdmin,
                ]
            );
        }
    }

    $citasDemo = [
        [
            'dias' => 0,
            'hora' => '08:00:00',
            'cliente' => $idsCliente[0] ?? 0,
            'vehiculo' => $idsVeh[0] ?? 0,
            'servicio' => $idsServ[0] ?? 0,
            'estado' => 'Confirmada',
            'motivo' => 'Cambio de aceite programado',
        ],
        [
            'dias' => 1,
            'hora' => '10:00:00',
            'cliente' => $idsCliente[1] ?? 0,
            'vehiculo' => $idsVeh[1] ?? 0,
            'servicio' => $idsServ[1] ?? 0,
            'estado' => 'Programada',
            'motivo' => 'Diagnóstico general',
        ],
    ];
    foreach ($citasDemo as $cd) {
        if ((int) $cd['cliente'] < 1 || (int) $cd['vehiculo'] < 1 || (int) $cd['servicio'] < 1) {
            continue;
        }
        $fechaCita = date('Y-m-d', strtotime('+' . (int) $cd['dias'] . ' day'));
        $horarioCita = seedFetch(
            $db,
            'SELECT id_horario FROM Agenda_Disponible
             WHERE id_usuario = :u AND fecha = :f AND hora_inicio = :h AND estado <> :canc LIMIT 1',
            [':u' => $idMeca, ':f' => $fechaCita, ':h' => $cd['hora'], ':canc' => 'Cancelado']
        );
        if (!$horarioCita) {
            continue;
        }
        $idHorarioCita = (int) $horarioCita['id_horario'];
        $existeCita = seedFetch(
            $db,
            'SELECT id_cita FROM Citas WHERE id_horario = :h AND deleted_at IS NULL AND estado_cita <> :canc LIMIT 1',
            [':h' => $idHorarioCita, ':canc' => 'Cancelada']
        );
        if ($existeCita) {
            continue;
        }
        $db->query(
            'INSERT INTO Citas (id_cliente, id_vehiculo, id_orden, id_servicio, id_horario, motivo, estado_cita, created_by)
             VALUES (:cli, :veh, NULL, :ser, :hor, :mot, :est, :cre)',
            [
                ':cli' => $cd['cliente'],
                ':veh' => $cd['vehiculo'],
                ':ser' => $cd['servicio'],
                ':hor' => $idHorarioCita,
                ':mot' => $cd['motivo'],
                ':est' => $cd['estado'],
                ':cre' => $idAdmin,
            ]
        );
        $db->query(
            'UPDATE Agenda_Disponible SET estado = :est WHERE id_horario = :id AND capacidad <= 1',
            [':est' => 'Ocupado', ':id' => $idHorarioCita]
        );
    }

    (new RolPermisoSchema($db))->asegurar();

    $db->commit();

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Seed MotorSoft</title>';
    echo '<style>body{font-family:Segoe UI,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;color:#1e293b}';
    echo 'table{border-collapse:collapse;width:100%}td,th{border:1px solid #cbd5e1;padding:8px 10px;text-align:left}';
    echo 'th{background:#0f172a;color:#fff}code{background:#f1f5f9;padding:2px 6px}</style></head><body>';
    echo '<h1>Datos de prueba cargados</h1>';
    echo '<p>Contraseña de todos los <strong>trabajadores</strong>: <code>123456</code></p>';
    echo '<h2>Personal (documento + contraseña)</h2>';
    echo '<table><tr><th>Rol</th><th>Documento</th><th>Correo</th><th>Destino</th></tr>';
    echo '<tr><td>Administrador</td><td>1000000001</td><td>admin@tallerpaisa.com</td><td>Panel trabajadores</td></tr>';
    echo '<tr><td>Gerente</td><td>1000000004</td><td>gerente@tallerpaisa.com</td><td>Panel trabajadores</td></tr>';
    echo '<tr><td>Mecánico</td><td>1000000002</td><td>mecanico@tallerpaisa.com</td><td>App mecánicos</td></tr>';
    echo '<tr><td>Recepcionista</td><td>1000000003</td><td>recepcion@tallerpaisa.com</td><td>Panel trabajadores</td></tr>';
    echo '</table>';
    echo '<h2>Clientes (documento + placa, sin contraseña)</h2>';
    echo '<table><tr><th>Cliente</th><th>Documento</th><th>Placa</th></tr>';
    echo '<tr><td>Juan Perez Garcia</td><td>1234567890</td><td>ABC123</td></tr>';
    echo '<tr><td>Maria Lopez Ruiz</td><td>9876543210</td><td>XYZ789</td></tr>';
    echo '</table>';
    echo '<p>Login: <a href="../../views/auth/login.php">views/auth/login.php</a></p>';
    echo '</body></html>';
} catch (Throwable $e) {
    if (isset($db)) {
        try {
            $db->rollback();
        } catch (Throwable $ignored) {
        }
    }
    http_response_code(500);
    echo '<pre>Error en seed: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
