<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Repuestos - Taller El Paisa</title>
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
                    <span>Repuestos</span>
                </div>
                <h1><i class="fas fa-cogs"></i> Repuestos</h1>
                <p>Piezas originales y alternativas con garantía de calidad</p>
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
                    <img src="../../assets/images/repuestos.svg" alt="Repuestos automotrices" />
                </div>
                <div class="contenido">
                    <h2>Disponibilidad inmediata y calidad garantizada</h2>
                    <p>
                        Contamos con un amplio inventario de repuestos originales y alternativos para diferentes marcas y modelos de vehículos. Nuestro sistema nos permite identificar rápidamente la pieza que necesita tu automóvil.
                    </p>
                    <p>
                        Todos nuestros repuestos pasan por un control de calidad riguroso para garantizar el mejor desempeño y durabilidad. Puedes consultar disponibilidad directamente desde la plataforma.
                    </p>
                    
                    <div class="servicio-precios">
                        <h3>Precios desde:</h3>
                        <div class="precio-item">
                            <span class="servicio">Filtros de aceite</span>
                            <span class="precio">$15.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Pastillas de freno</span>
                            <span class="precio">$45.000</span>
                        </div>
                        <div class="precio-item">
                            <span class="servicio">Baterías</span>
                            <span class="precio">$180.000</span>
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
                        <span>Repuestos originales y alternativos</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Garantía de calidad</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Asesoría técnica especializada</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Verificación de compatibilidad</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Instalación profesional</span>
                    </div>
                    <div class="caracteristica">
                        <i class="fas fa-check-circle"></i>
                        <span>Consulta de disponibilidad en línea</span>
                    </div>
                </div>
            </div>
            
            <div class="servicio-proceso">
                <h3>Nuestro proceso de trabajo</h3>
                <div class="proceso-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Identificación de la pieza</h4>
                            <p>Determinamos exactamente qué repuesto necesita tu vehículo</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Verificación de disponibilidad</h4>
                            <p>Confirmamos stock y opciones disponibles (original/alternativo)</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>Control de calidad</h4>
                            <p>Verificamos el estado y autenticidad de cada repuesto</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h4>Instalación y garantía</h4>
                            <p>Instalamos el repuesto y ofrecemos garantía por su funcionamiento</p>
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
