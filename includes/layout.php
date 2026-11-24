<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

function render_header(string $title, string $activeNav = 'dashboard'): void
{
    $user = require_login();
    $flash = flash_get();

    $navItems = [
        ['id' => 'dashboard',      'label' => 'Panel Operativo',   'href' => 'index.php',          'perm' => 'dashboard',           'icon' => 'grid'],
        ['id' => 'presolicitudes', 'label' => 'Pre-Solicitudes',   'href' => 'presolicitudes.php', 'perm' => 'presolicitudes.view', 'icon' => 'inbox'],
        ['id' => 'cotizaciones',   'label' => 'Cotizaciones',      'href' => 'cotizaciones.php',   'perm' => 'cotizaciones.view',   'icon' => 'file-text'],
        ['id' => 'proyectos',      'label' => 'Proyectos',         'href' => 'proyectos.php',      'perm' => 'proyectos.view',      'icon' => 'briefcase'],
        ['id' => 'clientes',       'label' => 'Clientes',          'href' => 'clientes.php',       'perm' => 'clientes.view',       'icon' => 'users'],
        ['id' => 'tarifario',      'label' => 'Tarifario',         'href' => 'tarifario.php',      'perm' => 'tarifario.view',      'icon' => 'tag'],
        ['id' => 'usuarios',       'label' => 'Usuarios y Roles',  'href' => 'usuarios.php',       'perm' => 'usuarios.manage',     'icon' => 'shield'],
        ['id' => 'auditoria',      'label' => 'Bitácora Auditoría','href' => 'auditoria.php',      'perm' => 'auditoria.view',      'icon' => 'activity'],
    ];
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | <?= e(APP_ORG) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">Z</div>
            <div>
                <div class="brand-title"><?= e(APP_ORG) ?></div>
                <div class="brand-sub">Operaciones &amp; TI</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
                <?php if (has_permission($item['perm'])): ?>
                    <a href="<?= e($item['href']) ?>"
                       class="nav-link <?= $activeNav === $item['id'] ? 'active' : '' ?>">
                        <span class="nav-dot"></span>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="nav-divider"></div>
            <a href="manual.php" class="nav-link <?= $activeNav === 'manual' ? 'active' : '' ?>">
                <span class="nav-dot"></span>
                <span>Manual de Usuario</span>
            </a>
            <a href="portal.php" class="nav-link" target="_blank" rel="noopener">
                <span class="nav-dot"></span>
                <span>Portal de Clientes &#8599;</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= e(strtoupper(substr($user['nombre_completo'], 0, 1))) ?></div>
                <div class="user-meta">
                    <div class="user-name"><?= e($user['nombre_completo']) ?></div>
                    <div class="user-role"><?= e($user['rol_nombre']) ?></div>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline btn-sm btn-block">Cerrar Sesión</a>
        </div>
    </aside>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú">
                    <span></span><span></span><span></span>
                </button>
                <div>
                    <h1 class="page-title"><?= e($title) ?></h1>
                    <div class="page-subtitle"><?= e(APP_DEPT) ?></div>
                </div>
            </div>
            <div class="topbar-right">
                <span class="topbar-pill"><?= e($user['rol_nombre']) ?></span>
            </div>
        </header>

        <main class="main-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
        </main>
        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e(APP_ORG) ?> &mdash; <?= e(APP_DEPT) ?></span>
            <span>Contacto Institucional: <?= e(APP_CONTACT_EMAIL) ?> &middot; <?= e(APP_CONTACT_PHONE) ?></span>
        </footer>
    </div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
    <?php
}
