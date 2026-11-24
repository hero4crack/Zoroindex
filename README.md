# Zoroindex Ops — Plataforma de Gestión Operativa, Pre-Solicitudes y Cotizaciones

Sistema web multiusuario desarrollado en **PHP + MariaDB** para el **Departamento de Operaciones / Tecnología de la Información de Zoroindex**. Centraliza el ciclo operativo completo desde la recepción de pre-solicitudes de clientes externos hasta la emisión de cotizaciones, asignación de proyectos y bitácoras de auditoría.

## Módulos Principales

- **Portal Público de Clientes (`portal.php`)**: Registro en línea de pre-solicitudes de servicio y consulta de estado mediante código de seguimiento y RIF/correo.
- **Panel Operativo (`index.php`)**: Indicadores clave (KPIs) en tiempo real y accesos directos según el rol del usuario.
- **Pre-Solicitudes (`presolicitudes.php`)**: Recepción, clasificación por prioridad/canal, asignación a operadores y conversión directa a cotización.
- **Cotizaciones y Tarifario (`cotizaciones.php`, `tarifario.php`)**: Catálogo de servicios con tarifas base, cálculo automático de subtotales e IVA, flujo de aprobación gerencial y formato imprimible.
- **Proyectos y Seguimiento (`proyectos.php`)**: Asignación de supervisores y consultores técnicos, control de avance porcentual y registro de seguimientos técnicos y de calidad (QA).
- **Clientes (`clientes.php`)**: Directorio centralizado de organizaciones y contactos.
- **Usuarios, Roles y Bitácora de Auditoría (`usuarios.php`, `auditoria.php`)**: Control de acceso basado en roles (RBAC) y trazabilidad completa de acciones e inicios de sesión.

## Requisitos de Despliegue

- **Servidor Web**: Apache / Nginx con PHP 7.4 o superior (extensión `pdo_mysql` habilitada).
- **Base de Datos**: MariaDB 10.4+ o MySQL 8.0+ (administrable mediante phpMyAdmin).
- **Navegadores Compatibles**: Google Chrome, Mozilla Firefox, Microsoft Edge y Opera (escritorio y móvil).

## Instalación Rápida (XAMPP / MariaDB en Windows)

1. Copie la carpeta del proyecto en `C:\xampp\htdocs\zoroindex_gestion` (o configure un VirtualHost apuntando al directorio del proyecto).
2. Inicie los servicios **Apache** y **MySQL/MariaDB** en el Panel de Control de XAMPP.
3. Importe el archivo `database/zoroindex_ops.sql` desde **phpMyAdmin** (o mediante línea de comandos):
   ```sql
   SOURCE database/zoroindex_ops.sql;
   ```
4. Si su servidor MariaDB utiliza credenciales distintas a `root` sin contraseña, ajuste `config/database.php` o defina las variables de entorno `ZOROINDEX_DB_HOST`, `ZOROINDEX_DB_NAME`, `ZOROINDEX_DB_USER` y `ZOROINDEX_DB_PASS`.
5. Acceda desde su navegador a:
   - **Sistema interno**: `http://localhost/zoroindex_gestion/login.php`
   - **Portal de clientes**: `http://localhost/zoroindex_gestion/portal.php`

## Usuarios de Prueba Incluidos

Todos los usuarios iniciales utilizan la contraseña: `123456`

| Usuario | Rol | Responsabilidad |
|---|---|---|
| `hector` | Gerencia de Operaciones y TI | Acceso total, aprobaciones, tarifario, usuarios y auditoría |
| `jesus` | Supervisión de Atención al Cliente | Supervisión de pre-solicitudes y operadores |
| `jose` | Supervisión de Proyectos y Consultoría | Cotizaciones, tarifario y asignación de proyectos |
| `osmary` | Operador de Atención al Cliente | Registro de clientes y pre-solicitudes |
| `roy` | Consultor Técnico / Desarrollador | Seguimiento y avances en proyectos asignados |
| `jhon` | Analista de Calidad y Soporte Operativo | Revisión de calidad (QA) y consulta de auditoría |
