<?php
$title = 'Inicio - App Mecánicos';
$page_title = 'Inicio';
$active_module = 'dashboard';
$show_refresh = true;
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>
        <main class="main-content">
            <div class="dashboard-container" id="dashboard-container">
                <div class="kpis-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon bg-primary"><i class="fas fa-clipboard-list"></i></div>
                        <div class="kpi-content">
                            <h3 id="kpi-ordenes-asignadas">0</h3>
                            <p>Órdenes asignadas</p>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon bg-warning"><i class="fas fa-tools"></i></div>
                        <div class="kpi-content">
                            <h3 id="kpi-en-progreso">0</h3>
                            <p>En proceso</p>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon bg-info"><i class="fas fa-bell"></i></div>
                        <div class="kpi-content">
                            <h3 id="kpi-notificaciones">0</h3>
                            <p>Mensajes sin leer</p>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="kpi-content">
                            <h3 id="kpi-stock-critico">0</h3>
                            <p>Stock crítico</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-section">
                    <div class="section-header"><h2>Próximas citas</h2></div>
                    <div class="ordenes-recientes" id="citas-hoy">
                        <div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Cargando citas...</p></div>
                    </div>
                </div>

                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Órdenes recientes</h2>
                        <a href="../ordenes/index-mecanicos.php" class="btn btn-outline btn-sm">Ver todas <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="ordenes-recientes" id="ordenes-recientes">
                        <div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Cargando órdenes...</p></div>
                    </div>
                </div>

                <div class="dashboard-section">
                    <div class="section-header"><h2>Acciones rápidas</h2></div>
                    <div class="acciones-grid">
                        <a href="../ordenes/index-mecanicos.php" class="accion-card">
                            <div class="accion-icon bg-primary"><i class="fas fa-clipboard-list"></i></div>
                            <div class="accion-content"><h4>Órdenes</h4><p>Gestionar órdenes de trabajo</p></div>
                        </a>
                        <a href="../stock/index.php" class="accion-card">
                            <div class="accion-icon bg-success"><i class="fas fa-boxes"></i></div>
                            <div class="accion-content"><h4>Stock</h4><p>Consultar inventario</p></div>
                        </a>
                        <a href="../chat/index-mecanicos.php" class="accion-card">
                            <div class="accion-icon bg-info"><i class="fas fa-comments"></i></div>
                            <div class="accion-content"><h4>Chat</h4><p>Mensajes del taller</p></div>
                        </a>
                        <a href="../perfil/index-mecanicos.php" class="accion-card">
                            <div class="accion-icon bg-warning"><i class="fas fa-user"></i></div>
                            <div class="accion-content"><h4>Perfil</h4><p>Ver mi perfil</p></div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
        <div class="toast-container" id="toast-container"></div>
        <div class="loading-overlay hidden" id="loading-overlay">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p id="loading-text">Cargando...</p>
            </div>
        </div>
    </div>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script src="../../assets/js/modulos/mecanicos/dashboard.js?v=20260915ot2"></script>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
