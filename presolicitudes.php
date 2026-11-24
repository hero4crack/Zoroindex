<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('presolicitudes.view');
$pdo = db();

$q      = trim((string)($_GET['q'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));
$prio   = trim((string)($_GET['prioridad'] ?? ''));

$sql = 'SELECT p.*, c.razon_social, c.documento_rif, u.nombre_completo AS asignado_nombre
        FROM presolicitudes p
        INNER JOIN clientes c ON c.id = p.cliente_id
        LEFT JOIN usuarios u ON u.id = p.usuario_asignado_id
        WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (p.codigo LIKE ? OR c.razon_social LIKE ? OR p.tipo_servicio LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($estado !== '') {
    $sql .= ' AND p.estado = ?';
    $params[] = $estado;
}
if ($prio !== '') {
    $sql .= ' AND p.prioridad = ?';
    $params[] = $prio;
}

$sql .= ' ORDER BY p.creado_en DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Pre-Solicitudes de Servicio', 'presolicitudes');
?>

<div class="toolbar">
    <form method="get" action="presolicitudes.php" class="toolbar-filters">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar código, cliente o servicio...">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['Recibida','En Revision','Cotizada','Aprobada','Rechazada','Cerrada'] as $est): ?>
                <option value="<?= e($est) ?>" <?= $estado === $est ? 'selected' : '' ?>><?= e($est) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="prioridad">
            <option value="">Todas las prioridades</option>
            <?php foreach (['Baja','Media','Alta','Urgente'] as $pr): ?>
                <option value="<?= e($pr) ?>" <?= $prio === $pr ? 'selected' : '' ?>><?= e($pr) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <?php if ($q !== '' || $estado !== '' || $prio !== ''): ?>
            <a href="presolicitudes.php" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (has_permission('presolicitudes.create')): ?>
        <a href="presolicitud_form.php" class="btn btn-primary">+ Nueva Pre-Solicitud</a>
    <?php endif; ?>
</div>

<section class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Canal</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Asignado a</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="text-muted">No se encontraron pre-solicitudes con los criterios indicados.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><strong><?= e($r['codigo']) ?></strong></td>
                            <td>
                                <div><?= e($r['razon_social']) ?></div>
                                <div class="text-muted" style="font-size:12px;"><?= e($r['documento_rif']) ?></div>
                            </td>
                            <td><?= e($r['tipo_servicio']) ?></td>
                            <td><?= e($r['canal']) ?></td>
                            <td><span class="badge <?= e(badge_class($r['prioridad'])) ?>"><?= e($r['prioridad']) ?></span></td>
                            <td><span class="badge <?= e(badge_class($r['estado'])) ?>"><?= e($r['estado']) ?></span></td>
                            <td><?= e($r['asignado_nombre'] ?? 'Sin asignar') ?></td>
                            <td><?= e(format_date($r['creado_en'])) ?></td>
                            <td>
                                <div class="flex-gap">
                                    <a href="presolicitud_ver.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Ver</a>
                                    <?php if (has_permission('presolicitudes.edit')): ?>
                                        <a href="presolicitud_form.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
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
