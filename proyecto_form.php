<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('proyectos.manage');
$user = current_user();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cotId = isset($_GET['cotizacion_id']) ? (int)$_GET['cotizacion_id'] : 0;

$clientes = $pdo->query('SELECT id, razon_social FROM clientes WHERE activo = 1 ORDER BY razon_social')->fetchAll();
$cotizaciones = $pdo->query("SELECT id, codigo, cliente_id FROM cotizaciones WHERE estado = 'Aprobada' ORDER BY id DESC")->fetchAll();
$supervisores = $pdo->query(
    "SELECT u.id, u.nombre_completo FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
     WHERE u.activo = 1 AND r.codigo IN ('sup_proyectos','gerencia') ORDER BY u.nombre_completo"
)->fetchAll();
$consultores = $pdo->query(
    "SELECT u.id, u.nombre_completo FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
     WHERE u.activo = 1 AND r.codigo IN ('consultor','sup_proyectos') ORDER BY u.nombre_completo"
)->fetchAll();

$record = [
    'cotizacion_id'     => $cotId > 0 ? $cotId : '',
    'cliente_id'        => '',
    'supervisor_id'     => $user['id'],
    'consultor_id'      => '',
    'nombre_proyecto'   => '',
    'descripcion'       => '',
    'fecha_inicio'      => date('Y-m-d'),
    'fecha_entrega_est' => date('Y-m-d', strtotime('+30 days')),
    'progreso'          => 0,
    'estado'            => 'Planificado',
];

if ($cotId > 0 && $id === 0) {
    $cs = $pdo->prepare(
        'SELECT c.cliente_id, cl.razon_social
         FROM cotizaciones c INNER JOIN clientes cl ON cl.id = c.cliente_id
         WHERE c.id = ? LIMIT 1'
    );
    $cs->execute([$cotId]);
    $fc = $cs->fetch();
    if ($fc) {
        $record['cliente_id'] = $fc['cliente_id'];
        $record['nombre_proyecto'] = 'Proyecto ' . $fc['razon_social'];
    }
}

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM proyectos WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Proyecto no encontrado.');
        redirect('proyectos.php');
    }
    $record = $found;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $cotizacionId   = ($_POST['cotizacion_id'] ?? '') !== '' ? (int)$_POST['cotizacion_id'] : null;
    $clienteId      = (int)($_POST['cliente_id'] ?? 0);
    $supervisorId   = (int)($_POST['supervisor_id'] ?? 0);
    $consultorId    = ($_POST['consultor_id'] ?? '') !== '' ? (int)$_POST['consultor_id'] : null;
    $nombreProyecto = trim((string)($_POST['nombre_proyecto'] ?? ''));
    $descripcion    = trim((string)($_POST['descripcion'] ?? ''));
    $fechaInicio    = ($_POST['fecha_inicio'] ?? '') !== '' ? (string)$_POST['fecha_inicio'] : null;
    $fechaEntrega   = ($_POST['fecha_entrega_est'] ?? '') !== '' ? (string)$_POST['fecha_entrega_est'] : null;
    $progreso       = max(0, min(100, (int)($_POST['progreso'] ?? 0)));
    $estado         = (string)($_POST['estado'] ?? 'Planificado');

    if ($clienteId <= 0 || $supervisorId <= 0 || $nombreProyecto === '') {
        $error = 'Cliente, supervisor y nombre del proyecto son obligatorios.';
    } else {
        if ($id > 0) {
            $upd = $pdo->prepare(
                'UPDATE proyectos
                 SET cotizacion_id = ?, cliente_id = ?, supervisor_id = ?, consultor_id = ?,
                     nombre_proyecto = ?, descripcion = ?, fecha_inicio = ?, fecha_entrega_est = ?,
                     progreso = ?, estado = ?
                 WHERE id = ?'
            );
            $upd->execute([
                $cotizacionId, $clienteId, $supervisorId, $consultorId,
                $nombreProyecto, $descripcion ?: null, $fechaInicio, $fechaEntrega,
                $progreso, $estado, $id
            ]);
            log_audit('Proyectos', 'Actualización', (string)$record['codigo'], "Estado: {$estado}, Progreso: {$progreso}%");
            flash_set('success', 'Proyecto actualizado correctamente.');
            redirect('proyecto_ver.php?id=' . $id);
        } else {
            $codigo = generate_code($pdo, 'proyectos', 'PRY');
            $ins = $pdo->prepare(
                'INSERT INTO proyectos
                 (codigo, cotizacion_id, cliente_id, supervisor_id, consultor_id, nombre_proyecto,
                  descripcion, fecha_inicio, fecha_entrega_est, progreso, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([
                $codigo, $cotizacionId, $clienteId, $supervisorId, $consultorId, $nombreProyecto,
                $descripcion ?: null, $fechaInicio, $fechaEntrega, $progreso, $estado
            ]);
            $newId = (int)$pdo->lastInsertId();
            log_audit('Proyectos', 'Creación', $codigo, "Proyecto asignado: {$nombreProyecto}");
            flash_set('success', "Proyecto {$codigo} creado y asignado correctamente.");
            redirect('proyecto_ver.php?id=' . $newId);
        }
    }
}

render_header($id > 0 ? 'Editar Proyecto ' . $record['codigo'] : 'Nuevo Proyecto', 'proyectos');
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $id > 0 ? 'Editar Asignación de Proyecto' : 'Asignar Nuevo Proyecto' ?></h2>
        <a href="proyectos.php" class="btn btn-secondary btn-sm">&larr; Volver</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre_proyecto">Nombre del Proyecto *</label>
                    <input type="text" id="nombre_proyecto" name="nombre_proyecto" value="<?= e($record['nombre_proyecto']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Seleccione un cliente...</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= (int)$cli['id'] ?>" <?= (int)$record['cliente_id'] === (int)$cli['id'] ? 'selected' : '' ?>>
                                <?= e($cli['razon_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cotizacion_id">Cotización Aprobada (opcional)</label>
                    <select id="cotizacion_id" name="cotizacion_id">
                        <option value="">Sin cotización vinculada</option>
                        <?php foreach ($cotizaciones as $cot): ?>
                            <option value="<?= (int)$cot['id'] ?>" <?= (int)$record['cotizacion_id'] === (int)$cot['id'] ? 'selected' : '' ?>>
                                <?= e($cot['codigo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="supervisor_id">Supervisor de Proyecto *</label>
                    <select id="supervisor_id" name="supervisor_id" required>
                        <?php foreach ($supervisores as $sup): ?>
                            <option value="<?= (int)$sup['id'] ?>" <?= (int)$record['supervisor_id'] === (int)$sup['id'] ? 'selected' : '' ?>>
                                <?= e($sup['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="consultor_id">Consultor Técnico / Desarrollador</label>
                    <select id="consultor_id" name="consultor_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($consultores as $con): ?>
                            <option value="<?= (int)$con['id'] ?>" <?= (int)$record['consultor_id'] === (int)$con['id'] ? 'selected' : '' ?>>
                                <?= e($con['nombre_completo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado">
                        <?php foreach (['Planificado','En Ejecucion','En Pruebas','Entregado','Pausado'] as $est): ?>
                            <option value="<?= e($est) ?>" <?= $record['estado'] === $est ? 'selected' : '' ?>><?= e($est) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha de Inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= e((string)$record['fecha_inicio']) ?>">
                </div>
                <div class="form-group">
                    <label for="fecha_entrega_est">Fecha de Entrega Estimada</label>
                    <input type="date" id="fecha_entrega_est" name="fecha_entrega_est" value="<?= e((string)$record['fecha_entrega_est']) ?>">
                </div>
                <div class="form-group">
                    <label for="progreso">Progreso (%)</label>
                    <input type="number" id="progreso" name="progreso" min="0" max="100" value="<?= (int)$record['progreso'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Alcance y Descripción Técnica</label>
                <textarea id="descripcion" name="descripcion" rows="4"><?= e((string)$record['descripcion']) ?></textarea>
            </div>

            <div class="flex-gap">
                <button type="submit" class="btn btn-primary">Guardar Proyecto</button>
                <a href="proyectos.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php render_footer(); ?>
