<?php
$title = 'Agenda y Citas - Taller El Paisa';
$css_files = ['modulos-integrados.css'];
$active_page = 'agenda';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left"><h2 class="page-title">Agenda y Citas</h2><div class="breadcrumb"><span>Inicio</span> / <span>Agenda</span></div></div>
                <div class="topbar-right"><button class="btn btn-outline btn-sm" id="btn-refresh-agenda"><i class="fas fa-sync-alt"></i></button></div>
            </header>

            <div class="content-wrapper" id="modulo-agenda">
                <div class="kpi-row">
                    <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-calendar-check"></i></div><div><p class="kpi-label">Pendientes</p><p class="kpi-value" id="kpi-citas-hoy">0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-user-check"></i></div><div><p class="kpi-label">Confirmadas</p><p class="kpi-value" id="kpi-citas-confirmadas">0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon warning"><i class="fas fa-clipboard-list"></i></div><div><p class="kpi-label">Ya en OT</p><p class="kpi-value" id="kpi-citas-ot">0</p></div></div>
                </div>

                <div class="actions-bar d-flex justify-content-between mb-3 align-items-end" style="gap:1rem;flex-wrap:wrap">
                    <div class="d-flex" style="gap:0.75rem;flex-wrap:wrap;align-items:end">
                        <div class="form-group" style="margin:0"><label>Fecha</label><input type="date" id="filter-fecha-agenda" class="form-control"></div>
                        <div class="form-group" style="margin:0"><label>Estado</label>
                            <select id="filter-estado-citas" class="form-control">
                                <option value="todos">Todos</option>
                                <option value="Programada">Programada</option>
                                <option value="Confirmada">Confirmada</option>
                                <option value="Completada">Convertida a OT</option>
                                <option value="Cancelada">Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex;gap:0.5rem">
                        <button class="btn btn-outline" id="btn-nuevo-horario"><i class="fas fa-plus"></i> Crear horario</button>
                        <button class="btn btn-primary" id="btn-nueva-cita"><i class="fas fa-plus"></i> Nueva Cita</button>
                    </div>
                </div>

                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="agenda"><i class="fas fa-calendar"></i><span class="tab-label">Disponibilidad</span></button>
                    <button type="button" class="tab-btn" data-tab="citas"><i class="fas fa-list"></i><span class="tab-label">Citas</span></button>
                </nav>

                <div class="module-panel active" data-panel="agenda">
                    <section class="card"><div class="card-header"><h3>Horarios del día</h3><p class="text-muted" style="margin:0.25rem 0 0;font-size:0.85rem">Un cupo libre se agenda al instante. Si ya hay cliente, el slot muestra el nombre.</p></div>
                        <div class="card-body"><div class="agenda-grid" id="agenda-grid"></div></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="citas">
                    <p class="text-muted" style="margin:0 0 0.75rem;font-size:0.9rem">Cuando el cliente llega, confirme la cita y conviértala en orden. El mecánico del horario queda asignado y el servicio entra como primera línea.</p>
                    <section class="card"><div class="card-header"><h3>Listado de citas</h3></div>
                        <div class="card-body"><table class="table" id="tabla-citas"><thead><tr>
                            <th>Cliente</th><th>Vehículo</th><th>Servicio</th><th>Mecánico</th><th>Fecha/Hora</th><th>Estado</th><th>Acciones</th>
                        </tr></thead><tbody></tbody></table></div>
                    </section>
                </div>
            </div>
        </main>

    <div class="modal" id="modal-cita">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-cita-title">Nueva Cita</h3><button class="btn-close" id="btn-cerrar-cita"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-cita" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <input type="hidden" name="id_horario_prev" id="cita-id-horario-prev">
                    <div id="cita-form-errores" class="form-errores"></div>
                    <div class="form-group" style="grid-column:1/-1"><label>Cliente *</label><select name="id_cliente" id="cita-id-cliente" required></select></div>
                    <div class="form-group"><label>Vehículo *</label><select name="id_vehiculo" id="cita-id-vehiculo" required></select></div>
                    <div class="form-group"><label>Servicio *</label><select name="id_servicio" id="cita-id-servicio" required></select></div>
                    <div class="form-group"><label>Fecha *</label><input type="date" id="cita-fecha" required></div>
                    <div class="form-group"><label>Horario *</label><select name="id_horario" id="cita-id-horario" required></select></div>
                    <div class="form-group" style="grid-column:1/-1"><label>Motivo</label><textarea name="motivo" rows="2"></textarea></div>
                    <div class="form-group" id="grupo-estado-cita"><label>Estado</label><select name="estado_cita" id="cita-estado"><option>Programada</option><option>Confirmada</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" id="btn-cancelar-cita">Cancelar</button><button class="btn btn-primary" id="btn-guardar-cita">Guardar Cita</button></div>
        </div>
    </div>

    <div class="modal" id="modal-horario">
        <div class="modal-content">
            <div class="modal-header"><h3>Crear disponibilidad</h3><button class="btn-close" id="btn-cerrar-horario"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-horario" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div id="horario-form-errores" class="form-errores"></div>
                    <div class="form-group"><label>Mecánico *</label><select name="id_usuario" id="horario-id-usuario" required></select></div>
                    <div class="form-group"><label>Fecha *</label><input type="date" name="fecha" id="horario-fecha" required></div>
                    <div class="form-group"><label>Hora inicio *</label><input type="time" name="hora_inicio" required value="08:00"></div>
                    <div class="form-group"><label>Capacidad</label><input type="number" name="capacidad" value="1" min="1"></div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" id="btn-cancelar-horario">Cancelar</button><button class="btn btn-primary" id="btn-guardar-horario">Guardar</button></div>
        </div>
    </div>

    <!-- Scripts específicos del módulo (después de config/api del footer) -->
<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/agenda.js?v=20260915int1"></script>
