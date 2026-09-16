<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ff9800">
    <title>Definir contraseña | Taller El Paisa</title>
    <?php require_once __DIR__ . '/../../assets/includes/theme-fouc.php'; ?>
    <?php require __DIR__ . '/../../assets/includes/favicon.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/index.css?v=20260915logo1">
    <link rel="stylesheet" href="../../assets/css/login.css?v=20260915logo1">
    <script src="../../assets/js/config.js" defer></script>
    <script src="../../assets/js/utils/theme.js?v=20260915unif1" defer></script>
    <script src="../../assets/js/bienvenida/activar.js?v=20260915mail1" defer></script>
</head>
<body class="home login-page">
    <header class="site-header">
        <div class="wrap header-bar">
            <a class="logo" href="../bienvenida/index.php"><img src="../../assets/images/logo-taller.png" width="40" height="40" alt="Taller El Paisa"><span class="marca-nombre">Taller El Paisa</span></a>
        </div>
    </header>
    <section class="hero login-hero">
        <div class="hero-bg" aria-hidden="true">
            <span class="hero-slide is-a"></span>
            <span class="hero-slide is-b"></span>
        </div>
        <div class="wrap hero-grid login-grid">
            <div class="login-card">
                <div class="form-header">
                    <p class="hero-panel-kicker">Acceso</p>
                    <h1>Definir contraseña</h1>
                    <p>Enlace de un solo uso para activar su cuenta o restablecer la clave.</p>
                </div>
                <form id="form-activar" class="formulario-login" action="#" method="post" novalidate>
                    <input type="hidden" id="token-activar" name="token">
                    <div class="input-group">
                        <label for="password-activar">Nueva contraseña</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password-activar" name="password" placeholder="Mínimo 6 caracteres" required minlength="6">
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="password-confirm-activar">Confirmar contraseña</label>
                        <div class="input-wrap">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password-confirm-activar" name="password_confirm" placeholder="Repita la contraseña" required minlength="6">
                        </div>
                    </div>
                    <p id="activar-mensaje" class="text-muted" style="min-height:1.4em"></p>
                    <button type="submit" class="btn-login" id="btn-activar">
                        <i class="fas fa-check"></i>
                        <span>Guardar e ingresar</span>
                    </button>
                    <div class="form-links">
                        <a href="login.php" class="link-volver"><i class="fas fa-arrow-left"></i> Volver al inicio de sesión</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</body>
</html>
