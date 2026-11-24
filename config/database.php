<?php
declare(strict_types=1);

define('APP_NAME', 'Zoroindex Ops');
define('APP_ORG', 'Zoroindex');
define('APP_DEPT', 'Departamento de Operaciones / Tecnología de la Información');
define('APP_CONTACT_NAME', 'Carlos Eduardo Mendoza');
define('APP_CONTACT_ROLE', 'Gerente de Operaciones');
define('APP_CONTACT_EMAIL', 'cmendoza@zoroindex.com');
define('APP_CONTACT_PHONE', '0412-8850889');

define('DB_HOST', getenv('ZOROINDEX_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('ZOROINDEX_DB_PORT') ?: '3306');
define('DB_NAME', getenv('ZOROINDEX_DB_NAME') ?: 'zoroindex_ops');
define('DB_USER', getenv('ZOROINDEX_DB_USER') ?: 'root');
define('DB_PASS', getenv('ZOROINDEX_DB_PASS') !== false ? getenv('ZOROINDEX_DB_PASS') : '');

/**
 * Retorna la instancia singleton de conexión PDO a MariaDB.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit(
            '<div style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:24px;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:8px;">'
            . '<h2 style="margin-top:0;">Error de conexión con la base de datos</h2>'
            . '<p>No se pudo establecer comunicación con el servidor MariaDB (<code>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</code>).</p>'
            . '<p style="font-size:13px;color:#7f1d1d;">Verifique que el servicio MariaDB/MySQL esté activo y que el script <code>database/zoroindex_ops.sql</code> haya sido importado.</p>'
            . '</div>'
        );
    }

    return $pdo;
}
