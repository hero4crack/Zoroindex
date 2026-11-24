-- ============================================================================
-- ZOROINDEX - Plataforma de Gestión Operativa, Pre-Solicitudes y Cotizaciones
-- Motor: MariaDB / MySQL (utf8mb4)
-- Departamento de Operaciones / Tecnología de la Información
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `zoroindex_ops`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `zoroindex_ops`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `bitacoras_auditoria`;
DROP TABLE IF EXISTS `proyecto_seguimientos`;
DROP TABLE IF EXISTS `proyectos`;
DROP TABLE IF EXISTS `cotizacion_items`;
DROP TABLE IF EXISTS `cotizaciones`;
DROP TABLE IF EXISTS `presolicitudes`;
DROP TABLE IF EXISTS `servicios_tarifas`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `roles`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. ROLES Y USUARIOS (Control de Acceso Basado en Roles - RBAC)
-- ----------------------------------------------------------------------------
CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(40) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rol_id` INT UNSIGNED NOT NULL,
  `nombre_completo` VARCHAR(120) NOT NULL,
  `usuario` VARCHAR(60) NOT NULL,
  `correo` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_acceso` DATETIME DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  UNIQUE KEY `uk_usuarios_correo` (`correo`),
  KEY `idx_usuarios_rol` (`rol_id`),
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. CLIENTES
-- ----------------------------------------------------------------------------
CREATE TABLE `clientes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `razon_social` VARCHAR(150) NOT NULL,
  `documento_rif` VARCHAR(30) NOT NULL,
  `contacto_principal` VARCHAR(120) NOT NULL,
  `correo` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(30) NOT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `sector` VARCHAR(80) DEFAULT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_clientes_rif` (`documento_rif`),
  KEY `idx_clientes_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. TARIFARIO DE SERVICIOS
-- ----------------------------------------------------------------------------
CREATE TABLE `servicios_tarifas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `unidad` ENUM('Hora','Proyecto','Mensual','Licencia','Servicio') NOT NULL DEFAULT 'Servicio',
  `tarifa_base` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_servicios_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. PRE-SOLICITUDES DE SERVICIO
-- ----------------------------------------------------------------------------
CREATE TABLE `presolicitudes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `usuario_asignado_id` INT UNSIGNED DEFAULT NULL,
  `canal` ENUM('Web','Telefono','Correo','Presencial') NOT NULL DEFAULT 'Web',
  `tipo_servicio` VARCHAR(120) NOT NULL,
  `prioridad` ENUM('Baja','Media','Alta','Urgente') NOT NULL DEFAULT 'Media',
  `descripcion` TEXT NOT NULL,
  `estado` ENUM('Recibida','En Revision','Cotizada','Aprobada','Rechazada','Cerrada') NOT NULL DEFAULT 'Recibida',
  `observaciones_internas` TEXT DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_presolicitudes_codigo` (`codigo`),
  KEY `idx_presol_cliente` (`cliente_id`),
  KEY `idx_presol_asignado` (`usuario_asignado_id`),
  KEY `idx_presol_estado` (`estado`),
  CONSTRAINT `fk_presol_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_presol_usuario` FOREIGN KEY (`usuario_asignado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. COTIZACIONES Y DETALLE DE ÍTEMS
-- ----------------------------------------------------------------------------
CREATE TABLE `cotizaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `presolicitud_id` INT UNSIGNED DEFAULT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `elaborado_por_id` INT UNSIGNED NOT NULL,
  `aprobado_por_id` INT UNSIGNED DEFAULT NULL,
  `fecha_emision` DATE NOT NULL,
  `vigencia_dias` INT UNSIGNED NOT NULL DEFAULT 15,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `iva_porcentaje` DECIMAL(5,2) NOT NULL DEFAULT 16.00,
  `iva_monto` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('Borrador','Enviada','Aprobada','Rechazada','Vencida') NOT NULL DEFAULT 'Borrador',
  `condiciones` TEXT DEFAULT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cotizaciones_codigo` (`codigo`),
  KEY `idx_cot_presol` (`presolicitud_id`),
  KEY `idx_cot_cliente` (`cliente_id`),
  KEY `idx_cot_estado` (`estado`),
  CONSTRAINT `fk_cot_presol` FOREIGN KEY (`presolicitud_id`) REFERENCES `presolicitudes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cot_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cot_elaborado` FOREIGN KEY (`elaborado_por_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_cot_aprobado` FOREIGN KEY (`aprobado_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cotizacion_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cotizacion_id` INT UNSIGNED NOT NULL,
  `servicio_id` INT UNSIGNED DEFAULT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_cotitem_cotizacion` (`cotizacion_id`),
  CONSTRAINT `fk_cotitem_cot` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_cotitem_serv` FOREIGN KEY (`servicio_id`) REFERENCES `servicios_tarifas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. ASIGNACIONES DE PROYECTOS Y SEGUIMIENTO TÉCNICO
-- ----------------------------------------------------------------------------
CREATE TABLE `proyectos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(30) NOT NULL,
  `cotizacion_id` INT UNSIGNED DEFAULT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `supervisor_id` INT UNSIGNED NOT NULL,
  `consultor_id` INT UNSIGNED DEFAULT NULL,
  `nombre_proyecto` VARCHAR(160) NOT NULL,
  `descripcion` TEXT DEFAULT NULL,
  `fecha_inicio` DATE DEFAULT NULL,
  `fecha_entrega_est` DATE DEFAULT NULL,
  `progreso` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `estado` ENUM('Planificado','En Ejecucion','En Pruebas','Entregado','Pausado') NOT NULL DEFAULT 'Planificado',
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proyectos_codigo` (`codigo`),
  KEY `idx_pry_cliente` (`cliente_id`),
  KEY `idx_pry_consultor` (`consultor_id`),
  KEY `idx_pry_estado` (`estado`),
  CONSTRAINT `fk_pry_cot` FOREIGN KEY (`cotizacion_id`) REFERENCES `cotizaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pry_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pry_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pry_consultor` FOREIGN KEY (`consultor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proyecto_seguimientos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `proyecto_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('Avance','Calidad','Incidencia','Entrega') NOT NULL DEFAULT 'Avance',
  `horas_registradas` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `porcentaje_reportado` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `comentario` TEXT NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_seg_proyecto` (`proyecto_id`),
  CONSTRAINT `fk_seg_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_seg_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. BITÁCORAS DE AUDITORÍA
-- ----------------------------------------------------------------------------
CREATE TABLE `bitacoras_auditoria` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `usuario_nombre` VARCHAR(120) NOT NULL,
  `rol_codigo` VARCHAR(40) NOT NULL,
  `modulo` VARCHAR(60) NOT NULL,
  `accion` VARCHAR(60) NOT NULL,
  `registro_id` VARCHAR(50) DEFAULT NULL,
  `detalle` TEXT DEFAULT NULL,
  `ip_origen` VARCHAR(45) NOT NULL,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_modulo` (`modulo`),
  KEY `idx_audit_fecha` (`creado_en`),
  KEY `idx_audit_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- DATOS INICIALES (SEED)
-- Contraseña por defecto para todos los usuarios de prueba: 123456
-- ============================================================================

INSERT INTO `roles` (`id`, `codigo`, `nombre`, `descripcion`) VALUES
(1, 'gerencia', 'Gerencia de Operaciones y TI', 'Acceso total al sistema, aprobaciones, tarifas, usuarios y auditoría'),
(2, 'sup_atencion', 'Supervisión de Atención al Cliente', 'Supervisión de recepción de servicios, pre-solicitudes y operadores'),
(3, 'sup_proyectos', 'Supervisión de Proyectos y Consultoría', 'Gestión de cotizaciones, asignaciones de proyectos y consultores técnicos'),
(4, 'operador', 'Operador de Atención al Cliente', 'Registro y seguimiento inicial de pre-solicitudes y clientes'),
(5, 'consultor', 'Consultor Técnico / Desarrollador', 'Ejecución de proyectos asignados y registro de avances técnicos'),
(6, 'calidad', 'Analista de Calidad y Soporte Operativo', 'Revisión de cumplimiento, soporte operativo y consulta de auditoría');

INSERT INTO `usuarios` (`id`, `rol_id`, `nombre_completo`, `usuario`, `correo`, `telefono`, `password_hash`, `activo`) VALUES
(1, 1, 'Hector', 'hector', 'hector@zoroindex.com', '0412-8850889', '$2y$12$FWAPUwU36EchsRKPRHJnDemOJcxNGzfFe1pSsk/Sub9REi7OvcKX.', 1),
(2, 2, 'Jesus', 'jesus', 'jesus@zoroindex.com', '0414-3201145', '$2y$12$FWAPUwU36EchsRKPRHJnDemOJcxNGzfFe1pSsk/Sub9REi7OvcKX.', 1),
(3, 3, 'Jose', 'jose', 'jose@zoroindex.com', '0424-9184420', '$2y$12$FWAPUwU36EchsRKPRHJnDemOJcxNGzfFe1pSsk/Sub9REi7OvcKX.', 1),
(4, 4, 'Osmary', 'osmary', 'osmary@zoroindex.com', '0412-5540981', '$2y$12$FWAPUwU36EchsRKPRHJnDemOJcxNGzfFe1pSsk/Sub9REi7OvcKX.', 1),
(5, 5, 'Roy', 'roy', 'roy@zoroindex.com', '0416-7731209', '$2y$12$FWAPUwU36EchsRKPRHJnDemOJcxNGzfFe1pSsk/Sub9REi7OvcKX.', 1),
(6, 6, 'Jhon', 'jhon', 'jhon@zoroindex.com', '0414-6092234', '$2y$12$FWAPUwU36EchsRKPRHJnDemOJcxNGzfFe1pSsk/Sub9REi7OvcKX.', 1);

INSERT INTO `clientes` (`id`, `razon_social`, `documento_rif`, `contacto_principal`, `correo`, `telefono`, `direccion`, `sector`) VALUES
(1, 'Inversiones TecnoSur C.A.', 'J-40129834-2', 'Roberto Silva', 'rsilva@tecnosur.com', '0212-9914450', 'Av. Principal de Las Mercedes, Edif. Centro Empresarial, Piso 4', 'Comercio y Tecnología'),
(2, 'Distribuidora Logística Andina S.A.', 'J-30981245-8', 'Elena Morales', 'emorales@logisticaandina.com', '0241-8223310', 'Zona Industrial Norte, Galpón 14', 'Logística y Transporte'),
(3, 'Clínica Integral SaludPlus C.A.', 'J-41002391-5', 'Dr. Javier Márquez', 'jmarquez@saludplus.com', '0212-7628890', 'Calle El Recreo, Torre Salud, Piso 2', 'Salud');

INSERT INTO `servicios_tarifas` (`id`, `codigo`, `nombre`, `descripcion`, `unidad`, `tarifa_base`, `activo`) VALUES
(1, 'SRV-001', 'Consultoría Técnica y Levantamiento de Procesos', 'Análisis de infraestructura, flujos operativos y definición de arquitectura técnica.', 'Hora', 45.00, 1),
(2, 'SRV-002', 'Desarrollo de Aplicación Web a Medida', 'Diseño, programación e integración de sistemas web multiusuario con base de datos relacional.', 'Proyecto', 1200.00, 1),
(3, 'SRV-003', 'Configuración y Optimización de Base de Datos MariaDB', 'Modelado relacional, indexación, respaldos automatizados y endurecimiento de seguridad.', 'Servicio', 380.00, 1),
(4, 'SRV-004', 'Soporte Operativo y Mantenimiento Mensual', 'Monitoreo preventivo, corrección de incidencias y soporte a usuarios finales.', 'Mensual', 250.00, 1),
(5, 'SRV-005', 'Auditoría de Calidad y Seguridad de Software', 'Pruebas funcionales, revisión de control de acceso por roles y validación de estándares.', 'Servicio', 420.00, 1);

INSERT INTO `presolicitudes` (`id`, `codigo`, `cliente_id`, `usuario_asignado_id`, `canal`, `tipo_servicio`, `prioridad`, `descripcion`, `estado`, `observaciones_internas`) VALUES
(1, 'PRE-2026-0001', 1, 4, 'Web', 'Desarrollo de Aplicación Web a Medida', 'Alta', 'Requerimiento de plataforma web interna para control de inventario y órdenes de despacho en 3 sucursales.', 'Cotizada', 'Cliente atendido en primera llamada. Se validó alcance técnico con Supervisión de Proyectos.'),
(2, 'PRE-2026-0002', 2, 4, 'Correo', 'Configuración y Optimización de Base de Datos MariaDB', 'Media', 'Migración de hojas de cálculo de flota hacia base de datos centralizada con respaldos diarios.', 'En Revision', 'Pendiente confirmar volumen histórico de registros.'),
(3, 'PRE-2026-0003', 3, NULL, 'Telefono', 'Auditoría de Calidad y Seguridad de Software', 'Urgente', 'Revisión de permisos de usuarios y bitácoras de acceso en sistema de historias clínicas.', 'Recibida', NULL);

INSERT INTO `cotizaciones` (`id`, `codigo`, `presolicitud_id`, `cliente_id`, `elaborado_por_id`, `aprobado_por_id`, `fecha_emision`, `vigencia_dias`, `subtotal`, `iva_porcentaje`, `iva_monto`, `total`, `estado`, `condiciones`) VALUES
(1, 'COT-2026-0001', 1, 1, 3, 1, '2026-10-25', 15, 1650.00, 16.00, 264.00, 1914.00, 'Aprobada', '50% al inicio del proyecto y 50% contra entrega y validación operativa. Tiempo estimado: 4 semanas.');

INSERT INTO `cotizacion_items` (`cotizacion_id`, `servicio_id`, `descripcion`, `cantidad`, `precio_unitario`, `subtotal`) VALUES
(1, 2, 'Desarrollo de Aplicación Web a Medida (Módulo Inventario y Despachos)', 1.00, 1200.00, 1200.00),
(1, 1, 'Consultoría Técnica y Levantamiento de Procesos (10 horas)', 10.00, 45.00, 450.00);

INSERT INTO `proyectos` (`id`, `codigo`, `cotizacion_id`, `cliente_id`, `supervisor_id`, `consultor_id`, `nombre_proyecto`, `descripcion`, `fecha_inicio`, `fecha_entrega_est`, `progreso`, `estado`) VALUES
(1, 'PRY-2026-0001', 1, 1, 3, 5, 'Sistema Web de Inventario y Despachos - TecnoSur', 'Implementación de plataforma web centralizada con MariaDB y control de acceso por roles para 3 sucursales.', '2026-10-28', '2026-11-28', 65, 'En Ejecucion');

INSERT INTO `proyecto_seguimientos` (`proyecto_id`, `usuario_id`, `tipo`, `horas_registradas`, `porcentaje_reportado`, `comentario`) VALUES
(1, 5, 'Avance', 14.50, 40, 'Modelo de base de datos MariaDB implementado y módulo de autenticación RBAC operativo.'),
(1, 5, 'Avance', 12.00, 65, 'Módulo de órdenes de despacho completado. Iniciando pruebas de concurrencia.'),
(1, 6, 'Calidad', 3.00, 65, 'Verificación de cumplimiento de políticas de seguridad y validación de formularios en Chrome y Edge.');

INSERT INTO `bitacoras_auditoria` (`usuario_id`, `usuario_nombre`, `rol_codigo`, `modulo`, `accion`, `registro_id`, `detalle`, `ip_origen`) VALUES
(1, 'Carlos Eduardo Mendoza', 'gerencia', 'Sistema', 'Inicialización', '1', 'Carga inicial de estructura operativa Zoroindex', '127.0.0.1'),
(3, 'Alejandro José Vargas', 'sup_proyectos', 'Cotizaciones', 'Aprobación', 'COT-2026-0001', 'Cotización aprobada y vinculada al proyecto PRY-2026-0001', '127.0.0.1');
