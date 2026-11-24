<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('cotizaciones.view');
$user = current_user();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_permission('cotizaciones.approve')) {
    verify_csrf();
    $newStatus = (string)($_POST['new_status'] ?? '');
    if (in_array($newStatus, ['Enviada', 'Aprobada', 'Rechazada'], true)) {
        $aprobadoPor = $newStatus === 'Aprobada' ? $user['id'] : null;
        $upd = $pdo->prepare('UPDATE cotizaciones SET estado = ?, aprobado_por_id = COALESCE(?, aprobado_por_id) WHERE id = ?');
        $upd->execute([$newStatus, $aprobadoPor, $id]);

        $cInfo = $pdo->prepare('SELECT codigo, presolicitud_id FROM cotizaciones WHERE id = ?');
        $cInfo->execute([$id]);
        $ci = $cInfo->fetch();

        if ($ci && $newStatus === 'Aprobada' && !empty($ci['presolicitud_id'])) {
            $pdo->prepare("UPDATE presolicitudes SET estado = 'Aprobada' WHERE id = ?")->execute([$ci['presolicitud_id']]);
        }

        log_audit('Cotizaciones', 'Cambio de estado', $ci['codigo'] ?? (string)$id, "Nuevo estado: {$newStatus}");
        flash_set('success', "Estado de la cotización actualizado a {$newStatus}.");
        redirect('cotizacion_ver.php?id=' . $id);
    }
}

$stmt = $pdo->prepare(
    'SELECT cot.*, c.razon_social, c.documento_rif, c.contacto_principal, c.correo, c.telefono, c.direccion,
            p.codigo AS presol_codigo,
            ue.nombre_completo AS elaborador_nombre,
            ua.nombre_completo AS aprobador_nombre
     FROM cotizaciones cot
     INNER JOIN clientes c ON c.id = cot.cliente_id
     LEFT JOIN presolicitudes p ON p.id = cot.presolicitud_id
     INNER JOIN usuarios ue ON ue.id = cot.elaborado_por_id
     LEFT JOIN usuarios ua ON ua.id = cot.aprobado_por_id
     WHERE cot.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$cot = $stmt->fetch();

if (!$cot) {
    flash_set('danger', 'Cotización no encontrada.');
    redirect('cotizaciones.php');
}

$itStmt = $pdo->prepare('SELECT * FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY id');
$itStmt->execute([$id]);
$items = $itStmt->fetchAll();

$pryStmt = $pdo->prepare('SELECT id, codigo FROM proyectos WHERE cotizacion_id = ? LIMIT 1');
$pryStmt->execute([$id]);
$proyectoVinculado = $pryStmt->fetch();

render_header('Cotización ' . $cot['codigo'], 'cotizaciones');
?>

<div class="toolbar no-print">
    <div class="flex-gap">
        <a href="cotizaciones.php" class="btn btn-secondary">&larr; Volver</a>
        <?php if (has_permission('cotizaciones.manage') && in_array($cot['estado'], ['Borrador', 'Enviada'], true)): ?>
            <a href="cotizacion_form.php?id=<?= (int)$cot['id'] ?>" class="btn btn-secondary">Editar</a>
        <?php endif; ?>
        <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimir / PDF</button>
    </div>

    <div class="flex-gap">
        <?php if (has_permission('cotizaciones.approve') && in_array($cot['estado'], ['Borrador', 'Enviada'], true)): ?>
            <form method="post" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="new_status" value="Aprobada">
                <button type="submit" class="btn btn-success" data-confirm="¿Aprobar esta cotización?">Aprobar</button>
            </form>
            <form method="post" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="new_status" value="Rechazada">
                <button type="submit" class="btn btn-danger" data-confirm="¿Rechazar esta cotización?">Rechazar</button>
            </form>
        <?php endif; ?>

        <?php if ($proyectoVinculado): ?>
            <a href="proyecto_ver.php?id=<?= (int)$proyectoVinculado['id'] ?>" class="btn btn-primary">
                Ver Proyecto <?= e($proyectoVinculado['codigo']) ?>
            </a>
        <?php elseif ($cot['estado'] === 'Aprobada' && has_permission('proyectos.manage')): ?>
            <a href="proyecto_form.php?cotizacion_id=<?= (int)$cot['id'] ?>" class="btn btn-primary">
                + Asignar Proyecto
            </a>
        <?php endif; ?>
    </div>
</div>

<section class="card">
    <div class="card-body">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;border-bottom:2px solid var(--border);padding-bottom:16px;margin-bottom:20px;">
            <div>
                <h2 style="margin:0;font-size:22px;"><?= e(APP_ORG) ?></h2>
                <div class="text-muted"><?= e(APP_DEPT) ?></div>
                <div class="text-muted" style="font-size:12.5px;"><?= e(APP_CONTACT_EMAIL) ?> &middot; <?= e(APP_CONTACT_PHONE) ?></div>
            </div>
            <div class="text-right">
                <div style="font-size:18px;font-weight:800;"><?= e($cot['codigo']) ?></div>
                <div>Fecha: <?= e(format_date($cot['fecha_emision'])) ?> &middot; Vigencia: <?= (int)$cot['vigencia_dias'] ?> días</div>
                <div class="mt-2"><span class="badge <?= e(badge_class($cot['estado'])) ?>"><?= e($cot['estado']) ?></span></div>
            </div>
        </div>

        <div class="grid-2" style="margin-bottom:16px;">
            <div>
                <h3 style="margin:0 0 8px;font-size:14px;text-transform:uppercase;color:var(--text-muted);">Cliente</h3>
                <p style="margin:2px 0;"><strong><?= e($cot['razon_social']) ?></strong></p>
                <p style="margin:2px 0;">RIF / Cédula: <?= e($cot['documento_rif']) ?></p>
                <p style="margin:2px 0;">Contacto: <?= e($cot['contacto_principal']) ?></p>
                <p style="margin:2px 0;"><?= e($cot['correo']) ?> &middot; <?= e($cot['telefono']) ?></p>
            </div>
            <div>
                <h3 style="margin:0 0 8px;font-size:14px;text-transform:uppercase;color:var(--text-muted);">Control Interno</h3>
                <p style="margin:2px 0;"><strong>Pre-Solicitud:</strong> <?= e($cot['presol_codigo'] ?? 'Directa') ?></p>
                <p style="margin:2px 0;"><strong>Elaborada por:</strong> <?= e($cot['elaborador_nombre']) ?></p>
                <?php if ($cot['aprobador_nombre']): ?>
                    <p style="margin:2px 0;"><strong>Aprobada por:</strong> <?= e($cot['aprobador_nombre']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Descripción del Servicio</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Precio Unitario</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= e($it['descripcion']) ?></td>
                            <td class="text-right"><?= number_format((float)$it['cantidad'], 2, ',', '.') ?></td>
                            <td class="text-right"><?= e(format_money($it['precio_unitario'])) ?></td>
                            <td class="text-right"><strong><?= e(format_money($it['subtotal'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:16px;">
            <div style="min-width:260px;">
                <div style="display:flex;justify-content:space-between;padding:4px 0;">
                    <span>Subtotal:</span>
                    <strong><?= e(format_money($cot['subtotal'])) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:4px 0;">
                    <span>IVA (<?= number_format((float)$cot['iva_porcentaje'], 2) ?>%):</span>
                    <strong><?= e(format_money($cot['iva_monto'])) ?></strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:2px solid var(--border);font-size:16px;">
                    <strong>Total:</strong>
                    <strong><?= e(format_money($cot['total'])) ?></strong>
                </div>
            </div>
        </div>

        <?php if (!empty($cot['condiciones'])): ?>
            <div style="margin-top:20px;padding-top:14px;border-top:1px solid var(--border);">
                <strong>Condiciones Comerciales:</strong>
                <p class="text-muted" style="margin:6px 0 0;"><?= nl2br(e($cot['condiciones'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php render_footer(); ?>
