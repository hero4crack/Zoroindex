(function () {
    'use strict';

    const STORAGE_KEY = 'zoroindex_demo_v1';

    const ROLE_PERMS = {
        gerencia: ['*'],
        sup_atencion: ['dashboard', 'presolicitudes.view', 'presolicitudes.create', 'presolicitudes.edit', 'clientes.view', 'clientes.manage', 'cotizaciones.view', 'proyectos.view'],
        sup_proyectos: ['dashboard', 'presolicitudes.view', 'clientes.view', 'cotizaciones.view', 'cotizaciones.manage', 'cotizaciones.approve', 'tarifario.view', 'tarifario.manage', 'proyectos.view', 'proyectos.manage', 'proyectos.track'],
        operador: ['dashboard', 'presolicitudes.view', 'presolicitudes.create', 'presolicitudes.edit', 'clientes.view', 'clientes.manage'],
        consultor: ['dashboard', 'proyectos.view', 'proyectos.track'],
        calidad: ['dashboard', 'presolicitudes.view', 'cotizaciones.view', 'proyectos.view', 'proyectos.qa', 'auditoria.view']
    };

    function seedData() {
        return {
            usuarios: [
                { id: 1, usuario: 'hector', nombre: 'Hector', correo: 'hector@zoroindex.com', rol_codigo: 'gerencia', rol_nombre: 'Gerencia de Operaciones y TI', pass: '123456', activo: true },
                { id: 2, usuario: 'jesus', nombre: 'Jesus', correo: 'jesus@zoroindex.com', rol_codigo: 'sup_atencion', rol_nombre: 'Supervisión de Atención al Cliente', pass: '123456', activo: true },
                { id: 3, usuario: 'jose', nombre: 'Jose', correo: 'jose@zoroindex.com', rol_codigo: 'sup_proyectos', rol_nombre: 'Supervisión de Proyectos y Consultoría', pass: '123456', activo: true },
                { id: 4, usuario: 'osmary', nombre: 'Osmary', correo: 'osmary@zoroindex.com', rol_codigo: 'operador', rol_nombre: 'Operador de Atención al Cliente', pass: '123456', activo: true },
                { id: 5, usuario: 'roy', nombre: 'Roy', correo: 'roy@zoroindex.com', rol_codigo: 'consultor', rol_nombre: 'Consultor Técnico / Desarrollador', pass: '123456', activo: true },
                { id: 6, usuario: 'jhon', nombre: 'Jhon', correo: 'jhon@zoroindex.com', rol_codigo: 'calidad', rol_nombre: 'Analista de Calidad y Soporte Operativo', pass: '123456', activo: true }
            ],
            clientes: [
                { id: 1, razon_social: 'Inversiones TecnoSur C.A.', rif: 'J-40129834-2', contacto: 'Roberto Silva', correo: 'rsilva@tecnosur.com', telefono: '0212-9914450', sector: 'Comercio y Tecnología', activo: true },
                { id: 2, razon_social: 'Distribuidora Logística Andina S.A.', rif: 'J-30981245-8', contacto: 'Elena Morales', correo: 'emorales@logisticaandina.com', telefono: '0241-8223310', sector: 'Logística y Transporte', activo: true },
                { id: 3, razon_social: 'Clínica Integral SaludPlus C.A.', rif: 'J-41002391-5', contacto: 'Dr. Javier Márquez', correo: 'jmarquez@saludplus.com', telefono: '0212-7628890', sector: 'Salud', activo: true }
            ],
            tarifario: [
                { id: 1, codigo: 'SRV-001', nombre: 'Consultoría Técnica y Levantamiento de Procesos', unidad: 'Hora', tarifa: 45.00, activo: true },
                { id: 2, codigo: 'SRV-002', nombre: 'Desarrollo de Aplicación Web a Medida', unidad: 'Proyecto', tarifa: 1200.00, activo: true },
                { id: 3, codigo: 'SRV-003', nombre: 'Configuración y Optimización de Base de Datos MariaDB', unidad: 'Servicio', tarifa: 380.00, activo: true },
                { id: 4, codigo: 'SRV-004', nombre: 'Soporte Operativo y Mantenimiento Mensual', unidad: 'Mensual', tarifa: 250.00, activo: true },
                { id: 5, codigo: 'SRV-005', nombre: 'Auditoría de Calidad y Seguridad de Software', unidad: 'Servicio', tarifa: 420.00, activo: true }
            ],
            presolicitudes: [
                { id: 1, codigo: 'PRE-2026-0001', cliente_id: 1, asignado: 'Osmary', canal: 'Web', tipo_servicio: 'Desarrollo de Aplicación Web a Medida', prioridad: 'Alta', estado: 'Cotizada', descripcion: 'Requerimiento de plataforma web interna para control de inventario y órdenes de despacho en 3 sucursales.', fecha: '25/10/2026 09:15' },
                { id: 2, codigo: 'PRE-2026-0002', cliente_id: 2, asignado: 'Osmary', canal: 'Correo', tipo_servicio: 'Configuración y Optimización de Base de Datos MariaDB', prioridad: 'Media', estado: 'En Revision', descripcion: 'Migración de hojas de cálculo de flota hacia base de datos centralizada con respaldos diarios.', fecha: '27/10/2026 14:20' },
                { id: 3, codigo: 'PRE-2026-0003', cliente_id: 3, asignado: 'Sin asignar', canal: 'Telefono', tipo_servicio: 'Auditoría de Calidad y Seguridad de Software', prioridad: 'Urgente', estado: 'Recibida', descripcion: 'Revisión de permisos de usuarios y bitácoras de acceso en sistema de historias clínicas.', fecha: '28/10/2026 11:05' }
            ],
            cotizaciones: [
                {
                    id: 1, codigo: 'COT-2026-0001', presolicitud_id: 1, cliente_id: 1, elaborador: 'Jose', aprobador: 'Hector',
                    fecha: '2026-10-25', vigencia: 15, iva_pct: 16, subtotal: 1650, iva_monto: 264, total: 1914, estado: 'Aprobada',
                    condiciones: '50% al inicio del proyecto y 50% contra entrega y validación operativa.',
                    items: [
                        { descripcion: 'Desarrollo de Aplicación Web a Medida (Módulo Inventario y Despachos)', cantidad: 1, precio: 1200 },
                        { descripcion: 'Consultoría Técnica y Levantamiento de Procesos (10 horas)', cantidad: 10, precio: 45 }
                    ]
                }
            ],
            proyectos: [
                {
                    id: 1, codigo: 'PRY-2026-0001', cotizacion_id: 1, cliente_id: 1, supervisor: 'Jose', consultor_id: 5, consultor: 'Roy',
                    nombre: 'Sistema Web de Inventario y Despachos - TecnoSur',
                    descripcion: 'Implementación de plataforma web centralizada con MariaDB y control de acceso por roles.',
                    fecha_inicio: '2026-10-28', fecha_entrega: '2026-11-28', progreso: 65, estado: 'En Ejecucion',
                    seguimientos: [
                        { fecha: '02/11/2026 10:30', autor: 'Roy', rol: 'Consultor Técnico', tipo: 'Avance', horas: 14.5, pct: 40, comentario: 'Modelo de base de datos MariaDB implementado y módulo de autenticación RBAC operativo.' },
                        { fecha: '12/11/2026 16:15', autor: 'Roy', rol: 'Consultor Técnico', tipo: 'Avance', horas: 12.0, pct: 65, comentario: 'Módulo de órdenes de despacho completado. Iniciando pruebas de concurrencia.' },
                        { fecha: '15/11/2026 09:40', autor: 'Jhon', rol: 'Analista de Calidad', tipo: 'Calidad', horas: 3.0, pct: 65, comentario: 'Verificación de cumplimiento de políticas de seguridad y validación de formularios en Chrome y Edge.' }
                    ]
                }
            ],
            auditoria: [
                { fecha: '25/10/2026 09:00', usuario: 'Hector', rol: 'gerencia', modulo: 'Sistema', accion: 'Inicialización', registro: '1', detalle: 'Carga inicial de estructura operativa Zoroindex' },
                { fecha: '25/10/2026 15:10', usuario: 'Jose', rol: 'sup_proyectos', modulo: 'Cotizaciones', accion: 'Aprobación', registro: 'COT-2026-0001', detalle: 'Cotización aprobada y vinculada al proyecto PRY-2026-0001' }
            ]
        };
    }

    function loadDB() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) {}
        const seeded = seedData();
        saveDB(seeded);
        return seeded;
    }

    function saveDB(db) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(db));
    }

    let db = loadDB();
    let currentUser = null;
    let currentView = 'login';
    let viewParam = null;
    let flashMsg = null;

    function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
    }

    function money(n) {
        return '$ ' + Number(n || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function nowStr() {
        const d = new Date();
        return d.toLocaleDateString('es-VE') + ' ' + d.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
    }

    function badgeClass(status) {
        const map = {
            'Recibida': 'badge-info', 'En Revision': 'badge-warning', 'Cotizada': 'badge-primary',
            'Borrador': 'badge-neutral', 'Enviada': 'badge-info', 'Aprobada': 'badge-success',
            'Rechazada': 'badge-danger', 'Cerrada': 'badge-neutral', 'Planificado': 'badge-neutral',
            'En Ejecucion': 'badge-primary', 'En Pruebas': 'badge-warning', 'Entregado': 'badge-success',
            'Pausado': 'badge-danger', 'Baja': 'badge-neutral', 'Media': 'badge-info',
            'Alta': 'badge-warning', 'Urgente': 'badge-danger', 'Avance': 'badge-primary',
            'Calidad': 'badge-success', 'Incidencia': 'badge-danger', 'Entrega': 'badge-success'
        };
        return map[status] || 'badge-neutral';
    }

    function can(perm) {
        if (!currentUser) return false;
        const p = ROLE_PERMS[currentUser.rol_codigo] || [];
        return p.includes('*') || p.includes(perm);
    }

    function addAudit(modulo, accion, registro, detalle, actor) {
        const u = actor || currentUser || { nombre: 'Portal Público', rol_codigo: 'externo' };
        db.auditoria.unshift({
            fecha: nowStr(),
            usuario: u.nombre,
            rol: u.rol_codigo,
            modulo,
            accion,
            registro: String(registro || '—'),
            detalle
        });
        saveDB(db);
    }

    function nextCode(arr, prefix) {
        const num = arr.length + 1;
        return `${prefix}-2026-${String(num).padStart(4, '0')}`;
    }

    function setFlash(type, text) {
        flashMsg = { type, text };
    }

    function go(view, param) {
        currentView = view;
        viewParam = param || null;
        render();
        window.scrollTo(0, 0);
    }

    function demoBanner() {
        return `
        <div class="demo-banner no-print">
            <span><strong>Zoroindex Ops</strong> &mdash; Vista Interactiva (GitHub Pages). Los cambios se guardan en tu navegador.</span>
            <div style="display:flex;gap:8px;">
                <button type="button" id="btnResetDemo">Reiniciar Datos</button>
            </div>
        </div>`;
    }

    function renderLogin() {
        return `
        ${demoBanner()}
        <div class="auth-page">
            <div class="auth-card">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                    <div class="brand-logo">Z</div>
                    <div>
                        <h1 style="margin:0;font-size:20px;">Zoroindex</h1>
                        <div class="text-muted" style="font-size:12px;">Departamento de Operaciones / TI</div>
                    </div>
                </div>
                <h2 style="margin:0 0 6px;font-size:17px;">Inicio de Sesión</h2>
                <p class="text-muted" style="margin:0 0 16px;font-size:13px;">Plataforma de Gestión de Pre-Solicitudes, Cotizaciones y Proyectos</p>
                ${flashMsg ? `<div class="alert alert-${esc(flashMsg.type)}">${esc(flashMsg.text)}</div>` : ''}
                <form id="loginForm">
                    <div class="form-group">
                        <label for="loginUser">Usuario o Correo</label>
                        <input type="text" id="loginUser" value="hector" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPass">Contraseña</label>
                        <input type="password" id="loginPass" value="123456" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block mt-2">Ingresar al Sistema</button>
                </form>
                <div style="margin-top:14px;font-size:12px;color:var(--text-muted);">
                    <strong>Usuarios (clave <code>123456</code>):</strong>
                    <code>hector</code>, <code>jesus</code>, <code>jose</code>, <code>osmary</code>, <code>roy</code>, <code>jhon</code>
                </div>
                <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;font-size:12.5px;">
                    <span class="text-muted">¿Es cliente externo?</span>
                    <a href="#" id="goPortal" style="font-weight:600;">Ir al Portal de Clientes &rarr;</a>
                </div>
            </div>
        </div>`;
    }

    function renderPortal() {
        return `
        ${demoBanner()}
        <div class="portal-shell">
            <header class="portal-header">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="brand-logo">Z</div>
                    <div>
                        <div style="font-weight:700;font-size:17px;">Zoroindex &mdash; Portal de Atención al Cliente</div>
                        <div style="font-size:12px;color:#94a3b8;">Departamento de Operaciones / Tecnología de la Información</div>
                    </div>
                </div>
                <a href="#" id="goLogin" class="btn btn-outline btn-sm">Acceso Personal Interno</a>
            </header>
            <main class="portal-main">
                ${flashMsg ? `<div class="alert alert-${esc(flashMsg.type)}">${esc(flashMsg.text)}</div>` : ''}
                <div class="grid-2">
                    <section class="card">
                        <div class="card-header"><h2 class="card-title">Nueva Pre-Solicitud de Servicio</h2></div>
                        <div class="card-body">
                            <form id="portalCreateForm">
                                <div class="form-grid">
                                    <div class="form-group"><label>Empresa / Razón Social *</label><input type="text" name="razon" required></div>
                                    <div class="form-group"><label>RIF o Cédula *</label><input type="text" name="rif" placeholder="J-12345678-9" required></div>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group"><label>Contacto *</label><input type="text" name="contacto" required></div>
                                    <div class="form-group"><label>Correo *</label><input type="email" name="correo" required></div>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group"><label>Teléfono *</label><input type="text" name="telefono" required></div>
                                    <div class="form-group"><label>Prioridad</label>
                                        <select name="prioridad"><option>Baja</option><option selected>Media</option><option>Alta</option><option>Urgente</option></select>
                                    </div>
                                </div>
                                <div class="form-group"><label>Tipo de Servicio *</label>
                                    <select name="servicio" required>
                                        ${db.tarifario.map(s => `<option value="${esc(s.nombre)}">${esc(s.nombre)}</option>`).join('')}
                                    </select>
                                </div>
                                <div class="form-group"><label>Descripción del Requerimiento *</label><textarea name="descripcion" rows="3" required></textarea></div>
                                <button type="submit" class="btn btn-primary btn-block">Enviar Pre-Solicitud</button>
                            </form>
                        </div>
                    </section>
                    <section class="card">
                        <div class="card-header"><h2 class="card-title">Consultar Estado de Solicitud</h2></div>
                        <div class="card-body">
                            <p class="text-muted" style="margin-top:0;">Ejemplo: código <code>PRE-2026-0001</code> y RIF <code>J-40129834-2</code>.</p>
                            <form id="portalLookupForm">
                                <div class="form-group"><label>Código de Pre-Solicitud</label><input type="text" name="codigo" placeholder="PRE-2026-0001" required></div>
                                <div class="form-group"><label>RIF / Cédula o Correo</label><input type="text" name="verif" placeholder="J-40129834-2" required></div>
                                <button type="submit" class="btn btn-secondary btn-block">Consultar Estado</button>
                            </form>
                            <div id="portalLookupResult"></div>
                        </div>
                    </section>
                </div>
            </main>
        </div>`;
    }

    function shell(title, activeNav, contentHtml) {
        const navItems = [
            { id: 'dashboard', label: 'Panel Operativo', perm: 'dashboard' },
            { id: 'presolicitudes', label: 'Pre-Solicitudes', perm: 'presolicitudes.view' },
            { id: 'cotizaciones', label: 'Cotizaciones', perm: 'cotizaciones.view' },
            { id: 'proyectos', label: 'Proyectos', perm: 'proyectos.view' },
            { id: 'clientes', label: 'Clientes', perm: 'clientes.view' },
            { id: 'tarifario', label: 'Tarifario', perm: 'tarifario.view' },
            { id: 'usuarios', label: 'Usuarios y Roles', perm: 'usuarios.manage' },
            { id: 'auditoria', label: 'Bitácora Auditoría', perm: 'auditoria.view' }
        ];

        return `
        ${demoBanner()}
        <div class="app-shell">
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-brand">
                    <div class="brand-logo">Z</div>
                    <div>
                        <div class="brand-title">Zoroindex</div>
                        <div class="brand-sub">Operaciones &amp; TI</div>
                    </div>
                </div>
                <nav class="sidebar-nav">
                    ${navItems.filter(i => can(i.perm)).map(i => `
                        <a href="#" data-nav="${i.id}" class="nav-link ${activeNav === i.id ? 'active' : ''}">
                            <span class="nav-dot"></span><span>${esc(i.label)}</span>
                        </a>`).join('')}
                    <div class="nav-divider"></div>
                    <a href="#" data-nav="manual" class="nav-link ${activeNav === 'manual' ? 'active' : ''}">
                        <span class="nav-dot"></span><span>Manual de Usuario</span>
                    </a>
                    <a href="#" id="goPortalFromShell" class="nav-link">
                        <span class="nav-dot"></span><span>Portal de Clientes &#8599;</span>
                    </a>
                </nav>
                <div class="sidebar-footer">
                    <div class="user-card">
                        <div class="user-avatar">${esc(currentUser.nombre.charAt(0).toUpperCase())}</div>
                        <div class="user-meta">
                            <div class="user-name">${esc(currentUser.nombre)}</div>
                            <div class="user-role">${esc(currentUser.rol_nombre)}</div>
                        </div>
                    </div>
                    <button type="button" id="btnLogout" class="btn btn-outline btn-sm btn-block">Cerrar Sesión</button>
                </div>
            </aside>
            <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
            <div class="main-wrapper">
                <header class="topbar">
                    <div class="topbar-left">
                        <button type="button" class="menu-toggle" id="menuToggle"><span></span><span></span><span></span></button>
                        <div>
                            <h1 class="page-title">${esc(title)}</h1>
                            <div class="page-subtitle">Departamento de Operaciones / Tecnología de la Información</div>
                        </div>
                    </div>
                    <div class="topbar-right"><span class="topbar-pill">${esc(currentUser.rol_nombre)}</span></div>
                </header>
                <main class="main-content">
                    ${flashMsg ? `<div class="alert alert-${esc(flashMsg.type)}">${esc(flashMsg.text)}</div>` : ''}
                    ${contentHtml}
                </main>
                <footer class="app-footer">
                    <span>&copy; 2026 Zoroindex &mdash; Departamento de Operaciones / TI</span>
                    <span>Contacto: cmendoza@zoroindex.com &middot; 0412-8850889</span>
                </footer>
            </div>
        </div>`;
    }

    function clientName(id) {
        const c = db.clientes.find(x => x.id === Number(id));
        return c ? c.razon_social : '—';
    }

    function renderDashboard() {
        const pend = db.presolicitudes.filter(p => ['Recibida', 'En Revision'].includes(p.estado)).length;
        const cotAct = db.cotizaciones.filter(c => ['Borrador', 'Enviada'].includes(c.estado)).length;
        const pryAct = db.proyectos.filter(p => ['Planificado', 'En Ejecucion', 'En Pruebas'].includes(p.estado)).length;
        const monto = db.cotizaciones.filter(c => c.estado === 'Aprobada').reduce((a, b) => a + Number(b.total), 0);

        const body = `
        <div class="grid-kpi">
            <div class="kpi-card"><div class="kpi-label">Pre-Solicitudes Pendientes</div><div class="kpi-value">${pend}</div><div class="kpi-hint">Recibidas o en revisión</div></div>
            <div class="kpi-card"><div class="kpi-label">Cotizaciones en Gestión</div><div class="kpi-value">${cotAct}</div><div class="kpi-hint">En borrador o enviadas</div></div>
            <div class="kpi-card"><div class="kpi-label">Proyectos Activos</div><div class="kpi-value">${pryAct}</div><div class="kpi-hint">Planificados, ejecución o QA</div></div>
            <div class="kpi-card"><div class="kpi-label">Cotizaciones Aprobadas</div><div class="kpi-value">${money(monto)}</div><div class="kpi-hint">Acumulado aprobado</div></div>
        </div>
        <div class="grid-2">
            ${can('presolicitudes.view') ? `
            <section class="card">
                <div class="card-header"><h2 class="card-title">Últimas Pre-Solicitudes</h2><button class="btn btn-secondary btn-sm" data-nav="presolicitudes">Ver todas</button></div>
                <div class="table-responsive"><table><thead><tr><th>Código</th><th>Cliente</th><th>Prioridad</th><th>Estado</th></tr></thead><tbody>
                ${db.presolicitudes.slice(0, 5).map(p => `<tr>
                    <td><strong>${esc(p.codigo)}</strong></td><td>${esc(clientName(p.cliente_id))}</td>
                    <td><span class="badge ${badgeClass(p.prioridad)}">${esc(p.prioridad)}</span></td>
                    <td><span class="badge ${badgeClass(p.estado)}">${esc(p.estado)}</span></td>
                </tr>`).join('')}
                </tbody></table></div>
            </section>` : ''}
            ${can('proyectos.view') ? `
            <section class="card">
                <div class="card-header"><h2 class="card-title">Proyectos en Seguimiento</h2><button class="btn btn-secondary btn-sm" data-nav="proyectos">Ver todos</button></div>
                <div class="table-responsive"><table><thead><tr><th>Código</th><th>Proyecto</th><th>Avance</th><th>Estado</th></tr></thead><tbody>
                ${db.proyectos.filter(pr => currentUser.rol_codigo !== 'consultor' || pr.consultor_id === currentUser.id).map(pr => `<tr>
                    <td><strong>${esc(pr.codigo)}</strong></td><td>${esc(pr.nombre)}</td>
                    <td style="min-width:100px;"><div style="font-size:12px;font-weight:600;">${pr.progreso}%</div><div class="progress-bar"><div class="progress-fill" style="width:${pr.progreso}%"></div></div></td>
                    <td><span class="badge ${badgeClass(pr.estado)}">${esc(pr.estado)}</span></td>
                </tr>`).join('')}
                </tbody></table></div>
            </section>` : ''}
        </div>`;
        return shell('Panel Operativo', 'dashboard', body);
    }

    function renderPresolicitudes() {
        const body = `
        ${can('presolicitudes.create') ? `
        <section class="card">
            <div class="card-header"><h2 class="card-title">+ Nueva Pre-Solicitud</h2></div>
            <div class="card-body">
                <form id="newPresolForm" class="form-grid">
                    <div class="form-group"><label>Cliente *</label>
                        <select name="cliente_id">${db.clientes.map(c => `<option value="${c.id}">${esc(c.razon_social)}</option>`).join('')}</select>
                    </div>
                    <div class="form-group"><label>Servicio *</label>
                        <select name="servicio">${db.tarifario.map(s => `<option value="${esc(s.nombre)}">${esc(s.nombre)}</option>`).join('')}</select>
                    </div>
                    <div class="form-group"><label>Prioridad</label>
                        <select name="prioridad"><option>Baja</option><option selected>Media</option><option>Alta</option><option>Urgente</option></select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Descripción *</label><input type="text" name="descripcion" required placeholder="Detalle del requerimiento..."></div>
                    <div><button type="submit" class="btn btn-primary">Registrar Pre-Solicitud</button></div>
                </form>
            </div>
        </section>` : ''}
        <section class="card">
            <div class="card-header"><h2 class="card-title">Listado de Pre-Solicitudes</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Código</th><th>Cliente</th><th>Servicio</th><th>Canal</th><th>Prioridad</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                ${db.presolicitudes.map(p => `<tr>
                    <td><strong>${esc(p.codigo)}</strong></td>
                    <td>${esc(clientName(p.cliente_id))}</td>
                    <td>${esc(p.tipo_servicio)}</td>
                    <td>${esc(p.canal)}</td>
                    <td><span class="badge ${badgeClass(p.prioridad)}">${esc(p.prioridad)}</span></td>
                    <td><span class="badge ${badgeClass(p.estado)}">${esc(p.estado)}</span></td>
                    <td>${can('cotizaciones.manage') ? `<button class="btn btn-primary btn-sm" data-gen-cot="${p.id}">+ Cotizar</button>` : '—'}</td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
        return shell('Pre-Solicitudes de Servicio', 'presolicitudes', body);
    }

    function renderCotizaciones() {
        const body = `
        ${can('cotizaciones.manage') ? `
        <section class="card">
            <div class="card-header"><h2 class="card-title">+ Nueva Cotización</h2></div>
            <div class="card-body">
                <form id="newCotForm" class="form-grid">
                    <div class="form-group"><label>Cliente *</label>
                        <select name="cliente_id">${db.clientes.map(c => `<option value="${c.id}" ${viewParam && viewParam.cliente_id === c.id ? 'selected' : ''}>${esc(c.razon_social)}</option>`).join('')}</select>
                    </div>
                    <div class="form-group"><label>Servicio del Tarifario *</label>
                        <select name="servicio_id">${db.tarifario.map(s => `<option value="${s.id}">${esc(s.nombre)} (${money(s.tarifa)})</option>`).join('')}</select>
                    </div>
                    <div class="form-group"><label>Cantidad *</label><input type="number" name="cantidad" min="1" value="1" required></div>
                    <div><button type="submit" class="btn btn-primary">Emitir Cotización</button></div>
                </form>
            </div>
        </section>` : ''}
        <section class="card">
            <div class="card-header"><h2 class="card-title">Cotizaciones Registradas</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Código</th><th>Cliente</th><th>Fecha</th><th>Total (con IVA 16%)</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                ${db.cotizaciones.map(c => `<tr>
                    <td><strong>${esc(c.codigo)}</strong></td>
                    <td>${esc(clientName(c.cliente_id))}</td>
                    <td>${esc(c.fecha)}</td>
                    <td><strong>${money(c.total)}</strong></td>
                    <td><span class="badge ${badgeClass(c.estado)}">${esc(c.estado)}</span></td>
                    <td class="flex-gap">
                        ${can('cotizaciones.approve') && ['Borrador', 'Enviada'].includes(c.estado) ? `<button class="btn btn-success btn-sm" data-approve-cot="${c.id}">Aprobar</button>` : ''}
                        ${can('proyectos.manage') && c.estado === 'Aprobada' ? `<button class="btn btn-primary btn-sm" data-gen-pry="${c.id}">+ Asignar Proyecto</button>` : ''}
                    </td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
        return shell('Cotizaciones', 'cotizaciones', body);
    }

    function renderProyectos() {
        const list = db.proyectos.filter(pr => currentUser.rol_codigo !== 'consultor' || pr.consultor_id === currentUser.id);
        const body = `
        <section class="card">
            <div class="card-header"><h2 class="card-title">Proyectos y Seguimiento Técnico</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Código</th><th>Proyecto</th><th>Cliente</th><th>Consultor</th><th>Progreso</th><th>Estado</th><th>Acción</th></tr></thead>
                <tbody>
                ${list.map(pr => `<tr>
                    <td><strong>${esc(pr.codigo)}</strong></td>
                    <td>${esc(pr.nombre)}</td>
                    <td>${esc(clientName(pr.cliente_id))}</td>
                    <td>${esc(pr.consultor)}</td>
                    <td style="min-width:110px;"><div style="font-size:12px;font-weight:600;">${pr.progreso}%</div><div class="progress-bar"><div class="progress-fill" style="width:${pr.progreso}%"></div></div></td>
                    <td><span class="badge ${badgeClass(pr.estado)}">${esc(pr.estado)}</span></td>
                    <td><button class="btn btn-secondary btn-sm" data-view-pry="${pr.id}">Seguimiento</button></td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>
        ${viewParam && viewParam.pryId ? renderProyectoDetalle(viewParam.pryId) : ''}`;
        return shell('Proyectos', 'proyectos', body);
    }

    function renderProyectoDetalle(pryId) {
        const pr = db.proyectos.find(p => p.id === pryId);
        if (!pr) return '';
        const canTrack = can('proyectos.track') || can('proyectos.qa');
        return `
        <div class="grid-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">${esc(pr.codigo)} &mdash; ${esc(pr.nombre)}</h2></div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> ${esc(clientName(pr.cliente_id))}</p>
                    <p><strong>Consultor:</strong> ${esc(pr.consultor)} &middot; <strong>Supervisor:</strong> ${esc(pr.supervisor)}</p>
                    <p><strong>Progreso:</strong> ${pr.progreso}% &middot; <strong>Estado:</strong> <span class="badge ${badgeClass(pr.estado)}">${esc(pr.estado)}</span></p>
                </div>
            </section>
            ${canTrack ? `
            <section class="card">
                <div class="card-header"><h2 class="card-title">Registrar Avance / QA</h2></div>
                <div class="card-body">
                    <form id="trackForm" data-pry-id="${pr.id}">
                        <div class="form-grid">
                            <div class="form-group"><label>Tipo</label><select name="tipo"><option>Avance</option><option>Calidad</option><option>Incidencia</option><option>Entrega</option></select></div>
                            <div class="form-group"><label>Nuevo Progreso (%)</label><input type="number" name="pct" min="0" max="100" value="${pr.progreso}"></div>
                        </div>
                        <div class="form-group"><label>Comentario *</label><input type="text" name="comentario" required></div>
                        <button type="submit" class="btn btn-primary btn-block">Guardar Seguimiento</button>
                    </form>
                </div>
            </section>` : ''}
        </div>
        <section class="card">
            <div class="card-header"><h2 class="card-title">Historial de Seguimiento</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Responsable</th><th>Avance</th><th>Detalle</th></tr></thead>
                <tbody>
                ${pr.seguimientos.map(s => `<tr>
                    <td>${esc(s.fecha)}</td>
                    <td><span class="badge ${badgeClass(s.tipo)}">${esc(s.tipo)}</span></td>
                    <td>${esc(s.autor)}</td>
                    <td>${s.pct}%</td>
                    <td>${esc(s.comentario)}</td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
    }

    function renderClientes() {
        const body = `
        <section class="card">
            <div class="card-header"><h2 class="card-title">Directorio de Clientes</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Razón Social</th><th>RIF / Cédula</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Sector</th></tr></thead>
                <tbody>
                ${db.clientes.map(c => `<tr>
                    <td><strong>${esc(c.razon_social)}</strong></td><td>${esc(c.rif)}</td><td>${esc(c.contacto)}</td>
                    <td>${esc(c.correo)}</td><td>${esc(c.telefono)}</td><td>${esc(c.sector || '—')}</td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
        return shell('Clientes', 'clientes', body);
    }

    function renderTarifario() {
        const body = `
        <section class="card">
            <div class="card-header"><h2 class="card-title">Tarifario de Servicios</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Código</th><th>Servicio</th><th>Unidad</th><th class="text-right">Tarifa Base</th></tr></thead>
                <tbody>
                ${db.tarifario.map(s => `<tr>
                    <td><strong>${esc(s.codigo)}</strong></td><td>${esc(s.nombre)}</td><td>${esc(s.unidad)}</td><td class="text-right"><strong>${money(s.tarifa)}</strong></td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
        return shell('Tarifario de Servicios', 'tarifario', body);
    }

    function renderUsuarios() {
        const body = `
        <section class="card">
            <div class="card-header"><h2 class="card-title">Usuarios y Roles (RBAC)</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th></tr></thead>
                <tbody>
                ${db.usuarios.map(u => `<tr>
                    <td><strong>${esc(u.nombre)}</strong></td><td><code>${esc(u.usuario)}</code></td><td>${esc(u.correo)}</td>
                    <td>${esc(u.rol_nombre)}</td><td><span class="badge badge-success">Activo</span></td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
        return shell('Usuarios y Roles', 'usuarios', body);
    }

    function renderAuditoria() {
        const body = `
        <section class="card">
            <div class="card-header"><h2 class="card-title">Bitácora de Auditoría</h2></div>
            <div class="table-responsive"><table>
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Rol</th><th>Módulo</th><th>Acción</th><th>Registro</th><th>Detalle</th></tr></thead>
                <tbody>
                ${db.auditoria.map(a => `<tr>
                    <td>${esc(a.fecha)}</td><td>${esc(a.usuario)}</td><td><code>${esc(a.rol)}</code></td>
                    <td>${esc(a.modulo)}</td><td><strong>${esc(a.accion)}</strong></td><td><code>${esc(a.registro)}</code></td><td>${esc(a.detalle)}</td>
                </tr>`).join('')}
                </tbody>
            </table></div>
        </section>`;
        return shell('Bitácora de Auditoría', 'auditoria', body);
    }

    function renderManual() {
        const body = `
        <section class="card">
            <div class="card-header"><h2 class="card-title">Manual de Usuario &mdash; Zoroindex Ops</h2><button onclick="window.print()" class="btn btn-secondary btn-sm no-print">Imprimir</button></div>
            <div class="card-body">
                <h3 style="margin-top:0;">1. Portal Público de Clientes</h3>
                <p>Permite a clientes externos registrar pre-solicitudes de servicio sin iniciar sesión y consultar el estado con su código y RIF/correo.</p>
                <h3>2. Flujo Operativo</h3>
                <ol>
                    <li><strong>Atención al Cliente (Osmary / Jesus):</strong> Revisa las pre-solicitudes recibidas y gestiona el directorio de clientes.</li>
                    <li><strong>Cotizaciones y Tarifario (Jose / Hector):</strong> Convierte pre-solicitudes en cotizaciones usando el tarifario oficial con cálculo automático de IVA.</li>
                    <li><strong>Proyectos y Calidad (Roy / Jhon):</strong> Las cotizaciones aprobadas pasan a proyectos asignados donde se registran avances técnicos y revisiones de calidad (QA).</li>
                    <li><strong>Gerencia y Auditoría (Hector):</strong> Administra usuarios, roles y consulta la bitácora completa de trazabilidad.</li>
                </ol>
            </div>
        </section>`;
        return shell('Manual de Usuario', 'manual', body);
    }

    function render() {
        const root = document.getElementById('appRoot');
        if (!currentUser) {
            root.innerHTML = currentView === 'portal' ? renderPortal() : renderLogin();
        } else {
            const views = {
                dashboard: renderDashboard,
                presolicitudes: renderPresolicitudes,
                cotizaciones: renderCotizaciones,
                proyectos: renderProyectos,
                clientes: renderClientes,
                tarifario: renderTarifario,
                usuarios: renderUsuarios,
                auditoria: renderAuditoria,
                manual: renderManual
            };
            root.innerHTML = (views[currentView] || renderDashboard)();
        }
        flashMsg = null;
        bindEvents();
    }

    function bindEvents() {
        const resetBtn = document.getElementById('btnResetDemo');
        if (resetBtn) {
            resetBtn.onclick = () => {
                db = seedData();
                saveDB(db);
                setFlash('info', 'Datos de demostración restablecidos.');
                render();
            };
        }

        const goPortal = document.getElementById('goPortal');
        if (goPortal) goPortal.onclick = e => { e.preventDefault(); go('portal'); };

        const goLogin = document.getElementById('goLogin');
        if (goLogin) goLogin.onclick = e => { e.preventDefault(); go('login'); };

        const goPortalShell = document.getElementById('goPortalFromShell');
        if (goPortalShell) goPortalShell.onclick = e => { e.preventDefault(); currentUser = null; go('portal'); };

        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.onsubmit = e => {
                e.preventDefault();
                const u = document.getElementById('loginUser').value.trim().toLowerCase();
                const p = document.getElementById('loginPass').value;
                const found = db.usuarios.find(x => (x.usuario === u || x.correo === u) && x.pass === p);
                if (!found) {
                    setFlash('danger', 'Usuario o contraseña incorrectos.');
                    render();
                    return;
                }
                currentUser = found;
                addAudit('Autenticación', 'Inicio de sesión', found.id, 'Acceso al sistema.', found);
                setFlash('success', `Bienvenido, ${found.nombre}.`);
                go('dashboard');
            };
        }

        const btnLogout = document.getElementById('btnLogout');
        if (btnLogout) {
            btnLogout.onclick = () => {
                addAudit('Autenticación', 'Cierre de sesión', currentUser.id, 'Salida voluntaria.');
                currentUser = null;
                setFlash('info', 'Sesión cerrada.');
                go('login');
            };
        }

        document.querySelectorAll('[data-nav]').forEach(el => {
            el.onclick = e => {
                e.preventDefault();
                go(el.getAttribute('data-nav'));
            };
        });

        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (menuToggle && sidebar && backdrop) {
            menuToggle.onclick = () => { sidebar.classList.toggle('open'); backdrop.classList.toggle('open'); };
            backdrop.onclick = () => { sidebar.classList.remove('open'); backdrop.classList.remove('open'); };
        }

        const portalCreate = document.getElementById('portalCreateForm');
        if (portalCreate) {
            portalCreate.onsubmit = e => {
                e.preventDefault();
                const fd = new FormData(portalCreate);
                const rif = String(fd.get('rif')).trim().toUpperCase();
                let cli = db.clientes.find(c => c.rif.toUpperCase() === rif);
                if (!cli) {
                    cli = {
                        id: db.clientes.length + 1,
                        razon_social: String(fd.get('razon')).trim(),
                        rif,
                        contacto: String(fd.get('contacto')).trim(),
                        correo: String(fd.get('correo')).trim(),
                        telefono: String(fd.get('telefono')).trim(),
                        sector: 'General'
                    };
                    db.clientes.push(cli);
                }
                const codigo = nextCode(db.presolicitudes, 'PRE');
                db.presolicitudes.unshift({
                    id: db.presolicitudes.length + 1,
                    codigo,
                    cliente_id: cli.id,
                    asignado: 'Sin asignar',
                    canal: 'Web',
                    tipo_servicio: String(fd.get('servicio')),
                    prioridad: String(fd.get('prioridad')),
                    estado: 'Recibida',
                    descripcion: String(fd.get('descripcion')).trim(),
                    fecha: nowStr()
                });
                addAudit('Pre-Solicitudes', 'Registro Portal Público', codigo, `Nueva pre-solicitud web de ${cli.razon_social}.`);
                setFlash('success', `Pre-Solicitud registrada exitosamente. Tu código es: ${codigo}`);
                render();
            };
        }

        const portalLookup = document.getElementById('portalLookupForm');
        if (portalLookup) {
            portalLookup.onsubmit = e => {
                e.preventDefault();
                const fd = new FormData(portalLookup);
                const cod = String(fd.get('codigo')).trim().toUpperCase();
                const ver = String(fd.get('verif')).trim().toLowerCase();
                const pre = db.presolicitudes.find(p => p.codigo.toUpperCase() === cod);
                const cli = pre ? db.clientes.find(c => c.id === pre.cliente_id) : null;
                const out = document.getElementById('portalLookupResult');
                if (pre && cli && (cli.rif.toLowerCase() === ver || cli.correo.toLowerCase() === ver)) {
                    const cot = db.cotizaciones.find(c => c.presolicitud_id === pre.id);
                    out.innerHTML = `<div style="margin-top:16px;padding:14px;border:1px solid var(--border);border-radius:8px;background:var(--surface-alt);">
                        <div style="display:flex;justify-content:space-between;"><strong>${esc(pre.codigo)}</strong><span class="badge ${badgeClass(pre.estado)}">${esc(pre.estado)}</span></div>
                        <p class="mt-2 mb-0"><strong>Cliente:</strong> ${esc(cli.razon_social)}</p>
                        <p class="mt-2 mb-0"><strong>Servicio:</strong> ${esc(pre.tipo_servicio)}</p>
                        ${cot ? `<p class="mt-2 mb-0"><strong>Cotización:</strong> ${esc(cot.codigo)} (${esc(cot.estado)}) &mdash; ${money(cot.total)}</p>` : ''}
                    </div>`;
                } else {
                    out.innerHTML = `<div class="alert alert-warning mt-4">No se encontró ninguna pre-solicitud con esos datos.</div>`;
                }
            };
        }

        const newPresol = document.getElementById('newPresolForm');
        if (newPresol) {
            newPresol.onsubmit = e => {
                e.preventDefault();
                const fd = new FormData(newPresol);
                const codigo = nextCode(db.presolicitudes, 'PRE');
                db.presolicitudes.unshift({
                    id: db.presolicitudes.length + 1,
                    codigo,
                    cliente_id: Number(fd.get('cliente_id')),
                    asignado: currentUser.nombre,
                    canal: 'Telefono',
                    tipo_servicio: String(fd.get('servicio')),
                    prioridad: String(fd.get('prioridad')),
                    estado: 'Recibida',
                    descripcion: String(fd.get('descripcion')),
                    fecha: nowStr()
                });
                addAudit('Pre-Solicitudes', 'Creación', codigo, 'Pre-solicitud registrada internamente.');
                setFlash('success', `Pre-solicitud ${codigo} registrada.`);
                render();
            };
        }

        document.querySelectorAll('[data-gen-cot]').forEach(btn => {
            btn.onclick = () => {
                const pre = db.presolicitudes.find(p => p.id === Number(btn.getAttribute('data-gen-cot')));
                go('cotizaciones', pre ? { cliente_id: pre.cliente_id } : null);
            };
        });

        const newCot = document.getElementById('newCotForm');
        if (newCot) {
            newCot.onsubmit = e => {
                e.preventDefault();
                const fd = new FormData(newCot);
                const srv = db.tarifario.find(s => s.id === Number(fd.get('servicio_id')));
                const qty = Math.max(1, Number(fd.get('cantidad')));
                const sub = qty * srv.tarifa;
                const iva = sub * 0.16;
                const tot = sub + iva;
                const codigo = nextCode(db.cotizaciones, 'COT');
                db.cotizaciones.unshift({
                    id: db.cotizaciones.length + 1,
                    codigo,
                    presolicitud_id: null,
                    cliente_id: Number(fd.get('cliente_id')),
                    elaborador: currentUser.nombre,
                    aprobador: null,
                    fecha: new Date().toISOString().slice(0, 10),
                    vigencia: 15,
                    iva_pct: 16,
                    subtotal: sub,
                    iva_monto: iva,
                    total: tot,
                    estado: 'Enviada',
                    items: [{ descripcion: srv.nombre, cantidad: qty, precio: srv.tarifa }]
                });
                addAudit('Cotizaciones', 'Creación', codigo, `Cotización emitida por ${money(tot)}.`);
                setFlash('success', `Cotización ${codigo} creada.`);
                render();
            };
        }

        document.querySelectorAll('[data-approve-cot]').forEach(btn => {
            btn.onclick = () => {
                const cot = db.cotizaciones.find(c => c.id === Number(btn.getAttribute('data-approve-cot')));
                if (cot) {
                    cot.estado = 'Aprobada';
                    cot.aprobador = currentUser.nombre;
                    addAudit('Cotizaciones', 'Aprobación', cot.codigo, 'Cotización aprobada.');
                    setFlash('success', `Cotización ${cot.codigo} aprobada.`);
                    render();
                }
            };
        });

        document.querySelectorAll('[data-gen-pry]').forEach(btn => {
            btn.onclick = () => {
                const cot = db.cotizaciones.find(c => c.id === Number(btn.getAttribute('data-gen-pry')));
                if (cot) {
                    const codigo = nextCode(db.proyectos, 'PRY');
                    db.proyectos.unshift({
                        id: db.proyectos.length + 1,
                        codigo,
                        cotizacion_id: cot.id,
                        cliente_id: cot.cliente_id,
                        supervisor: currentUser.nombre,
                        consultor_id: 5,
                        consultor: 'Roy',
                        nombre: `Proyecto ${clientName(cot.cliente_id)}`,
                        progreso: 10,
                        estado: 'Planificado',
                        seguimientos: []
                    });
                    addAudit('Proyectos', 'Creación', codigo, `Proyecto asignado desde ${cot.codigo}.`);
                    setFlash('success', `Proyecto ${codigo} asignado a Roy.`);
                    go('proyectos');
                }
            };
        });

        document.querySelectorAll('[data-view-pry]').forEach(btn => {
            btn.onclick = () => go('proyectos', { pryId: Number(btn.getAttribute('data-view-pry')) });
        });

        const trackForm = document.getElementById('trackForm');
        if (trackForm) {
            trackForm.onsubmit = e => {
                e.preventDefault();
                const pryId = Number(trackForm.getAttribute('data-pry-id'));
                const pr = db.proyectos.find(p => p.id === pryId);
                const fd = new FormData(trackForm);
                const pct = Math.max(0, Math.min(100, Number(fd.get('pct'))));
                const tipo = String(fd.get('tipo'));
                const comentario = String(fd.get('comentario')).trim();
                pr.progreso = pct;
                if (pct === 100) pr.estado = 'Entregado';
                pr.seguimientos.unshift({
                    fecha: nowStr(),
                    autor: currentUser.nombre,
                    rol: currentUser.rol_nombre,
                    tipo,
                    pct,
                    comentario
                });
                addAudit('Proyectos', 'Seguimiento', pr.codigo, `${tipo} (${pct}%): ${comentario}`);
                setFlash('success', 'Seguimiento registrado.');
                go('proyectos', { pryId });
            };
        }
    }

    render();
})();
