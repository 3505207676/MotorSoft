<?php
$title = 'Facturación y Ventas - Taller El Paisa';
$css_files = ['modulos-integrados.css', 'facturacion.css'];
$active_page = 'facturacion';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left"><h2 class="page-title">Facturación y Ventas</h2><div class="breadcrumb"><span>Inicio</span> / <span>Facturación</span></div></div>
                <div class="topbar-right">
                    <div class="search-container"><input type="text" id="search-factura" placeholder="Buscar factura..."><button><i class="fas fa-search"></i></button></div>
                    <button class="btn btn-outline btn-sm" id="btn-refresh-fac"><i class="fas fa-sync-alt"></i></button>
                </div>
            </header>

            <div class="content-wrapper" id="modulo-facturacion">
                <div id="barra-caja-facturacion" class="barra-estado cerrada" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding:0.85rem 1rem;border-radius:8px">
                    <div id="estado-caja-facturacion">Cargando caja...</div>
                    <div style="display:flex;gap:0.5rem">
                        <button type="button" class="btn btn-success btn-sm" id="btn-abrir-caja-fac" hidden><i class="fas fa-lock-open"></i> Abrir caja</button>
                        <button type="button" class="btn btn-danger btn-sm" id="btn-cerrar-caja-fac" hidden><i class="fas fa-lock"></i> Cerrar caja</button>
                    </div>
                </div>
                <div class="kpi-row">
                    <div class="kpi-mini"><div class="kpi-icon warning"><i class="fas fa-clock"></i></div><div><p class="kpi-label">Pendientes</p><p class="kpi-value" id="kpi-facturas-pendientes">0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon warning"><i class="fas fa-file-invoice-dollar"></i></div><div><p class="kpi-label">Por cobrar</p><p class="kpi-value" id="kpi-monto-pendiente">$0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-hand-holding-usd"></i></div><div><p class="kpi-label">Cobrado hoy</p><p class="kpi-value" id="kpi-cobrado-hoy">$0</p></div></div>
                    <div class="kpi-mini"><div class="kpi-icon success"><i class="fas fa-shopping-cart"></i></div><div><p class="kpi-label">Mostrador hoy</p><p class="kpi-value" id="kpi-ventas-hoy">$0</p></div></div>
                </div>
                <p class="text-muted" style="margin:-0.5rem 0 1rem">El efectivo entra a la caja del turno. Tarjeta y transferencia van a la cuenta Banco y no alteran el conteo del cajón. La venta de mostrador exige caja abierta.</p>

                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="facturas"><i class="fas fa-file-invoice"></i><span class="tab-label">Facturas</span></button>
                    <button type="button" class="tab-btn" data-tab="ventas"><i class="fas fa-store"></i><span class="tab-label">Ventas Directas</span></button>
                </nav>

                <div class="module-panel active" data-panel="facturas">
                    <div class="actions-bar d-flex justify-content-between mb-3">
                        <div class="d-flex gap-2">
                            <select id="filter-estado" class="form-control"><option value="todos">Todos</option><option>Pagada</option><option>Pendiente</option><option>Anulada</option></select>
                            <select id="filter-tipo" class="form-control">
                                <option value="todos">Todos los tipos</option>
                                <option value="Orden de servicio">Orden de servicio</option>
                                <option value="Venta mostrador">Venta mostrador</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" id="btn-nueva-factura"><i class="fas fa-plus"></i> Facturar orden</button>
                    </div>
                    <section class="card"><div class="card-header"><h3>Facturas emitidas</h3></div>
                        <div class="card-body"><table class="table" id="tabla-facturas"><thead>                        <tr>
                            <th>Nº</th><th>Fecha</th><th>Cliente</th><th>Tipo</th><th>Pago</th><th>Total</th><th>Estado</th><th>Acciones</th>
                        </tr></thead><tbody></tbody></table></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="ventas">
                    <div class="actions-bar mb-3" style="text-align:right">
                        <button class="btn btn-success" id="btn-nueva-venta"><i class="fas fa-cash-register"></i> Nueva Venta Directa</button>
                    </div>
                    <section class="card"><div class="card-header"><h3>Ventas de mostrador</h3><p class="text-muted" style="margin:0.25rem 0 0;font-size:0.85rem">Descuenta stock y genera factura de mostrador</p></div>
                        <div class="card-body"><table class="table" id="tabla-ventas"><thead>                        <tr>
                            <th>#</th><th>Fecha</th><th>Cliente</th><th>Items</th><th>Total</th><th>Factura</th><th>Acciones</th>
                        </tr></thead><tbody></tbody></table></div>
                    </section>
                </div>
            </div>
        </main>

    <div class="modal" id="modal-factura">
        <div class="modal-content modal-lg">
            <div class="modal-header"><h3 id="modal-factura-title">Facturar orden</h3><button class="btn-close" id="btn-cerrar-factura"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-factura" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group" style="grid-column:1/-1">
                        <label>Orden de servicio *</label>
                        <select name="id_orden" id="factura-orden" required>
                            <option value="">Seleccione una orden lista para cobro...</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Cliente</label><input type="text" id="factura-cliente-nombre" readonly></div>
                    <div class="form-group"><label>Vehículo</label><input type="text" id="factura-vehiculo" readonly></div>
                    <div class="form-group"><label>Método pago *</label><select name="metodo_pago" id="factura-metodo" required><option>Efectivo</option><option>Tarjeta</option><option>Transferencia</option></select></div>
                    <div class="form-group"><label>Cobro</label><select name="estado" id="factura-estado"><option value="Pendiente">Dejar pendiente</option><option value="Pagada">Cobrar ahora</option></select></div>
                    <p id="factura-caja-hint" class="text-muted" style="grid-column:1/-1;margin:0">Efectivo suma al cajón. Tarjeta o transferencia van a Banco.</p>
                </form>
                <h4 style="margin:1rem 0 0.5rem">Detalle</h4>
                <table class="table"><thead><tr><th>Tipo</th><th>Descripción</th><th>Monto</th></tr></thead>
                    <tbody id="factura-lineas-tbody"></tbody>
                </table>
                <p id="factura-iva-label" class="text-muted" style="margin:0.5rem 0">IVA según configuración del taller</p>
                <p><strong>Subtotal:</strong> <span id="factura-subtotal-preview">$0</span></p>
                <p><strong>IVA:</strong> <span id="factura-iva-preview">$0</span></p>
                <p style="font-size:1.1rem"><strong>Total:</strong> <span id="factura-total-preview">$0</span></p>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" id="btn-cancelar-factura">Cancelar</button><button class="btn btn-primary" id="btn-guardar-factura">Emitir factura</button></div>
        </div>
    </div>

    <div class="modal" id="modal-venta">
        <div class="modal-content modal-lg">
            <div class="modal-header"><h3>Nueva Venta Directa</h3><button class="btn-close" id="btn-cerrar-venta"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-venta" class="mb-3" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group"><label>Cliente</label><select id="venta-cliente"></select></div>
                    <div class="form-group"><label>Método pago</label><select id="venta-metodo-pago"><option>Efectivo</option><option>Tarjeta</option><option>Transferencia</option></select></div>
                    <p class="text-muted" style="grid-column:1/-1;margin:0">Se descuenta stock y se cobra al confirmar. Efectivo al cajón; tarjeta o transferencia a Banco.</p>
                </form>
                <button type="button" class="btn btn-outline btn-sm mb-2" id="btn-add-venta-item"><i class="fas fa-plus"></i> Agregar producto</button>
                <table class="table venta-items-table"><thead><tr><th>Producto</th><th>Cant.</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody id="venta-items-tbody"></tbody>
                    <tfoot>
                        <tr><td colspan="2" style="text-align:right">Subtotal</td><td colspan="2" id="venta-subtotal-preview">$0</td></tr>
                        <tr><td colspan="2" style="text-align:right">IVA</td><td colspan="2" id="venta-iva-preview">$0</td></tr>
                        <tr><td colspan="2" style="text-align:right"><strong>Total</strong></td><td colspan="2"><strong id="venta-total-preview">$0</strong></td></tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer"><button class="btn btn-outline" id="btn-cancelar-venta">Cancelar</button><button class="btn btn-success" id="btn-guardar-venta"><i class="fas fa-check"></i> Confirmar Venta</button></div>
        </div>
    </div>

    <div class="modal" id="modal-abrir-caja-fac">
        <div class="modal-content">
            <div class="modal-header"><h3>Abrir caja</h3><button class="btn-close" id="btn-cerrar-abrir-caja"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-abrir-caja-fac">
                    <div class="form-group"><label>Cuenta de efectivo *</label><select id="fac-caja-cuenta" class="form-control" required></select></div>
                    <div class="form-group"><label>Monto de apertura *</label><input type="number" id="fac-caja-monto" class="form-control" min="0" step="0.01" value="0" required></div>
                    <p class="text-muted">Abra su turno para cobrar facturas y ventas de mostrador.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cancelar-abrir-caja">Cancelar</button>
                <button class="btn btn-success" id="btn-confirmar-abrir-caja">Abrir caja</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-cerrar-caja-fac">
        <div class="modal-content">
            <div class="modal-header"><h3>Cerrar caja</h3><button class="btn-close" id="btn-cerrar-cierre-caja"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <form id="form-cerrar-caja-fac">
                    <p id="fac-caja-cierre-resumen" class="text-muted"></p>
                    <div class="form-group"><label>Monto real en caja *</label><input type="number" id="fac-caja-monto-real" name="monto_real" class="form-control" min="0" step="0.01" required></div>
                    <p class="text-muted">El sistema compara este conteo con apertura + cobros − egresos de su turno.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cancelar-cierre-caja">Cancelar</button>
                <button class="btn btn-danger" id="btn-confirmar-cierre-caja">Cerrar caja</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-cobrar-factura">
        <div class="modal-content">
            <div class="modal-header"><h3>Cobrar factura</h3><button class="btn-close" id="btn-cerrar-cobrar"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <p id="cobrar-resumen" class="text-muted"></p>
                <div class="form-group"><label>Método de pago *</label>
                    <select id="cobrar-metodo" class="form-control">
                        <option>Efectivo</option>
                        <option>Tarjeta</option>
                        <option>Transferencia</option>
                    </select>
                </div>
                <p class="text-muted">Efectivo entra a su caja del turno. Tarjeta y transferencia se registran en Banco.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cancelar-cobrar">Cancelar</button>
                <button class="btn btn-success" id="btn-confirmar-cobrar">Registrar cobro</button>
            </div>
        </div>
    </div>

<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/facturacion-module.js?v=20260915int1"></script>
