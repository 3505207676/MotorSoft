<?php
$title = 'Configuración - Taller El Paisa';
$active_page = 'configuracion';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
        <main class="main-content">
            <header class="topbar">
                <div class="topbar-left">
                    <h2 class="page-title">Configuración del taller</h2>
                    <div class="breadcrumb"><span>Inicio</span> / <span>Configuración</span></div>
                </div>
            </header>
            <div class="content-wrapper">
                <p class="text-muted" id="config-rol-aviso">Datos fiscales, correo SMTP y WhatsApp Cloud API. Solo quien tenga permiso de edición puede guardar.</p>
                <section class="card">
                    <div class="card-header"><h3>Facturación</h3></div>
                    <div class="card-body">
                        <form id="form-config" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                            <div class="form-group" style="grid-column:1/-1">
                                <label style="display:flex;align-items:center;gap:0.5rem">
                                    <input type="checkbox" id="iva_activo" name="iva_activo">
                                    <span>Cobrar IVA</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="iva_porcentaje">Porcentaje de IVA</label>
                                <input type="number" id="iva_porcentaje" name="iva_porcentaje" min="0" max="100" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="factura_prefijo">Prefijo de factura</label>
                                <input type="text" id="factura_prefijo" name="factura_prefijo" maxlength="10">
                            </div>
                            <div class="form-group">
                                <label for="moneda">Moneda</label>
                                <input type="text" id="moneda" name="moneda" maxlength="10">
                            </div>
                            <div class="form-group">
                                <label for="empresa_nombre">Razón social</label>
                                <input type="text" id="empresa_nombre" name="empresa_nombre">
                            </div>
                            <div class="form-group">
                                <label for="empresa_nit">NIT</label>
                                <input type="text" id="empresa_nit" name="empresa_nit">
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label for="empresa_direccion">Dirección</label>
                                <input type="text" id="empresa_direccion" name="empresa_direccion">
                            </div>
                            <div class="form-group">
                                <label for="empresa_telefono">Teléfono</label>
                                <input type="text" id="empresa_telefono" name="empresa_telefono">
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label for="empresa_logo">Logo en el documento de cobro</label>
                                <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap">
                                    <img id="logo-preview" alt="Logo del taller" src="../../assets/images/logo-taller.png" width="64" height="64" style="width:64px;height:64px;object-fit:cover;border-radius:50%;background:#141414">
                                    <input type="file" id="empresa_logo" accept="image/jpeg,image/png,image/gif">
                                    <button type="button" class="btn btn-outline btn-sm" id="btn-quitar-logo">Quitar logo</button>
                                </div>
                                <small class="text-muted">JPG o PNG. Si no subes uno, el PDF y el sistema usan el sello de Taller El Paisa.</small>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer" style="text-align:right">
                        <button class="btn btn-primary" id="btn-guardar-config"><i class="fas fa-save"></i> Guardar</button>
                    </div>
                </section>
                <section class="card" style="margin-top:1.5rem">
                    <div class="card-header"><h3>Correo del taller (SMTP)</h3></div>
                    <div class="card-body">
                        <p class="text-muted">Invitaciones, recuperación de clave y aviso de contrato. Si aún no hay SMTP (Gmail u otro), MotorSoft guarda el mensaje en el buzón interno de esta pantalla y el admin puede copiar el enlace. En Gmail use una <em>contraseña de aplicación</em>, no la clave de la cuenta. Host típico: smtp.gmail.com, puerto 587, cifrado TLS.</p>
                        <p class="text-muted" id="correo-estado" style="margin:0.5rem 0"></p>
                        <form id="form-correo" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                            <div class="form-group" style="grid-column:1/-1">
                                <label style="display:flex;align-items:center;gap:0.5rem">
                                    <input type="checkbox" id="correo_activo" name="correo_activo">
                                    <span>Activar envío de correos</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="correo_host">Servidor SMTP</label>
                                <input type="text" id="correo_host" name="correo_host" placeholder="smtp.gmail.com" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="correo_puerto">Puerto</label>
                                <input type="number" id="correo_puerto" name="correo_puerto" min="1" max="65535">
                            </div>
                            <div class="form-group">
                                <label for="correo_cifrado">Cifrado</label>
                                <select id="correo_cifrado" name="correo_cifrado">
                                    <option value="tls">TLS (587)</option>
                                    <option value="ssl">SSL (465)</option>
                                    <option value="none">Sin cifrado</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="correo_usuario">Usuario SMTP</label>
                                <input type="text" id="correo_usuario" name="correo_usuario" autocomplete="off">
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label for="correo_password">Contraseña SMTP</label>
                                <input type="password" id="correo_password" name="correo_password" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="correo_remitente">Remitente (From)</label>
                                <input type="email" id="correo_remitente" name="correo_remitente" placeholder="taller@gmail.com">
                            </div>
                            <div class="form-group">
                                <label for="correo_remitente_nombre">Nombre del remitente</label>
                                <input type="text" id="correo_remitente_nombre" name="correo_remitente_nombre">
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label for="app_url_publica">URL pública de la app (enlaces del correo)</label>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                                    <input type="url" id="app_url_publica" name="app_url_publica" placeholder="https://su-dominio/MotorSoft..." style="flex:1;min-width:220px">
                                    <button type="button" class="btn btn-secondary" id="btn-usar-url-sugerida">Usar URL actual</button>
                                </div>
                                <small class="text-muted" id="app-url-sugerida">En local puede dejarlo vacío. En producción ponga la URL HTTPS con la que entra la gente.</small>
                            </div>
                            <div class="form-group">
                                <label for="correo_prueba_destino">Enviar prueba a</label>
                                <input type="email" id="correo_prueba_destino" placeholder="su-correo@gmail.com">
                            </div>
                            <div class="form-group" style="display:flex;align-items:flex-end">
                                <button type="button" class="btn btn-secondary" id="btn-probar-correo">
                                    <i class="fas fa-paper-plane"></i> Probar envío
                                </button>
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label>Buzón interno (últimos envíos)</label>
                                <div id="correo-buzon" class="text-muted">Sin mensajes todavía.</div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer" style="text-align:right">
                        <button type="button" class="btn btn-primary js-guardar-config"><i class="fas fa-save"></i> Guardar</button>
                    </div>
                </section>
                <section class="card" style="margin-top:1.5rem">
                    <div class="card-header"><h3>WhatsApp (Meta Cloud API)</h3></div>
                    <div class="card-body">
                        <p class="text-muted" id="whatsapp-ayuda">El chat interno sigue funcionando sin Meta. Para que el cliente reciba en el celular: active el canal, pegue el token permanente y el Phone Number ID, y en Meta registre el webhook HTTPS (verify token de abajo). En este computador puede simular un mensaje entrante y verlo en Chat.</p>
                        <p class="text-muted" id="whatsapp-estado" style="margin:0.5rem 0"></p>
                        <form id="form-whatsapp" class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                            <div class="form-group" style="grid-column:1/-1">
                                <label style="display:flex;align-items:center;gap:0.5rem">
                                    <input type="checkbox" id="whatsapp_activo" name="whatsapp_activo">
                                    <span>Activar envío y recepción por WhatsApp</span>
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="whatsapp_phone_id">Phone Number ID</label>
                                <input type="text" id="whatsapp_phone_id" name="whatsapp_phone_id" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="whatsapp_waba_id">WABA ID (opcional)</label>
                                <input type="text" id="whatsapp_waba_id" name="whatsapp_waba_id" autocomplete="off">
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label for="whatsapp_token">Token permanente</label>
                                <input type="password" id="whatsapp_token" name="whatsapp_token" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="whatsapp_verify_token">Verify token del webhook</label>
                                <input type="text" id="whatsapp_verify_token" name="whatsapp_verify_token">
                            </div>
                            <div class="form-group">
                                <label>URL del webhook</label>
                                <div style="display:flex;gap:0.5rem">
                                    <input type="text" id="whatsapp_webhook_url" readonly style="flex:1">
                                    <button type="button" class="btn btn-secondary" id="btn-copiar-webhook">Copiar</button>
                                </div>
                                <small id="whatsapp-webhook-aviso" class="text-muted" hidden></small>
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <button type="button" class="btn btn-secondary" id="btn-probar-whatsapp">
                                    <i class="fas fa-plug"></i> Probar conexión con Meta
                                </button>
                                <button type="button" class="btn btn-outline" id="btn-probar-webhook" style="margin-left:0.5rem">
                                    Probar webhook local
                                </button>
                            </div>
                            <div class="form-group">
                                <label for="wa_sim_telefono">Simular mensaje entrante (celular)</label>
                                <input type="text" id="wa_sim_telefono" placeholder="3105550101" inputmode="numeric">
                            </div>
                            <div class="form-group">
                                <label for="wa_sim_nombre">Nombre (opcional)</label>
                                <input type="text" id="wa_sim_nombre" placeholder="Juan Pérez">
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label for="wa_sim_texto">Texto</label>
                                <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
                                    <input type="text" id="wa_sim_texto" placeholder="Hola, ¿ya está lista la moto?" style="flex:1;min-width:180px">
                                    <button type="button" class="btn btn-secondary" id="btn-simular-whatsapp">Simular y abrir en Chat</button>
                                </div>
                                <small class="text-muted">Si el número coincide con un cliente, entra a su hilo. Si no, se crea un contacto de WhatsApp.</small>
                            </div>
                            <div class="form-group" style="grid-column:1/-1">
                                <label>Últimos eventos del webhook</label>
                                <div id="whatsapp-eventos" class="text-muted">Sin eventos todavía.</div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer" style="text-align:right">
                        <button type="button" class="btn btn-primary js-guardar-config"><i class="fas fa-save"></i> Guardar</button>
                    </div>
                </section>
            </div>
        </main>
<?php include '../../assets/includes/footer.php'; ?>
    <script src="../../assets/js/modulos/trabajadores/configuracion.js?v=20260915logo1"></script>
