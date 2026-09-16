<?php
$title = 'Consumos - App Mecánicos';
$page_title = 'Consumos';
$active_module = 'consumos';
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Información de la Orden -->
            <div class="orden-info" id="orden-info">
                <!-- Se llena dinámicamente -->
            </div>

            <!-- Consumos Registrados -->
            <div class="consumos-section">
                <h2><i class="fas fa-list"></i> Consumos Registrados</h2>
                <div class="consumos-lista" id="consumos-lista">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>

            <!-- Formulario de Consumo -->
            <div class="consumo-form-section">
                <form id="form-consumo" class="consumo-form">
                    <!-- Se llena dinámicamente -->
                </form>
            </div>
        </main>

        <div class="toast-container" id="toast-container"></div>
    </div>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script src="../../assets/js/modulos/mecanicos/consumos.js?v=20260915inv1"></script>
    <style>
        .orden-info {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
        }

        .orden-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-md);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }

        .orden-header h2 {
            margin: 0;
            color: var(--text-dark);
        }

        .orden-details {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row .label {
            font-weight: 500;
            color: var(--text-muted);
        }

        .detail-row .value {
            color: var(--text-dark);
            font-weight: 500;
        }

        .consumos-section {
            margin-bottom: var(--spacing-lg);
        }

        .consumos-section h2 {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-md);
            color: var(--text-dark);
        }

        .consumos-section h2 i {
            color: var(--color-primary);
        }

        .consumos-lista {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-sm);
        }

        .consumo-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            gap: var(--spacing-md);
        }

        .consumo-item:last-child {
            border-bottom: none;
        }

        .consumo-info {
            flex: 1;
        }

        .consumo-info h4 {
            margin: 0 0 var(--spacing-xs) 0;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .consumo-info .cantidad {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-right: var(--spacing-sm);
        }

        .consumo-info .precio {
            font-weight: 600;
            color: var(--color-success);
        }

        .consumo-meta {
            text-align: right;
        }

        .consumo-meta .fecha {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .consumo-meta .observaciones {
            margin: var(--spacing-xs) 0 0 0;
            font-size: 0.875rem;
            color: var(--text-muted);
            font-style: italic;
        }

        .consumo-form-section {
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-lg);
            box-shadow: var(--shadow-sm);
        }

        .consumo-form h3 {
            margin: 0 0 var(--spacing-lg) 0;
            color: var(--text-dark);
        }

        .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .form-group label {
            display: block;
            margin-bottom: var(--spacing-sm);
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: var(--spacing-md);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: var(--bg-light);
            color: var(--text-dark);
            font-size: 1rem;
            transition: var(--transition-fast);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: var(--spacing-xs);
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .producto-seleccionado {
            min-height: 60px;
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-style: italic;
        }

        .producto-seleccionado .producto-card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--spacing-md);
            background: var(--bg-light);
            width: 100%;
            margin: 0;
        }

        .producto-seleccionado .producto-info {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }

        .producto-seleccionado .producto-info strong {
            color: var(--text-dark);
            font-size: 1rem;
        }

        .producto-seleccionado .producto-info span {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .producto-seleccionado .producto-details {
            display: flex;
            justify-content: space-between;
            margin-top: var(--spacing-sm);
            font-size: 0.875rem;
        }

        .producto-seleccionado .producto-details span {
            color: var(--text-muted);
        }

        .form-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
        }

        .error-message {
            color: var(--color-danger);
            font-size: 0.875rem;
            margin-top: var(--spacing-xs);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .error-message::before {
            content: '⚠️';
        }

        .field-error {
            border-color: var(--color-danger) !important;
        }

        @media (max-width: 480px) {
            .consumo-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .consumo-meta {
                text-align: left;
                width: 100%;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
