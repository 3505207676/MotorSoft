<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

require_once __DIR__ . '/core/AppException.php';
require_once __DIR__ . '/core/ApiRequest.php';
require_once __DIR__ . '/core/ApiResponse.php';

require_once __DIR__ . '/models/Seguridad/Permiso.php';
require_once __DIR__ . '/models/Seguridad/Rol.php';
require_once __DIR__ . '/models/Seguridad/Usuario.php';
require_once __DIR__ . '/models/Seguridad/Sesion.php';
require_once __DIR__ . '/models/Seguridad/LogAuditoria.php';

require_once __DIR__ . '/models/Ordenes/Servicio.php';
require_once __DIR__ . '/models/Ordenes/DetalleServicio.php';
require_once __DIR__ . '/models/Ordenes/OrdenServicio.php';
require_once __DIR__ . '/models/Ordenes/Especialidad.php';

require_once __DIR__ . '/repositories/Seguridad/PermisoRepository.php';
require_once __DIR__ . '/repositories/Seguridad/RolRepository.php';
require_once __DIR__ . '/repositories/Seguridad/UsuarioRepository.php';
require_once __DIR__ . '/repositories/Seguridad/SesionRepository.php';
require_once __DIR__ . '/repositories/Seguridad/LogAuditoriaRepository.php';

require_once __DIR__ . '/repositories/Ordenes/ServicioRepository.php';
require_once __DIR__ . '/repositories/Ordenes/DetalleServicioRepository.php';
require_once __DIR__ . '/repositories/Ordenes/OrdenServicioRepository.php';
require_once __DIR__ . '/repositories/Ordenes/EspecialidadRepository.php';

require_once __DIR__ . '/config/catalogo_roles.php';
require_once __DIR__ . '/services/Seguridad/RolPermisoSchema.php';
require_once __DIR__ . '/services/Seguridad/AuthService.php';
require_once __DIR__ . '/services/Seguridad/UserService.php';
require_once __DIR__ . '/services/Seguridad/AuditoriaService.php';
require_once __DIR__ . '/services/Seguridad/AuditoriaPresentador.php';
require_once __DIR__ . '/services/Ordenes/ServicioService.php';
require_once __DIR__ . '/services/Ordenes/OrdenesService.php';
require_once __DIR__ . '/services/Ordenes/EspecialidadService.php';

require_once __DIR__ . '/http/Seguridad/AuthController.php';
require_once __DIR__ . '/http/Seguridad/UserController.php';
require_once __DIR__ . '/http/Seguridad/AuditoriaController.php';
require_once __DIR__ . '/http/Ordenes/ServiciosController.php';
require_once __DIR__ . '/http/Ordenes/OrdenesController.php';
require_once __DIR__ . '/http/Ordenes/EspecialidadesController.php';

require_once __DIR__ . '/models/Clientes/Vehiculo.php';
require_once __DIR__ . '/models/Clientes/Cliente.php';
require_once __DIR__ . '/repositories/Clientes/VehiculoRepository.php';
require_once __DIR__ . '/repositories/Clientes/ClienteRepository.php';
require_once __DIR__ . '/services/Clientes/ClienteService.php';
require_once __DIR__ . '/services/Clientes/VehiculoService.php';
require_once __DIR__ . '/http/Clientes/ClienteController.php';
require_once __DIR__ . '/http/Clientes/VehiculoController.php';

require_once __DIR__ . '/models/Inventario/Categoria.php';
require_once __DIR__ . '/models/Inventario/Stock.php';
require_once __DIR__ . '/models/Inventario/Producto.php';
require_once __DIR__ . '/models/Inventario/MovimientoStock.php';
require_once __DIR__ . '/repositories/Inventario/CategoriaRepository.php';
require_once __DIR__ . '/repositories/Inventario/StockRepository.php';
require_once __DIR__ . '/repositories/Inventario/ProductoRepository.php';
require_once __DIR__ . '/repositories/Inventario/MovimientoStockRepository.php';
require_once __DIR__ . '/services/Inventario/CategoriaService.php';
require_once __DIR__ . '/services/Inventario/InventarioSchema.php';
require_once __DIR__ . '/services/Inventario/InventarioService.php';
require_once __DIR__ . '/http/Inventario/CategoriaController.php';
require_once __DIR__ . '/http/Inventario/ProductoController.php';
require_once __DIR__ . '/http/Inventario/StockController.php';

require_once __DIR__ . '/models/Configuracion/Configuracion.php';
require_once __DIR__ . '/repositories/Configuracion/ConfiguracionRepository.php';
require_once __DIR__ . '/services/Configuracion/ConfiguracionService.php';
require_once __DIR__ . '/services/Correo/SmtpCliente.php';
require_once __DIR__ . '/services/Correo/CorreoService.php';
require_once __DIR__ . '/http/Configuracion/ConfiguracionController.php';

require_once __DIR__ . '/models/Facturacion/DetalleFactura.php';
require_once __DIR__ . '/models/Facturacion/Factura.php';
require_once __DIR__ . '/models/Facturacion/DetalleItem.php';
require_once __DIR__ . '/models/Facturacion/Venta.php';
require_once __DIR__ . '/repositories/Facturacion/DetalleFacturaRepository.php';
require_once __DIR__ . '/repositories/Facturacion/FacturaRepository.php';
require_once __DIR__ . '/repositories/Facturacion/DetalleItemRepository.php';
require_once __DIR__ . '/repositories/Facturacion/VentaRepository.php';
require_once __DIR__ . '/models/Caja/Cuenta.php';
require_once __DIR__ . '/models/Caja/SesionCaja.php';
require_once __DIR__ . '/models/Caja/ConceptoFinanciero.php';
require_once __DIR__ . '/models/Caja/TransaccionCaja.php';
require_once __DIR__ . '/repositories/Caja/CuentaRepository.php';
require_once __DIR__ . '/repositories/Caja/SesionCajaRepository.php';
require_once __DIR__ . '/repositories/Caja/ConceptoFinancieroRepository.php';
require_once __DIR__ . '/repositories/Caja/TransaccionCajaRepository.php';
require_once __DIR__ . '/services/Caja/CajaService.php';
require_once __DIR__ . '/http/Caja/CajaController.php';

require_once __DIR__ . '/models/RRHH/Contrato.php';
require_once __DIR__ . '/models/RRHH/Nomina.php';
require_once __DIR__ . '/models/RRHH/NominaRol.php';
require_once __DIR__ . '/repositories/RRHH/ContratoRepository.php';
require_once __DIR__ . '/repositories/RRHH/NominaRolRepository.php';
require_once __DIR__ . '/repositories/RRHH/NominaRepository.php';
require_once __DIR__ . '/services/RRHH/RrhhService.php';
require_once __DIR__ . '/http/RRHH/ContratoController.php';
require_once __DIR__ . '/http/RRHH/NominaController.php';

require_once __DIR__ . '/models/Agenda/Horario.php';
require_once __DIR__ . '/models/Agenda/Cita.php';
require_once __DIR__ . '/repositories/Agenda/HorarioRepository.php';
require_once __DIR__ . '/repositories/Agenda/CitaRepository.php';
require_once __DIR__ . '/services/Agenda/AgendaService.php';
require_once __DIR__ . '/http/Agenda/AgendaController.php';
require_once __DIR__ . '/http/Agenda/CitaController.php';

require_once __DIR__ . '/models/Chat/Mensaje.php';
require_once __DIR__ . '/models/Chat/Conversacion.php';
require_once __DIR__ . '/models/Proveedores/Proveedor.php';
require_once __DIR__ . '/repositories/Chat/MensajeRepository.php';
require_once __DIR__ . '/repositories/Chat/ConversacionRepository.php';
require_once __DIR__ . '/repositories/Proveedores/ProveedorRepository.php';
require_once __DIR__ . '/services/Chat/ChatSchema.php';
require_once __DIR__ . '/services/Chat/ChatContactoResolver.php';
require_once __DIR__ . '/services/Chat/Canales/CanalChatInterface.php';
require_once __DIR__ . '/services/Chat/Canales/CanalInterno.php';
require_once __DIR__ . '/services/Chat/Canales/CanalWhatsApp.php';
require_once __DIR__ . '/services/Chat/Canales/CanalChatFactory.php';
require_once __DIR__ . '/services/Chat/ChatService.php';
require_once __DIR__ . '/http/Chat/ChatController.php';
require_once __DIR__ . '/repositories/Dashboard/DashboardRepository.php';
require_once __DIR__ . '/services/Dashboard/DashboardService.php';
require_once __DIR__ . '/http/Dashboard/DashboardController.php';
require_once __DIR__ . '/services/Mecanicos/DashboardMecanicoService.php';
require_once __DIR__ . '/services/Mecanicos/PerfilMecanicoService.php';
require_once __DIR__ . '/services/Mecanicos/ConsumoMecanicoService.php';
require_once __DIR__ . '/http/Mecanicos/MecanicoController.php';

require_once __DIR__ . '/services/Facturacion/FacturacionService.php';
require_once __DIR__ . '/http/Facturacion/FacturaController.php';
require_once __DIR__ . '/http/Facturacion/VentaController.php';

require_once __DIR__ . '/models/Archivos/Adjunto.php';
require_once __DIR__ . '/services/Archivos/ArchivoService.php';
require_once __DIR__ . '/services/Archivos/AdjuntoSchema.php';
require_once __DIR__ . '/repositories/Archivos/AdjuntoRepository.php';
require_once __DIR__ . '/services/Archivos/AdjuntoService.php';
require_once __DIR__ . '/http/Archivos/AdjuntoController.php';
require_once __DIR__ . '/services/Facturacion/FacturaPdf.php';
require_once __DIR__ . '/services/Reportes/ReportesService.php';
require_once __DIR__ . '/http/Reportes/ReportesController.php';

function seguridad_auth_controller(): AuthController
{
    return app_container()['auth'];
}

function seguridad_user_controller(): UserController
{
    return app_container()['users'];
}

function servicios_controller(): ServiciosController
{
    return app_container()['servicios'];
}

function ordenes_controller(): OrdenesController
{
    return app_container()['ordenes'];
}

function especialidades_controller(): EspecialidadesController
{
    return app_container()['especialidades'];
}

function clientes_controller(): ClienteController
{
    return app_container()['clientes'];
}

function vehiculos_controller(): VehiculoController
{
    return app_container()['vehiculos'];
}

function categorias_controller(): CategoriaController
{
    return app_container()['categorias'];
}

function productos_controller(): ProductoController
{
    return app_container()['productos'];
}

function stock_controller(): StockController
{
    return app_container()['stock'];
}

function configuracion_controller(): ConfiguracionController
{
    return app_container()['configuracion'];
}

function facturas_controller(): FacturaController
{
    return app_container()['facturas'];
}

function ventas_controller(): VentaController
{
    return app_container()['ventas'];
}

function caja_controller(): CajaController
{
    return app_container()['caja'];
}

function rrhh_contratos_controller(): ContratoController
{
    return app_container()['contratos'];
}

function rrhh_nominas_controller(): NominaController
{
    return app_container()['nominas'];
}

function agenda_controller(): AgendaController
{
    return app_container()['agenda'];
}

function citas_controller(): CitaController
{
    return app_container()['citas'];
}

function chat_controller(): ChatController
{
    return app_container()['chat'];
}

function adjuntos_controller(): AdjuntoController
{
    return app_container()['adjuntos'];
}

function reportes_controller(): ReportesController
{
    return app_container()['reportes'];
}

function auditoria_controller(): AuditoriaController
{
    return app_container()['auditoria'];
}

function dashboard_controller(): DashboardController
{
    return app_container()['dashboard'];
}

function mecanico_controller(): MecanicoController
{
    return app_container()['mecanico'];
}

function seguridad_container(): array
{
    return app_container();
}

function app_container(): array
{
    static $container = null;
    if ($container !== null) {
        return $container;
    }

    $db = getDB();
    $rolPermisoSchema = new RolPermisoSchema($db);
    $rolPermisoSchema->asegurar();
    (new AdjuntoSchema($db))->asegurar();
    (new InventarioSchema($db))->asegurar();

    $permisoRepo = new PermisoRepository($db);
    $rolRepo     = new RolRepository($db, $permisoRepo);
    $usuarioRepo = new UsuarioRepository($db, $rolRepo);
    $sesionRepo  = new SesionRepository($db, $usuarioRepo);
    $logRepo     = new LogAuditoriaRepository($db);

    $vehiculoRepo = new VehiculoRepository($db);
    $clienteRepo  = new ClienteRepository($db, $vehiculoRepo);

    $authService = new AuthService($usuarioRepo, $sesionRepo, $logRepo, $clienteRepo, $vehiculoRepo);
    $auditoriaService = new AuditoriaService($logRepo, $authService);

    $clienteService  = new ClienteService($clienteRepo, $authService);
    $vehiculoService = new VehiculoService($vehiculoRepo, $clienteRepo, $authService);

    $categoriaRepo   = new CategoriaRepository($db);
    $stockRepo       = new StockRepository($db);
    $productoRepo    = new ProductoRepository($db, $categoriaRepo, $stockRepo);
    $movimientoRepo  = new MovimientoStockRepository($db);
    $categoriaService  = new CategoriaService($categoriaRepo, $authService);
    $inventarioService = new InventarioService($db, $productoRepo, $stockRepo, $movimientoRepo, $categoriaService, $authService);
    $detalleItemRepo   = new DetalleItemRepository($db, $productoRepo);

    $servicioRepo = new ServicioRepository($db);
    $detalleRepo  = new DetalleServicioRepository($db, $servicioRepo);
    $ordenRepo    = new OrdenServicioRepository($db, $detalleRepo, $usuarioRepo, $vehiculoRepo);
    $espeRepo     = new EspecialidadRepository($db, $usuarioRepo);

    $servicioService     = new ServicioService($servicioRepo, $authService);
    $ordenesService      = new OrdenesService($db, $ordenRepo, $detalleRepo, $servicioRepo, $usuarioRepo, $authService, $detalleItemRepo);
    $especialidadService = new EspecialidadService($espeRepo, $usuarioRepo, $authService);

    $configRepo    = new ConfiguracionRepository($db);
    $configService = new ConfiguracionService($configRepo, $authService);
    $correoService = new CorreoService($configService);
    $userService   = new UserService($usuarioRepo, $rolRepo, $authService, $correoService, $rolPermisoSchema);

    $cuentaRepo    = new CuentaRepository($db);
    $conceptoRepo  = new ConceptoFinancieroRepository($db);
    $sesionCajaRepo = new SesionCajaRepository($db, $cuentaRepo, $usuarioRepo);
    $txCajaRepo    = new TransaccionCajaRepository($db, $conceptoRepo, $cuentaRepo);
    $cajaService   = new CajaService($db, $cuentaRepo, $sesionCajaRepo, $conceptoRepo, $txCajaRepo, $authService);

    $contratoRepo = new ContratoRepository($db);
    $nominaRolRepo = new NominaRolRepository($db);
    $nominaRepo   = new NominaRepository($db, $contratoRepo, $nominaRolRepo);
    $rrhhService  = new RrhhService($db, $contratoRepo, $nominaRepo, $nominaRolRepo, $usuarioRepo, $cajaService, $authService, $correoService);

    $horarioRepo = new HorarioRepository($db);
    $citaRepo    = new CitaRepository($db, $horarioRepo);
    $agendaService = new AgendaService(
        $db,
        $horarioRepo,
        $citaRepo,
        $usuarioRepo,
        $clienteRepo,
        $vehiculoRepo,
        $servicioRepo,
        $ordenesService,
        $authService
    );

    $mensajeRepo = new MensajeRepository($db);
    $conversacionRepo = new ConversacionRepository($db);
    $proveedorRepo = new ProveedorRepository($db);
    $chatSchema = new ChatSchema($db);
    $adjuntoRepo = new AdjuntoRepository($db);
    $chatContactos = new ChatContactoResolver($clienteRepo, $usuarioRepo, $proveedorRepo);
    $canalInterno = new CanalInterno($mensajeRepo, $conversacionRepo);
    $canalWhatsApp = new CanalWhatsApp($mensajeRepo, $conversacionRepo, $configService);
    $canalFactory = new CanalChatFactory($canalInterno, $canalWhatsApp);
    $chatService = new ChatService(
        $mensajeRepo,
        $conversacionRepo,
        $chatContactos,
        $canalFactory,
        $authService,
        $chatSchema,
        $adjuntoRepo
    );

    $detalleFacturaRepo = new DetalleFacturaRepository($db);
    $facturaRepo        = new FacturaRepository($db, $detalleFacturaRepo, $clienteRepo);
    $ventaRepo          = new VentaRepository($db, $detalleItemRepo, $clienteRepo);
    $facturacionService = new FacturacionService(
        $db,
        $facturaRepo,
        $detalleFacturaRepo,
        $ventaRepo,
        $detalleItemRepo,
        $clienteRepo,
        $ordenRepo,
        $configService,
        $inventarioService,
        $ordenesService,
        $authService,
        $cajaService
    );

    $dashboardRepo = new DashboardRepository($db);
    $adjuntoService = new AdjuntoService(
        new ArchivoService(),
        $adjuntoRepo,
        $conversacionRepo,
        $ordenesService,
        $facturacionService,
        $authService,
        new AdjuntoSchema($db)
    );
    $reportesService = new ReportesService($ordenesService, $nominaRepo, $facturacionService, $authService);

    $container = [
        'auth'            => new AuthController($authService, $userService, $correoService),
        'users'           => new UserController($userService, $authService),
        'auditoria'       => new AuditoriaController($auditoriaService, $authService),
        'servicios'       => new ServiciosController($servicioService, $authService),
        'ordenes'         => new OrdenesController($ordenesService, $authService),
        'especialidades'  => new EspecialidadesController($especialidadService, $authService),
        'clientes'        => new ClienteController($clienteService, $authService, $adjuntoService),
        'vehiculos'       => new VehiculoController($vehiculoService, $authService),
        'categorias'      => new CategoriaController($categoriaService, $authService),
        'productos'       => new ProductoController($inventarioService, $authService),
        'stock'           => new StockController($inventarioService, $authService),
        'configuracion'   => new ConfiguracionController($configService, $authService, $correoService),
        'facturas'        => new FacturaController($facturacionService, $authService, $configService),
        'ventas'          => new VentaController($facturacionService, $authService),
        'caja'            => new CajaController($cajaService, $authService),
        'contratos'       => new ContratoController($rrhhService, $authService),
        'nominas'         => new NominaController($rrhhService, $authService),
        'agenda'          => new AgendaController($agendaService, $authService),
        'citas'           => new CitaController($agendaService, $authService),
        'chat'            => new ChatController($chatService, $authService),
        'adjuntos'        => new AdjuntoController($adjuntoService, $authService),
        'reportes'        => new ReportesController($reportesService, $authService),
        'dashboard'       => new DashboardController(
            new DashboardService(
                $dashboardRepo,
                $chatService,
                $configService,
                $authService
            ),
            $authService
        ),
        'mecanico'        => new MecanicoController(
            new DashboardMecanicoService($dashboardRepo, $chatService, $configService),
            new PerfilMecanicoService($userService, $espeRepo, $contratoRepo, $dashboardRepo),
            new ConsumoMecanicoService($db, $ordenesService, $detalleItemRepo, $inventarioService, $authService),
            $authService
        ),
        'db'              => $db,
    ];

    return $container;
}
