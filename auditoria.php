<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('auditoria.view');
$pdo = db();

$modulo = trim((string)($_GET['modulo'] ?? ''));
$q      = trim((string)($_GET['q'] ?? ''));

$sql = 'SELECT * FROM bitacoras_auditoria WHERE 1=1';
$params = [];

if ($modulo !== '') {
    $sql .= ' AND modulo = ?';
    $params[] = $modulo;
}
if ($q !== '') {
    $sql .= ' AND (usuario_nombre LIKE ? OR accion LIKE ? OR registro_id LIKE ? OR detalle LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= ' ORDER BY id DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$modulos = $pdo->query('SELECT DISTINCT modulo FROM bitacoras_auditoria ORDER BY modulo')->fetchAll(PDO::FETCH_COLUMN);

render_header('Bitácora de Auditoría', 'auditoria');
?>

<div class="toolbar">
    <form method="get" action="auditoria.php" class="toolbar-filters">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar usuario, acción, detalle...">
        <select name="modulo">
            <option value="">Todos los módulos</option>
            <?php foreach ($modulos as $mod): ?>
                <option value="<?= e($mod) ?>" <?= $modulo === $mod ? 'selected' : '' ?>><?= e($mod) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <?php if ($modulo !== '' || $q !== ''): ?>
            <a href="auditoria.php" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>
</div>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">Registro de Trazabilidad y Seguridad (últimos 200 eventos)</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha y Hora</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Registro</th>
                    <th>Detalle</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                    <tr><td colspan="8" class="text-muted">No hay eventos registrados para los filtros seleccionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?= e(format_datetime($log['creado_en'])) ?></td>
                            <td><?= e($log['usuario_nombre']) ?></td>
                            <td><code><?= e($log['rol_codigo']) ?></code></td>
                            <td><?= e($log['modulo']) ?></td>
                            <td><strong><?= e($log['accion']) ?></strong></td>
                            <td><code><?= e($log['registro_id'] ?? '—') ?></code></td>
                            <td><?= e($log['detalle'] ?? '—') ?></td>
                            <td><code><?= e($log['ip_origen']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php render_footer(); ?>
