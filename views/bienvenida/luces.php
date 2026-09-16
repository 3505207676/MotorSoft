<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistema de Luces - Taller El Paisa</title>
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
                    <span>Sistema de Luces</span>
                </div>
                <h1><i class="fas fa-lightbulb"></i> Sistema de Luces</h1>
                <p>Reparación y mantenimiento de sistemas de iluminación</p>
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
                    <img src="../../assets/images/luces.svg" alt="Sistema de luces" />
                </div>
                <div class="contenido">
                    <h2>Visibilidad y seguridad en todo momento</h2>
                    <p>
                        Un sistema de iluminación en buen estado es fundamental para la seguridad vial. Nuestro servicio especializado abarca desde la reparación de faros hasta la instalación de sistemas LED de última generación.
                    </p>
                    <p>
                        Realizamos diagnósticos precisos para identificar fallas en el sistema eléctrico relacionado con la iluminación, asegurando que todas las luces de tu vehículo funcionen correctamente en cualquier condición.
                    </p>
                    
                    <div class="servicio-precios">
                        <h3>Precios desde:</h3>
                        <div class="precio-item">
                            <span class="servicio">Cambio de bombillas</span>
                            <span class="precio">$25.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Reparación de circuitos</span>
                            <span class="precio">$60.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Instalación sistema LED</span>
                            <span class="precio">$120.000</span>
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
                        <span>Diagnóstico completo del sistema</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Reparación de cableado</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Reemplazo de bombillas</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Ajuste de faros</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Verificación de fusibles y relés</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Instalación de sistemas LED</span>
                    </div>
                </div>
            </div>
            
            <div class="servicio-proceso">
                <h3>Nuestro proceso de trabajo</h3>
                <div class="proceso-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Diagnóstico eléctrico</h4>
                            <p>Evaluamos todo el sistema de iluminación y sus componentes</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Identificación de fallas</h4>
                            <p>Localizamos con precisión los problemas en el sistema</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Reparación o reemplazo</h4>
                            <p>Solucionamos las fallas encontradas con piezas de calidad</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Verificación y ajuste</h4>
                            <p>Comprobamos el funcionamiento y ajustamos la alineación</p>
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