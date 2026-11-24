<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();
if (current_user()) {
    redirect('index.php');
}

$error = null;
$identity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($identity === '' || $password === '') {
        $error = 'Ingrese su usuario o correo y contraseña.';
    } else {
        $res = attempt_login($identity, $password);
        if ($res['ok']) {
            flash_set('success', 'Bienvenido, ' . $res['user']['nombre_completo'] . '.');
            redirect('index.php');
        }
        $error = $res['error'];
    }
}

$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Corporativo | <?= e(APP_ORG) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div class="brand-logo">Z</div>
            <div>
                <h1 style="margin:0;font-size:20px;"><?= e(APP_ORG) ?></h1>
                <div class="text-muted" style="font-size:12px;"><?= e(APP_DEPT) ?></div>
            </div>
        </div>

        <h2 style="margin:0 0 6px;font-size:17px;">Inicio de Sesión</h2>
        <p class="text-muted" style="margin:0 0 20px;font-size:13px;">
            Plataforma de Gestión de Pre-Solicitudes, Cotizaciones y Proyectos
        </p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="on">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="identity">Usuario o Correo Corporativo</label>
                <input type="text" id="identity" name="identity" value="<?= e($identity) ?>" required autofocus placeholder="ej. cmendoza">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-2">Ingresar al Sistema</button>
        </form>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:12.5px;">
            <span class="text-muted">¿Es cliente externo?</span>
            <a href="portal.php" style="font-weight:600;">Ir al Portal de Clientes &rarr;</a>
        </div>
    </div>
</body>
</html>
