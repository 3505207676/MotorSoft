<?php
$title = 'Chat - Taller El Paisa';
$chatCssArchivo = __DIR__ . 'http://localhost/MotorSoft - Jhonnier/App - System/assets/css/chat.css';
$chatJsArchivo = __DIR__ . '/../../assets/js/modulos/trabajadores/chat.js';
$chatCssVersion = is_file($chatCssArchivo) ? (string) filemtime($chatCssArchivo) : 'missing';
$chatJsVersion = is_file($chatJsArchivo) ? (string) filemtime($chatJsArchivo) : 'missing';
$css_files = ['chat.css?v=' . $chatCssVersion];
$active_page = 'chat';
include '../../assets/includes/header.php';
include '../../assets/includes/sidebar.php';
?>
        <main class="main-content chat-page">
            <div class="chat-container">
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h3>Conversaciones</h3>
                        <div class="chat-header-actions">
                            <button class="btn-icon" id="btn-nueva-conversacion" aria-label="Nueva conversación" title="Nueva conversación">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="btn-icon" id="btn-toggle-tema" aria-label="Cambiar tema" title="Cambiar tema">
                                <i class="fas fa-moon" id="icon-tema"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chat-toolbar">
                        <div class="chat-search">
                            <i class="fas fa-search"></i>
                            <input type="text" id="chat-search-input" placeholder="Buscar contacto..." aria-label="Buscar conversación">
                        </div>
                        <div class="chat-filters" id="chat-filtros-tipo" role="tablist">
                            <button type="button" class="chat-chip active" data-tipo="" role="tab">Todos</button>
                            <button type="button" class="chat-chip" data-tipo="Cliente" role="tab">Clientes</button>
                            <button type="button" class="chat-chip" data-tipo="Usuario" role="tab">Personal</button>
                            <button type="button" class="chat-chip" data-tipo="Proveedor" role="tab">Proveedores</button>
                        </div>
                        <div class="chat-filters chat-filters-canal" id="chat-filtros-canal">
                            <button type="button" class="chat-chip active" data-canal="">Todos los canales</button>
                            <button type="button" class="chat-chip" data-canal="Interno">Interno</button>
                            <button type="button" class="chat-chip" data-canal="WhatsApp">WhatsApp</button>
                        </div>
                    </div>
                    <div class="chat-sidebar-body" id="lista-conversaciones"></div>
                </div>
                <div class="chat-main">
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <button type="button" class="btn-icon hidden" id="btn-volver-chats" aria-label="Volver a conversaciones" title="Volver">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div>
                                <h3 id="chat-header-nombre">Seleccione una conversación</h3>
                                <small id="chat-header-meta" class="chat-header-meta"></small>
                            </div>
                        </div>
                        <div class="chat-header-actions">
                            <button class="btn-icon" id="btn-info-conversacion" aria-label="Información de conversación" title="Información">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chat-messages" id="mensajes-contenedor"></div>
                    <div class="chat-input">
                        <button class="btn-icon" id="btn-adjuntar" aria-label="Adjuntar archivo" title="Adjuntar archivo">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="text" id="input-mensaje" placeholder="Escribe un mensaje..." aria-label="Escribe un mensaje" disabled>
                        <button class="btn btn-primary" id="btn-enviar-mensaje" aria-label="Enviar mensaje" title="Enviar mensaje">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>

    <div id="modal-nueva-conversacion" class="modal">
        <div class="modal-content" style="max-width:560px">
            <div class="modal-header">
                <h3>Nueva conversación</h3>
                <button type="button" class="btn-close" id="btn-cerrar-nueva-conv"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group">
                        <label for="nueva-conv-tipo">Tipo de contacto</label>
                        <select id="nueva-conv-tipo">
                            <option value="Cliente">Cliente</option>
                            <option value="Usuario">Personal del taller</option>
                            <option value="Proveedor">Proveedor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nueva-conv-canal">Canal</label>
                        <select id="nueva-conv-canal">
                            <option value="Interno">Chat interno</option>
                            <option value="WhatsApp">WhatsApp</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label for="nueva-conv-buscar">Buscar</label>
                        <input type="text" id="nueva-conv-buscar" placeholder="Nombre, documento o teléfono">
                    </div>
                </div>
                <div id="nueva-conv-proveedor" class="chat-nuevo-proveedor hidden">
                    <p class="text-muted" style="margin:0.75rem 0 0.5rem">Si no está en la lista, créelo aquí:</p>
                    <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:0.5rem">
                        <input type="text" id="nuevo-prov-nombre" placeholder="Nombre">
                        <input type="tel" id="nuevo-prov-tel" placeholder="Teléfono" data-filter="digits">
                        <button type="button" class="btn btn-outline" id="btn-crear-proveedor">Crear</button>
                    </div>
                </div>
                <div id="nueva-conv-lista" class="chat-contactos-lista"></div>
            </div>
        </div>
    </div>

    <div id="modal-info-conversacion" class="modal">
        <div class="modal-content" style="max-width:440px">
            <div class="modal-header">
                <h3>Datos del contacto</h3>
                <button type="button" class="btn-close" id="btn-cerrar-info-conv"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="info-conv-cuerpo"></div>
        </div>
    </div>
    <style>
        #modal-nueva-conversacion.modal.active,
        #modal-nueva-conversacion.modal.show,
        #modal-info-conversacion.modal.active,
        #modal-info-conversacion.modal.show {
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
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 0;
        }
        .chat-info-lista {
            display: grid;
            gap: 0.65rem;
            margin: 0;
        }
        .chat-info-lista dt {
            font-size: 0.75rem;
            color: var(--text-muted, #64748b);
            margin: 0;
        }
        .chat-info-lista dd {
            margin: 0.15rem 0 0;
            font-weight: 600;
        }
    </style>
    <script
        src="../../assets/js/modulos/trabajadores/chat.js?v=<?php echo htmlspecialchars($chatJsVersion); ?>"
        onerror="window.MotorSoftChatTrabajadoresErrorCarga=true"></script>
    <script>
        window.setTimeout(function () {
            var panel = document.querySelector('.chat-container');
            var lista = document.getElementById('lista-conversaciones');
            if (panel && window.getComputedStyle(panel).display !== 'flex') {
                var avisoCss = document.createElement('p');
                avisoCss.style.cssText = 'padding:12px;background:#7f1d1d;color:#fff;margin:8px';
                avisoCss.textContent = 'No se cargó assets/css/chat.css en el servidor.';
                panel.prepend(avisoCss);
            }
            if (!lista || window.MotorSoftChatTrabajadoresIniciado) return;
            lista.innerHTML = window.MotorSoftChatTrabajadoresErrorCarga
                ? '<p style="padding:1rem">No se pudo descargar <code>assets/js/modulos/trabajadores/chat.js</code>.</p>'
                : '<p style="padding:1rem">El chat no pudo iniciar. Revisa la consola del navegador.</p>';
        }, 5000);
    </script>
<?php include '../../assets/includes/footer.php'; ?>
