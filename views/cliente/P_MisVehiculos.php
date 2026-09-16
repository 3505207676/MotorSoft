<?php
$title = 'Mis vehículos | Cliente';
$page_title = 'Mis vehículos';
$active_page = 'vehiculos';
$page_scripts = ['vehiculos.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <section class="mis-vehiculos">
        <div class="selector-vista">
          <h2>Tus vehículos</h2>
          <div class="botones-vista">
            <button id="btn-vista-tarjetas" class="btn-vista activo" type="button"><i class="fas fa-th"></i> Tarjetas</button>
            <button id="btn-vista-tabla" class="btn-vista" type="button"><i class="fas fa-table"></i> Tabla</button>
          </div>
        </div>
        <div id="vista-tarjetas" class="vehiculos-tarjetas"></div>
        <div id="vista-tabla" class="tabla-contenedor" style="display: none;">
          <table class="tabla-vehiculos">
            <thead>
              <tr>
                <th>Vehículo</th>
                <th>Tipo</th>
                <th>Placa</th>
                <th>Último servicio</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </section>
    </main>
<?php include '../../assets/includes/cliente/footer.php'; ?>
