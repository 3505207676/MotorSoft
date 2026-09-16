<?php
$title = 'Contabilidad - Taller El Paisa';
$css_files = ['modulos-integrados.css', 'contabilidad.css'];
$active_page = 'contabilidad';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
<style>
.text-success{color:#16a34a;font-weight:600}.text-danger{color:#dc2626;font-weight:600}
#modal-cerrar-caja{z-index:5000}
#modal-cerrar-caja:not(.active):not(.show){display:none !important}
#banner-turno-caja,#caja-acciones-abierta:not([hidden]){display:flex;flex-wrap:wrap;align-items:center;gap:0.75rem;margin-bottom:1rem;padding:0.85rem 1rem;border-radius:8px}
#caja-acciones-abierta[hidden]{display:none !important}
#btn-nueva-cuenta[hidden]{display:none !important}
#caja-acciones-abierta input{max-width:160px}
</style>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left"><h2 class="page-title">Contabilidad</h2><div class="breadcrumb"><span>Inicio</span> / <span>Contabilidad</span></div></div>
                <div class="topbar-right"><button class="btn btn-outline btn-sm" id="btn-refresh-cont"><i class="fas fa-sync-alt"></i> Actualizar</button></div>
            </header>

            <div class="content-wrapper" id="modulo-contabilidad">
                <div id="aviso-caja" class="alert aviso-caja" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px"></div>
                <div class="card-grid mb-3" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem">
                    <div class="card card-stat"><div class="card-icon bg-success"><i class="fas fa-arrow-up"></i></div><div class="card-content"><h3 id="lbl-ingresos">Ingresos del turno</h3><p class="stat-value" id="total-ingresos">$0</p></div></div>
                    <div class="card card-stat"><div class="card-icon bg-danger"><i class="fas fa-arrow-down"></i></div><div class="card-content"><h3 id="lbl-egresos">Egresos del turno</h3><p class="stat-value" id="total-egresos">$0</p></div></div>
                    <div class="card card-stat"><div class="card-icon bg-primary"><i class="fas fa-balance-scale"></i></div><div class="card-content"><h3 id="lbl-balance">Saldo en sistema</h3><p class="stat-value" id="balance">$0</p></div></div>
                    <div class="card card-stat"><div class="card-icon bg-warning"><i class="fas fa-wallet"></i></div><div class="card-content"><h3>Saldo en cuentas</h3><p class="stat-value" id="saldo-cuentas">$0</p></div></div>
                </div>

                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="resumen"><i class="fas fa-chart-pie"></i><span class="tab-label">Resumen</span></button>
                    <button type="button" class="tab-btn" data-tab="cuentas"><i class="fas fa-university"></i><span class="tab-label">Cuentas</span></button>
                    <button type="button" class="tab-btn" data-tab="movimientos"><i class="fas fa-exchange-alt"></i><span class="tab-label">Movimientos</span></button>
                    <button type="button" class="tab-btn" data-tab="caja"><i class="fas fa-cash-register"></i><span class="tab-label">Caja</span></button>
                </nav>

                <div class="module-panel active" data-panel="resumen">
                    <div id="banner-turno-caja" class="banner-turno cerrada">Cargando turno de caja...</div>
                    <section class="card"><div class="card-header"><h3>Cuentas del taller</h3></div>
                        <div class="card-body"><table class="table" id="tabla-resumen-cuentas"><thead><tr><th>Nombre</th><th>Tipo</th><th>Saldo</th><th>Estado de la cuenta</th><th>Turno de caja</th></tr></thead><tbody></tbody></table></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="cuentas">
                    <div class="actions-bar mb-3" style="text-align:right"><button class="btn btn-primary" id="btn-nueva-cuenta"><i class="fas fa-plus"></i> Nueva cuenta</button></div>
                    <section class="card"><div class="card-header"><h3>Cuentas financieras</h3></div>
                        <div class="card-body"><table class="table" id="tabla-cuentas"><thead><tr><th>Nombre</th><th>Tipo</th><th>Saldo</th><th>Estado</th><th>Acciones</th></tr></thead><tbody></tbody></table></div>
                    </section>
                </div>
                

                <div class="module-panel" data-panel="movimientos">
                    <div class="actions-bar d-flex justify-content-between mb-3">
                        <div class="d-flex gap-2">
                            <select id="filter-tipo-tx" class="form-control"><option value="todos">Todos</option><option value="Ingreso">Ingresos</option><option value="Egreso">Egresos</option></select>
                            <select id="filter-cuenta-tx" class="form-control"></select>
                            <select id="filter-ambito-tx" class="form-control">
                                <option value="turno">Este turno</option>
                                <option value="todos">Todos los movimientos</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" id="btn-nueva-transaccion"><i class="fas fa-plus"></i> Nueva transacción</button>
                    </div>
                    <section class="card"><div class="card-header"><h3>Transacciones financieras</h3></div>
                        <div class="card-body"><table class="table" id="tabla-transacciones"><thead><tr><th>Fecha</th><th>Concepto</th><th>Tipo</th><th>Cuenta</th><th>Monto</th><th>Acciones</th></tr></thead><tbody></tbody></table></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="caja">
                    <div id="caja-status" class="caja-status cerrada"></div>
                    <div id="caja-acciones-abierta" class="banner-turno abierta" hidden>
                        <button type="button" class="btn btn-danger" id="btn-cerrar-caja"><i class="fas fa-lock"></i> Cerrar caja</button>
                    </div>
                    <div id="form-abrir-caja" class="card mb-3" style="padding:1rem">
                        <h4 style="margin-bottom:1rem">Abrir sesión de caja</h4>
                        <p class="text-muted" id="hint-apertura-caja" style="margin:0 0 1rem">El monto de apertura debe ser el efectivo que hay en caja. Ese valor queda como saldo de la cuenta.</p>
                        <div class="d-flex gap-2 flex-wrap align-items-end">
                            <div class="form-group"><label>Cuenta</label><select id="caja-id-cuenta" class="form-control"></select></div>
                            <div class="form-group"><label>Efectivo al abrir</label><input type="number" id="caja-monto-apertura" class="form-control" value="0" min="0" step="0.01"></div>
                            <button type="button" class="btn btn-success" id="btn-abrir-caja"><i class="fas fa-lock-open"></i> Abrir caja</button>
                        </div>
                    </div>
                    <section class="card"><div class="card-header"><h3>Historial de sesiones</h3></div>
                        <div class="card-body"><table class="table" id="tabla-sesiones-caja"><thead><tr><th>Apertura</th><th>Cierre</th><th>Responsable</th><th>Cuenta</th><th>M. Apertura</th><th>M. Real</th><th>Diferencia</th><th>Estado</th></tr></thead><tbody></tbody></table></div>
                    </section>
                </div>
            </div>
        </main>

    <div class="modal" id="modal-transaccion">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-transaccion-title">Nueva transacción</h3><button class="btn-close" id="btn-cerrar-tx"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-transaccion" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group"><label>Tipo *</label><select name="tipo" required><option value="Ingreso">Ingreso</option><option value="Egreso">Egreso</option></select></div>
                    <div class="form-group"><label>Concepto *</label><select name="id_concepto" id="tx-id-concepto" required></select></div>
                    <div class="form-group"><label>Cuenta de la caja</label><select name="id_cuenta" id="tx-id-cuenta" disabled></select></div>
                    <div class="form-group"><label>Monto *</label><input type="number" name="monto" required min="1" step="0.01"></div>
                    <div class="form-group" style="grid-column:1/-1"><p class="text-muted" style="margin:0">El movimiento queda en la cuenta de la caja que usted tiene abierta. No se puede registrar dinero sin sesión activa.</p></div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" id="btn-cancelar-tx">Cancelar</button><button class="btn btn-primary" id="btn-guardar-transaccion">Guardar</button></div>
        </div>
    </div>

    <div class="modal" id="modal-cuenta">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-cuenta-title">Nueva cuenta</h3><button class="btn-close" id="btn-cerrar-cuenta"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-cuenta" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group" style="grid-column:1/-1"><label>Nombre *</label><input type="text" name="nombre" required></div>
                    <div class="form-group"><label>Tipo *</label><select name="tipo" required><option>Efectivo</option><option>Banco</option><option>Otro</option></select></div>
                    <div class="form-group"><label>Saldo inicial</label><input type="number" name="saldo_actual" value="0" min="0"></div>
                    <div class="form-group"><label>Estado</label><select name="estado"><option value="Activa">Activa</option><option value="Inactiva">Inactiva</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" id="btn-cancelar-cuenta">Cancelar</button><button class="btn btn-primary" id="btn-guardar-cuenta">Guardar</button></div>
        </div>
    </div>

    <div class="modal" id="modal-cerrar-caja">
        <div class="modal-content">
            <div class="modal-header"><h3>Cerrar caja</h3><button class="btn-close" id="btn-cerrar-cierre"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-cerrar-caja">
                    <p id="resumen-cierre-caja" class="text-muted"></p>
                    <div class="form-group">
                        <label>Efectivo físico al cerrar *</label>
                        <input type="number" name="monto_real" required min="0" step="0.01" value="0">
                    </div>
                    <p class="text-muted">Cuente el dinero que hay en caja. El saldo de la cuenta quedará igual a ese conteo. La diferencia con el sistema queda en el historial del turno.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-cancelar-cierre">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-cierre">Cerrar caja</button>
            </div>
        </div>
    </div>

<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/contabilidad.js?v=20260915caja2"></script>
