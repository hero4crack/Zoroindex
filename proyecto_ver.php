<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('proyectos.view');
$user = current_user();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare(
    'SELECT pr.*, c.razon_social, c.contacto_principal, c.correo,
            cot.codigo AS cot_codigo,
            us.nombre_completo AS supervisor_nombre,
            uc.nombre_completo AS consultor_nombre
     FROM proyectos pr
     INNER JOIN clientes c ON c.id = pr.cliente_id
     LEFT JOIN cotizaciones cot ON cot.id = pr.cotizacion_id
     INNER JOIN usuarios us ON us.id = pr.supervisor_id
     LEFT JOIN usuarios uc ON uc.id = pr.consultor_id
     WHERE pr.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$pry = $stmt->fetch();

if (!$pry) {
    flash_set('danger', 'Proyecto no encontrado.');
    redirect('proyectos.php');
}

if ($user['rol_codigo'] === 'consultor' && (int)$pry['consultor_id'] !== (int)$user['id']) {
    flash_set('danger', 'Solo puede consultar los proyectos asignados a su usuario.');
    redirect('proyectos.php');
}

$canTrack = has_permission('proyectos.track') || has_permission('proyectos.qa');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canTrack) {
    verify_csrf();
    $tipo = (string)($_POST['tipo'] ?? 'Avance');
    $horas = max(0.0, (float)($_POST['horas_registradas'] ?? 0));
    $pct = max(0, min(100, (int)($_POST['porcentaje_reportado'] ?? (int)$pry['progreso'])));
    $nuevoEstado = (string)($_POST['nuevo_estado'] ?? $pry['estado']);
    $comentario = trim((string)($_POST['comentario'] ?? ''));

    if ($comentario === '') {
        flash_set('danger', 'El comentario de seguimiento es obligatorio.');
        redirect('proyecto_ver.php?id=' . $id);
    }

    $ins = $pdo->prepare(
        'INSERT INTO proyecto_seguimientos (proyecto_id, usuario_id, tipo, horas_registradas, porcentaje_reportado, comentario)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$id, $user['id'], $tipo, $horas, $pct, $comentario]);

    $upd = $pdo->prepare('UPDATE proyectos SET progreso = ?, estado = ? WHERE id = ?');
    $upd->execute([$pct, $nuevoEstado, $id]);

    log_audit('Proyectos', 'Registro de Seguimiento', (string)$pry['codigo'], "{$tipo} ({$pct}%): {$comentario}");
    flash_set('success', 'Seguimiento técnico registrado correctamente.');
    redirect('proyecto_ver.php?id=' . $id);
}

$segStmt = $pdo->prepare(
    'SELECT s.*, u.nombre_completo, r.nombre AS rol_nombre
     FROM proyecto_seguimientos s
     INNER JOIN usuarios u ON u.id = s.usuario_id
     INNER JOIN roles r ON r.id = u.rol_id
     WHERE s.proyecto_id = ?
     ORDER BY s.creado_en DESC'
);
$segStmt->execute([$id]);
$seguimientos = $segStmt->fetchAll();

render_header('Proyecto ' . $pry['codigo'], 'proyectos');
?>

<div class="toolbar">
    <div class="flex-gap">
        <a href="proyectos.php" class="btn btn-secondary">&larr; Volver</a>
        <?php if (has_permission('proyectos.manage')): ?>
            <a href="proyecto_form.php?id=<?= (int)$pry['id'] ?>" class="btn btn-secondary">Editar Asignación</a>
        <?php endif; ?>
    </div>
    <span class="badge <?= e(badge_class($pry['estado'])) ?>"><?= e($pry['estado']) ?></span>
</div>

<div class="grid-2">
    <section class="card">
        <div class="card-header">
            <h2 class="card-title"><?= e($pry['nombre_proyecto']) ?></h2>
        </div>
        <div class="card-body">
            <p><strong>Código:</strong> <?= e($pry['codigo']) ?></p>
            <p><strong>Cliente:</strong> <?= e($pry['razon_social']) ?> (<?= e($pry['contacto_principal']) ?>)</p>
            <p><strong>Cotización:</strong> <?= e($pry['cot_codigo'] ?? 'Sin vínculo') ?></p>
            <p><strong>Supervisor:</strong> <?= e($pry['supervisor_nombre']) ?></p>
            <p><strong>Consultor Técnico:</strong> <?= e($pry['consultor_nombre'] ?? 'Sin asignar') ?></p>
            <p><strong>Inicio:</strong> <?= e(format_date($pry['fecha_inicio'])) ?> &middot; <strong>Entrega Est.:</strong> <?= e(format_date($pry['fecha_entrega_est'])) ?></p>

            <div style="margin:14px 0;">
                <div style="display:flex;justify-content:space-between;font-weight:600;margin-bottom:4px;">
                    <span>Progreso General</span>
                    <span><?= (int)$pry['progreso'] ?>%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?= (int)$pry['progreso'] ?>%"></div></div>
            </div>

            <?php if (!empty($pry['descripcion'])): ?>
                <p class="text-muted"><?= nl2br(e($pry['descripcion'])) ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($canTrack): ?>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Registrar Seguimiento Técnico / QA</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="tipo">Tipo de Registro</label>
                            <select id="tipo" name="tipo">
                                <option value="Avance">Avance Técnico</option>
                                <option value="Calidad">Revisión de Calidad (QA)</option>
                                <option value="Incidencia">Incidencia / Bloqueo</option>
                                <option value="Entrega">Hito / Entrega</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="horas_registradas">Horas Dedicadas</label>
                            <input type="number" step="0.5" min="0" id="horas_registradas" name="horas_registradas" value="2.0">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="porcentaje_reportado">Nuevo Progreso (%)</label>
                            <input type="number" min="0" max="100" id="porcentaje_reportado" name="porcentaje_reportado" value="<?= (int)$pry['progreso'] ?>">
                        </div>
                        <div class="form-group">
                            <label for="nuevo_estado">Estado del Proyecto</label>
                            <select id="nuevo_estado" name="nuevo_estado">
                                <?php foreach (['Planificado','En Ejecucion','En Pruebas','Entregado','Pausado'] as $est): ?>
                                    <option value="<?= e($est) ?>" <?= $pry['estado'] === $est ? 'selected' : '' ?>><?= e($est) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comentario">Detalle de Actividades u Observaciones *</label>
                        <textarea id="comentario" name="comentario" rows="3" required placeholder="Describa los avances realizados, hallazgos de calidad o entregables..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Guardar Seguimiento</button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</div>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">Historial de Seguimiento</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Responsable</th>
                    <th>Horas</th>
                    <th>Avance</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$seguimientos): ?>
                    <tr><td colspan="6" class="text-muted">Aún no se han registrado seguimientos en este proyecto.</td></tr>
                <?php else: ?>
                    <?php foreach ($seguimientos as $seg): ?>
                        <tr>
                            <td><?= e(format_datetime($seg['creado_en'])) ?></td>
                            <td><span class="badge <?= e(badge_class($seg['tipo'])) ?>"><?= e($seg['tipo']) ?></span></td>
                            <td>
                                <div><?= e($seg['nombre_completo']) ?></div>
                                <div class="text-muted" style="font-size:11.5px;"><?= e($seg['rol_nombre']) ?></div>
                            </td>
                            <td><?= number_format((float)$seg['horas_registradas'], 1) ?> h</td>
                            <td><?= (int)$seg['porcentaje_reportado'] ?>%</td>
                            <td><?= nl2br(e($seg['comentario'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php render_footer(); ?>
