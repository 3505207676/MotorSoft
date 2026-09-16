<?php
$title = 'Usuarios y RRHH - Taller El Paisa';
$css_files = ['modulos-integrados.css', 'usuarios.css'];
$active_page = 'usuarios';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>

        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left"><h2 class="page-title">Personal y RRHH</h2><div class="breadcrumb"><span>Inicio</span> / <span>Usuarios</span></div></div>
                <div class="topbar-right">
                    <div class="search-container"><input type="text" id="search-usuario" placeholder="Buscar usuario..."><button><i class="fas fa-search"></i></button></div>
                    <button class="btn btn-outline btn-sm" id="btn-refresh-usr"><i class="fas fa-sync-alt"></i></button>
                </div>
            </header>

            <div class="content-wrapper" id="modulo-usuarios">
                <nav class="module-tabs">
                    <button type="button" class="tab-btn active" data-tab="personal"><i class="fas fa-users"></i><span class="tab-label">Personal</span></button>
                    <button type="button" class="tab-btn" data-tab="contratos"><i class="fas fa-file-contract"></i><span class="tab-label">Contratos</span></button>
                    <button type="button" class="tab-btn" data-tab="nominas"><i class="fas fa-money-check-alt"></i><span class="tab-label">Nóminas</span></button>
                    <button type="button" class="tab-btn" data-tab="especialidades"><i class="fas fa-cogs"></i><span class="tab-label">Especialidades</span></button>
                    <button type="button" class="tab-btn" data-tab="roles"><i class="fas fa-user-shield"></i><span class="tab-label">Roles</span></button>
                </nav>

                <div class="module-panel active" data-panel="personal" id="user-list-view">
                    <div class="actions-bar usuarios-toolbar">
                        <select id="filter-rol" class="form-control">
                            <option value="todos">Todos los roles</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Mecánico">Mecánico</option>
                            <option value="Recepcionista">Recepcionista</option>
                        </select>
                        <button type="button" class="btn btn-primary" id="btn-nuevo-usuario">
                            <i class="fas fa-plus"></i> Nuevo usuario
                        </button>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Usuarios del sistema</h3></div>
                        <div class="card-body"><table class="table" id="tabla-usuarios"><thead><tr>
                            <th>Nombre</th><th>Correo</th><th>Documento</th><th>Teléfono</th><th>Rol</th><th>Oficios</th><th>Estado</th><th>Acciones</th>
                        </tr></thead><tbody></tbody></table></div>
                        <div class="card-footer"><div class="pagination">
                            <button class="btn btn-outline btn-sm" id="prev-page"><i class="fas fa-chevron-left"></i></button>
                            <span>Página 1 de 1</span>
                            <button class="btn btn-outline btn-sm" id="next-page"><i class="fas fa-chevron-right"></i></button>
                        </div></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="contratos">
                    <div class="actions-bar mb-3" style="text-align:right"><button type="button" class="btn btn-primary" id="btn-nuevo-contrato"><i class="fas fa-plus"></i> Nuevo Contrato</button></div>
                    <section class="card"><div class="card-header"><h3>Contratos laborales</h3></div>
                        <div class="card-body"><table class="table" id="tabla-contratos"><thead><tr>
                            <th>Empleado</th><th>Salario base</th><th>Comisión</th><th>Ingreso</th><th>Retiro</th><th>Estado</th><th>Acciones</th>
                        </tr></thead><tbody></tbody></table></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="nominas">
                    <div class="actions-bar mb-3" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
                        <p id="aviso-caja-nomina" class="alert aviso-caja" style="display:none;margin:0;padding:0.6rem 0.9rem;border-radius:8px"></p>
                        <button type="button" class="btn btn-primary" id="btn-nueva-nomina" style="margin-left:auto"><i class="fas fa-plus"></i> Registrar Nómina</button>
                    </div>
                    <section class="card"><div class="card-header"><h3>Nóminas y pagos</h3></div>
                        <div class="card-body"><table class="table" id="tabla-nominas"><thead><tr>
                            <th>Empleado</th><th>Periodo</th><th>Fecha pago</th><th>Total neto</th><th>Acciones</th>
                        </tr></thead><tbody></tbody></table></div>
                    </section>
                </div>

                <div class="module-panel" data-panel="especialidades">
                    <div class="actions-bar mb-3" style="text-align:right">
                        <button type="button" class="btn btn-primary" id="btn-nueva-especialidad"><i class="fas fa-plus"></i> Nueva especialidad</button>
                    </div>
                    <section class="card">
                        <div class="card-header"><h3>Oficios del taller</h3></div>
                        <div class="card-body">
                            <p class="text-muted" style="margin-top:0">Frenos, electricidad, motor… así se sabe qué puede atender cada mecánico. Quitar una especialidad no borra al trabajador.</p>
                            <table class="table" id="tabla-especialidades"><thead><tr>
                                <th>Nombre</th><th>Descripción</th><th>Estado</th><th>Asignados</th><th>Acciones</th>
                            </tr></thead><tbody></tbody></table>
                        </div>
                    </section>
                </div>

                <div class="module-panel" data-panel="roles">
                    <section class="card">
                        <div class="card-header"><h3>Matriz de permisos por rol</h3></div>
                        <div class="card-body">
                            <p class="text-muted" style="margin-top:0">El cambio aplica a todas las personas con ese rol. El Administrador no se recorta: siempre entra a todo. El gerente no edita esta pantalla.</p>
                            <div class="rrhh-roles-layout">
                                <aside id="lista-roles-rrhh" class="rrhh-roles-lista"></aside>
                                <div id="panel-matriz-rol" class="rrhh-matriz-panel"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>

    <div id="modal-usuario-form" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-usuario-title">Nuevo Usuario</h3><button class="btn-close" id="btn-cerrar-modal-usuario"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <div id="usuario-form-errores" class="alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:#fee2e2;color:#991b1b"></div>
                <form id="form-usuario" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem" novalidate>
                <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" required minlength="2" maxlength="80" data-filter="letters" placeholder="Nombre"></div>
                <div class="form-group"><label>Apellido</label><input type="text" name="apellido" maxlength="80" data-filter="letters" placeholder="Apellido"></div>
                <div class="form-group"><label>Correo *</label><input type="email" name="correo" required maxlength="120" placeholder="correo@taller.com"></div>
                <div class="form-group"><label>Teléfono *</label><input type="tel" name="telefono" required data-filter="digits" inputmode="numeric" minlength="7" maxlength="15" placeholder="3001234567"></div>
                <div class="form-group"><label>Documento *</label><input type="text" name="documento" required data-filter="digits" inputmode="numeric" minlength="5" maxlength="15" placeholder="Solo números"></div>
                <div class="form-group"><label>Rol *</label><select name="id_rol" id="usuario-rol" required><option value="">Seleccione...</option></select></div>
                <div class="form-group"><label>Estado</label><select name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Contraseña <span id="usuario-pass-hint" hidden></span></label>
                    <input type="password" name="password" minlength="6" maxlength="72" autocomplete="new-password" placeholder="Opcional">
                    <small class="text-muted" id="usuario-pass-ayuda">Si la deja vacía se envía un correo para que la persona cree su clave. Configure SMTP en Configuración.</small>
                </div>
                <div class="form-group" style="grid-column:1/-1" id="grupo-usuario-especialidades">
                    <label>Especialidades</label>
                    <div id="usuario-especialidades" class="rrhh-chips"></div>
                    <small class="text-muted">Marque los oficios del trabajador, sobre todo si es mecánico.</small>
                </div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" id="btn-cancelar-usuario">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-usuario">Guardar</button></div>
        </div>
    </div>

    <div class="modal" id="modal-contrato">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-contrato-title">Nuevo Contrato</h3><button type="button" class="btn-close" id="btn-cerrar-contrato"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <div id="contrato-form-errores" class="alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:#fee2e2;color:#991b1b"></div>
                <form id="form-contrato" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem" novalidate>
                <div class="form-group" style="grid-column:1/-1"><label>Empleado *</label><select name="id_usuario" id="contrato-id-usuario" required></select></div>
                <div class="form-group"><label>Salario base *</label><input type="number" name="salario_base" required min="1" step="1" placeholder="1800000"></div>
                <div class="form-group"><label>Comisión (%)</label><input type="number" name="porcentaje_comision" value="0" min="0" max="100" step="0.01"></div>
                <div class="form-group"><label>Fecha ingreso *</label><input type="date" name="fecha_ingreso" required></div>
                <div class="form-group"><label>Estado</label><select name="estado_contrato" id="contrato-estado"><option value="Vigente">Vigente</option><option value="Finalizado">Finalizado</option></select></div>
                <div class="form-group" id="grupo-fecha-retiro" style="display:none"><label>Fecha retiro</label><input type="date" name="fecha_retiro"></div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" id="btn-cancelar-contrato">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-contrato">Guardar</button></div>
        </div>
    </div>

    <div class="modal" id="modal-nomina">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-nomina-title">Registrar Nómina</h3><button type="button" class="btn-close" id="btn-cerrar-nomina"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <div id="nomina-form-errores" class="alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:#fee2e2;color:#991b1b"></div>
                <p id="aviso-caja-modal-nomina" class="alert aviso-caja" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px">Para registrar el pago debe tener caja abierta.</p>
                <form id="form-nomina" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem" novalidate>
                <div class="form-group" style="grid-column:1/-1"><label>Contrato / Empleado *</label><select name="id_contrato" id="nomina-id-contrato" required></select></div>
                <div class="form-group"><label>Periodo *</label><input type="text" name="periodo_pago" maxlength="30" placeholder="2026-09-1ra quincena" required></div>
                <div class="form-group"><label>Fecha pago *</label><input type="date" name="fecha_pago" required></div>
                <div class="form-group" style="grid-column:1/-1"><label>Total neto *</label><input type="number" name="total_neto" required min="1" step="1"><small class="text-muted">Se llena con el salario del contrato. Si cambia el valor, la diferencia queda como devengo o descuento.</small></div>
            </form></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" id="btn-cancelar-nomina">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-nomina">Guardar y pagar</button></div>
        </div>
    </div>

    <div class="modal" id="modal-especialidad">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-especialidad-title">Nueva especialidad</h3><button type="button" class="btn-close" id="btn-cerrar-especialidad"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <div id="especialidad-form-errores" class="alert" style="display:none;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:#fee2e2;color:#991b1b"></div>
                <form id="form-especialidad" style="display:grid;gap:1rem" novalidate>
                    <div class="form-group"><label>Nombre *</label><input type="text" name="nombre_especialidad" required minlength="2" maxlength="50" placeholder="Frenos"></div>
                    <div class="form-group"><label>Descripción</label><textarea name="descripcion" maxlength="500" rows="3" placeholder="Qué cubre este oficio"></textarea></div>
                    <div class="form-group"><label>Estado</label><select name="estado"><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></div>
                </form>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" id="btn-cancelar-especialidad">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-especialidad">Guardar</button></div>
        </div>
    </div>

    <div class="modal" id="modal-asignar-especialidad">
        <div class="modal-content">
            <div class="modal-header"><h3 id="modal-asignar-title">Asignar a personal</h3><button type="button" class="btn-close" id="btn-cerrar-asignar"><i class="fas fa-times"></i></button></div>
            <div class="modal-body">
                <p class="text-muted" id="asignar-especialidad-ayuda" style="margin-top:0">Marque quién atiende este oficio. Puede dejarlo vacío.</p>
                <div id="asignar-usuarios-lista" class="rrhh-chips"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" id="btn-cancelar-asignar">Cancelar</button><button type="button" class="btn btn-primary" id="btn-guardar-asignar">Guardar asignación</button></div>
        </div>
    </div>

    <!-- Scripts específicos del módulo -->
    <style>
        #btn-nuevo-usuario {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        #modulo-usuarios .tab-btn.tab-hidden { display: none !important; }
        .rrhh-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem 0.75rem;
        }
        .rrhh-chips label {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin: 0;
            font-weight: 500;
        }
        .rrhh-roles-layout {
            display: grid;
            grid-template-columns: minmax(180px, 240px) 1fr;
            gap: 1rem;
            align-items: start;
        }
        .rrhh-roles-lista { display: flex; flex-direction: column; gap: 0.4rem; }
        .rrhh-rol-btn {
            text-align: left;
            border: 1px solid var(--border-color, #d1d5db);
            background: var(--card-bg, #fff);
            border-radius: 8px;
            padding: 0.65rem 0.8rem;
            cursor: pointer;
        }
        .rrhh-rol-btn.active {
            border-color: var(--primary, #2563eb);
            background: rgba(37, 99, 235, 0.08);
        }
        .rrhh-matriz-grupos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 0.85rem;
        }
        .rrhh-matriz-grupo {
            border: 1px solid var(--border-color, #e5e7eb);
            border-radius: 8px;
            padding: 0.7rem 0.8rem;
        }
        .rrhh-matriz-grupo h4 {
            margin: 0 0 0.5rem;
            font-size: 0.9rem;
            text-transform: capitalize;
        }
        .rrhh-matriz-grupo label {
            display: flex;
            gap: 0.4rem;
            align-items: flex-start;
            font-size: 0.85rem;
            margin: 0.25rem 0;
        }
        @media (max-width: 800px) {
            .rrhh-roles-layout { grid-template-columns: 1fr; }
        }
        #modal-usuario-form.modal.active,
        #modal-usuario-form.modal.show,
        #modal-contrato.modal.active,
        #modal-contrato.modal.show,
        #modal-nomina.modal.active,
        #modal-nomina.modal.show,
        #modal-especialidad.modal.active,
        #modal-especialidad.modal.show,
        #modal-asignar-especialidad.modal.active,
        #modal-asignar-especialidad.modal.show {
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
        window.abrirModalUsuario = function () {
            if (typeof window.openUsuarioModal === 'function') {
                window.openUsuarioModal();
                return false;
            }
            var modal = document.getElementById('modal-usuario-form');
            if (!modal) return false;
            modal.classList.add('active', 'show');
            modal.style.display = 'flex';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            return false;
        };
        window.cerrarModalUsuario = function () {
            var modal = document.getElementById('modal-usuario-form');
            if (!modal) return;
            modal.classList.remove('active', 'show');
            modal.style.display = 'none';
        };
        document.getElementById('btn-cerrar-modal-usuario')?.addEventListener('click', window.cerrarModalUsuario);
        document.getElementById('btn-cancelar-usuario')?.addEventListener('click', window.cerrarModalUsuario);
        document.getElementById('modal-usuario-form')?.addEventListener('click', function (e) {
            if (e.target === this) window.cerrarModalUsuario();
        });
    </script>
<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/usuarios-rrhh.js?v=20260915smtp1"></script>
