<?php
$title = 'Panel de Control - Taller El Paisa';
$active_page = 'dashboard';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>

        <!-- Contenido principal -->
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Panel Principal</h2>
                    <div class="breadcrumb">
                        <span>Inicio</span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="search-container">
                        <input type="text" id="search-dashboard" placeholder="Buscar orden, placa o cliente..." aria-label="Buscar órdenes recientes">
                        <button type="button" aria-label="Buscar"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="topbar-actions">
                        <button class="btn-icon" id="btn-notificaciones">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" hidden>0</span>
                        </button>
                        <button class="btn-icon" id="btn-mensajes">
                            <i class="fas fa-envelope"></i>
                            <span class="notification-badge" id="badge-chat-top" hidden>0</span>
                        </button>
                        <button class="btn-icon" id="theme-toggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- Resumen general -->
                <section class="dashboard-summary">
                    <div class="card-grid">
                        <div class="card card-stat">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="card-content">
                                <h3>Vehículos en Taller</h3>
                                <p class="stat-value" id="kpi-vehiculos-taller">0</p>
                                <p class="stat-change" id="hint-vehiculos">Cargando...</p>
                            </div>
                        </div>
                        <div class="card card-stat">
                            <div class="card-icon bg-success">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="card-content">
                                <h3>Órdenes Activas</h3>
                                <p class="stat-value" id="kpi-ordenes-activas">0</p>
                                <p class="stat-change" id="hint-ordenes">Cargando...</p>
                            </div>
                        </div>
                        <div class="card card-stat" id="card-kpi-ingresos" hidden style="display:none">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="card-content">
                                <h3>Ingresos del Día</h3>
                                <p class="stat-value" id="kpi-ingresos-dia">$0</p>
                                <p class="stat-change" id="hint-ingresos">Cargando...</p>
                            </div>
                        </div>
                        <div class="card card-stat" id="card-kpi-citas">
                            <div class="card-icon bg-info">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="card-content">
                                <h3>Citas de hoy</h3>
                                <p class="stat-value" id="kpi-citas-hoy">0</p>
                                <p class="stat-change" id="hint-citas">Cargando...</p>
                            </div>
                        </div>
                        <div class="card card-stat">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="card-content">
                                <h3>Stock Bajo</h3>
                                <p class="stat-value" id="kpi-stock-bajo">0</p>
                                <p class="stat-change" id="hint-stock">Cargando...</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Órdenes recientes y actividad -->
                <div class="dashboard-grid">
                    <section class="card">
                        <div class="card-header">
                            <h3>Órdenes Recientes</h3>
                            <div class="card-actions">
                                <button class="btn-icon">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Cliente</th>
                                            <th>Vehículo</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-ordenes-recientes">
                                        <tr><td colspan="6" class="text-center">Cargando...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <a href="../ordenes/index-trabajadores.php" class="btn btn-outline">Ver todas las órdenes</a>
                            </div>
                        </div>
                    </section>

                    <div class="dashboard-side">
                        <section class="card" id="card-citas">
                            <div class="card-header">
                                <h3>Próximas citas</h3>
                            </div>
                            <div class="card-body">
                                <ul class="activity-list" id="lista-citas">
                                    <li class="activity-item"><div class="activity-content"><p>Cargando...</p></div></li>
                                </ul>
                            </div>
                            <div class="card-footer">
                                <a href="../agenda/index.php" class="btn btn-outline">Ver agenda</a>
                            </div>
                        </section>

                        <section class="card" id="card-actividad">
                            <div class="card-header">
                                <h3 id="titulo-actividad">Actividad Reciente</h3>
                            </div>
                            <div class="card-body">
                                <ul class="activity-list" id="lista-actividad">
                                    <li class="activity-item"><div class="activity-content"><p>Cargando...</p></div></li>
                                </ul>
                            </div>
                            <div class="card-footer" id="footer-auditoria" hidden style="display:none">
                                <a href="../auditoria/index.php" class="btn btn-outline">Ver toda la actividad</a>
                            </div>
                        </section>

                        <section class="card">
                            <div class="card-header">
                                <h3>Mecánicos Disponibles</h3>
                            </div>
                            <div class="card-body">
                                <ul class="user-list" id="lista-mecanicos">
                                    <li class="user-item"><div class="user-info"><h4>Cargando...</h4></div></li>
                                </ul>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Gráficos y estadísticas -->
                <section class="dashboard-charts" id="seccion-finanzas" hidden style="display:none">
                    <div class="card">
                        <div class="card-header">
                            <h3>Estadísticas Mensuales</h3>
                            <div class="card-actions">
                                <select id="chart-period">
                                    <option value="week">Esta Semana</option>
                                    <option value="month" selected>Este Mes</option>
                                    <option value="year">Este Año</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <div class="chart-placeholder">
                                    <!-- Aquí iría el gráfico real con JavaScript -->
                                    <div class="chart-mock" id="chart-ingresos"></div>
                                    <div class="chart-labels" id="chart-ingresos-labels"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Modal de configuración -->
    <div class="modal" id="modal-configuracion">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Configuración</h3>
                <button class="btn-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="config-section">
                    <h4>Apariencia</h4>
                    <div class="form-group">
                        <label>Tema</label>
                        <div class="theme-options">
                            <button class="theme-option active" data-theme="light">
                                <i class="fas fa-sun"></i>
                                <span>Claro</span>
                            </button>
                            <button class="theme-option" data-theme="dark">
                                <i class="fas fa-moon"></i>
                                <span>Oscuro</span>
                            </button>
                            <button class="theme-option" data-theme="auto">
                                <i class="fas fa-magic"></i>
                                <span>Auto</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="config-section">
                    <h4>Notificaciones</h4>
                    <div class="form-group">
                        <div class="toggle-switch">
                            <input type="checkbox" id="notif-email" checked>
                            <label for="notif-email">Notificaciones por email</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="toggle-switch">
                            <input type="checkbox" id="notif-app" checked>
                            <label for="notif-app">Notificaciones en la aplicación</label>
                        </div>
                    </div>
                </div>
                <div class="config-section">
                    <h4>Información del Taller</h4>
                    <div class="form-group">
                        <label for="taller-nombre">Nombre del Taller</label>
                        <input type="text" id="taller-nombre" value="">
                    </div>
                    <div class="form-group">
                        <label for="taller-direccion">Dirección</label>
                        <input type="text" id="taller-direccion" value="">
                    </div>
                    <div class="form-group">
                        <label for="taller-telefono">Teléfono</label>
                        <input type="text" id="taller-telefono" value="">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline">Cancelar</button>
                <button class="btn btn-primary" id="btn-guardar-taller">Guardar Cambios</button>
            </div>
        </div>
    </div>

<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/dashboard.js?v=20260915int1"></script>