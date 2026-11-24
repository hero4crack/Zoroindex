# Manual de Usuario — Zoroindex Ops

**Organización:** Zoroindex  
**Área:** Departamento de Operaciones / Tecnología de la Información  
**Contacto Institucional:** Carlos Eduardo Mendoza (`cmendoza@zoroindex.com` · `0412-8850889`)

---

## 1. Introducción

**Zoroindex Ops** es la plataforma web multiusuario diseñada para centralizar el ciclo operativo de atención al cliente, pre-solicitudes de servicio, elaboración de cotizaciones, asignación y seguimiento técnico de proyectos, y bitácoras de auditoría.

### Puntos de acceso
- **Sistema interno (personal autorizado):** `http://localhost/zoroindex_gestion/login.php`
- **Portal público de clientes:** `http://localhost/zoroindex_gestion/portal.php`

---

## 2. Control de Acceso y Roles (RBAC)

Cada usuario ingresa con su identificador y contraseña, accediendo únicamente a los módulos autorizados para su rol:

| Rol | Usuario ejemplo | Permisos principales |
|---|---|---|
| **Gerencia de Operaciones y TI** | `hector` | Acceso total: panel, pre-solicitudes, clientes, cotizaciones (y aprobación), tarifario, proyectos, usuarios/roles y bitácora de auditoría. |
| **Supervisión de Atención al Cliente** | `jesus` | Gestión y asignación de pre-solicitudes, directorio de clientes, lectura de cotizaciones y proyectos. |
| **Supervisión de Proyectos y Consultoría** | `jose` | Elaboración y aprobación de cotizaciones, gestión de tarifario, asignación y seguimiento de proyectos. |
| **Operador de Atención al Cliente** | `osmary` | Registro de clientes y registro/edición inicial de pre-solicitudes. |
| **Consultor Técnico / Desarrollador** | `roy` | Consulta de proyectos asignados y registro de avances técnicos y horas. |
| **Analista de Calidad y Soporte Operativo** | `jhon` | Lectura de pre-solicitudes, cotizaciones y proyectos; registro de observaciones de calidad (QA) y consulta de bitácora de auditoría. |

> **Contraseña inicial de los usuarios de prueba:** `123456`

---

## 3. Uso de los Módulos

### 3.1 Portal Público de Clientes (`portal.php`)
1. El cliente externo accede sin iniciar sesión.
2. En **Nueva Pre-Solicitud de Servicio**, ingresa los datos de su empresa (Razón Social, RIF/Cédula, contacto, correo, teléfono, sector), selecciona el tipo de servicio, la prioridad y describe su requerimiento.
3. Al enviar el formulario, el sistema genera un código de seguimiento (ejemplo: `PRE-2026-0004`).
4. En **Consultar Estado de Solicitud**, el cliente introduce su código y su RIF/Cédula o correo para ver el estado actual y, si ya fue cotizada, el código, estado y monto total de la cotización.

### 3.2 Inicio de Sesión y Panel Operativo (`login.php` / `index.php`)
1. Ingrese su usuario o correo corporativo y su contraseña.
2. El **Panel Operativo** muestra los indicadores clave (pre-solicitudes pendientes, cotizaciones en gestión, proyectos activos y monto aprobado) junto con listados recientes filtrados según su rol.

### 3.3 Pre-Solicitudes (`presolicitudes.php`)
- **Filtrar:** Busque por código, cliente o servicio, o filtre por estado (`Recibida`, `En Revision`, `Cotizada`, `Aprobada`, `Rechazada`, `Cerrada`) y prioridad (`Baja`, `Media`, `Alta`, `Urgente`).
- **Registrar:** Haga clic en **+ Nueva Pre-Solicitud** para cargar solicitudes recibidas por teléfono, correo o de forma presencial.
- **Detalle y conversión:** Desde **Ver** puede revisar el requerimiento completo y, si posee permisos de cotización, hacer clic en **+ Generar Cotización**.

### 3.4 Clientes (`clientes.php`)
- Permite consultar, buscar, registrar y editar organizaciones clientes.
- El RIF/Cédula es único por cliente para evitar duplicados.

### 3.5 Tarifario de Servicios (`tarifario.php`)
- Administra el catálogo oficial de servicios, su unidad de cobro (`Hora`, `Proyecto`, `Mensual`, `Licencia`, `Servicio`) y tarifa base.
- Los servicios activos aparecen automáticamente disponibles al elaborar cotizaciones.

### 3.6 Cotizaciones (`cotizaciones.php`)
1. Haga clic en **+ Nueva Cotización** (o desde una pre-solicitud).
2. Seleccione el cliente, fecha de emisión, vigencia y porcentaje de IVA.
3. Agregue o elimine ítems: al seleccionar un servicio del tarifario, se rellenan automáticamente la descripción y el precio unitario, recalculando subtotal, IVA y total en tiempo real.
4. En la vista de detalle (`cotizacion_ver.php`):
   - **Imprimir / PDF:** Genera una versión limpia lista para imprimir o guardar en PDF.
   - **Aprobar / Rechazar:** Disponible para Gerencia y Supervisión de Proyectos.
   - **+ Asignar Proyecto:** Disponible una vez que la cotización está en estado `Aprobada`.

### 3.7 Asignación y Seguimiento de Proyectos (`proyectos.php`)
- Permite asignar un supervisor, un consultor técnico, fechas de inicio y entrega estimada, y porcentaje de avance.
- En **Seguimiento** (`proyecto_ver.php`), los consultores y analistas de calidad registran entradas de tipo `Avance`, `Calidad`, `Incidencia` o `Entrega` con horas dedicadas y actualización de progreso/estado.

### 3.8 Usuarios y Roles (`usuarios.php`)
- Exclusivo de **Gerencia de Operaciones y TI**.
- Permite crear usuarios, asignar roles, restablecer contraseñas y habilitar o deshabilitar cuentas sin eliminar su historial.

### 3.9 Bitácora de Auditoría (`auditoria.php`)
- Registra automáticamente inicios y cierres de sesión, intentos fallidos, accesos denegados y operaciones sobre los distintos módulos, indicando usuario, rol, fecha/hora, registro afectado, detalle e IP de origen.

---

## 4. Buenas Prácticas Operativas

- Cerrar sesión al finalizar el uso del sistema en equipos compartidos.
- Mantener actualizados los estados de pre-solicitudes, cotizaciones y avances de proyectos.
- Realizar respaldos periódicos de la base de datos `zoroindex_ops` mediante phpMyAdmin o `mysqldump`.
