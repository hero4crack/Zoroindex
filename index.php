<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$user = require_login();
$pdo = db();

$kpiPresolPend = (int)$pdo->query("SELECT COUNT(*) FROM presolicitudes WHERE estado IN ('Recibida','En Revision')")->fetchColumn();
$kpiCotActivas = (int)$pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE estado IN ('Borrador','Enviada')")->fetchColumn();
$kpiPryActivos = (int)$pdo->query("SELECT COUNT(*) FROM proyectos WHERE estado IN ('Planificado','En Ejecucion','En Pruebas')")->fetchColumn();
$kpiMontoAprob = (float)$pdo->query("SELECT COALESCE(SUM(total), 0) FROM cotizaciones WHERE estado = 'Aprobada'")->fetchColumn();

$recentPresol = [];
if (has_permission('presolicitudes.view')) {
    $recentPresol = $pdo->query(
        'SELECT p.id, p.codigo, p.tipo_servicio, p.prioridad, p.estado, p.creado_en, c.razon_social
         FROM presolicitudes p
         INNER JOIN clientes c ON c.id = p.cliente_id
         ORDER BY p.creado_en DESC
         LIMIT 6'
    )->fetchAll();
}

$activeProjects = [];
if (has_permission('proyectos.view')) {
    if ($user['rol_codigo'] === 'consultor') {
        $stmt = $pdo->prepare(
            'SELECT pr.id, pr.codigo, pr.nombre_proyecto, pr.progreso, pr.estado, pr.fecha_entrega_est, c.razon_social
             FROM proyectos pr
             INNER JOIN clientes c ON c.id = pr.cliente_id
             WHERE pr.consultor_id = ?
             ORDER BY pr.actualizado_en DESC
             LIMIT 6'
        );
        $stmt->execute([$user['id']]);
        $activeProjects = $stmt->fetchAll();
    } else {
        $activeProjects = $pdo->query(
            'SELECT pr.id, pr.codigo, pr.nombre_proyecto, pr.progreso, pr.estado, pr.fecha_entrega_est, c.razon_social
             FROM proyectos pr
             INNER JOIN clientes c ON c.id = pr.cliente_id
             ORDER BY pr.actualizado_en DESC
             LIMIT 6'
        )->fetchAll();
    }
}

$recentAudit = [];
if (has_permission('auditoria.view')) {
    $recentAudit = $pdo->query(
        'SELECT usuario_nombre, modulo, accion, registro_id, creado_en
         FROM bitacoras_auditoria
         ORDER BY id DESC
         LIMIT 6'
    )->fetchAll();
}

render_header('Panel Operativo', 'dashboard');
?>

<div class="grid-kpi">
    <div class="kpi-card">
        <div class="kpi-label">Pre-Solicitudes Pendientes</div>
        <div class="kpi-value"><?= $kpiPresolPend ?></div>
        <div class="kpi-hint">Recibidas o en revisión</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Cotizaciones en Gestión</div>
        <div class="kpi-value"><?= $kpiCotActivas ?></div>
        <div class="kpi-hint">En borrador o enviadas</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Proyectos Activos</div>
        <div class="kpi-value"><?= $kpiPryActivos ?></div>
        <div class="kpi-hint">Planificados, ejecución o QA</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Cotizaciones Aprobadas</div>
        <div class="kpi-value"><?= e(format_money($kpiMontoAprob)) ?></div>
        <div class="kpi-hint">Acumulado aprobado</div>
    </div>
</div>

<div class="grid-2">
    <?php if (has_permission('presolicitudes.view')): ?>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Últimas Pre-Solicitudes</h2>
                <a href="presolicitudes.php" class="btn btn-secondary btn-sm">Ver todas</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$recentPresol): ?>
                            <tr><td colspan="4" class="text-muted">Sin registros recientes.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPresol as $row): ?>
                                <tr>
                                    <td><a href="presolicitud_ver.php?id=<?= (int)$row['id'] ?>"><strong><?= e($row['codigo']) ?></strong></a></td>
                                    <td><?= e($row['razon_social']) ?></td>
                                    <td><span class="badge <?= e(badge_class($row['prioridad'])) ?>"><?= e($row['prioridad']) ?></span></td>
                                    <td><span class="badge <?= e(badge_class($row['estado'])) ?>"><?= e($row['estado']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if (has_permission('proyectos.view')): ?>
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Proyectos en Seguimiento</h2>
                <a href="proyectos.php" class="btn btn-secondary btn-sm">Ver todos</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Proyecto</th>
                            <th>Avance</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$activeProjects): ?>
                            <tr><td colspan="4" class="text-muted">No hay proyectos asignados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activeProjects as $pry): ?>
                                <tr>
                                    <td><a href="proyecto_ver.php?id=<?= (int)$pry['id'] ?>"><strong><?= e($pry['codigo']) ?></strong></a></td>
                                    <td><?= e($pry['nombre_proyecto']) ?></td>
                                    <td style="min-width:110px;">
                                        <div style="font-size:12px;font-weight:600;margin-bottom:3px;"><?= (int)$pry['progreso'] ?>%</div>
                                        <div class="progress-bar"><div class="progress-fill" style="width:<?= (int)$pry['progreso'] ?>%"></div></div>
                                    </td>
                                    <td><span class="badge <?= e(badge_class($pry['estado'])) ?>"><?= e($pry['estado']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php if (has_permission('auditoria.view')): ?>
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Actividad Reciente en Bitácora</h2>
            <a href="auditoria.php" class="btn btn-secondary btn-sm">Bitácora completa</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAudit as $aud): ?>
                        <tr>
                            <td><?= e(format_datetime($aud['creado_en'])) ?></td>
                            <td><?= e($aud['usuario_nombre']) ?></td>
                            <td><?= e($aud['modulo']) ?></td>
                            <td><?= e($aud['accion']) ?></td>
                            <td><code><?= e($aud['registro_id'] ?? '—') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php render_footer(); ?>
