<?php
$title = 'Reportes - Taller El Paisa';
$css_files = ['modulos-integrados.css'];
$active_page = 'reportes';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Reportes</h2>
                    <div class="breadcrumb"><span>Inicio</span> / <span>Reportes</span></div>
                </div>
                <div class="topbar-right">
                    <button class="btn btn-outline btn-sm" id="btn-export-reportes" type="button"><i class="fas fa-file-csv"></i> Exportar CSV</button>
                    <button class="btn btn-outline btn-sm" id="btn-refresh-reportes" type="button"><i class="fas fa-sync-alt"></i></button>
                </div>
            </header>

            <div class="content-wrapper" id="modulo-reportes">
                <div class="report-toolbar">
                    <div class="report-presets" role="group" aria-label="Periodo rápido">
                        <button type="button" class="btn btn-outline btn-sm" data-rango="hoy">Hoy</button>
                        <button type="button" class="btn btn-outline btn-sm" data-rango="semana">Esta semana</button>
                        <button type="button" class="btn btn-outline btn-sm active" data-rango="mes">Este mes</button>
                        <button type="button" class="btn btn-outline btn-sm" data-rango="anio">Este año</button>
                    </div>
                    <div>
                        <label for="rep-desde">Desde</label>
                        <input type="date" id="rep-desde" class="form-control">
                    </div>
                    <div>
                        <label for="rep-hasta">Hasta</label>
                        <input type="date" id="rep-hasta" class="form-control">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-aplicar-reportes"><i class="fas fa-filter"></i> Aplicar</button>
                    <p class="report-periodo" id="rep-periodo-label"></p>
                </div>

                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="ordenes" data-permiso="reportes.ordenes"><i class="fas fa-clipboard-list"></i><span class="tab-label">Órdenes</span></button>
                    <button type="button" class="tab-btn" data-tab="facturacion" data-permiso="reportes.ordenes"><i class="fas fa-file-invoice-dollar"></i><span class="tab-label">Facturación</span></button>
                    <button type="button" class="tab-btn" data-tab="nomina" data-permiso="reportes.nomina"><i class="fas fa-money-check-alt"></i><span class="tab-label">Nómina</span></button>
                </nav>

                <div class="module-panel active" data-panel="ordenes">
                    <div class="kpi-row">
                        <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-list"></i></div><div><p class="kpi-label">Órdenes</p><p class="kpi-value" id="rep-ord-total">0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon warning"><i class="fas fa-clock"></i></div><div><p class="kpi-label">Pendientes</p><p class="kpi-value" id="rep-ord-pend">0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon info"><i class="fas fa-wrench"></i></div><div><p class="kpi-label">En proceso</p><p class="kpi-value" id="rep-ord-proc">0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-check"></i></div><div><p class="kpi-label">Completadas</p><p class="kpi-value" id="rep-ord-ok">0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-coins"></i></div><div><p class="kpi-label">Valor del periodo</p><p class="kpi-value" id="rep-ord-valor">$0</p></div></div>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Órdenes del periodo</h3></div>
                        <div class="card-body">
                            <table class="table" id="tabla-rep-ordenes">
                                <thead><tr><th>Nº</th><th>Estado</th><th>Cliente</th><th>Vehículo</th><th>Mecánico</th><th>Ingreso</th><th>Total</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="module-panel" data-panel="facturacion">
                    <div class="kpi-row">
                        <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-file-invoice"></i></div><div><p class="kpi-label">Emitidas</p><p class="kpi-value" id="rep-fac-emi">0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-check-circle"></i></div><div><p class="kpi-label">Cobrado</p><p class="kpi-value" id="rep-fac-cobrado">$0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon warning"><i class="fas fa-hourglass-half"></i></div><div><p class="kpi-label">Por cobrar</p><p class="kpi-value" id="rep-fac-pend">$0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon danger"><i class="fas fa-ban"></i></div><div><p class="kpi-label">Anuladas</p><p class="kpi-value" id="rep-fac-anu">0</p></div></div>
                    </div>
                    <p class="text-muted" id="rep-fac-metodos" style="margin:0 0 1rem"></p>
                    <section class="card">
                        <div class="card-header"><h3>Facturas del periodo</h3></div>
                        <div class="card-body">
                            <table class="table" id="tabla-rep-facturas">
                                <thead><tr><th>Número</th><th>Cliente</th><th>Tipo</th><th>Método</th><th>Estado</th><th>Fecha</th><th>Total</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div class="module-panel" data-panel="nomina">
                    <div class="kpi-row">
                        <div class="kpi-mini"><div class="kpi-icon primary"><i class="fas fa-file-invoice-dollar"></i></div><div><p class="kpi-label">Pagos del periodo</p><p class="kpi-value" id="rep-nom-pagos">0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon info"><i class="fas fa-calculator"></i></div><div><p class="kpi-label">Promedio</p><p class="kpi-value" id="rep-nom-prom">$0</p></div></div>
                        <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-coins"></i></div><div><p class="kpi-label">Total pagado</p><p class="kpi-value" id="rep-nom-total">$0</p></div></div>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Pagos de nómina</h3></div>
                        <div class="card-body">
                            <table class="table" id="tabla-rep-nomina">
                                <thead><tr><th>Periodo</th><th>Persona</th><th>Fecha</th><th>Neto</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </main>
<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/reportes.js?v=20260915rep2"></script>
