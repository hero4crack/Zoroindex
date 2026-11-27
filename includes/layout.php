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
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile sidebar toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (menuToggle && sidebar && backdrop) {
        const closeMenu = () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
        };
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('open');
        });
        backdrop.addEventListener('click', closeMenu);
    }

    // 2. Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (ev) => {
            const msg = el.getAttribute('data-confirm') || '¿Confirmar esta acción?';
            if (!window.confirm(msg)) {
                ev.preventDefault();
            }
        });
    });

    // 3. Dynamic Quote Line Items
    const quoteBody = document.getElementById('quoteItemsBody');
    const addRowBtn = document.getElementById('addQuoteRow');
    const ivaInput = document.getElementById('iva_porcentaje');

    if (quoteBody && addRowBtn) {
        const recalc = () => {
            let subtotal = 0;
            quoteBody.querySelectorAll('tr.quote-row').forEach((row) => {
                const qty = parseFloat(row.querySelector('.item-qty')?.value || '0') || 0;
                const price = parseFloat(row.querySelector('.item-price')?.value || '0') || 0;
                const lineTotal = qty * price;
                subtotal += lineTotal;
                const lineCell = row.querySelector('.item-subtotal');
                if (lineCell) {
                    lineCell.textContent = '$ ' + lineTotal.toFixed(2);
                }
            });

            const ivaPct = parseFloat(ivaInput?.value || '16') || 0;
            const ivaAmt = subtotal * (ivaPct / 100);
            const total = subtotal + ivaAmt;

            const subEl = document.getElementById('quoteSubtotal');
            const ivaEl = document.getElementById('quoteIva');
            const totEl = document.getElementById('quoteTotal');
            if (subEl) subEl.textContent = '$ ' + subtotal.toFixed(2);
            if (ivaEl) ivaEl.textContent = '$ ' + ivaAmt.toFixed(2);
            if (totEl) totEl.textContent = '$ ' + total.toFixed(2);
        };

        const bindRowEvents = (row) => {
            const select = row.querySelector('.item-service');
            const desc = row.querySelector('.item-desc');
            const price = row.querySelector('.item-price');
            const qty = row.querySelector('.item-qty');
            const rm = row.querySelector('.remove-row');

            if (select) {
                select.addEventListener('change', () => {
                    const opt = select.options[select.selectedIndex];
                    if (opt && opt.value) {
                        const sName = opt.getAttribute('data-name') || '';
                        const sPrice = opt.getAttribute('data-price') || '0';
                        if (desc && !desc.value.trim()) {
                            desc.value = sName;
                        } else if (desc) {
                            desc.value = sName;
                        }
                        if (price) {
                            price.value = parseFloat(sPrice).toFixed(2);
                        }
                        recalc();
                    }
                });
            }

            if (qty) qty.addEventListener('input', recalc);
            if (price) price.addEventListener('input', recalc);

            if (rm) {
                rm.addEventListener('click', () => {
                    if (quoteBody.querySelectorAll('tr.quote-row').length > 1) {
                        row.remove();
                        recalc();
                    }
                });
            }
        };

        quoteBody.querySelectorAll('tr.quote-row').forEach(bindRowEvents);

        addRowBtn.addEventListener('click', () => {
            const firstRow = quoteBody.querySelector('tr.quote-row');
            if (!firstRow) return;
            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach((inp) => {
                if (inp.classList.contains('item-qty')) {
                    inp.value = '1';
                } else if (inp.classList.contains('item-price')) {
                    inp.value = '0.00';
                } else {
                    inp.value = '';
                }
            });
            const sel = clone.querySelector('select');
            if (sel) sel.selectedIndex = 0;
            const sub = clone.querySelector('.item-subtotal');
            if (sub) sub.textContent = '$ 0.00';

            quoteBody.appendChild(clone);
            bindRowEvents(clone);
            recalc();
        });

        if (ivaInput) {
            ivaInput.addEventListener('input', recalc);
        }

        recalc();
    }
});

</script>
</body>
</html>
    <?php
}
