<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

require_login();
render_header('Manual de Usuario', 'manual');
?>

<div class="toolbar no-print">
    <div class="text-muted">Guía operativa de la plataforma <?= e(APP_ORG) ?> &mdash; <?= e(APP_DEPT) ?></div>
    <button type="button" onclick="window.print()" class="btn btn-secondary">Imprimir / Guardar PDF</button>
</div>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">1. Descripción General del Sistema</h2>
    </div>
    <div class="card-body">
        <p>
            <strong><?= e(APP_ORG) ?> Ops</strong> es la plataforma web centralizada para la gestión de
            <strong>pre-solicitudes de servicio, cotizaciones, asignaciones de proyectos, clientes y auditoría operativa</strong>.
            Está diseñada para operar desde navegadores modernos en estaciones de escritorio y dispositivos móviles.
        </p>
        <ul>
            <li><strong>Acceso interno:</strong> <code>login.php</code> (personal autorizado según su rol).</li>
            <li><strong>Portal público de clientes:</strong> <code>portal.php</code> (registro y consulta externa de pre-solicitudes).</li>
        </ul>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">2. Roles y Permisos de Acceso (RBAC)</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Módulos Habilitados</th>
                        <th>Responsabilidad Principal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Gerencia de Operaciones y TI</strong></td>
                        <td>Todos los módulos</td>
                        <td>Supervisión general, aprobación de cotizaciones, gestión de tarifario, usuarios y auditoría.</td>
                    </tr>
                    <tr>
                        <td><strong>Supervisión de Atención al Cliente</strong></td>
                        <td>Panel, Pre-Solicitudes, Clientes, Cotizaciones (lectura), Proyectos (lectura)</td>
                        <td>Control de recepción de servicios, priorización y asignación de operadores.</td>
                    </tr>
                    <tr>
                        <td><strong>Supervisión de Proyectos y Consultoría</strong></td>
                        <td>Panel, Pre-Solicitudes (lectura), Clientes (lectura), Cotizaciones, Tarifario, Proyectos</td>
                        <td>Elaboración/aprobación de cotizaciones, gestión de tarifas y asignación de consultores.</td>
                    </tr>
                    <tr>
                        <td><strong>Operador de Atención al Cliente</strong></td>
                        <td>Panel, Pre-Solicitudes, Clientes</td>
                        <td>Registro de clientes y recepción/clasificación inicial de pre-solicitudes.</td>
                    </tr>
                    <tr>
                        <td><strong>Consultor Técnico / Desarrollador</strong></td>
                        <td>Panel, Proyectos asignados</td>
                        <td>Ejecución técnica y registro de avances, horas dedicadas y estado de proyecto.</td>
                    </tr>
                    <tr>
                        <td><strong>Analista de Calidad y Soporte Operativo</strong></td>
                        <td>Panel, Pre-Solicitudes (lectura), Cotizaciones (lectura), Proyectos (QA), Auditoría</td>
                        <td>Validación de calidad en proyectos y revisión de trazabilidad en bitácora.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">3. Flujo Operativo Paso a Paso</h2>
    </div>
    <div class="card-body">
        <h3 style="margin-top:0;font-size:15px;">3.1 Portal Público de Clientes (<code>portal.php</code>)</h3>
        <ol>
            <li>El cliente ingresa al portal externo sin necesidad de cuenta.</li>
            <li>Completa el formulario <strong>Nueva Pre-Solicitud de Servicio</strong> (Razón Social, RIF/Cédula, contacto, correo, teléfono, tipo de servicio, prioridad y descripción).</li>
            <li>Al enviar, el sistema genera un código único (ej. <code>PRE-2026-0004</code>).</li>
            <li>En la sección <strong>Consultar Estado de Solicitud</strong>, el cliente puede ingresar su código y su RIF/Cédula o correo para verificar el avance y si ya existe una cotización asociada.</li>
        </ol>

        <h3 style="font-size:15px;">3.2 Gestión de Pre-Solicitudes y Clientes</h3>
        <ol>
            <li>El operador o supervisor ingresa al módulo <strong>Pre-Solicitudes</strong>.</li>
            <li>Puede filtrar por código, cliente, estado (<code>Recibida</code>, <code>En Revision</code>, <code>Cotizada</code>, <code>Aprobada</code>, <code>Rechazada</code>, <code>Cerrada</code>) o prioridad.</li>
            <li>Para registrar una solicitud recibida por teléfono, correo o presencial, hace clic en <strong>+ Nueva Pre-Solicitud</strong>.</li>
            <li>Al abrir el detalle (<strong>Ver</strong>), si el usuario tiene permisos de cotización, puede hacer clic en <strong>+ Generar Cotización</strong>.</li>
        </ol>

        <h3 style="font-size:15px;">3.3 Tarifario y Cotizaciones</h3>
        <ol>
            <li>En <strong>Tarifario</strong> se administran los servicios estándar con su unidad de cobro y tarifa base.</li>
            <li>En <strong>Cotizaciones</strong> &rarr; <strong>+ Nueva Cotización</strong>, se selecciona el cliente (y opcionalmente la pre-solicitud asociada).</li>
            <li>Al elegir un servicio del tarifario en una fila, la descripción y el precio unitario se completan automáticamente; los totales e IVA se calculan en tiempo real y se validan en el servidor.</li>
            <li>Desde la vista de detalle de la cotización se puede usar <strong>Imprimir / PDF</strong>, o bien <strong>Aprobar</strong> / <strong>Rechazar</strong> (según permisos).</li>
            <li>Cuando una cotización está <strong>Aprobada</strong>, aparece el botón <strong>+ Asignar Proyecto</strong>.</li>
        </ol>

        <h3 style="font-size:15px;">3.4 Asignación y Seguimiento de Proyectos</h3>
        <ol>
            <li>Al crear o editar un proyecto en <strong>Proyectos</strong>, se define el supervisor, el consultor técnico asignado, fechas de inicio/entrega estimada y alcance.</li>
            <li>En <strong>Seguimiento</strong>, los consultores y analistas de calidad registran entradas de tipo <code>Avance</code>, <code>Calidad</code>, <code>Incidencia</code> o <code>Entrega</code>, indicando horas dedicadas, nuevo porcentaje de avance y estado.</li>
        </ol>

        <h3 style="font-size:15px;">3.5 Usuarios, Seguridad y Bitácora de Auditoría</h3>
        <ol>
            <li>En <strong>Usuarios y Roles</strong> (exclusivo de Gerencia), se crean cuentas, se asignan roles, se actualizan contraseñas y se activan o desactivan usuarios.</li>
            <li>En <strong>Bitácora Auditoría</strong> se consultan los eventos registrados automáticamente (inicios de sesión, intentos fallidos, accesos denegados, creaciones, ediciones y cambios de estado), filtrables por módulo o texto.</li>
        </ol>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <h2 class="card-title">4. Recomendaciones de Uso y Respaldo</h2>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <li>Cierre su sesión mediante el botón <strong>Cerrar Sesión</strong> al finalizar su jornada en estaciones compartidas.</li>
            <li>Utilice contraseñas robustas y actualícelas periódicamente desde el módulo de Usuarios (Gerencia).</li>
            <li>Realice respaldos periódicos de la base de datos <code>zoroindex_ops</code> desde phpMyAdmin o mediante <code>mysqldump</code>.</li>
        </ul>
    </div>
</section>

<?php render_footer(); ?>
