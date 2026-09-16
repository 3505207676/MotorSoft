<?php
$title = 'Mi perfil - App Mecánicos';
$page_title = 'Mi perfil';
$active_module = 'perfil';
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>
        <main class="main-content">
            <div class="perfil-layout">
                <div class="perfil-header">
                    <div class="perfil-avatar-section">
                        <div class="perfil-avatar" id="perfil-avatar">
                            <div class="avatar-placeholder" id="avatar-iniciales">—</div>
                        </div>
                    </div>
                    <div class="perfil-info-basic">
                        <h2 class="perfil-nombre" id="perfil-nombre">Cargando...</h2>
                        <p class="perfil-rol" id="perfil-rol">Mecánico</p>
                        <div class="perfil-status">
                            <span class="status-badge" id="perfil-status">—</span>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-edit-perfil" id="btn-editar-perfil" type="button">
                        <i class="fas fa-edit"></i>
                        Editar perfil
                    </button>
                </div>

                <div class="perfil-content">
                    <div class="perfil-section">
                        <h3><i class="fas fa-user-circle"></i> Información personal</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nombre</span>
                                <span class="info-value" id="info-nombre">—</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Documento</span>
                                <span class="info-value" id="info-documento">—</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Teléfono</span>
                                <span class="info-value" id="info-telefono">—</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Correo</span>
                                <span class="info-value" id="info-email">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="perfil-section">
                        <h3><i class="fas fa-briefcase"></i> Información laboral</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ID de usuario</span>
                                <span class="info-value" id="info-id">—</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Especialidad</span>
                                <span class="info-value" id="info-especialidad">—</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Fecha de ingreso</span>
                                <span class="info-value" id="info-ingreso">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="perfil-section">
                        <h3><i class="fas fa-chart-line"></i> Mis órdenes</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary"><i class="fas fa-clipboard-list"></i></div>
                                <div class="stat-content">
                                    <span class="stat-number" id="stat-asignadas">0</span>
                                    <span class="stat-label">Asignadas</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon bg-warning"><i class="fas fa-tools"></i></div>
                                <div class="stat-content">
                                    <span class="stat-number" id="stat-activas">0</span>
                                    <span class="stat-label">Activas</span>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                                <div class="stat-content">
                                    <span class="stat-number" id="stat-completadas">0</span>
                                    <span class="stat-label">Completadas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <div class="modal-overlay hidden" id="modal-editar-perfil">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Editar perfil</h3>
                    <button class="btn-icon btn-close-modal" id="btn-cerrar-modal" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="form-editar-perfil" class="form-perfil">
                        <div class="form-group">
                            <label for="edit-nombre">Nombre</label>
                            <input type="text" id="edit-nombre" name="nombre" required minlength="2" maxlength="80">
                        </div>
                        <div class="form-group">
                            <label for="edit-telefono">Teléfono</label>
                            <input type="tel" id="edit-telefono" name="telefono" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-email">Correo</label>
                            <input type="email" id="edit-email" name="email" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="btn-cancelar-edicion">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-guardar-perfil">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
        <div class="toast-container" id="toast-container"></div>
    </div>
    <style>
        .avatar-placeholder {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-primary, #2563eb);
            color: #fff;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 40;
        }
        .modal-overlay.hidden { display: none !important; }
        .modal-content {
            background: var(--bg-card, #fff);
            border-radius: 12px;
            width: min(420px, 92vw);
            overflow: hidden;
        }
        .modal-header, .modal-footer, .modal-body { padding: 1rem 1.25rem; }
        .modal-header, .modal-footer { display: flex; justify-content: space-between; align-items: center; }
        .form-group { margin-bottom: 0.85rem; }
        .form-group input { width: 100%; }
    </style>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script src="../../assets/js/modulos/mecanicos/perfil.js?v=20260914lay3"></script>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
