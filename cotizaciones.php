<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('cotizaciones.view');
$pdo = db();

$q      = trim((string)($_GET['q'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));

$sql = 'SELECT cot.*, c.razon_social, p.codigo AS presol_codigo, u.nombre_completo AS elaborador_nombre
        FROM cotizaciones cot
        INNER JOIN clientes c ON c.id = cot.cliente_id
        LEFT JOIN presolicitudes p ON p.id = cot.presolicitud_id
        INNER JOIN usuarios u ON u.id = cot.elaborado_por_id
        WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (cot.codigo LIKE ? OR c.razon_social LIKE ? OR p.codigo LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($estado !== '') {
    $sql .= ' AND cot.estado = ?';
    $params[] = $estado;
}

$sql .= ' ORDER BY cot.creado_en DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Cotizaciones', 'cotizaciones');
?>

<div class="toolbar">
    <form method="get" action="cotizaciones.php" class="toolbar-filters">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar código o cliente...">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['Borrador','Enviada','Aprobada','Rechazada','Vencida'] as $est): ?>
                <option value="<?= e($est) ?>" <?= $estado === $est ? 'selected' : '' ?>><?= e($est) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <?php if ($q !== '' || $estado !== ''): ?>
            <a href="cotizaciones.php" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (has_permission('cotizaciones.manage')): ?>
        <a href="cotizacion_form.php" class="btn btn-primary">+ Nueva Cotización</a>
    <?php endif; ?>
</div>

<section class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Pre-Solicitud</th>
                    <th>Fecha Emisión</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Elaborada por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="text-muted">No hay cotizaciones registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><strong><?= e($r['codigo']) ?></strong></td>
                            <td><?= e($r['razon_social']) ?></td>
                            <td><?= e($r['presol_codigo'] ?? 'Directa') ?></td>
                            <td><?= e(format_date($r['fecha_emision'])) ?></td>
                            <td><strong><?= e(format_money($r['total'])) ?></strong></td>
                            <td><span class="badge <?= e(badge_class($r['estado'])) ?>"><?= e($r['estado']) ?></span></td>
                            <td><?= e($r['elaborador_nombre']) ?></td>
                            <td>
                                <div class="flex-gap">
                                    <a href="cotizacion_ver.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Ver</a>
                                    <?php if (has_permission('cotizaciones.manage') && in_array($r['estado'], ['Borrador', 'Enviada'], true)): ?>
                                        <a href="cotizacion_form.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php render_footer(); ?>
