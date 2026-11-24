<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_secure_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Solicitud inválida o token de seguridad expirado. Recargue la página e intente nuevamente.');
    }
}

function flash_set(string $type, string $message): void
{
    start_secure_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    start_secure_session();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function format_money($amount): string
{
    return '$ ' . number_format((float)$amount, 2, ',', '.');
}

function format_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : $date;
}

function format_datetime(?string $dt): string
{
    if (!$dt) {
        return '—';
    }
    $ts = strtotime($dt);
    return $ts ? date('d/m/Y H:i', $ts) : $dt;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function generate_code(PDO $pdo, string $table, string $prefix): string
{
    $allowedTables = ['presolicitudes', 'cotizaciones', 'proyectos'];
    if (!in_array($table, $allowedTables, true)) {
        throw new InvalidArgumentException('Tabla no permitida para generación de código.');
    }
    $year = date('Y');
    $like = $prefix . '-' . $year . '-%';
    $stmt = $pdo->prepare("SELECT codigo FROM {$table} WHERE codigo LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();

    $nextNum = 1;
    if ($last && preg_match('/-(\d+)$/', $last, $m)) {
        $nextNum = (int)$m[1] + 1;
    }

    return sprintf('%s-%s-%04d', $prefix, $year, $nextNum);
}

function badge_class(string $status): string
{
    $map = [
        'Recibida'     => 'badge-info',
        'En Revision'  => 'badge-warning',
        'Cotizada'     => 'badge-primary',
        'Borrador'     => 'badge-neutral',
        'Enviada'      => 'badge-info',
        'Aprobada'     => 'badge-success',
        'Rechazada'    => 'badge-danger',
        'Vencida'      => 'badge-neutral',
        'Cerrada'      => 'badge-neutral',
        'Planificado'  => 'badge-neutral',
        'En Ejecucion' => 'badge-primary',
        'En Pruebas'   => 'badge-warning',
        'Entregado'    => 'badge-success',
        'Pausado'      => 'badge-danger',
        'Baja'         => 'badge-neutral',
        'Media'        => 'badge-info',
        'Alta'         => 'badge-warning',
        'Urgente'      => 'badge-danger',
        'Avance'       => 'badge-primary',
        'Calidad'      => 'badge-success',
        'Incidencia'   => 'badge-danger',
        'Entrega'      => 'badge-success',
    ];

    return $map[$status] ?? 'badge-neutral';
}
