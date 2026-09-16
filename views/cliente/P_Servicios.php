<?php
$title = 'Servicios | Cliente';
$page_title = 'Servicios';
$active_page = 'servicios';
$page_scripts = ['servicios.js'];
include '../../assets/includes/cliente/header.php';
include '../../assets/includes/cliente/nav.php';
?>
    <main class="contenido">
      <section class="servicios">
        <div class="servicios-header">
          <h2>¿Qué necesitas hoy?</h2>
          <div class="selector-vista-servicios">
            <div class="botones-vista">
              <button id="btn-vista-tarjetas" class="btn-vista activo" type="button">
                <i class="fas fa-th-large"></i> Tarjetas
              </button>
              <button id="btn-vista-tabla" class="btn-vista" type="button">
                <i class="fas fa-table"></i> Tabla
              </button>
            </div>
          </div>
        </div>
        <div class="controles-servicios">
          <div class="barra-busqueda">
            <i class="fas fa-search"></i>
            <input type="text" id="buscar-servicio" placeholder="Buscar servicios...">
          </div>
          <div class="filtros">
            <select id="filtro-categoria">
              <option value="">Todas las categorías</option>
            </select>
            <select id="filtro-precio">
              <option value="">Todos los precios</option>
              <option value="0-50000">$0 - $50.000</option>
              <option value="50000-100000">$50.000 - $100.000</option>
              <option value="100000-200000">$100.000 - $200.000</option>
              <option value="200000+">Más de $200.000</option>
            </select>
          </div>
        </div>
        <div id="vista-tarjetas-servicios" class="servicio-grid"></div>
        <div id="vista-tabla-servicios" class="servicios-tabla" style="display: none;">
          <div class="tabla-container">
            <table>
              <thead>
                <tr>
                  <th>Servicio</th>
                  <th>Categoría</th>
                  <th>Descripción</th>
                  <th>Duración</th>
                  <th>Precio</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </section>

      <section class="proxima-cita" id="seccion-mis-citas">
        <div class="cita-header">
          <h2><i class="fas fa-calendar-check"></i> Tus citas</h2>
        </div>
        <div id="lista-mis-citas" class="cita-box">
          <p>Cargando citas...</p>
        </div>
      </section>
    </main>

    <div class="modal" id="modal-agendar" style="display:none">
      <div class="modal-contenido">
        <div class="modal-header">
          <h3 id="modal-agendar-titulo">Agendar servicio</h3>
          <button type="button" class="cerrar-modal" id="cerrar-modal-agendar" aria-label="Cerrar"><i class="fas fa-times"></i></button>
        </div>
        <form id="form-agendar">
          <input type="hidden" id="agendar-id-servicio">
          <div id="agendar-errores" class="form-errores" style="display:none"></div>
          <div class="form-grupo">
            <label>Servicio</label>
            <input type="text" id="agendar-servicio-nombre" readonly>
          </div>
          <div class="form-grupo">
            <label>Vehículo *</label>
            <select id="agendar-vehiculo" required></select>
          </div>
          <div class="form-grupo">
            <label>Fecha *</label>
            <input type="date" id="agendar-fecha" required>
          </div>
          <div class="form-grupo">
            <label>Horario disponible *</label>
            <select id="agendar-horario" required>
              <option value="">Elija una fecha</option>
            </select>
          </div>
          <div class="form-grupo">
            <label>Motivo (opcional)</label>
            <textarea id="agendar-motivo" rows="2" placeholder="Describe el problema o lo que necesitas"></textarea>
          </div>
          <div class="form-actions">
            <button type="button" class="btn-detalle" id="cancelar-modal-agendar">Cancelar</button>
            <button type="submit" class="btn-solicitar" id="btn-confirmar-cita">
              <i class="fas fa-calendar-plus"></i> Confirmar cita
            </button>
          </div>
        </form>
      </div>
    </div>
<?php include '../../assets/includes/cliente/footer.php'; ?>
