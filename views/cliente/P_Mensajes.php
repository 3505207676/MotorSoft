<?php
$title = 'Mensajes | Cliente';
$page_title = 'Mensajes';
$active_page = 'mensajes';
$page_scripts = ['mensajes.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <section class="cli-chat" aria-label="Chat con el taller">
        <header class="cli-chat-head">
          <div class="cli-chat-avatar" aria-hidden="true">
            <i class="fas fa-store"></i>
          </div>
          <div class="cli-chat-who">
            <h2>Taller El Paisa</h2>
            <p id="cli-chat-status">Consulta el estado de tu vehículo o agenda</p>
          </div>
        </header>

        <div class="cli-chat-thread" id="cli-chat-thread" role="log" aria-live="polite">
          <div class="cli-chat-empty">
            <i class="fas fa-comments"></i>
            <p>Cargando conversación...</p>
          </div>
        </div>

        <p class="cli-chat-aviso" id="cli-chat-aviso" hidden></p>

        <form class="cli-chat-composer" id="cli-chat-form" autocomplete="off">
          <input id="cli-chat-archivo" type="file" accept="image/jpeg,image/png,image/gif,application/pdf" hidden>
          <button type="button" class="btn-icon" id="cli-chat-adjuntar" aria-label="Adjuntar archivo">
            <i class="fas fa-paperclip"></i>
          </button>
          <label class="sr-only" for="cli-chat-input">Escribe un mensaje</label>
          <input
            id="cli-chat-input"
            type="text"
            maxlength="4000"
            placeholder="Escribe un mensaje..."
            enterkeyhint="send"
          >
          <button type="submit" id="cli-chat-enviar" aria-label="Enviar mensaje">
            <i class="fas fa-paper-plane"></i>
          </button>
        </form>
      </section>
    </main>
<?php include '../../assets/includes/cliente/footer.php'; ?>
