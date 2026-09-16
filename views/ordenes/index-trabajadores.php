<?php
$title = 'Órdenes - Taller El Paisa';
$css_files = ['modulos-integrados.css', 'ordenes.css'];
$active_page = 'ordenes';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Órdenes de Trabajo</h2>
                    <div class="breadcrumb"><span>Inicio</span> / <span>Órdenes</span></div>
                </div>
                <div class="topbar-right">
                    <div class="search-container">
                        <input type="text" id="search-orden" placeholder="Buscar orden...">
                        <button type="button"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="topbar-actions">
                        <button class="btn-icon" id="btn-notificaciones" type="button"><i class="fas fa-bell"></i><span class="notification-badge" hidden>0</span></button>
                        <button class="btn-icon" id="theme-toggle" type="button"><i class="fas fa-moon"></i></button>
                    </div>
                </div>
            </header>

            <div class="content-wrapper" id="modulo-ordenes">
                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="ordenes"><i class="fas fa-clipboard-list"></i><span class="tab-label">Órdenes</span></button>
                    <button type="button" class="tab-btn" data-tab="catalogo"><i class="fas fa-list"></i><span class="tab-label">Catálogo de servicios</span></button>
                </nav>

                <div class="module-panel active" data-panel="ordenes">
                <div class="actions-bar">
                    <div class="actions-left">
                        <select id="filter-estado">
                            <option value="todos">Todos los estados</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Diagnóstico">Diagnóstico</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Pendiente Pago">Pendiente Pago</option>
                            <option value="Completada">Completada</option>
                            <option value="Cancelada">Cancelada</option>
                        </select>
                        <select id="filter-mecanico">
                            <option value="todos">Todos los mecánicos</option>
                        </select>
                    </div>
                    <div class="actions-right">
                        <button class="btn btn-outline" id="btn-refresh" type="button"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        <button class="btn btn-primary" id="btn-nueva-orden" type="button" onclick="return window.abrirModalOrden();">
                            <i class="fas fa-plus"></i> Nueva Orden
                        </button>
                    </div>
                </div>

                <section class="card">
                    <div class="card-header"><h3>Listado de Órdenes</h3></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="tabla-ordenes">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Vehículo</th>
                                        <th>Mecánico</th>
                                        <th>Estado</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div class="pagination">
                            <button class="btn btn-outline btn-sm" id="prev-page" type="button"><i class="fas fa-chevron-left"></i></button>
                            <span>Página 1 de 1</span>
                            <button class="btn btn-outline btn-sm" id="next-page" type="button"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </section>
                </div>

                <div class="module-panel" data-panel="catalogo">
                    <div class="actions-bar">
                        <div class="actions-left">
                            <input type="text" id="search-servicio" class="form-control" placeholder="Buscar servicio...">
                            <select id="filter-servicio-estado">
                                <option value="todos">Todos los estados</option>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="actions-right">
                            <button class="btn btn-outline" id="btn-refresh-servicios" type="button"><i class="fas fa-sync-alt"></i> Actualizar</button>
                            <button class="btn btn-primary" id="btn-nuevo-servicio" type="button">
                                <i class="fas fa-plus"></i> Nuevo servicio
                            </button>
                        </div>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Servicios del taller</h3></div>
                        <div class="card-body">
                            <p class="text-muted" id="catalogo-rol-aviso" style="margin-top:0">Estos nombres y precios aparecen al crear una orden. Desactivar uno no borra las órdenes ya hechas.</p>
                            <div class="table-responsive">
                                <table class="table" id="tabla-servicios">
                                    <thead>
                                        <tr>
                                            <th>Servicio</th>
                                            <th>Tipo</th>
                                            <th>Precio</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>

    <div class="modal" id="modal-orden-form">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="modal-orden-title">Nueva Orden de Trabajo</h3>
                <button class="btn-close" id="btn-cerrar-orden" type="button"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="orden-form-errores" class="alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:#fee2e2;color:#991b1b"></div>
                <form id="form-orden" novalidate>
                    <div class="form-section">
                        <h4>Información del Cliente y Vehículo</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="orden-cliente">Cliente *</label>
                                <select id="orden-cliente" name="clienteId" required>
                                    <option value="">Seleccione un cliente</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="orden-vehiculo">Vehículo *</label>
                                <select id="orden-vehiculo" name="vehiculoId" required>
                                    <option value="">Seleccione un vehículo</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="orden-mecanico">Mecánico *</label>
                                <select id="orden-mecanico" name="mecanicoId" required>
                                    <option value="">Seleccione mecánico</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="orden-estado">Estado</label>
                                <select id="orden-estado" name="estado">
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Diagnóstico">Diagnóstico</option>
                                    <option value="En Proceso">En Proceso</option>
                                    <option value="Pendiente Pago">Pendiente Pago</option>
                                    <option value="Completada">Completada</option>
                                    <option value="Cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="orden-fecha-entrega">Fecha estimada de entrega</label>
                                <input type="date" id="orden-fecha-entrega" name="fechaEntrega">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Servicios del catálogo</h4>
                        <p class="text-muted" id="servicios-catalogo-vacio" hidden style="margin:0 0 0.75rem">No hay servicios activos. Pida a un administrador o gerente que los cargue en el catálogo.</p>
                        <div id="servicios-container">
                            <div id="servicios-lista"></div>
                            <button type="button" class="btn btn-outline btn-sm" id="btn-agregar-servicio" onclick="return window.agregarServicio();">
                                <i class="fas fa-plus"></i> Agregar Servicio
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" id="btn-ir-catalogo-desde-orden">
                                <i class="fas fa-list"></i> Administrar catálogo
                            </button>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>Repuestos</h4>
                        <p class="text-muted" style="margin:0">Los repuestos se descuentan desde Inventario / Consumos del mecánico. Esta orden registra servicios del catálogo.</p>
                    </div>

                    <div class="form-section">
                        <h4>Totales</h4>
                        <div class="totales-resumen">
                            <div class="total-item">
                                <span>Subtotal servicios:</span>
                                <strong id="orden-subtotal">$0</strong>
                            </div>
                            <div class="total-item">
                                <span id="orden-iva-label">IVA (configuración del taller):</span>
                                <strong id="orden-iva">$0</strong>
                            </div>
                            <div class="total-item total-final">
                                <span>Total estimado:</span>
                                <strong id="orden-total">$0</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-group">
                            <label for="orden-notas">Notas / Observaciones</label>
                            <textarea id="orden-notas" name="notas" rows="3" placeholder="Descripción del problema, solicitudes especiales, etc."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-cancelar-orden">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-orden">Guardar Orden</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-orden-detalle">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="modal-orden-detalle-title">Detalle de orden</h3>
                <button class="btn-close" id="btn-cerrar-detalle" type="button"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="orden-detalle-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-cerrar-detalle-2">Cerrar</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-servicio">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-servicio-title">Nuevo servicio</h3>
                <button type="button" class="btn-close" id="btn-cerrar-servicio"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-servicio" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group" style="grid-column:1/-1">
                        <label for="srv-nombre">Nombre *</label>
                        <input type="text" id="srv-nombre" name="nombre" required maxlength="120" placeholder="Ej. Cambio de aceite">
                    </div>
                    <div class="form-group">
                        <label for="srv-tipo">Tipo</label>
                        <input type="text" id="srv-tipo" name="tipo" list="srv-tipos" maxlength="60" placeholder="Mantenimiento">
                        <datalist id="srv-tipos">
                            <option value="Mantenimiento"></option>
                            <option value="Diagnóstico"></option>
                            <option value="Suspensión"></option>
                            <option value="Frenos"></option>
                            <option value="Motor"></option>
                            <option value="Eléctrico"></option>
                            <option value="Aire acondicionado"></option>
                        </datalist>
                    </div>
                    <div class="form-group">
                        <label for="srv-precio">Precio (COP) *</label>
                        <input type="number" id="srv-precio" name="precio" min="0" step="1000" required>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label for="srv-descripcion">Descripción</label>
                        <textarea id="srv-descripcion" name="descripcion" rows="2" maxlength="400" placeholder="Qué incluye el servicio"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="srv-estado">Estado</label>
                        <select id="srv-estado" name="estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="btn-cancelar-servicio">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-guardar-servicio">Guardar</button>
            </div>
        </div>
    </div>

    <style>
        #btn-nueva-orden {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        #modal-orden-form.modal.active,
        #modal-orden-form.modal.show,
        #modal-orden-detalle.modal.active,
        #modal-orden-detalle.modal.show,
        #modal-servicio.modal.active,
        #modal-servicio.modal.show {
            display: flex !important;
            align-items: flex-start;
            justify-content: center;
            opacity: 1 !important;
            visibility: visible !important;
            z-index: 5000 !important;
            position: fixed !important;
            inset: 0 !important;
            background: rgba(0, 0, 0, 0.5) !important;
            padding: 2rem 1rem;
        }
    </style>
    <script>
        window.abrirModalOrden = function (id) {
            if (typeof window.openOrdenModal === 'function') {
                window.openOrdenModal(id || null);
                return false;
            }
            window._ordenModalPendiente = id || true;
            var modal = document.getElementById('modal-orden-form');
            if (!modal) return false;
            var cli = document.getElementById('orden-cliente');
            var veh = document.getElementById('orden-vehiculo');
            var mec = document.getElementById('orden-mecanico');
            if (cli) cli.innerHTML = '<option value="">Cargando clientes...</option>';
            if (veh) veh.innerHTML = '<option value="">Cargando vehículos...</option>';
            if (mec) mec.innerHTML = '<option value="">Cargando mecánicos...</option>';
            modal.classList.add('active', 'show');
            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            return false;
        };
        window.cerrarModalOrden = function () {
            var modal = document.getElementById('modal-orden-form');
            if (!modal) return;
            modal.classList.remove('active', 'show');
            modal.style.display = 'none';
        };
        window.cerrarModalDetalleOrden = function () {
            var modal = document.getElementById('modal-orden-detalle');
            if (!modal) return;
            modal.classList.remove('active', 'show');
            modal.style.display = 'none';
        };
        document.getElementById('btn-cerrar-orden')?.addEventListener('click', window.cerrarModalOrden);
        document.getElementById('btn-cancelar-orden')?.addEventListener('click', window.cerrarModalOrden);
        document.getElementById('modal-orden-form')?.addEventListener('click', function (e) {
            if (e.target === this) window.cerrarModalOrden();
        });
        document.getElementById('btn-cerrar-detalle')?.addEventListener('click', window.cerrarModalDetalleOrden);
        document.getElementById('btn-cerrar-detalle-2')?.addEventListener('click', window.cerrarModalDetalleOrden);
        document.getElementById('modal-orden-detalle')?.addEventListener('click', function (e) {
            if (e.target === this) window.cerrarModalDetalleOrden();
        });
    </script>
<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/ordenes.js?v=20260915placa1"></script>
    <script src="../../assets/js/modulos/trabajadores/servicios-catalogo.js?v=20260915precio1"></script>
