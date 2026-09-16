<?php
$title = 'Inventario - Taller El Paisa';
$active_page = 'inventario';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
<style>
.inv-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1rem}
.inv-hint{margin:0 0 1rem;padding:0.85rem 1rem;border-radius:8px;background:var(--surface-2,#f4f4f5)}
#modal-movimiento.modal:not(.active):not(.show),
#modal-kardex.modal:not(.active):not(.show),
#modal-categoria.modal:not(.active):not(.show),
#modal-producto.modal:not(.active):not(.show){display:none !important}
.stock-agotado{color:#dc2626;font-weight:600}
.stock-bajo{color:#d97706;font-weight:600}
</style>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Inventario</h2>
                    <div class="breadcrumb"><span>Inicio</span> / <span>Inventario</span></div>
                </div>
                <div class="topbar-right">
                    <div class="search-container">
                        <input type="text" id="search-producto" placeholder="Buscar por nombre o código...">
                        <button type="button"><i class="fas fa-search"></i></button>
                    </div>
                    <div class="topbar-actions">
                        <button class="btn-icon" id="btn-notificaciones"><i class="fas fa-bell"></i><span class="notification-badge" hidden>0</span></button>
                        <button class="btn-icon" id="theme-toggle"><i class="fas fa-moon"></i></button>
                    </div>
                </div>
            </header>
            <div class="content-wrapper" id="modulo-inventario">
                <div class="inv-kpis">
                    <div class="card card-stat"><div class="card-content"><h3>Productos</h3><p class="stat-value" id="kpi-productos">0</p></div></div>
                    <div class="card card-stat"><div class="card-content"><h3>Disponibles</h3><p class="stat-value" id="kpi-ok">0</p></div></div>
                    <div class="card card-stat"><div class="card-content"><h3>Stock bajo</h3><p class="stat-value" id="kpi-bajo">0</p></div></div>
                    <div class="card card-stat"><div class="card-content"><h3>Agotados</h3><p class="stat-value" id="kpi-agotados">0</p></div></div>
                </div>
                <p class="inv-hint text-muted">El stock se mueve con un motivo: compra (entrada), merma (salida) o conteo (ajuste). Los repuestos de una orden se descuentan cuando el mecánico los carga, no al facturar. No se puede dejar el almacén en negativo.</p>
                <div class="actions-bar">
                    <div class="actions-left">
                        <select id="filter-categoria">
                            <option value="todos">Todas las categorías</option>
                        </select>
                        <select id="filter-estado-stock" class="form-control">
                            <option value="todos">Todo el stock</option>
                            <option value="ok">Disponible</option>
                            <option value="bajo">Stock bajo</option>
                            <option value="agotado">Agotados</option>
                        </select>
                    </div>
                    <div class="actions-right">
                        <button class="btn btn-outline" id="btn-refresh" type="button"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        <button class="btn btn-outline" id="btn-nueva-categoria" type="button" hidden><i class="fas fa-tags"></i> Nueva categoría</button>
                        <button class="btn btn-primary" id="btn-nuevo-producto" type="button" hidden><i class="fas fa-plus"></i> Nuevo producto</button>
                    </div>
                </div>
                <section class="card">
                    <div class="card-header"><h3>Listado de productos</h3></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="tabla-inventario">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Estado</th>
                                        <th>Stock</th>
                                        <th>Mínimo</th>
                                        <th>Ubicación</th>
                                        <th>Precio</th>
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
                            <span id="lbl-paginacion">Página 1 de 1</span>
                            <button class="btn btn-outline btn-sm" id="next-page" type="button"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </section>
            </div>
        </main>

    <div class="modal" id="modal-producto">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-producto-title">Nuevo producto</h3>
                <button class="btn-close" id="btn-cerrar-producto" type="button"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-producto">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="codigo">Código *</label>
                            <input type="text" id="codigo" name="codigo" required placeholder="FLT-001" data-filter="alnum">
                        </div>
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required placeholder="Filtro de aceite">
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoría *</label>
                            <select id="categoria" name="categoria" required>
                                <option value="">Seleccione...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="precio">Precio de venta *</label>
                            <input type="number" id="precio" name="precio" required min="0" step="100" placeholder="25000">
                        </div>
                        <div class="form-group" id="grupo-stock-inicial">
                            <label for="stock">Existencia inicial *</label>
                            <input type="number" id="stock" name="stock" required min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="stockMinimo">Stock mínimo *</label>
                            <input type="number" id="stockMinimo" name="stockMinimo" required min="0" value="5">
                        </div>
                        <div class="form-group">
                            <label for="ubicacion">Ubicación</label>
                            <input type="text" id="ubicacion" name="ubicacion" placeholder="Estante A-1">
                        </div>
                    </div>
                    <p class="text-muted" id="hint-editar-stock" hidden>El stock no se cambia aquí. Use Entrada, salida o ajuste para no perder el historial.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cancelar-producto" type="button">Cancelar</button>
                <button class="btn btn-primary" id="btn-guardar-producto" type="button">Guardar</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-movimiento">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-movimiento-title">Movimiento de stock</h3>
                <button class="btn-close" id="btn-cerrar-mov" type="button"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-movimiento">
                    <p id="resumen-movimiento" class="text-muted"></p>
                    <div class="form-group">
                        <label for="mov-tipo">Tipo *</label>
                        <select id="mov-tipo" required>
                            <option value="ENTRADA">Entrada (compra o recepción)</option>
                            <option value="SALIDA">Salida (merma, daño o uso interno)</option>
                            <option value="AJUSTE">Ajuste (conteo físico)</option>
                        </select>
                    </div>
                    <div class="form-group" id="grupo-mov-cantidad">
                        <label for="mov-cantidad">Unidades *</label>
                        <input type="number" id="mov-cantidad" min="1" step="1" value="1">
                    </div>
                    <div class="form-group" id="grupo-mov-nueva" hidden>
                        <label for="mov-nueva">Cantidad contada *</label>
                        <input type="number" id="mov-nueva" min="0" step="1" value="0">
                    </div>
                    <div class="form-group">
                        <label for="mov-motivo">Motivo *</label>
                        <input type="text" id="mov-motivo" required maxlength="200" placeholder="Ej. Compra a proveedor, filtro dañado, inventario físico">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cancelar-mov" type="button">Cancelar</button>
                <button class="btn btn-primary" id="btn-guardar-mov" type="button">Registrar</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-kardex">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-kardex-title">Historial de stock</h3>
                <button class="btn-close" id="btn-cerrar-kardex" type="button"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="tabla-kardex">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Antes</th>
                                <th>Después</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cerrar-kardex-2" type="button">Cerrar</button>
            </div>
        </div>
    </div>

    <div class="modal" id="modal-categoria">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nueva categoría</h3>
                <button class="btn-close" id="btn-cerrar-cat" type="button"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="form-categoria">
                    <div class="form-group">
                        <label for="cat-nombre">Nombre *</label>
                        <input type="text" id="cat-nombre" required maxlength="80" placeholder="Filtros, Aceites, Frenos...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-cancelar-cat" type="button">Cancelar</button>
                <button class="btn btn-primary" id="btn-guardar-cat" type="button">Guardar</button>
            </div>
        </div>
    </div>

<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/inventario.js?v=20260915inv1"></script>
