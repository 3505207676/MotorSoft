<?php
$title = 'Facturas | Cliente';
$page_title = 'Facturas';
$active_page = 'facturas';
$page_scripts = ['facturas.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <section class="facturas">
        <div class="facturas-header">
          <h2>Historial de facturas</h2>
          <div class="facturas-controles">
            <div class="barra-busqueda">
              <i class="fas fa-search"></i>
              <input id="buscar-factura" type="text" placeholder="Buscar por número o concepto...">
            </div>
            <div class="filtros">
              <select id="filtro-estado">
                <option value="">Todos los estados</option>
                <option value="pagada">Pagada</option>
                <option value="pendiente">Pendiente</option>
                <option value="anulada">Anulada</option>
              </select>
              <select id="filtro-fecha">
                <option value="">Todas las fechas</option>
                <option value="mes">Este mes</option>
                <option value="anio">Este año</option>
              </select>
            </div>
          </div>
        </div>
        <div class="facturas-tabla">
          <div class="tabla-container">
            <table>
              <thead>
                <tr>
                  <th>No.</th>
                  <th>Fecha</th>
                  <th>Vehículo</th>
                  <th>Concepto</th>
                  <th>Total</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody-facturas"></tbody>
            </table>
          </div>
        </div>
      </section>
    </main>

    <div id="modal-ver-factura" class="modal oculto modal-factura">
      <div class="modal-contenido">
        <div class="modal-header">
          <h3>Factura <span id="modal-numero"></span></h3>
          <button id="cerrar-modal-factura" class="cerrar-modal" type="button" title="Cerrar">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="factura-detalle">
          <div class="factura-info">
            <span class="factura-fecha"><i class="fas fa-calendar-alt"></i> <span id="modal-fecha"></span></span>
            <span class="factura-estado" id="modal-estado"></span>
          </div>
          <div class="factura-contenido" id="modal-contenido-factura"></div>
          <div class="factura-acciones">
            <button id="btn-descargar-pdf" class="btn-descargar" type="button">
              <i class="fas fa-file-pdf"></i> Descargar PDF
            </button>
            <button id="btn-cerrar" class="btn-cancelar" type="button">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
<?php include '../../assets/includes/cliente/footer.php'; ?>
