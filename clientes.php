<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('clientes.view');
$pdo = db();
$canManage = has_permission('clientes.manage');

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0 && $canManage) {
    $st = $pdo->prepare('SELECT * FROM clientes WHERE id = ? LIMIT 1');
    $st->execute([$editId]);
    $editRow = $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    verify_csrf();
    $cid         = (int)($_POST['id'] ?? 0);
    $razonSocial = trim((string)($_POST['razon_social'] ?? ''));
    $rif         = strtoupper(trim((string)($_POST['documento_rif'] ?? '')));
    $contacto    = trim((string)($_POST['contacto_principal'] ?? ''));
    $correo      = trim((string)($_POST['correo'] ?? ''));
    $telefono    = trim((string)($_POST['telefono'] ?? ''));
    $direccion   = trim((string)($_POST['direccion'] ?? ''));
    $sector      = trim((string)($_POST['sector'] ?? ''));
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($razonSocial === '' || $rif === '' || $contacto === '' || $correo === '' || $telefono === '') {
        flash_set('danger', 'Complete los campos obligatorios del cliente.');
        redirect('clientes.php' . ($cid > 0 ? '?edit=' . $cid : ''));
    }

    try {
        if ($cid > 0) {
            $upd = $pdo->prepare(
                'UPDATE clientes SET razon_social = ?, documento_rif = ?, contacto_principal = ?,
                 correo = ?, telefono = ?, direccion = ?, sector = ?, activo = ? WHERE id = ?'
            );
            $upd->execute([$razonSocial, $rif, $contacto, $correo, $telefono, $direccion ?: null, $sector ?: null, $activo, $cid]);
            log_audit('Clientes', 'Actualización', $rif, "Cliente actualizado: {$razonSocial}");
            flash_set('success', 'Cliente actualizado correctamente.');
        } else {
            $ins = $pdo->prepare(
                'INSERT INTO clientes (razon_social, documento_rif, contacto_principal, correo, telefono, direccion, sector, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1)'
            );
            $ins->execute([$razonSocial, $rif, $contacto, $correo, $telefono, $direccion ?: null, $sector ?: null]);
            log_audit('Clientes', 'Creación', $rif, "Nuevo cliente: {$razonSocial}");
            flash_set('success', 'Cliente registrado correctamente.');
        }
    } catch (PDOException $e) {
        flash_set('danger', 'No se pudo guardar el cliente (verifique que el RIF/Cédula no esté duplicado).');
    }
    redirect('clientes.php');
}

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT * FROM clientes WHERE 1=1';
$params = [];
if ($q !== '') {
    $sql .= ' AND (razon_social LIKE ? OR documento_rif LIKE ? OR contacto_principal LIKE ?)';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY razon_social ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

render_header('Directorio de Clientes', 'clientes');
?>

<div class="grid-2">
    <section class="card" style="grid-column: 1 / -1;">
        <div class="card-header">
            <h2 class="card-title">Clientes Registrados</h2>
            <form method="get" action="clientes.php" class="toolbar-filters">
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Buscar razón social o RIF...">
                <button type="submit" class="btn btn-secondary btn-sm">Buscar</button>
                <?php if ($q !== ''): ?>
                    <a href="clientes.php" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Razón Social</th>
                        <th>RIF / Cédula</th>
                        <th>Contacto</th>
                        <th>Correo / Teléfono</th>
                        <th>Sector</th>
                        <th>Estado</th>
                        <?php if ($canManage): ?><th>Acción</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$clientes): ?>
                        <tr><td colspan="7" class="text-muted">No hay clientes registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td><strong><?= e($cli['razon_social']) ?></strong></td>
                                <td><?= e($cli['documento_rif']) ?></td>
                                <td><?= e($cli['contacto_principal']) ?></td>
                                <td>
                                    <div><?= e($cli['correo']) ?></div>
                                    <div class="text-muted" style="font-size:12px;"><?= e($cli['telefono']) ?></div>
                                </td>
                                <td><?= e($cli['sector'] ?? '—') ?></td>
                                <td>
                                    <span class="badge <?= (int)$cli['activo'] === 1 ? 'badge-success' : 'badge-neutral' ?>">
                                        <?= (int)$cli['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <?php if ($canManage): ?>
                                    <td><a href="clientes.php?edit=<?= (int)$cli['id'] ?>" class="btn btn-secondary btn-sm">Editar</a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($canManage): ?>
        <section class="card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h2 class="card-title"><?= $editRow ? 'Editar Cliente: ' . e($editRow['razon_social']) : 'Registrar Nuevo Cliente' ?></h2>
                <?php if ($editRow): ?>
                    <a href="clientes.php" class="btn btn-secondary btn-sm">Cancelar edición</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : 0 ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="razon_social">Razón Social *</label>
                            <input type="text" id="razon_social" name="razon_social" value="<?= e($editRow['razon_social'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="documento_rif">RIF / Cédula *</label>
                            <input type="text" id="documento_rif" name="documento_rif" value="<?= e($editRow['documento_rif'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="contacto_principal">Contacto Principal *</label>
                            <input type="text" id="contacto_principal" name="contacto_principal" value="<?= e($editRow['contacto_principal'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="correo">Correo *</label>
                            <input type="email" id="correo" name="correo" value="<?= e($editRow['correo'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="telefono">Teléfono *</label>
                            <input type="text" id="telefono" name="telefono" value="<?= e($editRow['telefono'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="sector">Sector</label>
                            <input type="text" id="sector" name="sector" value="<?= e($editRow['sector'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <input type="text" id="direccion" name="direccion" value="<?= e($editRow['direccion'] ?? '') ?>">
                    </div>
                    <?php if ($editRow): ?>
                        <div class="form-group">
                            <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                                <input type="checkbox" name="activo" value="1" <?= (int)$editRow['activo'] === 1 ? 'checked' : '' ?> style="width:auto;">
                                Cliente activo
                            </label>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRow ? 'Actualizar Cliente' : 'Registrar Cliente' ?></button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
