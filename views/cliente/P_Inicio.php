<?php
$title = 'Inicio | Cliente';
$page_title = 'Inicio';
$active_page = 'inicio';
$page_scripts = ['inicio.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <div class="page-heading">
        <div>
          <h2>Hola, <span id="nombre-cliente"></span></h2>
          <p class="fecha-actual" id="fecha-actual"></p>
        </div>
        <button class="btn-refresh" title="Actualizar datos" type="button" aria-label="Actualizar">
          <i class="fas fa-sync-alt"></i>
        </button>
      </div>

      <section class="inicio-principal">
        <section class="dashboard">
          <p class="section-kicker">Resumen</p>
          <div class="tarjetas">
            <a href="../cliente/P_MisVehiculos.php" class="tarjeta tarjeta-vehiculos">
              <div class="tarjeta-icon"><i class="fas fa-car-side"></i></div>
              <div class="contenido">
                <h3>Vehículos</h3>
                <p class="numero" data-target="0">0</p>
                <span class="descripcion">Cargando...</span>
              </div>
            </a>
            <a href="../cliente/P_Reparaciones.php" class="tarjeta tarjeta-reparacion">
              <div class="tarjeta-icon"><i class="fas fa-tools"></i></div>
              <div class="contenido">
                <h3>En reparación</h3>
                <p class="numero" data-target="0">0</p>
                <span class="descripcion">Cargando...</span>
              </div>
            </a>
            <a href="../cliente/P_Mensajes.php" class="tarjeta tarjeta-mensajes">
              <div class="tarjeta-icon"><i class="fas fa-envelope-open-text"></i></div>
              <div class="contenido">
                <h3>Mensajes</h3>
                <p class="numero" data-target="0">0</p>
                <span class="descripcion">Cargando...</span>
              </div>
            </a>
            <a href="../cliente/P_Reparaciones.php" class="tarjeta tarjeta-visita">
              <div class="tarjeta-icon"><i class="fas fa-calendar-check"></i></div>
              <div class="contenido">
                <h3>Última visita</h3>
                <p class="fecha">—</p>
                <span class="descripcion">Cargando...</span>
              </div>
            </a>
          </div>
        </section>

        <div class="home-split">
          <section class="proxima-cita">
            <div class="cita-header">
              <h2><i class="fas fa-calendar-alt"></i> Tu próxima cita</h2>
              <div class="cita-status">
                <span class="status-badge status-confirmada">Confirmada</span>
              </div>
            </div>
            <div class="cita-box">
              <p>Cargando cita...</p>
            </div>
          </section>

          <section class="estado-vehiculo">
            <h2><i class="fas fa-car"></i> Estado del vehículo</h2>
            <div class="estado-box">
              <p>Cargando...</p>
            </div>
          </section>
        </div>

        <section class="alertas">
          <h2><i class="fas fa-exclamation-triangle"></i> Recomendaciones</h2>
          <div class="alertas-grid"></div>
        </section>
      </section>
    </main>
<?php include '../../assets/includes/cliente/footer.php'; ?>
