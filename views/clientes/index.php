<?php
$title = 'Clientes y Vehículos - Taller El Paisa';
$css_files = ['modulos-integrados.css', 'vehiculos.css'];
$active_page = 'clientes';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Clientes y Vehículos</h2>
                    <div class="breadcrumb"><span>Inicio</span> / <span>Clientes y Vehículos</span></div>
                </div>
                <div class="topbar-right">
                    <div class="search-container">
                        <input type="text" id="search-cv" placeholder="Buscar cliente, documento o placa...">
                        <button type="button"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="topbar-actions">
                        <button class="btn-icon" id="theme-toggle"><i class="fas fa-moon"></i></button>
                    </div>
                </div>
            </header>

            <div class="content-wrapper" id="modulo-clientes-vehiculos">
                <div class="kpi-row">
                    <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-users"></i></div><div><p class="kpi-label">Clientes activos</p><p class="kpi-value" id="kpi-clientes">0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-car"></i></div><div><p class="kpi-label">Vehículos registrados</p><p class="kpi-value" id="kpi-vehiculos-total">0</p></div></div>
                </div>

                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="clientes"><i class="fas fa-users"></i><span class="tab-label">Clientes</span></button>
                    <button type="button" class="tab-btn" data-tab="vehiculos"><i class="fas fa-car"></i><span class="tab-label">Vehículos</span></button>
                </nav>

                <!-- Panel Clientes -->
                <div class="module-panel active" data-panel="clientes">
                    <div class="actions-bar d-flex justify-content-between align-items-center mb-3">
                        <select id="filter-cliente-estado" class="form-control">
                            <option value="todos">Todos los estados</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline" id="btn-refresh-cv"><i class="fas fa-sync-alt"></i> Actualizar</button>
                            <button class="btn btn-primary" id="btn-nuevo-cliente"><i class="fas fa-plus"></i> Nuevo Cliente</button>
                        </div>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Registro de Clientes</h3></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="tabla-clientes">
                                    <thead><tr>
                                        <th>Nombre</th><th>Documento</th><th>Email</th><th>Teléfono</th>
                                        <th>Contacto</th><th>Estado</th><th>Vehículos</th><th>Acciones</th>
                                    </tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div class="pagination">
                                <button class="btn btn-outline btn-sm" id="prev-clientes"><i class="fas fa-chevron-left"></i></button>
                                <span id="pag-clientes-label">Página 1 de 1</span>
                                <button class="btn btn-outline btn-sm" id="next-clientes"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Panel Vehículos -->
                <div class="module-panel" data-panel="vehiculos">
                    <div class="actions-bar d-flex justify-content-between align-items-center mb-3">
                        <select id="filter-vehiculo-estado" class="form-control">
                            <option value="todos">Todos los estados</option>
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                        <button class="btn btn-primary" id="btn-nuevo-vehiculo"><i class="fas fa-plus"></i> Nuevo Vehículo</button>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Registro de Vehículos</h3></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table" id="tabla-vehiculos">
                                    <thead><tr>
                                        <th>Placa</th><th>Tipo</th><th>Vehículo</th><th>Propietario</th><th>Estado</th><th>Acciones</th>
                                    </tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <div class="pagination">
                                <button class="btn btn-outline btn-sm" id="prev-vehiculos"><i class="fas fa-chevron-left"></i></button>
                                <span id="pag-vehiculos-label">Página 1 de 1</span>
                                <button class="btn btn-outline btn-sm" id="next-vehiculos"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>

    <!-- Modal Cliente -->
    <div class="modal" id="modal-cliente">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-cliente-title">Nuevo Cliente</h3>
                <button type="button" class="btn-close" id="btn-cerrar-cliente"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-cliente" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group" style="grid-column:1/-1"><label>Nombre completo *</label><input type="text" name="nombre" required data-filter="letters"></div>
                    <div class="form-group"><label>Documento *</label><input type="text" name="documento" required data-filter="digits" inputmode="numeric" maxlength="15"></div>
                    <div class="form-group"><label>Teléfono *</label><input type="tel" name="telefono" required data-filter="digits" inputmode="numeric" maxlength="15"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                    <div class="form-group"><label>Preferencia de contacto</label>
                        <select name="preferencia_contacto"><option>WhatsApp</option><option>Llamada</option><option>Email</option></select>
                    </div>
                    <div class="form-group"><label>Estado</label>
                        <select name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-cancelar-cliente">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-cliente">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal Vehículo -->
    <div class="modal" id="modal-vehiculo">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-vehiculo-title">Nuevo Vehículo</h3>
                <button type="button" class="btn-close" id="btn-cerrar-vehiculo"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-vehiculo" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group"><label>Placa *</label><input type="text" name="placa" required maxlength="7" data-filter="plate" placeholder="ABC123 o ABC12D"></div>
                    <div class="form-group"><label>Tipo *</label>
                        <select name="tipo" id="veh-tipo" required>
                            <option value="Automóvil">Automóvil</option>
                            <option value="Moto">Moto</option>
                            <option value="Camioneta">Camioneta</option>
                            <option value="Camión">Camión</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Cliente propietario *</label><select name="id_cliente" id="veh-id-cliente" required></select></div>
                    <div class="form-group"><label>Marca *</label><input type="text" name="marca" required data-filter="letters"></div>
                    <div class="form-group"><label>Modelo *</label><input type="text" name="modelo" required data-filter="alnum"></div>
                    <div class="form-group"><label>Año</label><input type="number" name="anio" min="1980" max="2099" data-filter="digits"></div>
                    <div class="form-group"><label>Estado</label>
                        <select name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-cancelar-vehiculo">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-vehiculo">Guardar</button>
            </div>
        </div>
    </div>

<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/trabajadores/clientes-vehiculos.js?v=20260915placa1"></script>
