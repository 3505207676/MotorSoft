<?php
$title = 'Auditoría - Taller El Paisa';
$css_files = ['modulos-integrados.css'];
$active_page = 'auditoria';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
<style>
.audit-resumen{margin:0 0 1rem;font-size:1.05rem;font-weight:600;color:var(--text-primary,#0f172a)}
.audit-meta{display:grid;grid-template-columns:1fr 1fr;gap:0.75rem 1rem;margin-bottom:1rem}
.audit-meta label{display:block;font-size:0.75rem;color:#64748b;margin-bottom:0.15rem}
.audit-meta p{margin:0}
.audit-cambios{width:100%;border-collapse:collapse;font-size:0.9rem}
.audit-cambios th,.audit-cambios td{padding:0.55rem 0.7rem;border-bottom:1px solid #e2e8f0;text-align:left;vertical-align:top}
.audit-cambios th{color:#64748b;font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.03em}
.audit-antes{color:#b42318;text-decoration:line-through;text-decoration-thickness:1px}
.audit-despues{color:#0a7a32;font-weight:600}
.tabla-auditoria td.col-hecho{max-width:420px}
</style>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Auditoría</h2>
                    <div class="breadcrumb"><span>Inicio</span> / <span>Auditoría</span></div>
                </div>
                <div class="topbar-right">
                    <button class="btn btn-outline btn-sm" id="btn-export-audit" type="button"><i class="fas fa-file-csv"></i> Exportar CSV</button>
                    <button class="btn btn-outline btn-sm" id="btn-refresh-audit" type="button"><i class="fas fa-sync-alt"></i> Actualizar</button>
                </div>
            </header>

            <div class="content-wrapper">
                <div class="kpi-row">
                    <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-history"></i></div><div><p class="kpi-label">Eventos hoy</p><p class="kpi-value" id="kpi-audit-hoy">0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon info"><i class="fas fa-filter"></i></div><div><p class="kpi-label">En el periodo</p><p class="kpi-value" id="kpi-audit-periodo">0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon warning"><i class="fas fa-list"></i></div><div><p class="kpi-label">Mostrados</p><p class="kpi-value" id="kpi-audit-mostrados">0</p></div></div>
                </div>
                <p class="text-muted" style="margin:0 0 1rem">Quién hizo qué, en lenguaje del taller. No aparecen nombres de tablas ni JSON.</p>

                <div class="report-toolbar">
                    <div class="report-presets">
                        <button type="button" class="btn btn-outline btn-sm" data-rango="hoy">Hoy</button>
                        <button type="button" class="btn btn-outline btn-sm" data-rango="semana">Esta semana</button>
                        <button type="button" class="btn btn-outline btn-sm active" data-rango="mes">Este mes</button>
                    </div>
                    <div>
                        <label for="audit-desde">Desde</label>
                        <input type="date" id="audit-desde" class="form-control">
                    </div>
                    <div>
                        <label for="audit-hasta">Hasta</label>
                        <input type="date" id="audit-hasta" class="form-control">
                    </div>
                    <div>
                        <label for="filter-accion">Acción</label>
                        <select id="filter-accion" class="form-control">
                            <option value="todos">Todas las acciones</option>
                            <option value="LOGIN">Inició sesión</option>
                            <option value="CREATE">Registró / abrió</option>
                            <option value="UPDATE">Actualizó / cerró</option>
                            <option value="DELETE">Eliminó</option>
                            <option value="LOGOUT">Cerró sesión</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-modulo">Módulo</label>
                        <select id="filter-modulo" class="form-control">
                            <option value="todos">Todos los módulos</option>
                        </select>
                    </div>
                    <div class="search-container" style="min-width:220px">
                        <input type="text" id="search-audit" placeholder="Buscar por persona o lo que ocurrió...">
                        <button type="button"><i class="fas fa-search"></i></button>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-aplicar-audit"><i class="fas fa-filter"></i> Aplicar</button>
                </div>
                <p class="text-muted" id="audit-aviso-truncado" hidden style="margin:0 0 1rem">Hay más eventos de los que se muestran. Acote el rango de fechas.</p>

                <section class="card">
                    <div class="card-header"><h3>Actividad del periodo</h3></div>
                    <div class="card-body">
                        <table class="table tabla-auditoria" id="tabla-auditoria"><thead><tr>
                            <th>Cuándo</th><th>Quién</th><th>Qué ocurrió</th><th>Módulo</th><th></th>
                        </tr></thead><tbody></tbody></table>
                    </div>
                </section>
            </div>
        </main>

<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/auditoria.js?v=20260915audit2"></script>
