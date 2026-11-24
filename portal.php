<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/audit.php';

start_secure_session();
$pdo = db();

$servicios = $pdo->query('SELECT nombre FROM servicios_tarifas WHERE activo = 1 ORDER BY nombre')->fetchAll();

$createdCode = null;
$formError = null;
$lookupResult = null;
$lookupError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'registrar_presolicitud') {
        $razonSocial = trim((string)($_POST['razon_social'] ?? ''));
        $rif         = strtoupper(trim((string)($_POST['documento_rif'] ?? '')));
        $contacto    = trim((string)($_POST['contacto_principal'] ?? ''));
        $correo      = trim((string)($_POST['correo'] ?? ''));
        $telefono    = trim((string)($_POST['telefono'] ?? ''));
        $sector      = trim((string)($_POST['sector'] ?? ''));
        $tipoServ    = trim((string)($_POST['tipo_servicio'] ?? ''));
        $prioridad   = (string)($_POST['prioridad'] ?? 'Media');
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        $allowedPriorities = ['Baja', 'Media', 'Alta', 'Urgente'];
        if (!in_array($prioridad, $allowedPriorities, true)) {
            $prioridad = 'Media';
        }

        if ($razonSocial === '' || $rif === '' || $contacto === '' || $correo === '' || $telefono === '' || $tipoServ === '' || $descripcion === '') {
            $formError = 'Por favor complete todos los campos obligatorios.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $formError = 'El correo electrónico ingresado no tiene un formato válido.';
        } else {
            try {
                $pdo->beginTransaction();

                $stmtCli = $pdo->prepare('SELECT id FROM clientes WHERE documento_rif = ? LIMIT 1');
                $stmtCli->execute([$rif]);
                $clienteId = $stmtCli->fetchColumn();

                if (!$clienteId) {
                    $insCli = $pdo->prepare(
                        'INSERT INTO clientes (razon_social, documento_rif, contacto_principal, correo, telefono, sector)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $insCli->execute([$razonSocial, $rif, $contacto, $correo, $telefono, $sector ?: null]);
                    $clienteId = (int)$pdo->lastInsertId();
                }

                $codigo = generate_code($pdo, 'presolicitudes', 'PRE');
                $insPre = $pdo->prepare(
                    'INSERT INTO presolicitudes (codigo, cliente_id, canal, tipo_servicio, prioridad, descripcion, estado)
                     VALUES (?, ?, "Web", ?, ?, ?, "Recibida")'
                );
                $insPre->execute([$codigo, $clienteId, $tipoServ, $prioridad, $descripcion]);

                $pdo->commit();

                log_audit('Pre-Solicitudes', 'Registro Portal Público', $codigo, "Nueva pre-solicitud web de {$razonSocial} ({$rif}).");
                $createdCode = $codigo;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $formError = 'No se pudo procesar su solicitud en este momento. Intente nuevamente.';
            }
        }
    } elseif ($action === 'consultar_estado') {
        $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
        $verif  = trim((string)($_POST['verificacion'] ?? ''));

        if ($codigo === '' || $verif === '') {
            $lookupError = 'Ingrese el código de pre-solicitud y su RIF/Cédula o correo.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT p.*, c.razon_social, c.documento_rif, c.correo,
                        cot.codigo AS cot_codigo, cot.estado AS cot_estado, cot.total AS cot_total
                 FROM presolicitudes p
                 INNER JOIN clientes c ON c.id = p.cliente_id
                 LEFT JOIN cotizaciones cot ON cot.presolicitud_id = p.id
                 WHERE p.codigo = ? AND (UPPER(c.documento_rif) = UPPER(?) OR LOWER(c.correo) = LOWER(?))
                 ORDER BY cot.id DESC
                 LIMIT 1'
            );
            $stmt->execute([$codigo, $verif, $verif]);
            $lookupResult = $stmt->fetch();
            if (!$lookupResult) {
                $lookupError = 'No se encontró ninguna pre-solicitud que coincida con los datos proporcionados.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Clientes | <?= e(APP_ORG) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="portal-shell">
    <header class="portal-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="brand-logo">Z</div>
            <div>
                <div style="font-weight:700;font-size:17px;"><?= e(APP_ORG) ?> &mdash; Portal de Atención al Cliente</div>
                <div style="font-size:12px;color:#94a3b8;"><?= e(APP_DEPT) ?></div>
            </div>
        </div>
        <a href="login.php" class="btn btn-outline btn-sm">Acceso Personal Interno</a>
    </header>

    <main class="portal-main">
        <?php if ($createdCode): ?>
            <div class="alert alert-success">
                <strong>Pre-Solicitud registrada exitosamente.</strong>
                Su código de seguimiento es: <strong><?= e($createdCode) ?></strong>.
                Guarde este código para consultar el avance de su requerimiento y cotización.
            </div>
        <?php endif; ?>

        <div class="grid-2">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Nueva Pre-Solicitud de Servicio</h2>
                </div>
                <div class="card-body">
                    <?php if ($formError): ?>
                        <div class="alert alert-danger"><?= e($formError) ?></div>
                    <?php endif; ?>

                    <form method="post" action="portal.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="registrar_presolicitud">

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="razon_social">Empresa o Razón Social *</label>
                                <input type="text" id="razon_social" name="razon_social" required>
                            </div>
                            <div class="form-group">
                                <label for="documento_rif">RIF o Cédula *</label>
                                <input type="text" id="documento_rif" name="documento_rif" placeholder="J-12345678-9" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="contacto_principal">Persona de Contacto *</label>
                                <input type="text" id="contacto_principal" name="contacto_principal" required>
                            </div>
                            <div class="form-group">
                                <label for="correo">Correo Electrónico *</label>
                                <input type="email" id="correo" name="correo" required>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="telefono">Teléfono *</label>
                                <input type="text" id="telefono" name="telefono" required>
                            </div>
                            <div class="form-group">
                                <label for="sector">Sector / Industria</label>
                                <input type="text" id="sector" name="sector" placeholder="Ej. Comercio, Salud, Logística">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="tipo_servicio">Tipo de Servicio Requerido *</label>
                                <select id="tipo_servicio" name="tipo_servicio" required>
                                    <option value="">Seleccione un servicio...</option>
                                    <?php foreach ($servicios as $srv): ?>
                                        <option value="<?= e($srv['nombre']) ?>"><?= e($srv['nombre']) ?></option>
                                    <?php endforeach; ?>
                                    <option value="Otro requerimiento técnico">Otro requerimiento técnico</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="prioridad">Prioridad</label>
                                <select id="prioridad" name="prioridad">
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción del Requerimiento *</label>
                            <textarea id="descripcion" name="descripcion" rows="4" required placeholder="Describa brevemente su necesidad técnica u operativa..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Enviar Pre-Solicitud</button>
                    </form>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Consultar Estado de Solicitud</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted" style="margin-top:0;">
                        Ingrese su código de pre-solicitud y el RIF/Cédula o correo asociado para verificar su estado actual.
                    </p>

                    <?php if ($lookupError): ?>
                        <div class="alert alert-warning"><?= e($lookupError) ?></div>
                    <?php endif; ?>

                    <form method="post" action="portal.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="consultar_estado">
                        <div class="form-group">
                            <label for="codigo">Código de Pre-Solicitud</label>
                            <input type="text" id="codigo" name="codigo" placeholder="Ej. PRE-2026-0001" required>
                        </div>
                        <div class="form-group">
                            <label for="verificacion">RIF / Cédula o Correo Electrónico</label>
                            <input type="text" id="verificacion" name="verificacion" placeholder="Ej. J-40129834-2" required>
                        </div>
                        <button type="submit" class="btn btn-secondary btn-block">Consultar Estado</button>
                    </form>

                    <?php if ($lookupResult): ?>
                        <div style="margin-top:20px;padding:16px;border:1px solid var(--border);border-radius:8px;background:var(--surface-alt);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                <strong><?= e($lookupResult['codigo']) ?></strong>
                                <span class="badge <?= e(badge_class($lookupResult['estado'])) ?>"><?= e($lookupResult['estado']) ?></span>
                            </div>
                            <p style="margin:4px 0;"><strong>Cliente:</strong> <?= e($lookupResult['razon_social']) ?></p>
                            <p style="margin:4px 0;"><strong>Servicio:</strong> <?= e($lookupResult['tipo_servicio']) ?></p>
                            <p style="margin:4px 0;"><strong>Fecha de registro:</strong> <?= e(format_datetime($lookupResult['creado_en'])) ?></p>
                            <?php if (!empty($lookupResult['cot_codigo'])): ?>
                                <hr style="border:none;border-top:1px solid var(--border);margin:12px 0;">
                                <p style="margin:4px 0;">
                                    <strong>Cotización asociada:</strong> <?= e($lookupResult['cot_codigo']) ?>
                                    <span class="badge <?= e(badge_class($lookupResult['cot_estado'])) ?>"><?= e($lookupResult['cot_estado']) ?></span>
                                </p>
                                <p style="margin:4px 0;"><strong>Monto total:</strong> <?= e(format_money($lookupResult['cot_total'])) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer class="app-footer">
        <span>&copy; <?= date('Y') ?> <?= e(APP_ORG) ?> &mdash; <?= e(APP_DEPT) ?></span>
        <span>Atención institucional: <?= e(APP_CONTACT_EMAIL) ?> &middot; <?= e(APP_CONTACT_PHONE) ?></span>
    </footer>
</body>
</html>
