<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/audit.php';

function role_permissions(): array
{
    return [
        'gerencia' => ['*'],
        'sup_atencion' => [
            'dashboard',
            'presolicitudes.view',
            'presolicitudes.create',
            'presolicitudes.edit',
            'presolicitudes.assign',
            'clientes.view',
            'clientes.manage',
            'cotizaciones.view',
            'proyectos.view',
        ],
        'sup_proyectos' => [
            'dashboard',
            'presolicitudes.view',
            'clientes.view',
            'cotizaciones.view',
            'cotizaciones.manage',
            'cotizaciones.approve',
            'tarifario.view',
            'tarifario.manage',
            'proyectos.view',
            'proyectos.manage',
            'proyectos.track',
        ],
        'operador' => [
            'dashboard',
            'presolicitudes.view',
            'presolicitudes.create',
            'presolicitudes.edit',
            'clientes.view',
            'clientes.manage',
        ],
        'consultor' => [
            'dashboard',
            'proyectos.view',
            'proyectos.track',
        ],
        'calidad' => [
            'dashboard',
            'presolicitudes.view',
            'cotizaciones.view',
            'proyectos.view',
            'proyectos.qa',
            'auditoria.view',
        ],
    ];
}

function current_user(): ?array
{
    start_secure_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash_set('warning', 'Debe iniciar sesión para acceder a la plataforma.');
        redirect('login.php');
    }
    return $user;
}

function has_permission(string $permission): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }
    $perms = role_permissions()[$user['rol_codigo']] ?? [];
    if (in_array('*', $perms, true)) {
        return true;
    }
    return in_array($permission, $perms, true);
}

function require_permission(string $permission): void
{
    require_login();
    if (!has_permission($permission)) {
        http_response_code(403);
        log_audit('Seguridad', 'Acceso denegado', $permission, 'Intento de acceso sin privilegios suficientes.');
        flash_set('danger', 'No tiene privilegios para acceder al módulo o acción solicitada.');
        redirect('index.php');
    }
}

function attempt_login(string $identity, string $password): array
{
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT u.*, r.codigo AS rol_codigo, r.nombre AS rol_nombre
         FROM usuarios u
         INNER JOIN roles r ON r.id = u.rol_id
         WHERE (u.usuario = ? OR u.correo = ?)
         LIMIT 1'
    );
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        log_audit(
            'Autenticación',
            'Intento fallido',
            $identity,
            'Credenciales incorrectas para el identificador ingresado.',
            ['id' => null, 'nombre_completo' => $identity, 'rol_codigo' => 'desconocido']
        );
        return ['ok' => false, 'error' => 'Usuario o contraseña incorrectos.'];
    }

    if ((int)$user['activo'] !== 1) {
        log_audit(
            'Autenticación',
            'Cuenta inactiva',
            (string)$user['id'],
            'Intento de acceso con usuario deshabilitado.',
            ['id' => $user['id'], 'nombre_completo' => $user['nombre_completo'], 'rol_codigo' => $user['rol_codigo']]
        );
        return ['ok' => false, 'error' => 'Su cuenta se encuentra deshabilitada. Contacte a Gerencia de Operaciones.'];
    }

    start_secure_session();
    session_regenerate_id(true);

    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    $upd = $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?');
    $upd->execute([$user['id']]);

    log_audit('Autenticación', 'Inicio de sesión', (string)$user['id'], 'Acceso exitoso al sistema.');

    return ['ok' => true, 'user' => $user];
}

function logout_user(): void
{
    start_secure_session();
    if (!empty($_SESSION['user'])) {
        log_audit('Autenticación', 'Cierre de sesión', (string)$_SESSION['user']['id'], 'Salida voluntaria del sistema.');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
