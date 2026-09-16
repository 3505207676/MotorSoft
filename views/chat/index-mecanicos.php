<?php
$title = 'Chat - App Mecánicos';
$page_title = 'Chats';
$active_module = 'chat';
$chatMecanicoArchivo = __DIR__ . '/../../assets/js/mecanicos/chat.js';
$chatMecanicoVersion = is_file($chatMecanicoArchivo) ? (string) filemtime($chatMecanicoArchivo) : 'missing';
include '../../assets/includes/mecanico/header.php';
include '../../assets/includes/mecanico/nav.php';
?>

        <!-- Contenido Principal -->
        <main class="main-content main-content--chat">
            <div class="chat-layout">
                <!-- Lista de Órdenes/Chats -->
                <div class="chat-sidebar" id="chat-sidebar">
                    <div class="chat-list-header">
                        <h2>Conversaciones</h2>
                        <div class="chat-header-actions">
                            <button class="btn-icon" id="btn-nueva-conversacion" aria-label="Nueva conversación" title="Nueva conversación">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="btn-icon" id="btn-refresh-chats" aria-label="Actualizar">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="chat-search">
                        <input type="text" id="chat-search-input" placeholder="Buscar cliente o personal..." class="search-input">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <div class="chat-filters" id="chat-filtros-tipo">
                        <button type="button" class="chat-chip active" data-tipo="">Todos</button>
                        <button type="button" class="chat-chip" data-tipo="Cliente">Clientes</button>
                        <button type="button" class="chat-chip" data-tipo="Usuario">Personal</button>
                    </div>
                    
                    <div class="ordenes-chat-list" id="ordenes-chat-list">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Cargando chats...</p>
                        </div>
                    </div>
                </div>

                <!-- Panel de Chat -->
                <div class="chat-main-panel" id="chat-main-panel">
                    <div class="chat-panel-header" id="chat-panel-header">
                        <div class="chat-header-info">
                            <button type="button" class="btn-icon hidden" id="btn-volver-chats" aria-label="Volver a chats">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div class="chat-avatar">
                                <i class="fas fa-comments"></i>
                            </div>
                            <div class="chat-details">
                                <h3 id="chat-cliente-nombre">Selecciona una conversación</h3>
                                <span class="chat-status" id="chat-vehiculo-info">para ver los mensajes</span>
                            </div>
                        </div>
                        <button class="btn-icon" id="btn-info-chat" aria-label="Información de la orden">
                            <i class="fas fa-info-circle"></i>
                        </button>
                    </div>

                    <div class="chat-messages-container" id="mensajes-container">
                        <div class="chat-welcome">
                            <div class="welcome-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3>Bienvenido al Chat</h3>
                            <p>Selecciona una conversación para ver los mensajes reales del taller</p>
                        </div>
                    </div>

                    <div class="chat-input-container hidden" id="chat-input-container">
                        <form id="form-mensaje" class="chat-input-form">
                            <div class="chat-input">
                                <button type="button" class="btn-icon" id="btn-adjuntar" aria-label="Adjuntar archivo">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                <input type="text" id="input-mensaje" placeholder="Escribe un mensaje..." aria-label="Escribe un mensaje">
                                <button type="submit" class="btn btn-primary" id="btn-enviar" aria-label="Enviar mensaje">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>

        <div class="toast-container" id="toast-container"></div>
    </div>

    <div id="modal-nueva-conversacion" class="modal">
        <div class="modal-content" style="max-width:520px">
            <div class="modal-header">
                <h3>Nueva conversación</h3>
                <button type="button" class="btn-close" id="btn-cerrar-nueva-conv"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nueva-conv-tipo">Tipo de contacto</label>
                    <select id="nueva-conv-tipo">
                        <option value="Cliente">Cliente</option>
                        <option value="Usuario">Personal del taller</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top:0.75rem">
                    <label for="nueva-conv-buscar">Buscar</label>
                    <input type="text" id="nueva-conv-buscar" placeholder="Nombre, documento o teléfono">
                </div>
                <div id="nueva-conv-lista" class="chat-contactos-lista"></div>
            </div>
        </div>
    </div>
    <div id="modal-info-conversacion" class="modal">
        <div class="modal-content" style="max-width:420px">
            <div class="modal-header">
                <h3>Datos del contacto</h3>
                <button type="button" class="btn-close" id="btn-cerrar-info-conv"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" id="info-conv-cuerpo"></div>
        </div>
    </div>
<?php include '../../assets/includes/mecanico/scripts.php'; ?>
    <script
        src="../../assets/js/mecanicos/chat.js?v=<?php echo htmlspecialchars($chatMecanicoVersion); ?>"
        onerror="window.MotorSoftChatErrorCarga=true"></script>
    <script>
        window.setTimeout(function () {
            var box = document.getElementById('ordenes-chat-list');
            if (!box || window.MotorSoftChatIniciado) return;
            box.innerHTML = window.MotorSoftChatErrorCarga
                ? '<p style="padding:1rem">No se pudo descargar el archivo del chat. Verifica que <code>assets/js/mecanicos/chat.js</code> exista en el servidor.</p>'
                : '<p style="padding:1rem">El chat no pudo iniciarse. Revisa la consola del navegador.</p>';
        }, 5000);
    </script>
    <style>
        /* Estilos específicos para el chat estilo WhatsApp */
        .chat-layout {
            display: flex;
            height: 100%;
            min-height: 0;
            background: var(--bg-light);
            overflow: hidden;
        }

        .chat-sidebar {
            width: 350px;
            background: var(--bg-card);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }

        .chat-main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-light);
        }

        .chat-list-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-card);
        }

        .chat-list-header h2 {
            margin: 0;
            color: var(--text-dark);
            font-size: 1.25rem;
        }

        .chat-search {
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: var(--spacing-sm) var(--spacing-lg) var(--spacing-sm) 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            background: var(--bg-light);
            color: var(--text-dark);
            font-size: 0.875rem;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .search-icon {
            position: absolute;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .chat-filters {
            display: flex;
            gap: 0.4rem;
            padding: 0.5rem 1rem 0.75rem;
            border-bottom: 1px solid var(--border-color);
            flex-wrap: wrap;
        }

        .chat-chip {
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-muted);
            border-radius: 999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .chat-chip.active {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: #fff;
        }

        .ordenes-chat-list {
            flex: 1;
            overflow-y: auto;
        }

        .chat-empty-list {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--text-muted);
        }

        .chat-empty-list i {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .orden-chat-item,
        button.orden-chat-item {
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            border-bottom: 1px solid var(--border-color);
            border-left: 3px solid transparent;
            cursor: pointer;
            transition: background 0.2s ease, border-color 0.2s ease;
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            background: var(--bg-card);
            width: 100%;
            text-align: left;
            font: inherit;
            color: inherit;
            appearance: none;
        }

        .orden-chat-item:hover {
            background: var(--bg-light);
        }

        .orden-chat-item.no-leido {
            background: rgba(22, 163, 74, 0.1);
            border-left: 3px solid #16a34a;
        }

        .orden-chat-item.no-leido .orden-cliente-nombre {
            font-weight: 700;
        }

        .orden-chat-item.no-leido .orden-ultimo-mensaje {
            color: var(--text-dark);
            font-weight: 600;
        }

        .orden-chat-item.no-leido .orden-hora {
            color: #16a34a;
            font-weight: 700;
        }

        .orden-chat-item.no-leido .orden-avatar {
            position: relative;
            background: #16a34a;
        }

        .orden-chat-item.no-leido .orden-avatar::after {
            content: '';
            position: absolute;
            right: 1px;
            top: 1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #22c55e;
            border: 2px solid var(--bg-card);
        }

        .orden-chat-item.recien {
            animation: chat-llegada 1.6s ease;
        }

        .orden-chat-item.sin-actividad {
            opacity: 0.72;
        }

        .orden-chat-item.no-leido.active {
            background: #16a34a;
            color: white;
        }

        .orden-chat-item.active {
            background: var(--color-primary);
            color: white;
        }

        .orden-chat-item.active:hover {
            background: var(--color-primary-dark);
        }

        .orden-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .orden-chat-item.active .orden-avatar {
            background: rgba(255, 255, 255, 0.2);
        }

        .orden-info {
            flex: 1;
            min-width: 0;
        }

        .orden-cliente-nombre {
            font-weight: 600;
            margin: 0 0 var(--spacing-xs) 0;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .orden-chat-item.active .orden-cliente-nombre {
            color: white;
        }

        .orden-vehiculo-info {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0 0 var(--spacing-xs) 0;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }

        .orden-chat-item.active .orden-vehiculo-info {
            color: rgba(255, 255, 255, 0.8);
        }

        .orden-placa {
            background: var(--color-success);
            color: white;
            padding: 2px 6px;
            border-radius: var(--border-radius);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .orden-chat-item.active .orden-placa {
            background: rgba(255, 255, 255, 0.2);
        }

        .orden-ultimo-mensaje {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .orden-chat-item.active .orden-ultimo-mensaje {
            color: rgba(255, 255, 255, 0.8);
        }

        .orden-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: var(--spacing-xs);
        }

        .orden-hora {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .orden-tipo {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--text-muted);
        }

        .orden-chat-item.active .orden-hora,
        .orden-chat-item.active .orden-tipo {
            color: rgba(255, 255, 255, 0.8);
        }

        .orden-badge {
            background: #16a34a;
            color: white;
            border-radius: 10px;
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0 6px;
        }

        .orden-chat-item.active .orden-badge {
            background: rgba(255, 255, 255, 0.2);
        }

        .chat-panel-header {
            padding: var(--spacing-lg);
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .chat-details h3 {
            margin: 0 0 var(--spacing-xs) 0;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .chat-status {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .chat-messages-container {
            flex: 1;
            overflow-y: auto;
            padding: var(--spacing-lg);
            background: var(--bg-light);
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(37, 99, 235, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
        }

        .chat-welcome {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            text-align: center;
        }

        .welcome-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: var(--spacing-lg);
        }

        .chat-welcome h3 {
            margin: 0 0 var(--spacing-md) 0;
            color: var(--text-dark);
            font-size: 1.5rem;
        }

        .chat-welcome p {
            margin: 0;
            max-width: 300px;
            line-height: 1.5;
        }

        .chat-input-container {
            padding: var(--spacing-lg);
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
        }

        .chat-input-form {
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
        }

        .chat-input {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--bg-light);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-sm);
            gap: var(--spacing-sm);
            transition: var(--transition-fast);
        }

        .chat-input:focus-within {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .chat-input input {
            flex: 1;
            border: none;
            background: transparent;
            outline: none;
            color: var(--text-dark);
            font-size: 1rem;
            padding: var(--spacing-sm);
        }

        .chat-input input::placeholder {
            color: var(--text-muted);
        }

        /* Mensajes */
        .mensaje {
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: flex-end;
            gap: var(--spacing-sm);
            min-width: 0;
            max-width: 100%;
        }

        .mensaje.enviado {
            flex-direction: row-reverse;
        }

        .mensaje.recibido.recien .mensaje-contenido {
            animation: chat-llegada 1.8s ease-out;
            outline: 2px solid rgba(22, 163, 74, 0.55);
            background-color: rgba(22, 163, 74, 0.16);
        }

        .mensaje-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .mensaje-contenido {
            max-width: min(70%, 420px);
            min-width: 0;
            overflow: hidden;
            background: var(--bg-card);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .mensaje.enviado .mensaje-contenido {
            background: var(--color-primary);
            color: white;
        }

        .mensaje-texto {
            margin: 0;
            line-height: 1.4;
            min-width: 0;
            overflow: hidden;
        }

        .chat-adjunto {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin: 0;
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .chat-adjunto-img {
            display: block;
            width: 100%;
            max-width: 100%;
            margin: 0.2rem 0 0;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
        }

        .chat-adjunto-img img {
            display: block;
            width: 100%;
            max-width: 100%;
            max-height: 220px;
            height: auto;
            object-fit: contain;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.06);
        }

        .mensaje-hora {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: var(--spacing-xs);
            text-align: right;
        }

        .mensaje.enviado .mensaje-hora {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-layout {
                height: 100%;
                border-radius: 0;
                box-shadow: none;
                position: relative;
            }

            .chat-sidebar {
                width: 100%;
                position: absolute;
                z-index: 10;
                height: 100%;
            }

            .chat-main-panel {
                width: 100%;
            }

            .chat-sidebar.hidden {
                display: none;
            }

            .chat-main-panel.hidden {
                display: none;
            }

            .chat-messages-container {
                padding: var(--spacing-md);
            }

            .chat-input-container {
                padding: var(--spacing-md);
            }

            .orden-chat-item {
                padding: var(--spacing-md);
            }

            .chat-list-header {
                padding: var(--spacing-md);
            }
        }

        /* Animaciones */
        @keyframes chat-llegada {
            0% { background-color: rgba(22, 163, 74, 0.32); }
            100% { background-color: rgba(22, 163, 74, 0.1); }
        }

        .chat-header-actions {
            display: flex;
            gap: 0.35rem;
        }

        .chat-contactos-lista {
            margin-top: 12px;
            max-height: 320px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .chat-contacto-item {
            text-align: left;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
            color: var(--text-dark);
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 2px;
            width: 100%;
            font: inherit;
        }

        .chat-info-lista {
            display: grid;
            gap: 0.65rem;
            margin: 0;
        }

        .chat-info-lista dt {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin: 0;
        }

        .chat-info-lista dd {
            margin: 0.15rem 0 0;
            font-weight: 600;
        }

        #modal-nueva-conversacion.modal,
        #modal-info-conversacion.modal {
            display: none !important;
        }

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
    </style>
<?php include '../../assets/includes/mecanico/footer.php'; ?>
