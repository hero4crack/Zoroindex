<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

/**
 * Registra un evento en la bitácora de auditoría de Zoroindex.
 */
function log_audit(
    string $modulo,
    string $accion,
    ?string $registroId = null,
    string $detalle = '',
    ?array $actorOverride = null
): void {
    try {
        $pdo = db();
        start_secure_session();

        $user = $actorOverride ?? ($_SESSION['user'] ?? null);
        $usuarioId = $user['id'] ?? null;
        $usuarioNombre = $user['nombre_completo'] ?? 'Sistema / Portal Público';
        $rolCodigo = $user['rol_codigo'] ?? 'externo';

        $stmt = $pdo->prepare(
            'INSERT INTO bitacoras_auditoria
             (usuario_id, usuario_nombre, rol_codigo, modulo, accion, registro_id, detalle, ip_origen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $usuarioId,
            $usuarioNombre,
            $rolCodigo,
            $modulo,
            $accion,
            $registroId,
            $detalle,
            client_ip(),
        ]);
    } catch (Throwable $e) {
        // Evitar que un fallo de escritura en bitácora interrumpa la operación crítica,
        // registrando silenciosamente en el log de PHP.
        error_log('Error en bitácora de auditoría: ' . $e->getMessage());
    }
}
