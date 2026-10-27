-- =========================================================================
-- COPIA DE SEGURIDAD INSTITUCIONAL DE BASE DE DATOS
-- Base de datos: db_tickets_soporte
-- Fecha y hora de generación: 2026-10-27 00:40:29
-- Motor SQL: 8.4.7 | Generador: PDO Nativo PHP 8.5.0
-- =========================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `roles`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id_rol` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `slug_rol` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Identificador para código PHP',
  `nombre_rol` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `slug_rol` (`slug_rol`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id_rol`, `slug_rol`, `nombre_rol`, `descripcion`) VALUES
('1', 'Superadmin', 'Superadministrador', 'Control total de seguridad, permisos, cuentas de personal, registros de acceso e historial de cambios'),
('2', 'Jefe_Soporte', 'Jefe de Departamento de Soporte', 'Acepta y asigna solicitudes en el tablero de trabajo global, gestiona equipos y consulta el panel de estadísticas institucional'),
('3', 'Personal_Soporte', 'Personal de Soporte Técnico', 'Atiende sus propios casos asignados en el tablero de trabajo y consulta sus indicadores personales'),
('4', 'Usuario_Comun', 'Usuario Común', 'Registra solicitudes de soporte técnico y visualiza el progreso de atención en su portal');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `modulos_sistema`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `modulos_sistema`;
CREATE TABLE `modulos_sistema` (
  `id_modulo` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `clave_modulo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: Mod1_Auth, Mod5_Kanban',
  `nombre_modulo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icono_argon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'ni ni-app' COMMENT 'Clase de ícono para Argon Dashboard',
  `orden_menu` tinyint unsigned DEFAULT '1',
  PRIMARY KEY (`id_modulo`),
  UNIQUE KEY `clave_modulo` (`clave_modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `modulos_sistema` (`id_modulo`, `clave_modulo`, `nombre_modulo`, `icono_argon`, `orden_menu`) VALUES
('1', 'Mod1_Auth', 'Mi cuenta y perfil', 'ni ni-single-02', '6'),
('2', 'Mod2_Admin', 'Seguridad, usuarios y auditoría', 'ni ni-key-25', '5'),
('3', 'Mod3_Inventario', 'Departamentos y equipos', 'ni ni-laptop', '4'),
('4', 'Mod4_Portal', 'Mis solicitudes de soporte', 'ni ni-send', '1'),
('5', 'Mod5_Kanban', 'Tablero de trabajo', 'ni ni-bullet-list-67', '2'),
('6', 'Mod6_Dashboard', 'Panel de estadísticas', 'ni ni-chart-bar-32', '3');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `roles_permisos`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles_permisos`;
CREATE TABLE `roles_permisos` (
  `id_rol_permiso` int unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` tinyint unsigned NOT NULL,
  `id_modulo` tinyint unsigned NOT NULL,
  `permiso_ver` tinyint(1) NOT NULL DEFAULT '0',
  `permiso_crear` tinyint(1) NOT NULL DEFAULT '0',
  `permiso_editar` tinyint(1) NOT NULL DEFAULT '0',
  `permiso_eliminar` tinyint(1) NOT NULL DEFAULT '0',
  `permiso_asignar_tickets` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_rol_permiso`),
  UNIQUE KEY `uk_rol_modulo` (`id_rol`,`id_modulo`),
  KEY `fk_rp_modulo` (`id_modulo`),
  CONSTRAINT `fk_rp_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos_sistema` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles_permisos` (`id_rol_permiso`, `id_rol`, `id_modulo`, `permiso_ver`, `permiso_crear`, `permiso_editar`, `permiso_eliminar`, `permiso_asignar_tickets`) VALUES
('1', '1', '1', '1', '1', '1', '1', '1'),
('2', '1', '2', '1', '1', '1', '1', '1'),
('3', '1', '3', '1', '1', '1', '1', '1'),
('4', '1', '5', '1', '1', '1', '1', '1'),
('5', '1', '6', '1', '1', '1', '1', '1'),
('6', '2', '1', '1', '0', '1', '0', '0'),
('7', '2', '3', '1', '1', '1', '0', '0'),
('8', '2', '5', '1', '1', '1', '0', '1'),
('9', '2', '6', '1', '1', '0', '0', '0'),
('10', '3', '1', '1', '0', '1', '0', '0'),
('11', '3', '5', '1', '0', '1', '0', '0'),
('12', '3', '6', '1', '0', '0', '0', '0'),
('13', '4', '1', '1', '0', '1', '0', '0'),
('14', '4', '4', '1', '1', '0', '0', '0');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `departamentos`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `departamentos`;
CREATE TABLE `departamentos` (
  `id_departamento` smallint unsigned NOT NULL AUTO_INCREMENT,
  `codigo_departamento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: DEP-CONT, DEP-SOP',
  `nombre_departamento` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sede_edificio` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `piso_oficina` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_oficina` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_extension` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_departamento`),
  UNIQUE KEY `codigo_departamento` (`codigo_departamento`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departamentos` (`id_departamento`, `codigo_departamento`, `nombre_departamento`, `sede_edificio`, `piso_oficina`, `numero_oficina`, `telefono_extension`, `activo`) VALUES
('1', 'DEP-SOP', 'Departamento de Soporte Técnico e Informática', 'Sede Central', 'Piso 1', 'Oficina 102', 'Ext-102', '1'),
('2', 'DEP-CONT', 'Departamento de Contabilidad y Finanzas', 'Sede Central', 'Piso 2', 'Oficina 204', 'Ext-204', '1'),
('3', 'DEP-RRHH', 'Departamento de Recursos Humanos', 'Sede Central', 'Piso 2', 'Oficina 208', 'Ext-208', '1'),
('4', 'DEP-OPER', 'Dirección de Operaciones y Atención', 'Edificio Anexo', 'Planta Baja', 'Oficina PB-04', 'Ext-304', '1');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `usuarios`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id_usuario` int unsigned NOT NULL AUTO_INCREMENT,
  `id_rol` tinyint unsigned NOT NULL,
  `id_departamento` smallint unsigned NOT NULL,
  `cedula_empleado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo_institucional` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Usar password_hash() BCRYPT/ARGON2ID en PHP',
  `nombres` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo_empleado` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono_extension` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_perfil_ruta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'assets/img/default-avatar.png',
  `pregunta_seguridad` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `respuesta_seguridad_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intentos_fallidos` tinyint unsigned NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `estado_cuenta` enum('Activo','Suspendido','Inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo',
  `estado_conexion` enum('Online','Offline') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Offline',
  `fecha_ultimo_acceso` datetime DEFAULT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `eliminado_en` datetime DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `cedula_empleado` (`cedula_empleado`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `correo_institucional` (`correo_institucional`),
  KEY `idx_usuario_depto` (`id_departamento`),
  KEY `idx_usuario_rol` (`id_rol`),
  CONSTRAINT `fk_usuario_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `id_departamento`, `cedula_empleado`, `username`, `correo_institucional`, `password_hash`, `nombres`, `apellidos`, `cargo_empleado`, `telefono_extension`, `foto_perfil_ruta`, `pregunta_seguridad`, `respuesta_seguridad_hash`, `intentos_fallidos`, `bloqueado_hasta`, `estado_cuenta`, `estado_conexion`, `fecha_ultimo_acceso`, `eliminado_en`) VALUES
('1', '1', '1', 'V-15000111', 'superadmin', 'admin@comunidad.gob.ve', '$2y$12$yn.MPw6EtXxeqMbeiKVfBeQ682jej1c.DhB1REb1BOjrRh4U1xOFW', 'Carlos', 'Mendoza', 'Administrador de Seguridad TI', '101', 'assets/img/default-avatar.png', '¿Nombre de primera mascota?', '$2y$10$3R0gZ6OHh0cf1.tC8DMUpuUYKj9Hzp2H3.8hkXmBDYyhgv3BSB.j.', '0', NULL, 'Activo', 'Online', '2026-10-27 00:39:00', NULL),
('2', '2', '1', 'V-18222333', 'jefesoporte', 'jefe.soporte@comunidad.gob.ve', '$2y$12$JApT/4hXviePXuPuHxNeU.AU7kC77DkT4BAHX/qH43hQ9cTSVmZz2', 'Roberto', 'Salazar', 'Jefe de Soporte Técnico', '102', 'assets/img/avatar_usuario_2_1793068854.jpg', '¿Ciudad de nacimiento de su madre?', '$2y$10$zr.qFE4WYA3UHnhySMSSxuSIJuL1bMA3dDmk47V9iPbcsb9QFP8Bi', '0', NULL, 'Activo', 'Online', '2026-10-27 00:36:50', NULL),
('3', '3', '1', 'V-22444555', 'tecnico1', 'miguel.torres@comunidad.gob.ve', '$2y$12$Xy3JstPYuuFR5HV/tVYT3eKdoTqQPyS4K.ACby74MutiG11LryKg6', 'Miguel', 'Torres', 'Técnico de Soporte Nivel 2', '103', 'assets/img/default-avatar.png', '¿Equipo de fútbol favorito?', '$2y$10$CGw6wBuVTKlQq/fewglYouiSO1JB02qE1RPZCTdlooGZwV0WRX7uO', '0', NULL, 'Activo', 'Online', '2026-10-27 00:36:50', NULL),
('4', '3', '1', 'V-24666777', 'tecnico2', 'laura.gomez@comunidad.gob.ve', '$2y$12$IpHo5RlEYch77oeBHQ2Uk.ntNuooailf2C/tguqhUnba0ms1tkATu', 'Laura', 'Gómez', 'Especialista en Redes e Impresoras', '104', 'assets/img/default-avatar.png', '¿Nombre de su abuelo?', '$2y$10$D93T1jbcwknzyaFijKSZDO6.oHXddrY6/RCXAxXq9nKt9L5s3KASa', '0', NULL, 'Activo', 'Online', '2026-10-27 00:36:50', NULL),
('5', '4', '2', 'V-19888999', 'mrodriguez', 'maria.rodriguez@comunidad.gob.ve', '$2y$12$woOASs8Qx8SZOyFCWv/jW.CdEh6btvOkPRAtHsmMi4jyFl5Yxbebi', 'María', 'Rodríguez', 'Analista Contable Senior', '204', 'assets/img/default-avatar.png', '¿Nombre de su esposo?', '$2y$10$Vko1G6idk2w.KmTds1ezu.HshO6qRIq/u8eh4oi.ux0MlGbw0aw9u', '0', NULL, 'Activo', 'Offline', '2026-10-26 21:59:09', NULL),
('6', '4', '3', 'V-21333444', 'jperez', 'juan.perez@comunidad.gob.ve', '$2y$12$iACCsy78cucl0xq8EFHVTejXptEUXCfSXRdoFaP5DdFotmyubaFGG', 'Juan', 'Pérez', 'Coordinador de Nómina', '208', 'assets/img/default-avatar.png', '¿Primer colegio donde estudió?', '$2y$10$lnNm/8.hoyblBVtzsLeXg.QcXROk7zdTsU7xUeeZaUIHbGUsSpFjS', '0', NULL, 'Activo', 'Offline', '2026-10-26 21:58:14', NULL);

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `solicitudes_recuperacion_clave`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `solicitudes_recuperacion_clave`;
CREATE TABLE `solicitudes_recuperacion_clave` (
  `id_solicitud` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario_afectado` int unsigned NOT NULL,
  `metodo_recuperacion` enum('Correo_Automatico','Chat_P2P_Soporte','Reset_Manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Chat_P2P_Soporte',
  `validacion_seguridad_superada` tinyint(1) NOT NULL DEFAULT '0',
  `id_usuario_validador` int unsigned DEFAULT NULL COMMENT 'Técnico o Admin que aprobó el cambio',
  `estado_solicitud` enum('Pendiente','Validada','Rechazada','Completada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `fecha_solicitud` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_solicitud`),
  KEY `fk_recup_afectado` (`id_usuario_afectado`),
  KEY `fk_recup_validador` (`id_usuario_validador`),
  CONSTRAINT `fk_recup_afectado` FOREIGN KEY (`id_usuario_afectado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_recup_validador` FOREIGN KEY (`id_usuario_validador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `equipos`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `equipos`;
CREATE TABLE `equipos` (
  `id_equipo` int unsigned NOT NULL AUTO_INCREMENT,
  `id_departamento` smallint unsigned NOT NULL,
  `id_usuario_asignado` int unsigned DEFAULT NULL COMMENT 'NULL si es equipo compartido del depto',
  `codigo_bien` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Etiqueta física de inventario',
  `numero_serie` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_equipo` enum('PC_Escritorio','Laptop','Impresora','Router_Switch','Servidor','Periferico') COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `procesador_cpu` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `memoria_ram_gb` smallint unsigned DEFAULT NULL,
  `tipo_almacenamiento` enum('SSD','HDD','NVMe','No_Aplica') COLLATE utf8mb4_unicode_ci DEFAULT 'No_Aplica',
  `capacidad_almacenamiento_gb` smallint unsigned DEFAULT NULL,
  `sistema_operativo` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_ip_fija` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion_mac` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_equipo_compartido` tinyint(1) NOT NULL DEFAULT '0',
  `estado_operativo` enum('Operativo','En_Reparacion','Danado','Desincorporado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Operativo',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_equipo`),
  UNIQUE KEY `codigo_bien` (`codigo_bien`),
  UNIQUE KEY `numero_serie` (`numero_serie`),
  KEY `idx_equipo_depto` (`id_departamento`),
  KEY `fk_equipo_usuario` (`id_usuario_asignado`),
  CONSTRAINT `fk_equipo_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_equipo_usuario` FOREIGN KEY (`id_usuario_asignado`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `equipos` (`id_equipo`, `id_departamento`, `id_usuario_asignado`, `codigo_bien`, `numero_serie`, `categoria_equipo`, `marca`, `modelo`, `procesador_cpu`, `memoria_ram_gb`, `tipo_almacenamiento`, `capacidad_almacenamiento_gb`, `sistema_operativo`, `direccion_ip_fija`, `direccion_mac`, `es_equipo_compartido`, `estado_operativo`) VALUES
('1', '2', '5', 'INV-2026-001', 'SN-HP8839201', 'PC_Escritorio', 'HP', 'ProDesk 400 G7', 'Intel Core i5-10500', '16', 'SSD', '512', 'Windows 11 Pro', '192.168.1.20', NULL, '0', 'Operativo'),
('2', '2', NULL, 'INV-2026-002', 'SN-EPSON9921', 'Impresora', 'Epson', 'EcoTank L5290', NULL, NULL, 'No_Aplica', NULL, 'Firmware Red', '192.168.1.25', NULL, '1', 'Operativo'),
('3', '3', '6', 'INV-2026-003', 'SN-DELL77412', 'Laptop', 'Dell', 'Latitude 5420', 'Intel Core i7-1185G7', '16', 'NVMe', '512', 'Windows 10 Pro', '192.168.1.40', NULL, '0', 'Operativo'),
('4', '4', NULL, 'INV-2026-004', 'SN-CISCO1120', 'Router_Switch', 'Cisco', 'Catalyst 2960-X', NULL, NULL, 'No_Aplica', NULL, 'Cisco IOS', '192.168.1.2', NULL, '1', 'Operativo');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `categorias_falla`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `categorias_falla`;
CREATE TABLE `categorias_falla` (
  `id_categoria_falla` smallint unsigned NOT NULL AUTO_INCREMENT,
  `rama_general` enum('Hardware','Software','Red_Internet','Impresoras','Accesos_Cuentas') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subcategoria` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: Pantalla Azul, Sin red, Atasco de papel',
  `tiempo_estimado_minutos` smallint unsigned NOT NULL DEFAULT '120' COMMENT 'SLA para medir cumplio_expectativa_tiempo',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id_categoria_falla`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categorias_falla` (`id_categoria_falla`, `rama_general`, `subcategoria`, `tiempo_estimado_minutos`, `activo`) VALUES
('1', 'Hardware', 'No enciende / Falla de Fuente de Poder', '180', '1'),
('2', 'Software', 'Error de Sistema Contable / Cierre inesperado', '90', '1'),
('3', 'Red_Internet', 'Sin conexión a la Intranet / IP conflictiva', '60', '1'),
('4', 'Impresoras', 'Atasco de papel o falla de impresión en red', '60', '1'),
('5', 'Accesos_Cuentas', 'Bloqueo de cuenta o restablecimiento de credenciales', '30', '1');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `tickets`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id_ticket` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo_ticket` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: TCK-2026-0001',
  `canal_creacion` enum('Portal_Web_Usuario','Llamada_Telefonica','Presencial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Portal_Web_Usuario',
  `id_usuario_creador` int unsigned NOT NULL COMMENT 'Quien registró el ticket',
  `id_usuario_afectado` int unsigned NOT NULL COMMENT 'Dueño de la problemática',
  `id_departamento` smallint unsigned NOT NULL COMMENT 'Depto donde ocurre el fallo',
  `id_equipo` int unsigned NOT NULL COMMENT 'Equipo que presenta la falla',
  `id_categoria_falla` smallint unsigned NOT NULL,
  `titulo_asunto` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion_problematica` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `prioridad_sugerida` enum('Baja','Media','Alta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Media',
  `estado_aceptacion_jefe` enum('Pendiente_Revision','Aceptado','Rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente_Revision',
  `motivo_rechazo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prioridad_oficial` enum('Baja','Media','Alta','Critica') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Media',
  `estado_columna_kanban` enum('1_Por_Aceptar','2_Asignado','3_En_Proceso','4_En_Espera','5_Resuelto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1_Por_Aceptar',
  `motivo_pausa_espera` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_jefe_asignador` int unsigned DEFAULT NULL,
  `diagnostico_real_tecnico` text COLLATE utf8mb4_unicode_ci,
  `solucion_aplicada_tecnico` text COLLATE utf8mb4_unicode_ci,
  `requirio_cambio_pieza` tinyint(1) NOT NULL DEFAULT '0',
  `detalle_pieza_cambiada` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmado_por_usuario` enum('Pendiente','Confirmado_Conforme','Reabierto_No_Funciona') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente',
  `tiempo_acumulado_espera_min` int unsigned NOT NULL DEFAULT '0' COMMENT 'Minutos pausado en columna 4',
  `archivado_kanban` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_aceptacion_asignacion` datetime DEFAULT NULL,
  `fecha_inicio_atencion` datetime DEFAULT NULL,
  `fecha_resolucion_tecnica` datetime DEFAULT NULL,
  `fecha_cierre_definitivo` datetime DEFAULT NULL,
  `eliminado_en` datetime DEFAULT NULL,
  `anio_creacion` smallint GENERATED ALWAYS AS (year(`fecha_creacion`)) STORED,
  `mes_creacion` tinyint GENERATED ALWAYS AS (month(`fecha_creacion`)) STORED,
  PRIMARY KEY (`id_ticket`),
  UNIQUE KEY `codigo_ticket` (`codigo_ticket`),
  KEY `idx_kanban_filtro` (`estado_columna_kanban`,`archivado_kanban`,`anio_creacion`,`mes_creacion`),
  KEY `idx_dashboard_depto_fecha` (`id_departamento`,`fecha_creacion`),
  KEY `idx_dashboard_equipo` (`id_equipo`),
  KEY `fk_tck_creador` (`id_usuario_creador`),
  KEY `fk_tck_afectado` (`id_usuario_afectado`),
  KEY `fk_tck_catfalla` (`id_categoria_falla`),
  KEY `fk_tck_jefe` (`id_jefe_asignador`),
  CONSTRAINT `fk_tck_afectado` FOREIGN KEY (`id_usuario_afectado`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_tck_catfalla` FOREIGN KEY (`id_categoria_falla`) REFERENCES `categorias_falla` (`id_categoria_falla`),
  CONSTRAINT `fk_tck_creador` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `fk_tck_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`),
  CONSTRAINT `fk_tck_equipo` FOREIGN KEY (`id_equipo`) REFERENCES `equipos` (`id_equipo`),
  CONSTRAINT `fk_tck_jefe` FOREIGN KEY (`id_jefe_asignador`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tickets` (`id_ticket`, `codigo_ticket`, `canal_creacion`, `id_usuario_creador`, `id_usuario_afectado`, `id_departamento`, `id_equipo`, `id_categoria_falla`, `titulo_asunto`, `descripcion_problematica`, `prioridad_sugerida`, `estado_aceptacion_jefe`, `motivo_rechazo`, `prioridad_oficial`, `estado_columna_kanban`, `motivo_pausa_espera`, `id_jefe_asignador`, `diagnostico_real_tecnico`, `solucion_aplicada_tecnico`, `requirio_cambio_pieza`, `detalle_pieza_cambiada`, `confirmado_por_usuario`, `tiempo_acumulado_espera_min`, `archivado_kanban`, `fecha_aceptacion_asignacion`, `fecha_inicio_atencion`, `fecha_resolucion_tecnica`, `fecha_cierre_definitivo`, `eliminado_en`) VALUES
('1', 'TCK-2026-0001', 'Portal_Web_Usuario', '5', '5', '2', '1', '2', 'El sistema administrativo se cierra al generar balance', 'Al intentar exportar el cierre del mes en mi PC, aparece un error de memoria y se cierra el programa.', 'Alta', 'Aceptado', NULL, 'Alta', '2_Asignado', NULL, '2', NULL, NULL, '0', NULL, 'Pendiente', '0', '0', '2026-10-26 22:24:14', NULL, NULL, NULL, NULL),
('2', 'TCK-2026-0002', 'Portal_Web_Usuario', '6', '6', '3', '3', '3', 'Sin acceso a la carpeta compartida de Nómina', 'La laptop conecta al Wi-Fi pero no me deja entrar al servidor local de Recursos Humanos.', 'Alta', 'Aceptado', NULL, 'Alta', '3_En_Proceso', NULL, '2', NULL, NULL, '0', NULL, 'Pendiente', '0', '0', '2026-10-25 18:38:10', '2026-10-25 23:59:37', NULL, NULL, NULL),
('3', 'TCK-2026-0003', 'Portal_Web_Usuario', '5', '5', '2', '2', '4', 'Impresora de Contabilidad no recibe órdenes por red', 'Nadie en la oficina 204 puede imprimir desde ayer en la tarde, parpadea el bombillo de red.', 'Media', 'Aceptado', NULL, 'Alta', '5_Resuelto', NULL, '2', 'Desconfiguración de puerta de enlace en la impresora.', 'Se reemplazó el conector RJ45 deteriorado y se reconfiguró la puerta de enlace de red.', '1', 'Conector RJ45 Categoría 6 blindado', 'Confirmado_Conforme', '0', '0', '2026-10-25 00:38:10', '2026-10-25 02:38:10', '2026-10-26 00:43:21', '2026-10-26 00:44:11', NULL),
('4', 'TCK-2026-0004', 'Llamada_Telefonica', '2', '6', '3', '3', '1', 'Cargador de Laptop Dell no suministra energía', 'El equipo se apagó y el adaptador de corriente huele a quemado.', 'Alta', 'Aceptado', NULL, 'Critica', '4_En_Espera', 'En espera de adaptador Dell 65W solicitado a almacén', '2', 'Cortocircuito en fuente externa.', NULL, '1', 'Cargador Dell USB-C 65W', 'Pendiente', '0', '0', '2026-10-23 00:38:10', '2026-10-23 02:38:10', NULL, NULL, NULL),
('5', 'TCK-2026-0005', 'Portal_Web_Usuario', '5', '5', '2', '1', '5', 'Restablecimiento de permisos en módulo tributario', 'No podía ingresar con mi usuario tras la actualización del lunes.', 'Media', 'Aceptado', NULL, 'Media', '5_Resuelto', NULL, '2', 'Permisos de sesión caducados.', 'Se refrescaron las credenciales y caché DNS local.', '0', NULL, 'Confirmado_Conforme', '0', '0', '2026-10-20 23:38:10', '2026-10-21 00:38:10', '2026-10-21 01:38:10', NULL, NULL);

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `ticket_asignaciones`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `ticket_asignaciones`;
CREATE TABLE `ticket_asignaciones` (
  `id_asignacion` int unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int unsigned NOT NULL,
  `id_personal_soporte` int unsigned NOT NULL,
  `rol_en_ticket` enum('Tecnico_Principal','Tecnico_Apoyo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tecnico_Principal',
  `fue_autoasignado` tinyint(1) NOT NULL DEFAULT '0',
  `activo_en_ticket` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_asignacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asig_tecnico` (`id_personal_soporte`,`activo_en_ticket`),
  KEY `fk_asig_ticket` (`id_ticket`),
  CONSTRAINT `fk_asig_tecnico` FOREIGN KEY (`id_personal_soporte`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT,
  CONSTRAINT `fk_asig_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ticket_asignaciones` (`id_asignacion`, `id_ticket`, `id_personal_soporte`, `rol_en_ticket`, `fue_autoasignado`, `activo_en_ticket`) VALUES
('1', '2', '3', 'Tecnico_Principal', '0', '1'),
('2', '3', '4', 'Tecnico_Principal', '0', '1'),
('3', '4', '3', 'Tecnico_Principal', '0', '1'),
('4', '5', '3', 'Tecnico_Principal', '0', '1'),
('7', '1', '4', 'Tecnico_Principal', '0', '1');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `ticket_comentarios`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `ticket_comentarios`;
CREATE TABLE `ticket_comentarios` (
  `id_comentario` int unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int unsigned NOT NULL,
  `id_usuario_autor` int unsigned NOT NULL,
  `mensaje_texto` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_nota_interna_privada` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1=Solo soporte, 0=Visible al usuario común',
  `fecha_comentario` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_comentario`),
  KEY `idx_com_ticket` (`id_ticket`),
  KEY `fk_com_autor` (`id_usuario_autor`),
  CONSTRAINT `fk_com_autor` FOREIGN KEY (`id_usuario_autor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT,
  CONSTRAINT `fk_com_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ticket_comentarios` (`id_comentario`, `id_ticket`, `id_usuario_autor`, `mensaje_texto`, `es_nota_interna_privada`) VALUES
('1', '3', '4', 'Hola María, ya estoy en el piso 2 revisando la configuración IP de la impresora Epson.', '0'),
('2', '3', '4', 'Nota técnica: El conmutador de red del piso 2 tenía bloqueada la dirección física de la impresora.', '1'),
('5', '4', '6', 'Buenos días, quedo atento a la revisión del acceso a la carpeta compartida de Nómina.', '0'),
('6', '3', '4', 'Ya se restableció el enlace de red en la oficina 204 y se realizaron pruebas de impresión.', '0'),
('7', '3', '5', 'Muchas gracias por la rápida atención.', '0'),
('8', '3', '5', 'Confirmación de cierre del solicitante: El equipo quedó funcionando en perfecto estado.', '0'),
('12', '1', '2', 'Instrucción de asignación del Jefe de Soporte: Revisar consumo de memoria RAM y archivos temporales del sistema contable.', '1'),
('13', '3', '2', 'María, confírmanos si la impresora compartida continúa operando sin inconvenientes.', '0');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `ticket_adjuntos`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `ticket_adjuntos`;
CREATE TABLE `ticket_adjuntos` (
  `id_adjunto` int unsigned NOT NULL AUTO_INCREMENT,
  `id_ticket` int unsigned NOT NULL,
  `id_usuario_subida` int unsigned NOT NULL,
  `nombre_archivo_original` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension_archivo` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peso_archivo_kb` int unsigned NOT NULL,
  `ruta_almacenamiento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_subida` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjunto`),
  KEY `fk_adj_ticket` (`id_ticket`),
  KEY `fk_adj_usuario` (`id_usuario_subida`),
  CONSTRAINT `fk_adj_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets` (`id_ticket`) ON DELETE CASCADE,
  CONSTRAINT `fk_adj_usuario` FOREIGN KEY (`id_usuario_subida`) REFERENCES `usuarios` (`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `logs_sistema_accesos`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `logs_sistema_accesos`;
CREATE TABLE `logs_sistema_accesos` (
  `id_log_acceso` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int unsigned DEFAULT NULL,
  `username_intento` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_evento` enum('Login_Exitoso','Login_Fallido','Logout','Bloqueo_Seguridad','Navegacion_Modulo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_modulo` tinyint unsigned DEFAULT NULL,
  `descripcion_accion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccion_ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `navegador_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_evento` date GENERATED ALWAYS AS (cast(`fecha_hora` as date)) STORED,
  `hora_evento` time GENERATED ALWAYS AS (cast(`fecha_hora` as time)) STORED,
  `fecha_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log_acceso`),
  KEY `idx_logs_fecha_tipo` (`fecha_evento`,`tipo_evento`),
  KEY `fk_log_usuario` (`id_usuario`),
  KEY `fk_log_modulo` (`id_modulo`),
  CONSTRAINT `fk_log_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos_sistema` (`id_modulo`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `logs_sistema_accesos` (`id_log_acceso`, `id_usuario`, `username_intento`, `tipo_evento`, `id_modulo`, `descripcion_accion`, `direccion_ip`, `navegador_user_agent`) VALUES
('1', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('2', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('3', '6', 'jperez', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Usuario Común', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('4', '6', 'jperez', 'Navegacion_Modulo', '4', 'Agregó un comentario público en la solicitud TCK-2026-0004', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('5', '6', 'jperez', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('6', '2', 'jefesoporte', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Jefe de Departamento de Soporte', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('7', '2', 'jefesoporte', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('8', '3', 'tecnico1', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Personal de Soporte Técnico', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('9', '3', 'tecnico1', 'Navegacion_Modulo', '5', 'Actualizó la solicitud TCK-2026-0002 a la etapa En atención', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('10', '3', 'tecnico1', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('11', '2', 'jefesoporte', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Jefe de Departamento de Soporte', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('12', '2', 'jefesoporte', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('13', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('14', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('15', '4', 'tecnico2', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Personal de Soporte Técnico', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('16', '4', 'tecnico2', 'Navegacion_Modulo', '5', 'Registró mensaje público para el solicitante en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('17', '4', 'tecnico2', 'Navegacion_Modulo', '5', 'Actualizó la solicitud TCK-2026-0003 a la etapa En espera', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('18', '4', 'tecnico2', 'Navegacion_Modulo', '5', 'Actualizó la solicitud TCK-2026-0003 a la etapa Resuelto', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('19', '4', 'tecnico2', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('20', '5', 'mrodriguez', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Usuario Común', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('21', '5', 'mrodriguez', 'Navegacion_Modulo', '4', 'Agregó un comentario público en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('22', '5', 'mrodriguez', 'Navegacion_Modulo', '4', 'Registró decisión Confirmado_Conforme en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('23', '5', 'mrodriguez', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('24', '2', 'jefesoporte', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Jefe de Departamento de Soporte', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('25', '2', 'jefesoporte', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('26', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('27', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('28', '6', 'jperez', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Usuario Común', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('29', '6', 'jperez', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('30', '5', 'mrodriguez', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Usuario Común', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('31', '5', 'mrodriguez', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('32', '4', 'tecnico2', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Personal de Soporte Técnico', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('33', '4', 'tecnico2', 'Navegacion_Modulo', '5', 'Registró nota interna para soporte en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('34', '4', 'tecnico2', 'Navegacion_Modulo', '5', 'Registró mensaje público para el solicitante en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('35', '4', 'tecnico2', 'Navegacion_Modulo', '5', 'Registró nota interna para soporte en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('36', '4', 'tecnico2', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('37', '2', 'jefesoporte', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Jefe de Departamento de Soporte', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('38', '2', 'jefesoporte', 'Navegacion_Modulo', '5', 'Aceptó y asignó la solicitud TCK-2026-0001 a Laura Gómez', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('39', '2', 'jefesoporte', 'Navegacion_Modulo', '5', 'Registró mensaje público para el solicitante en la solicitud TCK-2026-0003', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('40', '2', 'jefesoporte', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('41', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('42', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('43', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('44', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('45', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('46', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('47', '2', 'jefesoporte', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Jefe de Departamento de Soporte', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('48', '2', 'jefesoporte', 'Navegacion_Modulo', '1', 'Actualización de datos personales en Mi cuenta y perfil', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('49', '2', 'jefesoporte', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('50', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('51', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('52', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('54', '1', 'superadmin', 'Navegacion_Modulo', '2', 'Generó la copia de seguridad de base de datos respaldo_sistema_20261027_003413.sql con peso de 51.02 KB', '127.0.0.1', 'Navegador institucional en red local'),
('55', '1', 'superadmin', 'Logout', '1', 'Cierre de sesión realizado por el usuario', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36'),
('56', '1', 'superadmin', 'Login_Exitoso', '1', 'Inicio de sesión correcto con el rol Superadministrador', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `changelogs_auditoria`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `changelogs_auditoria`;
CREATE TABLE `changelogs_auditoria` (
  `id_changelog` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario_autor` int unsigned DEFAULT NULL,
  `tabla_afectada` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_registro_afectado` int unsigned NOT NULL,
  `tipo_operacion` enum('CREACION','MODIFICACION','ELIMINACION_LOGICA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `campo_modificado` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_anterior` text COLLATE utf8mb4_unicode_ci,
  `valor_nuevo` text COLLATE utf8mb4_unicode_ci,
  `motivo_cambio` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_changelog`),
  KEY `idx_changelog_entidad` (`tabla_afectada`,`id_registro_afectado`),
  KEY `fk_chg_autor` (`id_usuario_autor`),
  CONSTRAINT `fk_chg_autor` FOREIGN KEY (`id_usuario_autor`) REFERENCES `usuarios` (`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `changelogs_auditoria` (`id_changelog`, `id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`, `motivo_cambio`) VALUES
('5', '2', 'tickets', '2', 'MODIFICACION', 'estado_columna_kanban', '2_Asignado', '3_En_Proceso', NULL),
('6', '3', 'tickets', '2', 'MODIFICACION', 'avance_tecnico_kanban', 'Asignado', 'En atención', 'Actualización de avance técnico'),
('7', '2', 'tickets', '3', 'MODIFICACION', 'estado_columna_kanban', '3_En_Proceso', '4_En_Espera', 'En espera de conector RJ45 blindado para reemplazo en punto de red'),
('8', '4', 'tickets', '3', 'MODIFICACION', 'avance_tecnico_kanban', 'En atención', 'En espera', 'En espera de conector RJ45 blindado para reemplazo en punto de red'),
('9', '2', 'tickets', '3', 'MODIFICACION', 'estado_columna_kanban', '4_En_Espera', '5_Resuelto', NULL),
('10', '4', 'tickets', '3', 'MODIFICACION', 'avance_tecnico_kanban', 'En espera', 'Resuelto', 'Solución: Se reemplazó el conector RJ45 deteriorado y se reconfiguró la puerta de enlace de red.'),
('11', '5', 'tickets', '3', 'MODIFICACION', 'confirmado_por_usuario', 'Pendiente', 'Confirmado_Conforme', 'El usuario confirmó conformidad con la solución aplicada'),
('12', '2', 'tickets', '1', 'MODIFICACION', 'estado_columna_kanban', '1_Por_Aceptar', '2_Asignado', NULL),
('13', '2', 'tickets', '1', 'MODIFICACION', 'prioridad_oficial', 'Media', 'Alta', NULL),
('14', '2', 'ticket_asignaciones', '1', 'MODIFICACION', 'id_personal_soporte', 'Sin asignar', 'Laura Gómez', 'Aceptación y asignación del caso TCK-2026-0001 con prioridad Alta'),
('15', '2', 'usuarios', '2', 'MODIFICACION', 'foto_perfil_ruta', 'assets/img/default-avatar.png', 'assets/img/avatar_usuario_2_1793068854.jpg', 'Actualización de datos en cuenta propia: Fotografía de perfil'),
('17', '1', 'db_tickets_soporte', '1', 'CREACION', 'respaldo_sql', 'Sin archivo previo', 'respaldo_sistema_20261027_003413.sql — 51.02 KB', 'Copia de seguridad completa generada desde el panel de infraestructura');

-- -------------------------------------------------------------------------
-- Estructura y datos de la tabla `historial_reportes_generados`
-- -------------------------------------------------------------------------
DROP TABLE IF EXISTS `historial_reportes_generados`;
CREATE TABLE `historial_reportes_generados` (
  `id_reporte` int unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario_solicitante` int unsigned NOT NULL,
  `tipo_reporte` enum('Errores_Por_Departamento','Rendimiento_Tecnicos','Equipos_Con_Mas_Fallas','Resumen_Mensual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio_filtro` date NOT NULL,
  `fecha_fin_filtro` date NOT NULL,
  `id_departamento_filtro` smallint unsigned DEFAULT NULL COMMENT 'NULL equivale a Todos los departamentos',
  `formato_exportacion` enum('PDF','Excel_XLSX','CSV') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_generacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_reporte`),
  KEY `fk_rep_usuario` (`id_usuario_solicitante`),
  KEY `fk_rep_depto` (`id_departamento_filtro`),
  CONSTRAINT `fk_rep_depto` FOREIGN KEY (`id_departamento_filtro`) REFERENCES `departamentos` (`id_departamento`) ON DELETE SET NULL,
  CONSTRAINT `fk_rep_usuario` FOREIGN KEY (`id_usuario_solicitante`) REFERENCES `usuarios` (`id_usuario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- Disparador de auditoría automática trg_ticket_kanban_changelog
-- -------------------------------------------------------------------------
DROP TRIGGER IF EXISTS `trg_ticket_kanban_changelog`;
DELIMITER $$
CREATE TRIGGER `trg_ticket_kanban_changelog`
AFTER UPDATE ON `tickets`
FOR EACH ROW
BEGIN
    IF OLD.estado_columna_kanban <> NEW.estado_columna_kanban THEN
        INSERT INTO `changelogs_auditoria`
        (`id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`, `motivo_cambio`)
        VALUES
        (NEW.id_jefe_asignador, 'tickets', NEW.id_ticket, 'MODIFICACION', 'estado_columna_kanban', OLD.estado_columna_kanban, NEW.estado_columna_kanban, NEW.motivo_pausa_espera);
    END IF;
    IF OLD.prioridad_oficial <> NEW.prioridad_oficial THEN
        INSERT INTO `changelogs_auditoria`
        (`id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`)
        VALUES
        (NEW.id_jefe_asignador, 'tickets', NEW.id_ticket, 'MODIFICACION', 'prioridad_oficial', OLD.prioridad_oficial, NEW.prioridad_oficial);
    END IF;
END$$
DELIMITER ;

-- -------------------------------------------------------------------------
-- Vista consolidada de estadísticas vw_metricas_dashboard
-- -------------------------------------------------------------------------
DROP VIEW IF EXISTS `vw_metricas_dashboard`;
CREATE OR REPLACE VIEW `vw_metricas_dashboard` AS
SELECT
    t.id_ticket,
    t.codigo_ticket,
    t.anio_creacion AS anio_periodo,
    t.mes_creacion AS mes_periodo,
    WEEK(t.fecha_creacion, 1) AS semana_del_anio,
    DATE(t.fecha_creacion) AS dia_fecha,
    d.id_departamento,
    d.nombre_departamento,
    e.id_equipo,
    e.codigo_bien AS codigo_bien_equipo,
    e.categoria_equipo,
    cf.rama_general AS categoria_falla,
    cf.subcategoria AS subcategoria_falla,
    t.estado_columna_kanban,
    t.prioridad_oficial,
    ta.id_personal_soporte AS id_tecnico_resolutor,
    CONCAT(utec.nombres, ' ', utec.apellidos) AS nombre_tecnico,
    TIMESTAMPDIFF(MINUTE, t.fecha_creacion, COALESCE(t.fecha_inicio_atencion, NOW())) AS tiempo_respuesta_minutos,
    CASE
        WHEN t.fecha_resolucion_tecnica IS NOT NULL AND t.fecha_inicio_atencion IS NOT NULL
        THEN GREATEST(0, TIMESTAMPDIFF(MINUTE, t.fecha_inicio_atencion, t.fecha_resolucion_tecnica) - t.tiempo_acumulado_espera_min)
        ELSE NULL
    END AS tiempo_resolucion_minutos,
    t.tiempo_acumulado_espera_min AS tiempo_en_espera_minutos,
    CASE
        WHEN t.fecha_resolucion_tecnica IS NOT NULL
             AND (TIMESTAMPDIFF(MINUTE, t.fecha_inicio_atencion, t.fecha_resolucion_tecnica) - t.tiempo_acumulado_espera_min) <= cf.tiempo_estimado_minutos
        THEN 'Si'
        ELSE 'No'
    END AS cumplio_expectativa_tiempo
FROM `tickets` t
INNER JOIN `departamentos` d ON t.id_departamento = d.id_departamento
INNER JOIN `equipos` e ON t.id_equipo = e.id_equipo
INNER JOIN `categorias_falla` cf ON t.id_categoria_falla = cf.id_categoria_falla
LEFT JOIN `ticket_asignaciones` ta ON t.id_ticket = ta.id_ticket AND ta.rol_en_ticket = 'Tecnico_Principal' AND ta.activo_en_ticket = 1
LEFT JOIN `usuarios` utec ON ta.id_personal_soporte = utec.id_usuario
WHERE t.eliminado_en IS NULL;

SET FOREIGN_KEY_CHECKS = 1;
