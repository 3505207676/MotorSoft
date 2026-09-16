<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Diagnóstico Computarizado - Taller El Paisa</title>
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
                    <span>Diagnóstico Computarizado</span>
                </div>
                <h1><i class="fas fa-search"></i> Diagnóstico Computarizado</h1>
                <p>Detección precisa de fallas con tecnología avanzada</p>
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
                    <img src="../../assets/images/diagnostico.svg" alt="Imagen del servicio de diagnóstico computarizado" />
                </div>
                <div class="contenido">
                    <h2>Detección de fallas electrónicas con precisión</h2>
                    <p>
                        Utilizamos herramientas de diagnóstico computarizado de última generación para identificar fallos en sistemas electrónicos del vehículo, como sensores, centralitas, sistemas ABS, y más.
                    </p>
                    <p>
                        Este servicio permite ahorrar tiempo y dinero al detectar de forma exacta la causa de problemas eléctricos y electrónicos, optimizando la reparación y evitando daños mayores.
                    </p>
                    
                    <div class="servicio-precios">
                        <h3>Precios desde:</h3>
                        <div class="precio-item">
                            <span class="servicio">Escaneo básico</span>
                            <span class="precio">$30.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Diagnóstico completo</span>
                            <span class="precio">$60.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Reprogramación de módulos</span>
                            <span class="precio">$90.000</span>
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
                        <span>Lectura de códigos de error</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Análisis de parámetros en tiempo real</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Borrado de códigos de falla</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Verificación de sistemas electrónicos</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Diagnóstico de inyección electrónica</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Informe detallado de resultados</span>
                    </div>
                </div>
            </div>
            
            <div class="servicio-proceso">
                <h3>Nuestro proceso de trabajo</h3>
                <div class="proceso-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Conexión al sistema</h4>
                            <p>Conectamos nuestro equipo al puerto OBD de tu vehículo</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Escaneo completo</h4>
                            <p>Analizamos todos los módulos y sistemas electrónicos</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Interpretación de datos</h4>
                            <p>Nuestros técnicos evalúan los resultados y determinan la causa</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Solución y verificación</h4>
                            <p>Corregimos el problema y confirmamos la reparación</p>
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
