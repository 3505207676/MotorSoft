<?php
$title = 'Mi perfil | Cliente';
$page_title = 'Mi perfil';
$active_page = 'perfil';
$page_scripts = ['perfil.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <div class="perfil-layout">
        <section class="perfil-section perfil-header-card">
          <div class="perfil-avatar-wrap">
            <div class="menu-avatar" id="perfil-avatar">C</div>
            <button class="btn-avatar-cambiar" id="btn-change-avatar" type="button">Cambiar foto</button>
            <input id="input-avatar" type="file" accept="image/jpeg,image/png,image/gif" hidden>
          </div>
          <div>
            <h2 id="perfil-nombre" style="margin:0 0 0.2rem">Cargando...</h2>
            <p style="margin:0;color:#64748b">Cliente</p>
          </div>
          <button class="btn-nuevo-mensaje" id="btn-edit-profile" type="button" style="margin-left:auto">
            <i class="fas fa-edit"></i> Editar
          </button>
        </section>

        <section class="perfil-section">
          <h3><i class="fas fa-user-circle"></i> Información personal</h3>
          <form id="form-profile">
            <div class="info-grid">
              <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" disabled>
              </div>
              <div class="form-group">
                <label for="documento">Documento</label>
                <input type="text" id="documento" name="documento" disabled>
              </div>
              <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" disabled>
                <div class="error-message" id="error-telefono"></div>
              </div>
              <div class="form-group">
                <label for="email">Correo</label>
                <input type="email" id="email" name="email" disabled>
                <div class="error-message" id="error-email"></div>
              </div>
              <div class="form-group">
                <label for="preferencia_contacto">Cómo prefiere que lo contactemos</label>
                <select id="preferencia_contacto" name="preferencia_contacto" disabled>
                  <option value="WhatsApp">WhatsApp</option>
                  <option value="Llamada">Llamada</option>
                  <option value="Email">Correo</option>
                </select>
              </div>
              <div class="form-group">
                <label for="fecha_registro">Fecha de registro</label>
                <input type="text" id="fecha_registro" name="fecha_registro" disabled>
              </div>
            </div>
            <div class="form-actions" id="profile-actions">
              <button type="button" class="btn-cancelar" id="btn-cancel-edit" style="display:none">Cancelar</button>
              <button type="submit" class="btn-enviar" id="btn-save-profile" style="display:none">Guardar cambios</button>
            </div>
          </form>
        </section>

        <section class="perfil-section">
          <h3><i class="fas fa-sliders-h"></i> Preferencias</h3>
          <div class="pref-lista">
            <label class="pref-item">
              <span>Tema oscuro</span>
              <input type="checkbox" id="dark-theme">
            </label>
            <label class="pref-item">
              <span>Mostrar avisos de mensajes nuevos</span>
              <input type="checkbox" id="pref-avisos" checked>
            </label>
          </div>
          <p class="pref-ayuda">El tema se guarda en este dispositivo. Los avisos ocultan o muestran el número de mensajes sin leer en el menú.</p>
        </section>

        <section class="perfil-section">
          <h3><i class="fas fa-car"></i> Mis vehículos</h3>
          <div class="vehicles-list" id="perfil-vehiculos">
            <p>Cargando vehículos...</p>
          </div>
          <div class="form-actions">
            <a href="../cliente/P_MisVehiculos.php" class="btn-enviar" style="text-decoration:none;display:inline-flex">Ver todos</a>
          </div>
        </section>
      </div>
    </main>
<?php include '../../assets/includes/cliente/footer.php'; ?>
