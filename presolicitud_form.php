<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    require_permission('presolicitudes.edit');
} else {
    require_permission('presolicitudes.create');
}

$pdo = db();
$clientes = $pdo->query('SELECT id, razon_social, documento_rif FROM clientes WHERE activo = 1 ORDER BY razon_social')->fetchAll();
$servicios = $pdo->query('SELECT nombre FROM servicios_tarifas WHERE activo = 1 ORDER BY nombre')->fetchAll();
$operadores = $pdo->query(
    "SELECT u.id, u.nombre_completo, r.nombre AS rol_nombre
     FROM usuarios u
     INNER JOIN roles r ON r.id = u.rol_id
     WHERE u.activo = 1 AND r.codigo IN ('operador','sup_atencion','sup_proyectos','gerencia')
     ORDER BY u.nombre_completo"
)->fetchAll();

$record = [
    'cliente_id'             => '',
    'usuario_asignado_id'    => '',
    'canal'                  => 'Telefono',
    'tipo_servicio'          => '',
    'prioridad'              => 'Media',
    'estado'                 => 'Recibida',
    'descripcion'            => '',
    'observaciones_internas' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM presolicitudes WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Pre-solicitud no encontrada.');
        redirect('presolicitudes.php');
    }
    $record = $found;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $clienteId  = (int)($_POST['cliente_id'] ?? 0);
    $asignadoId = ($_POST['usuario_asignado_id'] ?? '') !== '' ? (int)$_POST['usuario_asignado_id'] : null;
    $canal      = (string)($_POST['canal'] ?? 'Telefono');
    $tipoServ   = trim((string)($_POST['tipo_servicio'] ?? ''));
    $prioridad  = (string)($_POST['prioridad'] ?? 'Media');
    $estado     = (string)($_POST['estado'] ?? 'Recibida');
    $desc       = trim((string)($_POST['descripcion'] ?? ''));
    $obs        = trim((string)($_POST['observaciones_internas'] ?? ''));

    if ($clienteId <= 0 || $tipoServ === '' || $desc === '') {
        $error = 'Cliente, tipo de servicio y descripción son obligatorios.';
    } else {
        if ($id > 0) {
            $upd = $pdo->prepare(
                'UPDATE presolicitudes
                 SET cliente_id = ?, usuario_asignado_id = ?, canal = ?, tipo_servicio = ?,
                     prioridad = ?, estado = ?, descripcion = ?, observaciones_internas = ?
                 WHERE id = ?'
            );
            $upd->execute([$clienteId, $asignadoId, $canal, $tipoServ, $prioridad, $estado, $desc, $obs ?: null, $id]);

            log_audit('Pre-Solicitudes', 'Actualización', (string)$record['codigo'], "Estado: {$estado}, Prioridad: {$prioridad}");
            flash_set('success', 'Pre-solicitud actualizada correctamente.');
            redirect('presolicitud_ver.php?id=' . $id);
        } else {
            $codigo = generate_code($pdo, 'presolicitudes', 'PRE');
            $ins = $pdo->prepare(
                'INSERT INTO presolicitudes
                 (codigo, cliente_id, usuario_asignado_id, canal, tipo_servicio, prioridad, estado, descripcion, observaciones_internas)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$codigo, $clienteId, $asignadoId, $canal, $tipoServ, $prioridad, $estado, $desc, $obs ?: null]);
            $newId = (int)$pdo->lastInsertId();

            log_audit('Pre-Solicitudes', 'Creación', $codigo, "Nueva pre-solicitud registrada vía {$canal}.");
            flash_set('success', "Pre-solicitud {$codigo} registrada correctamente.");
            redirect('presolicitud_ver.php?id=' . $newId);
        }
    }
}

render_header($id > 0 ? 'Editar Pre-Solicitud ' . $record['codigo'] : 'Nueva Pre-Solicitud', 'presolicitudes');
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $id > 0 ? 'Modificar Datos de Pre-Solicitud' : 'Registrar Pre-Solicitud de Servicio' ?></h2>
        <a href="presolicitudes.php" class="btn btn-secondary btn-sm">&larr; Volver al listado</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required>
                        <option value="">Seleccione un cliente...</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= (int)$cli['id'] ?>" <?= (int)$record['cliente_id'] === (int)$cli['id'] ? 'selected' : '' ?>>
                                <?= e($cli['razon_social']) ?> (<?= e($cli['documento_rif']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="canal">Canal de Recepción *</label>
                    <select id="canal" name="canal" required>
                        <?php foreach (['Web','Telefono','Correo','Presencial'] as $c): ?>
                            <option value="<?= e($c) ?>" <?= $record['canal'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prioridad">Prioridad *</label>
                    <select id="prioridad" name="prioridad" required>
                        <?php foreach (['Baja','Media','Alta','Urgente'] as $p): ?>
                            <option value="<?= e($p) ?>" <?= $record['prioridad'] === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tipo_servicio">Tipo de Servicio *</label>
                    <select id="tipo_servicio" name="tipo_servicio" required>
                        <option value="">Seleccione un servicio...</option>
                        <?php foreach ($servicios as $srv): ?>
                            <option value="<?= e($srv['nombre']) ?>" <?= $record['tipo_servicio'] === $srv['nombre'] ? 'selected' : '' ?>>
                                <?= e($srv['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Otro requerimiento técnico" <?= $record['tipo_servicio'] === 'Otro requerimiento técnico' ? 'selected' : '' ?>>
                            Otro requerimiento técnico
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="usuario_asignado_id">Responsable Asignado</label>
                    <select id="usuario_asignado_id" name="usuario_asignado_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($operadores as $op): ?>
                            <option value="<?= (int)$op['id'] ?>" <?= (int)$record['usuario_asignado_id'] === (int)$op['id'] ? 'selected' : '' ?>>
                                <?= e($op['nombre_completo']) ?> (<?= e($op['rol_nombre']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado" required>
                        <?php foreach (['Recibida','En Revision','Cotizada','Aprobada','Rechazada','Cerrada'] as $est): ?>
                            <option value="<?= e($est) ?>" <?= $record['estado'] === $est ? 'selected' : '' ?>><?= e($est) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción del Requerimiento *</label>
                <textarea id="descripcion" name="descripcion" rows="4" required><?= e($record['descripcion']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="observaciones_internas">Observaciones Internas</label>
                <textarea id="observaciones_internas" name="observaciones_internas" rows="3"><?= e($record['observaciones_internas']) ?></textarea>
            </div>

            <div class="flex-gap">
                <button type="submit" class="btn btn-primary"><?= $id > 0 ? 'Guardar Cambios' : 'Registrar Pre-Solicitud' ?></button>
                <a href="presolicitudes.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php render_footer(); ?>
