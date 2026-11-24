<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('proyectos.view');
$user = current_user();
$pdo = db();

$q      = trim((string)($_GET['q'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));

$sql = 'SELECT pr.*, c.razon_social, us.nombre_completo AS supervisor_nombre, uc.nombre_completo AS consultor_nombre
        FROM proyectos pr
        INNER JOIN clientes c ON c.id = pr.cliente_id
        INNER JOIN usuarios us ON us.id = pr.supervisor_id
        LEFT JOIN usuarios uc ON uc.id = pr.consultor_id
        WHERE 1=1';
$params = [];

if ($user['rol_codigo'] === 'consultor') {
    $sql .= ' AND pr.consultor_id = ?';
    $params[] = $user['id'];
}
if ($q !== '') {
    $sql .= ' AND (pr.codigo LIKE ? OR pr.nombre_proyecto LIKE ? OR c.razon_social LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($estado !== '') {
    $sql .= ' AND pr.estado = ?';
    $params[] = $estado;
}

$sql .= ' ORDER BY pr.creado_en DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Asignación y Seguimiento de Proyectos', 'proyectos');
?>

<div class="toolbar">
    <form method="get" action="proyectos.php" class="toolbar-filters">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar código, proyecto o cliente...">
        <select name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (['Planificado','En Ejecucion','En Pruebas','Entregado','Pausado'] as $est): ?>
                <option value="<?= e($est) ?>" <?= $estado === $est ? 'selected' : '' ?>><?= e($est) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <?php if ($q !== '' || $estado !== ''): ?>
            <a href="proyectos.php" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (has_permission('proyectos.manage')): ?>
        <a href="proyecto_form.php" class="btn btn-primary">+ Nuevo Proyecto</a>
    <?php endif; ?>
</div>

<section class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Proyecto</th>
                    <th>Cliente</th>
                    <th>Consultor Asignado</th>
                    <th>Entrega Est.</th>
                    <th>Progreso</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="text-muted">No hay proyectos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><strong><?= e($r['codigo']) ?></strong></td>
                            <td><?= e($r['nombre_proyecto']) ?></td>
                            <td><?= e($r['razon_social']) ?></td>
                            <td><?= e($r['consultor_nombre'] ?? 'Sin asignar') ?></td>
                            <td><?= e(format_date($r['fecha_entrega_est'])) ?></td>
                            <td style="min-width:110px;">
                                <div style="font-size:12px;font-weight:600;margin-bottom:3px;"><?= (int)$r['progreso'] ?>%</div>
                                <div class="progress-bar"><div class="progress-fill" style="width:<?= (int)$r['progreso'] ?>%"></div></div>
                            </td>
                            <td><span class="badge <?= e(badge_class($r['estado'])) ?>"><?= e($r['estado']) ?></span></td>
                            <td>
                                <div class="flex-gap">
                                    <a href="proyecto_ver.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Seguimiento</a>
                                    <?php if (has_permission('proyectos.manage')): ?>
                                        <a href="proyecto_form.php?id=<?= (int)$r['id'] ?>" class="btn btn-secondary btn-sm">Editar</a>
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
