<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mecánica General - Taller El Paisa</title>
    <?php require_once __DIR__ . '/../../assets/includes/theme-fouc.php'; ?>
    <link rel="stylesheet" href="../../assets/css/servicios.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="../../assets/js/utils/theme.js?v=20260915unif1" defer></script>
    <script src="../../assets/js/bienvenida/servicios.js?v=20260915unif1" defer></script>
</head>
<body>
    <div class="envoltorio">
        <header class="servicio-header">
            <div class="header-content">
                <div class="breadcrumb">
                    <a href="../bienvenida/index.php"><i class="fas fa-home"></i> Inicio</a>
                    <span>/</span>
                    <span>Mecánica General</span>
                </div>
                <h1><i class="fas fa-tools"></i> Mecánica General</h1>
                <p>Servicio completo de reparación y mantenimiento automotriz</p>
            </div>
            <div class="header-controls">
                <button id="theme-toggle" class="theme-toggle" title="Cambiar tema">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>
    
        <section class="servicio-detalle">
            <div class="servicio-info">
                <div class="imagen">
                    <img src="../../assets/images/mecanica.svg" alt="Imagen del servicio de mecánica" />
                </div>
                <div class="contenido">
                    <h2>Reparaciones y mantenimiento preventivo</h2>
                    <p>
                        Nuestro equipo técnico está especializado en diagnóstico y reparación de motores, frenos, suspensión y más. Utilizamos herramientas de última generación para garantizar la seguridad y el rendimiento de tu vehículo.
                    </p>
                    <p>
                        Este servicio incluye revisión general, cambio de piezas defectuosas, y asesoría personalizada para prolongar la vida útil de tu automóvil.
                    </p>
                    
                    <div class="servicio-precios">
                        <h3>Precios desde:</h3>
                        <div class="precio-item">
                            <span class="servicio">Diagnóstico general</span>
                            <span class="precio">$50.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Cambio de aceite</span>
                            <span class="precio">$45.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Revisión de frenos</span>
                            <span class="precio">$80.000</span>
                        </div>
                    </div>
                    
                    <div class="servicio-acciones">
                        <a href="../bienvenida/index.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
                        <a href="../bienvenida/index.php#servicios" class="btn-contacto"><i class="fas fa-phone"></i> Contactar</a>
                    </div>
                </div>
            </div>
            
            <div class="servicio-caracteristicas">
                <h3>¿Qué incluye este servicio?</h3>
                <div class="caracteristicas-grid">
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Diagnóstico completo del motor</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Revisión de sistema de frenos</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Inspección de suspensión</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Cambio de filtros y fluidos</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Revisión de sistema eléctrico</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Garantía en repuestos</span>
                    </div>
                </div>
            </div>
            
            <div class="servicio-proceso">
                <h3>Nuestro proceso de trabajo</h3>
                <div class="proceso-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Diagnóstico inicial</h4>
                            <p>Evaluamos el estado general de tu vehículo</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Cotización detallada</h4>
                            <p>Te presentamos un presupuesto claro y transparente</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Reparación especializada</h4>
                            <p>Nuestros técnicos realizan el trabajo con precisión</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Pruebas y entrega</h4>
                            <p>Verificamos que todo funcione correctamente</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    
        <footer>
            <p>&copy; 2025 Taller El Paisa. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>
