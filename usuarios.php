<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('usuarios.manage');
$pdo = db();

$roles = $pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
    $st->execute([$editId]);
    $editRow = $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $uid      = (int)($_POST['id'] ?? 0);
    $rolId    = (int)($_POST['rol_id'] ?? 0);
    $nombre   = trim((string)($_POST['nombre_completo'] ?? ''));
    $usuario  = strtolower(trim((string)($_POST['usuario'] ?? '')));
    $correo   = strtolower(trim((string)($_POST['correo'] ?? '')));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $activo   = isset($_POST['activo']) ? 1 : 0;

    if ($rolId <= 0 || $nombre === '' || $usuario === '' || $correo === '') {
        flash_set('danger', 'Rol, nombre, usuario y correo son obligatorios.');
        redirect('usuarios.php');
    }

    try {
        if ($uid > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $pdo->prepare(
                    'UPDATE usuarios SET rol_id = ?, nombre_completo = ?, usuario = ?, correo = ?, telefono = ?, password_hash = ?, activo = ? WHERE id = ?'
                );
                $upd->execute([$rolId, $nombre, $usuario, $correo, $telefono ?: null, $hash, $activo, $uid]);
            } else {
                $upd = $pdo->prepare(
                    'UPDATE usuarios SET rol_id = ?, nombre_completo = ?, usuario = ?, correo = ?, telefono = ?, activo = ? WHERE id = ?'
                );
                $upd->execute([$rolId, $nombre, $usuario, $correo, $telefono ?: null, $activo, $uid]);
            }
            log_audit('Usuarios', 'Actualización', $usuario, "Usuario {$nombre} actualizado (activo={$activo}).");
            flash_set('success', 'Usuario actualizado correctamente.');
        } else {
            if ($password === '' || strlen($password) < 6) {
                flash_set('danger', 'Debe asignar una contraseña de al menos 6 caracteres para el nuevo usuario.');
                redirect('usuarios.php');
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare(
                'INSERT INTO usuarios (rol_id, nombre_completo, usuario, correo, telefono, password_hash, activo)
                 VALUES (?, ?, ?, ?, ?, ?, 1)'
            );
            $ins->execute([$rolId, $nombre, $usuario, $correo, $telefono ?: null, $hash]);
            log_audit('Usuarios', 'Creación', $usuario, "Nuevo usuario creado: {$nombre}");
            flash_set('success', 'Usuario creado correctamente.');
        }
    } catch (PDOException $e) {
        flash_set('danger', 'No se pudo guardar el usuario (verifique que el nombre de usuario y correo no estén duplicados).');
    }
    redirect('usuarios.php');
}

$usuarios = $pdo->query(
    'SELECT u.*, r.nombre AS rol_nombre
     FROM usuarios u
     INNER JOIN roles r ON r.id = u.rol_id
     ORDER BY u.id ASC'
)->fetchAll();

render_header('Usuarios y Roles', 'usuarios');
?>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">Personal Registrado y Segmentación de Privilegios</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol Asignado</th>
                    <th>Último Acceso</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><strong><?= e($u['nombre_completo']) ?></strong></td>
                        <td><code><?= e($u['usuario']) ?></code></td>
                        <td><?= e($u['correo']) ?></td>
                        <td><?= e($u['rol_nombre']) ?></td>
                        <td><?= e(format_datetime($u['ultimo_acceso'])) ?></td>
                        <td>
                            <span class="badge <?= (int)$u['activo'] === 1 ? 'badge-success' : 'badge-danger' ?>">
                                <?= (int)$u['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td><a href="usuarios.php?edit=<?= (int)$u['id'] ?>" class="btn btn-secondary btn-sm">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $editRow ? 'Editar Usuario: ' . e($editRow['usuario']) : 'Crear Nuevo Usuario' ?></h2>
        <?php if ($editRow): ?>
            <a href="usuarios.php" class="btn btn-secondary btn-sm">Cancelar edición</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $editRow ? (int)$editRow['id'] : 0 ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo *</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" value="<?= e($editRow['nombre_completo'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="usuario">Nombre de Usuario *</label>
                    <input type="text" id="usuario" name="usuario" value="<?= e($editRow['usuario'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="rol_id">Rol Operativo *</label>
                    <select id="rol_id" name="rol_id" required>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= (int)$rol['id'] ?>" <?= (int)($editRow['rol_id'] ?? 0) === (int)$rol['id'] ? 'selected' : '' ?>>
                                <?= e($rol['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="correo">Correo Corporativo *</label>
                    <input type="email" id="correo" name="correo" value="<?= e($editRow['correo'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" value="<?= e($editRow['telefono'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña <?= $editRow ? '(dejar vacío para mantener)' : '*' ?></label>
                    <input type="password" id="password" name="password" <?= $editRow ? '' : 'required' ?> minlength="6">
                </div>
            </div>

            <?php if ($editRow): ?>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;font-weight:500;">
                        <input type="checkbox" name="activo" value="1" <?= (int)$editRow['activo'] === 1 ? 'checked' : '' ?> style="width:auto;">
                        Cuenta activa
                    </label>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary"><?= $editRow ? 'Guardar Cambios' : 'Crear Usuario' ?></button>
        </form>
    </div>
</section>

<?php render_footer(); ?>
