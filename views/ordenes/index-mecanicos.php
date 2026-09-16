<?php
$title = 'Órdenes - App Mecánicos';
$page_title = 'Mis Órdenes';
$active_module = 'ordenes';
$show_back = true;
$show_menu = false;
$show_notifications = false;
$show_refresh = true;
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Filtros -->
            <div class="filters-container">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="todas">
                        <span>Todas</span>
                        <span class="filter-count" id="count-todas">0</span>
                    </button>
                    <button class="filter-tab" data-filter="pendiente">
                        <span>Pendientes</span>
                        <span class="filter-count" id="count-pendiente">0</span>
                    </button>
                    <button class="filter-tab" data-filter="progreso">
                        <span>En Progreso</span>
                        <span class="filter-count" id="count-progreso">0</span>
                    </button>
                    <button class="filter-tab" data-filter="finalizado">
                        <span>Finalizadas</span>
                        <span class="filter-count" id="count-finalizado">0</span>
                    </button>
                </div>
            </div>

            <!-- Lista de Órdenes -->
            <div class="ordenes-container" id="ordenes-container">
                <div class="loading-state" id="loading-ordenes">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando órdenes...</p>
                </div>
            </div>

            <!-- Estado Vacío -->
            <div class="empty-state hidden" id="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h3>No hay órdenes</h3>
                <p>No tienes órdenes asignadas en este momento.</p>
                <button class="btn btn-primary" id="btn-refresh-empty">
                    <i class="fas fa-sync-alt"></i>
                    Actualizar
                </button>
            </div>
        </main>

        <div class="toast-container" id="toast-container"></div>
    </div>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script src="../../assets/js/modulos/mecanicos/ordenes.js?v=20260915cerca2"></script>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
