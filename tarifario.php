<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('tarifario.view');
$pdo = db();
$canManage = has_permission('tarifario.manage');

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0 && $canManage) {
    $st = $pdo->prepare('SELECT * FROM servicios_tarifas WHERE id = ? LIMIT 1');
    $st->execute([$editId]);
    $editRow = $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    verify_csrf();
    $sid    = (int)($_POST['id'] ?? 0);
    $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
    $nombre = trim((string)($_POST['nombre'] ?? ''));
    $desc   = trim((string)($_POST['descripcion'] ?? ''));
    $unidad = (string)($_POST['unidad'] ?? 'Servicio');
    $tarifa = max(0.0, (float)($_POST['tarifa_base'] ?? 0));
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($codigo === '' || $nombre === '') {
        flash_set('danger', 'El código y el nombre del servicio son obligatorios.');
        redirect('tarifario.php');
    }

    try {
        if ($sid > 0) {
            $upd = $pdo->prepare(
                'UPDATE servicios_tarifas SET codigo = ?, nombre = ?, descripcion = ?, unidad = ?, tarifa_base = ?, activo = ? WHERE id = ?'
            );
            $upd->execute([$codigo, $nombre, $desc ?: null, $unidad, $tarifa, $activo, $sid]);
            log_audit('Tarifario', 'Actualización', $codigo, "Servicio: {$nombre}, Tarifa: " . format_money($tarifa));
            flash_set('success', 'Servicio actualizado en el tarifario.');
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO servicios_tarifas (codigo, nombre, descripcion, unidad, tarifa_base, activo) VALUES (?, ?, ?, ?, ?, 1)'
            );
            $ins->execute([$codigo, $nombre, $desc ?: null, $unidad, $tarifa]);
            log_audit('Tarifario', 'Creación', $codigo, "Nuevo servicio: {$nombre}, Tarifa: " . format_money($tarifa));
            flash_set('success', 'Servicio agregado al tarifario.');
        }
    } catch (PDOException $e) {
        flash_set('danger', 'No se pudo guardar el servicio (verifique que el código no esté duplicado).');
    }
    redirect('tarifario.php');
}

$servicios = $pdo->query('SELECT * FROM servicios_tarifas ORDER BY codigo ASC')->fetchAll();

render_header('Tarifario de Servicios', 'tarifario');
?>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">Catálogo de Servicios y Tarifas Base</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Servicio</th>
                    <th>Unidad</th>
                    <th class="text-right">Tarifa Base</th>
                    <th>Estado</th>
                    <?php if ($canManage): ?><th>Acción</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servicios as $srv): ?>
                    <tr>
                        <td><strong><?= e($srv['codigo']) ?></strong></td>
                        <td>
                            <div><?= e($srv['nombre']) ?></div>
                            <?php if (!empty($srv['descripcion'])): ?>
                                <div class="text-muted" style="font-size:12px;"><?= e($srv['descripcion']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($srv['unidad']) ?></td>
                        <td class="text-right"><strong><?= e(format_money($srv['tarifa_base'])) ?></strong></td>
                        <td>
                            <span class="badge <?= (int)$srv['activo'] === 1 ? 'badge-success' : 'badge-neutral' ?>">
                                <?= (int)$srv['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <?php if ($canManage): ?>
                            <td><a href="tarifario.php?edit=<?= (int)$srv['id'] ?>" class="btn btn-secondary btn-sm">Editar</a></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($canManage): ?>
    <section class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $editRow ? 'Editar Servicio: ' . e($editRow['codigo']) : 'Agregar Servicio al Tarifario' ?></h2>
            <?php if ($editRow): ?>
                <a href="tarifario.php" class="btn btn-secondary btn-sm">Cancelar edición</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : 0 ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="codigo">Código *</label>
                        <input type="text" id="codigo" name="codigo" placeholder="SRV-006" value="<?= e($editRow['codigo'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre del Servicio *</label>
                        <input type="text" id="nombre" name="nombre" value="<?= e($editRow['nombre'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="unidad">Unidad de Cobro *</label>
                        <select id="unidad" name="unidad">
                            <?php foreach (['Hora','Proyecto','Mensual','Licencia','Servicio'] as $u): ?>
                                <option value="<?= e($u) ?>" <?= ($editRow['unidad'] ?? 'Servicio') === $u ? 'selected' : '' ?>><?= e($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tarifa_base">Tarifa Base ($) *</label>
                        <input type="number" step="0.01" min="0" id="tarifa_base" name="tarifa_base" value="<?= e((string)($editRow['tarifa_base'] ?? '0.00')) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="2"><?= e($editRow['descripcion'] ?? '') ?></textarea>
                </div>
                <?php if ($editRow): ?>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                            <input type="checkbox" name="activo" value="1" <?= (int)$editRow['activo'] === 1 ? 'checked' : '' ?> style="width:auto;">
                            Servicio activo
                        </label>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $editRow ? 'Guardar Cambios' : 'Agregar Servicio' ?></button>
            </form>
        </div>
    </section>
<?php endif; ?>

<?php render_footer(); ?>
