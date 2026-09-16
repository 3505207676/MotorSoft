<?php
$title = 'Reparaciones | Cliente';
$page_title = 'Reparaciones';
$active_page = 'reparaciones';
$page_scripts = ['reparaciones.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <section class="reparaciones">
        <h2>En curso</h2>
        <div class="reparaciones-en-curso"></div>

        <div class="historial-header">
          <h2>Historial</h2>
          <div class="selector-vista-historial">
            <div class="botones-vista">
              <button id="btn-vista-tarjetas-historial" class="btn-vista activo" type="button">
                <i class="fas fa-th-large"></i> Tarjetas
              </button>
              <button id="btn-vista-tabla-historial" class="btn-vista" type="button">
                <i class="fas fa-table"></i> Tabla
              </button>
            </div>
          </div>
        </div>

        <section id="vista-tarjetas-historial" class="historial-reparaciones-tarjetas"></section>
        <section id="vista-tabla-historial" class="historial-reparaciones-tabla" style="display: none;">
          <div class="tabla-container">
            <table>
              <thead>
                <tr>
                  <th>Vehículo</th>
                  <th>Placa</th>
                  <th>Servicio</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                  <th>Evidencias</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </section>
      </section>
    </main>
<?php include '../../assets/includes/cliente/footer.php'; ?>
