<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('presolicitudes.view');
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare(
    'SELECT p.*, c.razon_social, c.documento_rif, c.contacto_principal, c.correo, c.telefono,
            u.nombre_completo AS asignado_nombre
     FROM presolicitudes p
     INNER JOIN clientes c ON c.id = p.cliente_id
     LEFT JOIN usuarios u ON u.id = p.usuario_asignado_id
     WHERE p.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash_set('danger', 'Pre-solicitud no encontrada.');
    redirect('presolicitudes.php');
}

$cotStmt = $pdo->prepare('SELECT id, codigo, estado, total, fecha_emision FROM cotizaciones WHERE presolicitud_id = ? ORDER BY id DESC');
$cotStmt->execute([$id]);
$cotizaciones = $cotStmt->fetchAll();

render_header('Detalle Pre-Solicitud ' . $row['codigo'], 'presolicitudes');
?>

<div class="toolbar">
    <div class="flex-gap">
        <a href="presolicitudes.php" class="btn btn-secondary">&larr; Volver</a>
        <?php if (has_permission('presolicitudes.edit')): ?>
            <a href="presolicitud_form.php?id=<?= (int)$row['id'] ?>" class="btn btn-secondary">Editar</a>
        <?php endif; ?>
    </div>

    <?php if (has_permission('cotizaciones.manage')): ?>
        <a href="cotizacion_form.php?presolicitud_id=<?= (int)$row['id'] ?>" class="btn btn-primary">+ Generar Cotización</a>
    <?php endif; ?>
</div>

<div class="grid-2">
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Información de la Pre-Solicitud</h2>
            <span class="badge <?= e(badge_class($row['estado'])) ?>"><?= e($row['estado']) ?></span>
        </div>
        <div class="card-body">
            <p><strong>Código:</strong> <?= e($row['codigo']) ?></p>
            <p><strong>Tipo de Servicio:</strong> <?= e($row['tipo_servicio']) ?></p>
            <p><strong>Canal:</strong> <?= e($row['canal']) ?></p>
            <p><strong>Prioridad:</strong> <span class="badge <?= e(badge_class($row['prioridad'])) ?>"><?= e($row['prioridad']) ?></span></p>
            <p><strong>Responsable Asignado:</strong> <?= e($row['asignado_nombre'] ?? 'Sin asignar') ?></p>
            <p><strong>Registrada:</strong> <?= e(format_datetime($row['creado_en'])) ?></p>
            <hr style="border:none;border-top:1px solid var(--border);margin:14px 0;">
            <p><strong>Descripción del Requerimiento:</strong></p>
            <p><?= nl2br(e($row['descripcion'])) ?></p>
            <?php if (!empty($row['observaciones_internas'])): ?>
                <p class="mt-4"><strong>Observaciones Internas:</strong></p>
                <p class="text-muted"><?= nl2br(e($row['observaciones_internas'])) ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Datos del Cliente</h2>
        </div>
        <div class="card-body">
            <p><strong>Razón Social:</strong> <?= e($row['razon_social']) ?></p>
            <p><strong>RIF / Cédula:</strong> <?= e($row['documento_rif']) ?></p>
            <p><strong>Contacto Principal:</strong> <?= e($row['contacto_principal']) ?></p>
            <p><strong>Correo:</strong> <?= e($row['correo']) ?></p>
            <p><strong>Teléfono:</strong> <?= e($row['telefono']) ?></p>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">Cotizaciones Asociadas</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fecha Emisión</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$cotizaciones): ?>
                    <tr><td colspan="5" class="text-muted">Aún no se han emitido cotizaciones para esta pre-solicitud.</td></tr>
                <?php else: ?>
                    <?php foreach ($cotizaciones as $cot): ?>
                        <tr>
                            <td><strong><?= e($cot['codigo']) ?></strong></td>
                            <td><?= e(format_date($cot['fecha_emision'])) ?></td>
                            <td><span class="badge <?= e(badge_class($cot['estado'])) ?>"><?= e($cot['estado']) ?></span></td>
                            <td><?= e(format_money($cot['total'])) ?></td>
                            <td><a href="cotizacion_ver.php?id=<?= (int)$cot['id'] ?>" class="btn btn-secondary btn-sm">Ver Cotización</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php render_footer(); ?>
