<?php
$title = 'Detalle de orden - App Mecánicos';
$page_title = 'Detalle de orden';
$active_module = 'ordenes';
$show_back = true;
$show_notifications = false;
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>
        <main class="main-content">
            <div class="orden-detalle-container" id="orden-detalle-container">
                <div class="loading-state" id="loading-detalle">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando detalle de la orden...</p>
                </div>
            </div>
            <div class="orden-actions-container hidden" id="orden-actions-container">
                <div class="actions-grid">
                    <button class="action-btn" id="btn-iniciar-orden" type="button">
                        <i class="fas fa-play"></i><span>Iniciar</span>
                    </button>
                    <button class="action-btn" id="btn-pausar-orden" type="button">
                        <i class="fas fa-pause"></i><span>Pausar</span>
                    </button>
                    <button class="action-btn" id="btn-finalizar-orden" type="button">
                        <i class="fas fa-check"></i><span>Finalizar</span>
                    </button>
                    <button class="action-btn" id="btn-agregar-producto" type="button">
                        <i class="fas fa-box-open"></i><span>Agregar producto</span>
                    </button>
                    <button class="action-btn" id="btn-chat-orden" type="button">
                        <i class="fas fa-comments"></i><span>Chat</span>
                    </button>
                    <button class="action-btn" id="btn-consumos-orden" type="button">
                        <i class="fas fa-tools"></i><span>Consumos</span>
                    </button>
                </div>
            </div>
        </main>

        <div class="modal-overlay hidden" id="modal-agregar-producto">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Agregar producto</h3>
                    <button class="btn-icon" id="btn-cerrar-producto" type="button" aria-label="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="form-agregar-producto" class="modal-body">
                    <div class="form-group">
                        <label for="producto-orden">Producto</label>
                        <select id="producto-orden" name="id_producto" required>
                            <option value="">Cargando inventario...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cantidad-orden">Cantidad</label>
                        <input type="number" id="cantidad-orden" name="cantidad" min="1" value="1" required>
                    </div>
                    <div class="modal-footer" style="padding: 0.75rem 0 0; justify-content: flex-end; gap: 0.5rem;">
                        <button type="button" class="btn btn-outline" id="btn-cancelar-producto">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="toast-container" id="toast-container"></div>
    </div>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script src="../../assets/js/modulos/mecanicos/detalle.js?v=20260915inv1"></script>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
