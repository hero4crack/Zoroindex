<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_permission('cotizaciones.manage');
$user = current_user();
$pdo = db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preId = isset($_GET['presolicitud_id']) ? (int)$_GET['presolicitud_id'] : 0;

$clientes = $pdo->query('SELECT id, razon_social, documento_rif FROM clientes WHERE activo = 1 ORDER BY razon_social')->fetchAll();
$servicios = $pdo->query('SELECT id, codigo, nombre, unidad, tarifa_base FROM servicios_tarifas WHERE activo = 1 ORDER BY nombre')->fetchAll();
$presolicitudes = $pdo->query(
    "SELECT p.id, p.codigo, p.cliente_id, c.razon_social, p.tipo_servicio
     FROM presolicitudes p
     INNER JOIN clientes c ON c.id = p.cliente_id
     ORDER BY p.creado_en DESC"
)->fetchAll();

$record = [
    'presolicitud_id' => $preId > 0 ? $preId : '',
    'cliente_id'      => '',
    'fecha_emision'   => date('Y-m-d'),
    'vigencia_dias'   => 15,
    'iva_porcentaje'  => '16.00',
    'estado'          => 'Borrador',
    'condiciones'     => '50% al inicio del proyecto y 50% contra entrega y validación operativa.',
];

$items = [
    ['servicio_id' => '', 'descripcion' => '', 'cantidad' => '1', 'precio_unitario' => '0.00'],
];

if ($preId > 0 && $id === 0) {
    $ps = $pdo->prepare('SELECT cliente_id, tipo_servicio FROM presolicitudes WHERE id = ? LIMIT 1');
    $ps->execute([$preId]);
    $foundPre = $ps->fetch();
    if ($foundPre) {
        $record['cliente_id'] = $foundPre['cliente_id'];
        $items[0]['descripcion'] = $foundPre['tipo_servicio'];
    }
}

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM cotizaciones WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        flash_set('danger', 'Cotización no encontrada.');
        redirect('cotizaciones.php');
    }
    $record = $found;
    $itStmt = $pdo->prepare('SELECT * FROM cotizacion_items WHERE cotizacion_id = ? ORDER BY id');
    $itStmt->execute([$id]);
    $dbItems = $itStmt->fetchAll();
    if ($dbItems) {
        $items = $dbItems;
    }
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $presolicitudId = ($_POST['presolicitud_id'] ?? '') !== '' ? (int)$_POST['presolicitud_id'] : null;
    $clienteId      = (int)($_POST['cliente_id'] ?? 0);
    $fechaEmision   = (string)($_POST['fecha_emision'] ?? date('Y-m-d'));
    $vigenciaDias   = max(1, (int)($_POST['vigencia_dias'] ?? 15));
    $ivaPct         = max(0.0, (float)($_POST['iva_porcentaje'] ?? 16.0));
    $estado         = (string)($_POST['estado'] ?? 'Borrador');
    $condiciones    = trim((string)($_POST['condiciones'] ?? ''));

    $srvIds = $_POST['servicio_id'] ?? [];
    $descs  = $_POST['descripcion'] ?? [];
    $qtys   = $_POST['cantidad'] ?? [];
    $prices = $_POST['precio_unitario'] ?? [];

    $cleanItems = [];
    $subtotal = 0.0;

    for ($i = 0; $i < count($descs); $i++) {
        $d = trim((string)($descs[$i] ?? ''));
        $q = max(0.01, (float)($qtys[$i] ?? 0));
        $p = max(0.0, (float)($prices[$i] ?? 0));
        $sId = ($srvIds[$i] ?? '') !== '' ? (int)$srvIds[$i] : null;
        if ($d !== '') {
            $lineSub = round($q * $p, 2);
            $subtotal += $lineSub;
            $cleanItems[] = [
                'servicio_id'     => $sId,
                'descripcion'     => $d,
                'cantidad'        => $q,
                'precio_unitario' => $p,
                'subtotal'        => $lineSub,
            ];
        }
    }

    $ivaMonto = round($subtotal * ($ivaPct / 100), 2);
    $total = round($subtotal + $ivaMonto, 2);

    if ($clienteId <= 0 || !$cleanItems) {
        $error = 'Seleccione un cliente e incluya al menos un ítem válido en la cotización.';
    } else {
        try {
            $pdo->beginTransaction();

            if ($id > 0) {
                $upd = $pdo->prepare(
                    'UPDATE cotizaciones
                     SET presolicitud_id = ?, cliente_id = ?, fecha_emision = ?, vigencia_dias = ?,
                         subtotal = ?, iva_porcentaje = ?, iva_monto = ?, total = ?, estado = ?, condiciones = ?
                     WHERE id = ?'
                );
                $upd->execute([
                    $presolicitudId, $clienteId, $fechaEmision, $vigenciaDias,
                    $subtotal, $ivaPct, $ivaMonto, $total, $estado, $condiciones ?: null, $id
                ]);
                $pdo->prepare('DELETE FROM cotizacion_items WHERE cotizacion_id = ?')->execute([$id]);
                $cotId = $id;
                $codigo = $record['codigo'];
                $accion = 'Actualización';
            } else {
                $codigo = generate_code($pdo, 'cotizaciones', 'COT');
                $ins = $pdo->prepare(
                    'INSERT INTO cotizaciones
                     (codigo, presolicitud_id, cliente_id, elaborado_por_id, fecha_emision, vigencia_dias,
                      subtotal, iva_porcentaje, iva_monto, total, estado, condiciones)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $codigo, $presolicitudId, $clienteId, $user['id'], $fechaEmision, $vigenciaDias,
                    $subtotal, $ivaPct, $ivaMonto, $total, $estado, $condiciones ?: null
                ]);
                $cotId = (int)$pdo->lastInsertId();
                $accion = 'Creación';
            }

            $insItem = $pdo->prepare(
                'INSERT INTO cotizacion_items (cotizacion_id, servicio_id, descripcion, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            foreach ($cleanItems as $ci) {
                $insItem->execute([
                    $cotId, $ci['servicio_id'], $ci['descripcion'], $ci['cantidad'], $ci['precio_unitario'], $ci['subtotal']
                ]);
            }

            if ($presolicitudId) {
                $pdo->prepare("UPDATE presolicitudes SET estado = 'Cotizada' WHERE id = ? AND estado IN ('Recibida','En Revision')")
                    ->execute([$presolicitudId]);
            }

            $pdo->commit();

            log_audit('Cotizaciones', $accion, $codigo, "Total: " . format_money($total) . " ({$estado})");
            flash_set('success', "Cotización {$codigo} guardada correctamente.");
            redirect('cotizacion_ver.php?id=' . $cotId);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Error al guardar la cotización.';
        }
    }
}

render_header($id > 0 ? 'Editar Cotización ' . $record['codigo'] : 'Nueva Cotización', 'cotizaciones');
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= $id > 0 ? 'Editar Cotización' : 'Elaborar Cotización' ?></h2>
        <a href="cotizaciones.php" class="btn btn-secondary btn-sm">&larr; Volver</a>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>

            <div class="form-grid">
                <div class="form-group">
                    <label for="presolicitud_id">Pre-Solicitud Asociada (opcional)</label>
                    <select id="presolicitud_id" name="presolicitud_id">
                        <option value="">Sin pre-solicitud (Directa)</option>
                        <?php foreach ($presolicitudes as $ps): ?>
                            <option value="<?= (int)$ps['id'] ?>" <?= (int)$record['presolicitud_id'] === (int)$ps['id'] ? 'selected' : '' ?>>
                                <?= e($ps['codigo']) ?> &mdash; <?= e($ps['razon_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

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
                    <label for="estado">Estado *</label>
                    <select id="estado" name="estado">
                        <?php foreach (['Borrador','Enviada','Aprobada','Rechazada','Vencida'] as $est): ?>
                            <option value="<?= e($est) ?>" <?= $record['estado'] === $est ? 'selected' : '' ?>><?= e($est) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="fecha_emision">Fecha de Emisión *</label>
                    <input type="date" id="fecha_emision" name="fecha_emision" value="<?= e($record['fecha_emision']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="vigencia_dias">Vigencia (días) *</label>
                    <input type="number" id="vigencia_dias" name="vigencia_dias" min="1" value="<?= (int)$record['vigencia_dias'] ?>" required>
                </div>
                <div class="form-group">
                    <label for="iva_porcentaje">IVA (%)</label>
                    <input type="number" step="0.01" min="0" id="iva_porcentaje" name="iva_porcentaje" value="<?= e((string)$record['iva_porcentaje']) ?>">
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin:18px 0 10px;">
                <h3 style="margin:0;font-size:15px;">Ítems de la Cotización</h3>
                <button type="button" id="addQuoteRow" class="btn btn-secondary btn-sm">+ Agregar Ítem</button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="min-width:210px;">Servicio del Tarifario</th>
                            <th style="min-width:250px;">Descripción *</th>
                            <th style="width:100px;">Cantidad</th>
                            <th style="width:130px;">Precio Unit. ($)</th>
                            <th style="width:130px;">Subtotal</th>
                            <th style="width:70px;"></th>
                        </tr>
                    </thead>
                    <tbody id="quoteItemsBody">
                        <?php foreach ($items as $it): ?>
                            <tr class="quote-row">
                                <td>
                                    <select name="servicio_id[]" class="item-service">
                                        <option value="">Personalizado</option>
                                        <?php foreach ($servicios as $srv): ?>
                                            <option value="<?= (int)$srv['id'] ?>"
                                                    data-name="<?= e($srv['nombre']) ?>"
                                                    data-price="<?= e((string)$srv['tarifa_base']) ?>"
                                                    <?= (int)($it['servicio_id'] ?? 0) === (int)$srv['id'] ? 'selected' : '' ?>>
                                                <?= e($srv['codigo']) ?> - <?= e($srv['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="text" name="descripcion[]" class="item-desc" value="<?= e($it['descripcion']) ?>" required></td>
                                <td><input type="number" step="0.01" min="0.01" name="cantidad[]" class="item-qty" value="<?= e((string)$it['cantidad']) ?>" required></td>
                                <td><input type="number" step="0.01" min="0" name="precio_unitario[]" class="item-price" value="<?= e((string)$it['precio_unitario']) ?>" required></td>
                                <td class="item-subtotal" style="font-weight:600;">$ 0.00</td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-row" title="Quitar">&times;</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                <div style="min-width:260px;background:var(--surface-alt);border:1px solid var(--border);border-radius:8px;padding:14px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span class="text-muted">Subtotal:</span>
                        <strong id="quoteSubtotal">$ 0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span class="text-muted">IVA:</span>
                        <strong id="quoteIva">$ 0.00</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:16px;border-top:1px solid var(--border);padding-top:8px;">
                        <strong>Total:</strong>
                        <strong id="quoteTotal">$ 0.00</strong>
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <label for="condiciones">Condiciones Comerciales y Plazo de Entrega</label>
                <textarea id="condiciones" name="condiciones" rows="3"><?= e($record['condiciones']) ?></textarea>
            </div>

            <div class="flex-gap">
                <button type="submit" class="btn btn-primary">Guardar Cotización</button>
                <a href="cotizaciones.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php render_footer(); ?>
