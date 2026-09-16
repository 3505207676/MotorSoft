<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ff9800">
    <title>Taller El Paisa | Servicio automotriz en Cumaribo</title>
    <?php require_once __DIR__ . '/../../assets/includes/theme-fouc.php'; ?>
    <?php require __DIR__ . '/../../assets/includes/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css?v=20260915logo1">
    <script src="../../assets/js/utils/theme.js?v=20260915unif1" defer></script>
    <script src="../../assets/js/bienvenida/home.js?v=20260915unif1" defer></script>
</head>
<body class="home">
    <header class="site-header">
        <div class="wrap header-bar">
            <a class="logo" href="../bienvenida/index.php"><img src="../../assets/images/logo-taller.png" width="40" height="40" alt="Taller El Paisa"><span class="marca-nombre">Taller El Paisa</span></a>
            <nav id="site-nav" class="site-nav" aria-label="Principal">
                <a href="../bienvenida/index.php" class="is-active">Inicio</a>
                <a href="#servicios">Servicios</a>
                <a href="#beneficios">El taller</a>
                <a href="#faq">Ayuda</a>
                <a href="../auth/login.php" class="nav-login">Iniciar sesión</a>
            </nav>
            <div class="header-actions">
                <button type="button" id="theme-toggle" class="icon-btn" title="Cambiar tema" aria-label="Cambiar tema">
                    <i class="fas fa-moon"></i>
                </button>
                <button type="button" id="nav-toggle" class="icon-btn nav-toggle" aria-label="Abrir menú" aria-controls="site-nav" aria-expanded="false">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-bg" aria-hidden="true">
            <span class="hero-slide is-a"></span>
            <span class="hero-slide is-b"></span>
        </div>
        <div class="wrap hero-grid">
            <div class="hero-copy">
                <p class="kicker">Taller El Paisa · Cumaribo</p>
                <h1 class="hero-title">
                    <span id="hero-title" class="hero-title-text" aria-live="polite">Consulta el estado de tu vehículo</span>
                </h1>
                <p id="hero-lead" class="hero-lead">Información en tiempo real, atención personalizada y servicio técnico de confianza.</p>
                <div class="hero-dots" id="hero-dots" role="tablist" aria-label="Frases del banner"></div>
                <div class="hero-actions">
                    <a href="../auth/login.php" class="btn-primary"><i class="fas fa-right-to-bracket"></i> Iniciar sesión</a>
                    <a href="#beneficios" class="btn-ghost"><i class="fas fa-circle-info"></i> Conocer el taller</a>
                </div>
                <ul class="hero-chips">
                    <li><i class="fas fa-shield-halved"></i> Garantía</li>
                    <li><i class="fas fa-bolt"></i> Respuesta rápida</li>
                    <li><i class="fas fa-face-smile"></i> Clientes felices</li>
                </ul>
            </div>
            <aside class="hero-panel">
                <p class="hero-panel-kicker">Portal del cliente</p>
                <h2>Todo el taller, en tu celular</h2>
                <ul class="hero-panel-list">
                    <li><i class="fas fa-car-side"></i> Estado de la reparación</li>
                    <li><i class="fas fa-receipt"></i> Costos y repuestos</li>
                    <li><i class="fas fa-comments"></i> Mensajes con el taller</li>
                </ul>
                <div class="hero-panel-meta">
                    <span><strong>Lun a Sáb</strong> 7:00 a.m. – 6:00 p.m.</span>
                    <a href="tel:+573138007544">313 800 7544</a>
                </div>
            </aside>
        </div>
    </section>

    <section class="section about" id="beneficios">
        <div class="wrap about-grid">
            <div class="about-copy">
                <p class="kicker">Quiénes somos</p>
                <h2>Bienvenido a Taller El Paisa</h2>
                <p>Más de 12 años en reparación y mantenimiento automotriz. Servicio confiable, tiempos claros y atención cercana.</p>
                <p>Te mantenemos al tanto del estado de tu vehículo, desde el ingreso hasta la entrega. Tu confianza es la prioridad.</p>
            </div>
            <div class="about-visual" aria-hidden="true">
                <div class="about-badge">
                    <strong>12+</strong>
                    <span>años de experiencia</span>
                </div>
                <i class="fas fa-car-side"></i>
                <p>Mecánica · Electricidad · Diagnóstico</p>
            </div>
        </div>
    </section>

    <section class="section benefits">
        <div class="wrap">
            <div class="section-head">
                <p class="kicker">Por qué elegirnos</p>
                <h2>Un taller ordenado, de principio a fin</h2>
            </div>
            <div class="benefits-grid">
                <article class="benefit-card" data-reveal>
                    <div class="icon-box"><i class="fas fa-screwdriver-wrench"></i></div>
                    <h3>Técnicos certificados</h3>
                    <p>Personal capacitado y procesos claros para cuidar tu vehículo.</p>
                </article>
                <article class="benefit-card" data-reveal>
                    <div class="icon-box"><i class="fas fa-clock"></i></div>
                    <h3>Entrega oportuna</h3>
                    <p>Tiempos de entrega definidos y comunicación constante.</p>
                </article>
                <article class="benefit-card" data-reveal>
                    <div class="icon-box"><i class="fas fa-mobile-screen"></i></div>
                    <h3>Seguimiento en línea</h3>
                    <p>Consulta avances, repuestos y costos desde el celular o el computador.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section quotes">
        <div class="wrap">
            <div class="section-head">
                <p class="kicker">Clientes</p>
                <h2>Lo que dicen de nosotros</h2>
            </div>
            <div class="quotes-slider">
                <article class="quote is-active">
                    <div class="stars" aria-label="5 estrellas"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p>“Excelente servicio, muy profesionales. Mi carro quedó como nuevo y el seguimiento fue perfecto.”</p>
                    <div class="quote-author">
                        <strong>María González</strong>
                        <span>Cliente desde 2020</span>
                    </div>
                </article>
                <article class="quote">
                    <div class="stars" aria-label="5 estrellas"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p>“Rápidos, confiables y con precios justos. El sistema de seguimiento es muy útil.”</p>
                    <div class="quote-author">
                        <strong>Carlos Rodríguez</strong>
                        <span>Cliente desde 2019</span>
                    </div>
                </article>
                <article class="quote">
                    <div class="stars" aria-label="5 estrellas"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p>“La atención al cliente es excepcional. Siempre me mantienen informado del proceso.”</p>
                    <div class="quote-author">
                        <strong>Ana Martínez</strong>
                        <span>Cliente desde 2021</span>
                    </div>
                </article>
            </div>
            <div class="quotes-nav">
                <button type="button" class="icon-btn quote-prev" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
                <div class="quote-dots">
                    <button type="button" class="dot is-active" data-slide="0" aria-label="Testimonio 1"></button>
                    <button type="button" class="dot" data-slide="1" aria-label="Testimonio 2"></button>
                    <button type="button" class="dot" data-slide="2" aria-label="Testimonio 3"></button>
                </div>
                <button type="button" class="icon-btn quote-next" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <section class="section services" id="servicios">
        <div class="wrap">
            <div class="section-head">
                <p class="kicker">Catálogo</p>
                <h2>Nuestros servicios</h2>
            </div>
            <div class="filters">
                <button type="button" class="chip is-active" data-filtro="todos">Todos</button>
                <button type="button" class="chip" data-filtro="mecanica">Mecánica</button>
                <button type="button" class="chip" data-filtro="electricidad">Electricidad</button>
                <button type="button" class="chip" data-filtro="diagnostico">Diagnóstico</button>
            </div>
            <div class="service-grid">
                <a href="../bienvenida/mecanica.php" class="service-card" data-categoria="mecanica">
                    <span class="badge">Popular</span>
                    <div class="icon-box"><i class="fas fa-tools"></i></div>
                    <h3>Mecánica general</h3>
                    <p>Reparaciones, mantenimiento y diagnóstico completo.</p>
                    <span class="price">Desde $50.000</span>
                </a>
                <a href="../bienvenida/electricidad.php" class="service-card" data-categoria="electricidad">
                    <div class="icon-box"><i class="fas fa-car-battery"></i></div>
                    <h3>Electricidad automotriz</h3>
                    <p>Batería, luces, alternador y sistemas eléctricos.</p>
                    <span class="price">Desde $80.000</span>
                </a>
                <a href="../bienvenida/repuestos.php" class="service-card" data-categoria="mecanica">
                    <div class="icon-box"><i class="fas fa-cogs"></i></div>
                    <h3>Repuestos</h3>
                    <p>Repuestos originales, con garantía y control de calidad.</p>
                    <span class="price">Consultar</span>
                </a>
                <a href="../bienvenida/diagnostico.php" class="service-card" data-categoria="diagnostico">
                    <div class="icon-box"><i class="fas fa-search"></i></div>
                    <h3>Diagnóstico computarizado</h3>
                    <p>Escaneo digital para detectar fallas con precisión.</p>
                    <span class="price">Desde $30.000</span>
                </a>
                <a href="../bienvenida/aceite.php" class="service-card" data-categoria="mecanica">
                    <div class="icon-box"><i class="fas fa-oil-can"></i></div>
                    <h3>Cambio de aceite</h3>
                    <p>Mantenimiento preventivo con aceites de calidad.</p>
                    <span class="price">Desde $45.000</span>
                </a>
                <a href="../bienvenida/luces.php" class="service-card" data-categoria="electricidad">
                    <div class="icon-box"><i class="fas fa-lightbulb"></i></div>
                    <h3>Sistema de luces</h3>
                    <p>Reparación y mantenimiento de iluminación.</p>
                    <span class="price">Desde $25.000</span>
                </a>
            </div>
        </div>
    </section>

    <section class="section impact">
        <div class="wrap impact-grid">
            <div class="impact-item" data-counter data-target="1200">
                <i class="fas fa-car"></i>
                <strong class="num">0</strong>
                <span>Vehículos atendidos</span>
            </div>
            <div class="impact-item" data-counter data-target="4.8">
                <i class="fas fa-star"></i>
                <strong class="num">0</strong>
                <span>Calificación promedio</span>
            </div>
            <div class="impact-item" data-counter data-target="12">
                <i class="fas fa-briefcase"></i>
                <strong class="num">0</strong>
                <span>Años de experiencia</span>
            </div>
        </div>
    </section>

    <section class="section faq" id="faq">
        <div class="wrap">
            <div class="section-head">
                <p class="kicker">Ayuda</p>
                <h2>Preguntas frecuentes</h2>
            </div>
            <div class="faq-list">
                <div class="faq-item" data-accordion>
                    <button type="button" class="faq-q"><span>¿Cómo consulto el estado de mi vehículo?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-a"><p>Entra a Iniciar sesión e ingresa tu documento y placa para ver el estado actual y el historial.</p></div>
                </div>
                <div class="faq-item" data-accordion>
                    <button type="button" class="faq-q"><span>¿Puedo ver qué repuestos se usaron?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-a"><p>Sí. Verás los servicios, los repuestos utilizados y los tiempos, cuando apliquen.</p></div>
                </div>
                <div class="faq-item" data-accordion>
                    <button type="button" class="faq-q"><span>¿Tiene costo la consulta?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-a"><p>No. La consulta en línea es gratuita para nuestros clientes.</p></div>
                </div>
                <div class="faq-item" data-accordion>
                    <button type="button" class="faq-q"><span>¿Cómo sé si mi vehículo ya está listo?</span><i class="fas fa-chevron-down"></i></button>
                    <div class="faq-a"><p>Te avisamos por mensaje o correo cuando el vehículo esté listo para entrega.</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="wrap cta-inner">
            <h2>¿Listo para cuidar tu vehículo?</h2>
            <p>Inicia sesión y lleva el control de tus servicios, citas y mensajes con el taller.</p>
            <a href="../auth/login.php" class="btn-primary"><i class="fas fa-right-to-bracket"></i> Iniciar sesión</a>
        </div>
    </section>

    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div>
                <strong class="marca-taller"><img src="../../assets/images/logo-taller.png" width="36" height="36" alt=""> Taller El Paisa</strong>
                <p>Servicio técnico automotriz con calidad garantizada.</p>
            </div>
            <div>
                <p>Cumaribo, Vichada, Colombia</p>
                <p><a href="tel:+573138007544">+57 313 800 7544</a></p>
            </div>
            <p class="copy">&copy; 2026 Taller El Paisa. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
