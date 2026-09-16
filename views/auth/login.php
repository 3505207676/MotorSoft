<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ff9800">
    <title>Iniciar sesión | Taller El Paisa</title>
    <?php require_once __DIR__ . '/../../assets/includes/theme-fouc.php'; ?>
    <?php require __DIR__ . '/../../assets/includes/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css?v=20260915logo1">
    <link rel="stylesheet" href="../../assets/css/login.css?v=20260915logo1">
    <script src="../../assets/js/config.js" defer></script>
    <script src="../../assets/js/utils/input-filters.js" defer></script>
    <script src="../../assets/js/utils/placa.js?v=20260915placa2" defer></script>
    <script src="../../assets/js/utils/theme.js?v=20260915unif1" defer></script>
    <script src="../../assets/js/bienvenida/login.js?v=20260915placa2" defer></script>
</head>
<body class="home login-page">
    <header class="site-header">
        <div class="wrap header-bar">
            <a class="logo" href="../bienvenida/index.php"><img src="../../assets/images/logo-taller.png" width="40" height="40" alt="Taller El Paisa"><span class="marca-nombre">Taller El Paisa</span></a>
            <nav id="site-nav" class="site-nav" aria-label="Principal">
                <a href="../bienvenida/index.php">Inicio</a>
                <a href="../bienvenida/index.php#servicios">Servicios</a>
                <a href="../bienvenida/index.php#beneficios">El taller</a>
                <a href="../bienvenida/index.php#faq">Ayuda</a>
                <a href="../auth/login.php" class="nav-login is-active">Iniciar sesión</a>
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

    <section class="hero login-hero">
        <div class="hero-bg" aria-hidden="true">
            <span class="hero-slide is-a"></span>
            <span class="hero-slide is-b"></span>
        </div>
        <div class="wrap hero-grid login-grid">
            <div class="hero-copy login-copy">
                <p class="kicker">Taller El Paisa · Cumaribo</p>
                <h1 class="hero-title">Accede a tu taller</h1>
                <p class="hero-lead">Personal: documento y contraseña. Cliente: documento y placa para ver el estado del vehículo.</p>
                <ul class="hero-chips">
                    <li><i class="fas fa-shield-halved"></i> Garantía</li>
                    <li><i class="fas fa-bolt"></i> Respuesta rápida</li>
                    <li><i class="fas fa-mobile-screen"></i> Seguimiento en línea</li>
                </ul>
            </div>

            <div class="login-card">
                <div class="form-header">
                    <img class="login-seal" src="../../assets/images/logo-taller.png" width="72" height="72" alt="Taller El Paisa">
                    <p class="hero-panel-kicker">Portal</p>
                    <h1>Iniciar sesión</h1>
                    <p>Usa tu documento. Si eres cliente, agrega también la placa (carro ABC123 o moto ABC12D).</p>
                </div>

                <form id="form-login" class="formulario-login" action="#" method="post" novalidate>
                    <div class="input-group">
                        <label for="placa">Placa (clientes)</label>
                        <div class="input-wrap">
                            <i class="fas fa-car input-icon"></i>
                            <input type="text" id="placa" name="placa" placeholder="ABC123 o ABC12D" autocomplete="off" data-filter="plate" maxlength="7">
                        </div>
                        <div class="error-message" id="error-placa"></div>
                    </div>

                    <div class="input-group">
                        <label for="documento">Documento de identidad</label>
                        <div class="input-wrap">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" id="documento" name="documento" inputmode="numeric" placeholder="1234567890" autocomplete="off" data-filter="digits" maxlength="15">
                        </div>
                        <div class="error-message" id="error-documento"></div>
                    </div>

                    <div class="input-group">
                        <label for="password">Contraseña (personal del taller)</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" placeholder="Tu contraseña" autocomplete="off">
                        </div>
                        <div class="error-message" id="error-password"></div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="recordar">
                            <span class="checkmark"></span>
                            Recordar mis datos
                        </label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-right-to-bracket"></i>
                        <span>Acceder</span>
                        <div class="btn-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>

                    <div class="form-links">
                        <a href="#" id="recuperar-password" class="link-recuperar">¿Olvidaste tu contraseña?</a>
                        <a href="../bienvenida/index.php" class="link-volver"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <div id="modal-recuperar" class="modal-recuperar">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Recuperar contraseña</h2>
                <button type="button" class="close-modal" id="cerrar-modal" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Solo para personal del taller. Le enviaremos un enlace a su correo para definir una nueva contraseña. El cliente entra con documento y placa, sin contraseña.</p>
                <div id="alerta-recuperar" class="mensaje-general" hidden role="alert"></div>
                <form id="form-recuperar" class="form-recuperar">
                    <div class="input-group">
                        <label for="documento-recuperar">Documento de identidad</label>
                        <div class="input-wrap">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" id="documento-recuperar" name="documento" inputmode="numeric" placeholder="1000000001" data-filter="digits" maxlength="15" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="email-recuperar">Correo electrónico</label>
                        <div class="input-wrap">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" id="email-recuperar" name="email" placeholder="admin@tallerpaisa.com" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-login btn-recuperar">
                        <i class="fas fa-paper-plane"></i>
                        <span>Enviar enlace</span>
                        <div class="btn-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="wrap footer-grid">
            <div>
                <strong>Taller El Paisa</strong>
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
