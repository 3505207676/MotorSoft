<?php
$title = 'Stock - App Mecánicos';
$page_title = 'Stock disponible';
$active_module = 'stock';
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>
        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Búsqueda -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-productos" placeholder="Buscar por nombre o código...">
                    <button class="btn-icon" id="btn-clear-search" aria-label="Limpiar búsqueda">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Filtros de Categoría -->
            <div class="filters-container">
                <div class="filter-tabs" id="categorias-filters">
                    <!-- Se llenan dinámicamente -->
                </div>
            </div>

            <!-- Lista de Productos -->
            <div class="productos-container" id="productos-container">
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando productos...</p>
                </div>
            </div>
        </main>

        <div class="toast-container" id="toast-container"></div>
    </div>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script src="../../assets/js/modulos/mecanicos/stock.js?v=20260914mec"></script>
    <style>
        .search-container {
            margin-bottom: var(--spacing-lg);
        }

        .search-box {
            position: relative;
            display: flex;
            align-items: center;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-sm);
        }

        .search-box i {
            color: var(--text-muted);
            margin-right: var(--spacing-sm);
        }

        .search-box input {
            flex: 1;
            border: none;
            background: transparent;
            color: var(--text-dark);
            font-size: 1rem;
            padding: var(--spacing-sm);
        }

        .search-box input:focus {
            outline: none;
        }

        .search-box input::placeholder {
            color: var(--text-muted);
        }

        .producto-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            transition: var(--transition-fast);
        }

        .producto-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-1px);
        }

        .producto-card.stock-critico {
            border-left: 4px solid var(--color-danger);
        }

        .producto-card.stock-bajo {
            border-left: 4px solid var(--color-warning);
        }

        .producto-card.stock-normal {
            border-left: 4px solid var(--color-success);
        }

        .producto-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }

        .producto-info h3 {
            margin: 0 0 var(--spacing-xs) 0;
            color: var(--text-dark);
            font-size: 1.125rem;
        }

        .producto-nombre {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .stock-badge {
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--border-radius-full);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .stock-badge.stock-critico {
            background: rgba(239, 68, 68, 0.1);
            color: var(--color-danger);
        }

        .stock-badge.stock-bajo {
            background: rgba(245, 158, 11, 0.1);
            color: var(--color-warning);
        }

        .stock-badge.stock-normal {
            background: rgba(16, 185, 129, 0.1);
            color: var(--color-success);
        }

        .producto-body {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }

        .producto-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .producto-actions {
            display: flex;
            gap: var(--spacing-sm);
        }

        .sugerencias-container {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 var(--border-radius-lg) var(--border-radius-lg);
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
        }

        .sugerencia-item {
            padding: var(--spacing-md);
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition-fast);
        }

        .sugerencia-item:hover {
            background: var(--bg-light);
        }

        .sugerencia-item:last-child {
            border-bottom: none;
        }

        .sugerencia-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .sugerencia-info strong {
            color: var(--text-dark);
        }

        .sugerencia-info span {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .sugerencia-stock {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: var(--spacing-xs);
        }

        /* Modales */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: var(--spacing-md);
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-dark);
            font-size: 1.25rem;
        }

        .modal-body {
            padding: var(--spacing-lg);
        }

        .modal-footer {
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
            padding: var(--spacing-lg);
            border-top: 1px solid var(--border-color);
        }

        .producto-detalle-grid {
            display: grid;
            gap: var(--spacing-md);
        }

        .detalle-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detalle-item:last-child {
            border-bottom: none;
        }

        .detalle-item label {
            font-weight: 500;
            color: var(--text-muted);
        }

        .detalle-item span {
            color: var(--text-dark);
            text-align: right;
        }

        .detalle-completo {
            flex-direction: column;
            align-items: flex-start;
            gap: var(--spacing-xs);
        }

        .detalle-completo span {
            text-align: left;
        }

        .stock-info {
            font-weight: 600;
            color: var(--color-primary);
        }

        /* Modal de retiro */
        .modal-retiro {
            max-width: 600px;
        }

        .producto-info-retiro {
            background: var(--bg-light);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }

        .producto-nombre {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: var(--spacing-xs);
        }

        .producto-codigo {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-xs);
        }

        .producto-stock-actual {
            color: var(--text-dark);
            font-size: 0.875rem;
        }

        .form-retiro {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }

        .form-retiro .form-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .form-retiro label {
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-retiro input,
        .form-retiro select,
        .form-retiro textarea {
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-light);
            color: var(--text-dark);
            font-size: 1rem;
            transition: var(--transition-fast);
        }

        .form-retiro input:focus,
        .form-retiro select:focus,
        .form-retiro textarea:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-retiro small {
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .form-retiro textarea {
            min-height: 80px;
            resize: vertical;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Responsive modales */
        @media (max-width: 768px) {
            .modal-overlay {
                padding: var(--spacing-sm);
            }

            .modal-content {
                max-height: 95vh;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: var(--spacing-md);
            }

            .modal-footer {
                flex-direction: column;
            }

            .detalle-item {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }

            .detalle-item span {
                text-align: left;
            }
        }
    </style>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
