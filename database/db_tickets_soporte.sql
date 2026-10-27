-- =========================================================================
-- SISTEMA DE GESTIÓN DE TICKETS DE SOPORTE TÉCNICO (ARQUITECTURA 3FN)
-- Motor: MySQL / MariaDB (WampServer 3.4.0) | Codificación: UTF8MB4
-- =========================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS `db_tickets_soporte`;
CREATE DATABASE `db_tickets_soporte` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_tickets_soporte`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =========================================================================
-- 1. MÓDULO 2 y 1: SEGURIDAD, ROLES Y PERMISOS GRANULARES (RBAC)
-- =========================================================================

CREATE TABLE `roles` (
    `id_rol` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug_rol` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Identificador para código PHP',
    `nombre_rol` VARCHAR(60) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `modulos_sistema` (
    `id_modulo` TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `clave_modulo` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Ej: Mod1_Auth, Mod5_Kanban',
    `nombre_modulo` VARCHAR(80) NOT NULL,
    `icono_argon` VARCHAR(50) DEFAULT 'ni ni-app' COMMENT 'Clase de ícono para Argon Dashboard',
    `orden_menu` TINYINT UNSIGNED DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `roles_permisos` (
    `id_rol_permiso` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_rol` TINYINT UNSIGNED NOT NULL,
    `id_modulo` TINYINT UNSIGNED NOT NULL,
    `permiso_ver` TINYINT(1) NOT NULL DEFAULT 0,
    `permiso_crear` TINYINT(1) NOT NULL DEFAULT 0,
    `permiso_editar` TINYINT(1) NOT NULL DEFAULT 0,
    `permiso_eliminar` TINYINT(1) NOT NULL DEFAULT 0,
    `permiso_asignar_tickets` TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY `uk_rol_modulo` (`id_rol`, `id_modulo`),
    CONSTRAINT `fk_rp_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles`(`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rp_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos_sistema`(`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================================
-- 2. MÓDULO 3: ESTRUCTURA ORGANIZACIONAL (DEPARTAMENTOS Y OFICINAS)
-- =========================================================================

CREATE TABLE `departamentos` (
    `id_departamento` SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo_departamento` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Ej: DEP-CONT, DEP-SOP',
    `nombre_departamento` VARCHAR(100) NOT NULL,
    `sede_edificio` VARCHAR(80) NOT NULL,
    `piso_oficina` VARCHAR(30) NOT NULL,
    `numero_oficina` VARCHAR(30) NOT NULL,
    `telefono_extension` VARCHAR(25) NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================================
-- 3. MÓDULO 1: USUARIOS, CUENTAS Y RECUPERACIÓN EN INTRANET
-- =========================================================================

CREATE TABLE `usuarios` (
    `id_usuario` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_rol` TINYINT UNSIGNED NOT NULL,
    `id_departamento` SMALLINT UNSIGNED NOT NULL,
    `cedula_empleado` VARCHAR(20) NOT NULL UNIQUE,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `correo_institucional` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Usar password_hash() BCRYPT/ARGON2ID en PHP',
    `nombres` VARCHAR(80) NOT NULL,
    `apellidos` VARCHAR(80) NOT NULL,
    `cargo_empleado` VARCHAR(80) NOT NULL,
    `telefono_extension` VARCHAR(25) NULL,
    `foto_perfil_ruta` VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
    -- Seguridad y Re-autenticación P2P Intranet
    `pregunta_seguridad` VARCHAR(150) NULL,
    `respuesta_seguridad_hash` VARCHAR(255) NULL,
    `intentos_fallidos` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `bloqueado_hasta` DATETIME NULL,
    `estado_cuenta` ENUM('Activo', 'Suspendido', 'Inactivo') NOT NULL DEFAULT 'Activo',
    `estado_conexion` ENUM('Online', 'Offline') NOT NULL DEFAULT 'Offline',
    `fecha_ultimo_acceso` DATETIME NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `eliminado_en` DATETIME NULL COMMENT 'Soft delete',
    INDEX `idx_usuario_depto` (`id_departamento`),
    INDEX `idx_usuario_rol` (`id_rol`),
    CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles`(`id_rol`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_usuario_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos`(`id_departamento`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `solicitudes_recuperacion_clave` (
    `id_solicitud` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_usuario_afectado` INT UNSIGNED NOT NULL,
    `metodo_recuperacion` ENUM('Correo_Automatico', 'Chat_P2P_Soporte', 'Reset_Manual') NOT NULL DEFAULT 'Chat_P2P_Soporte',
    `validacion_seguridad_superada` TINYINT(1) NOT NULL DEFAULT 0,
    `id_usuario_validador` INT UNSIGNED NULL COMMENT 'Técnico o Admin que aprobó el cambio',
    `estado_solicitud` ENUM('Pendiente', 'Validada', 'Rechazada', 'Completada') NOT NULL DEFAULT 'Pendiente',
    `fecha_solicitud` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `fecha_resolucion` DATETIME NULL,
    CONSTRAINT `fk_recup_afectado` FOREIGN KEY (`id_usuario_afectado`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
    CONSTRAINT `fk_recup_validador` FOREIGN KEY (`id_usuario_validador`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================================
-- 4. MÓDULO 3: INVENTARIO DE EQUIPOS POR DEPARTAMENTO
-- =========================================================================

CREATE TABLE `equipos` (
    `id_equipo` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_departamento` SMALLINT UNSIGNED NOT NULL,
    `id_usuario_asignado` INT UNSIGNED NULL COMMENT 'NULL si es equipo compartido del depto',
    `codigo_bien` VARCHAR(40) NOT NULL UNIQUE COMMENT 'Etiqueta física de inventario',
    `numero_serie` VARCHAR(80) NOT NULL UNIQUE,
    `categoria_equipo` ENUM('PC_Escritorio', 'Laptop', 'Impresora', 'Router_Switch', 'Servidor', 'Periferico') NOT NULL,
    `marca` VARCHAR(50) NOT NULL,
    `modelo` VARCHAR(80) NOT NULL,
    `procesador_cpu` VARCHAR(80) NULL,
    `memoria_ram_gb` SMALLINT UNSIGNED NULL,
    `tipo_almacenamiento` ENUM('SSD', 'HDD', 'NVMe', 'No_Aplica') DEFAULT 'No_Aplica',
    `capacidad_almacenamiento_gb` SMALLINT UNSIGNED NULL,
    `sistema_operativo` VARCHAR(60) NULL,
    `direccion_ip_fija` VARCHAR(45) NULL,
    `direccion_mac` VARCHAR(17) NULL,
    `es_equipo_compartido` TINYINT(1) NOT NULL DEFAULT 0,
    `estado_operativo` ENUM('Operativo', 'En_Reparacion', 'Danado', 'Desincorporado') NOT NULL DEFAULT 'Operativo',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `actualizado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_equipo_depto` (`id_departamento`),
    CONSTRAINT `fk_equipo_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos`(`id_departamento`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_equipo_usuario` FOREIGN KEY (`id_usuario_asignado`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================================
-- 5. MÓDULOS 4 y 5: PORTAL DE SOLICITUDES Y GESTIÓN KANBAN DE TICKETS
-- =========================================================================

CREATE TABLE `categorias_falla` (
    `id_categoria_falla` SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rama_general` ENUM('Hardware', 'Software', 'Red_Internet', 'Impresoras', 'Accesos_Cuentas') NOT NULL,
    `subcategoria` VARCHAR(100) NOT NULL COMMENT 'Ej: Pantalla Azul, Sin red, Atasco de papel',
    `tiempo_estimado_minutos` SMALLINT UNSIGNED NOT NULL DEFAULT 120 COMMENT 'SLA para medir cumplio_expectativa_tiempo',
    `activo` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `tickets` (
    `id_ticket` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo_ticket` VARCHAR(25) NOT NULL UNIQUE COMMENT 'Ej: TCK-2026-0001',
    `canal_creacion` ENUM('Portal_Web_Usuario', 'Llamada_Telefonica', 'Presencial') NOT NULL DEFAULT 'Portal_Web_Usuario',
    `id_usuario_creador` INT UNSIGNED NOT NULL COMMENT 'Quien registró el ticket',
    `id_usuario_afectado` INT UNSIGNED NOT NULL COMMENT 'Dueño de la problemática',
    `id_departamento` SMALLINT UNSIGNED NOT NULL COMMENT 'Depto donde ocurre el fallo',
    `id_equipo` INT UNSIGNED NOT NULL COMMENT 'Equipo que presenta la falla',
    `id_categoria_falla` SMALLINT UNSIGNED NOT NULL,
    `titulo_asunto` VARCHAR(120) NOT NULL,
    `descripcion_problematica` TEXT NOT NULL,
    `prioridad_sugerida` ENUM('Baja', 'Media', 'Alta') NOT NULL DEFAULT 'Media',
    -- Gestión del Jefe de Soporte y Tablero Kanban
    `estado_aceptacion_jefe` ENUM('Pendiente_Revision', 'Aceptado', 'Rechazado') NOT NULL DEFAULT 'Pendiente_Revision',
    `motivo_rechazo` VARCHAR(255) NULL,
    `prioridad_oficial` ENUM('Baja', 'Media', 'Alta', 'Critica') NOT NULL DEFAULT 'Media',
    `estado_columna_kanban` ENUM('1_Por_Aceptar', '2_Asignado', '3_En_Proceso', '4_En_Espera', '5_Resuelto') NOT NULL DEFAULT '1_Por_Aceptar',
    `motivo_pausa_espera` VARCHAR(255) NULL,
    `id_jefe_asignador` INT UNSIGNED NULL,
    -- Cierre Técnico y Confirmación
    `diagnostico_real_tecnico` TEXT NULL,
    `solucion_aplicada_tecnico` TEXT NULL,
    `requirio_cambio_pieza` TINYINT(1) NOT NULL DEFAULT 0,
    `detalle_pieza_cambiada` VARCHAR(180) NULL,
    `confirmado_por_usuario` ENUM('Pendiente', 'Confirmado_Conforme', 'Reabierto_No_Funciona') NOT NULL DEFAULT 'Pendiente',
    -- Control de Tiempos y Archivo Mensual de Kanban
    `tiempo_acumulado_espera_min` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Minutos pausado en columna 4',
    `archivado_kanban` TINYINT(1) NOT NULL DEFAULT 0,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_aceptacion_asignacion` DATETIME NULL,
    `fecha_inicio_atencion` DATETIME NULL,
    `fecha_resolucion_tecnica` DATETIME NULL,
    `fecha_cierre_definitivo` DATETIME NULL,
    `eliminado_en` DATETIME NULL,
    -- Columnas Generadas Virtuales para filtrar rápido por Mes/Año en Kanban y Dashboard
    `anio_creacion` SMALLINT GENERATED ALWAYS AS (YEAR(`fecha_creacion`)) STORED,
    `mes_creacion` TINYINT GENERATED ALWAYS AS (MONTH(`fecha_creacion`)) STORED,
    -- Índices de Alto Rendimiento
    INDEX `idx_kanban_filtro` (`estado_columna_kanban`, `archivado_kanban`, `anio_creacion`, `mes_creacion`),
    INDEX `idx_dashboard_depto_fecha` (`id_departamento`, `fecha_creacion`),
    INDEX `idx_dashboard_equipo` (`id_equipo`),
    CONSTRAINT `fk_tck_creador` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios`(`id_usuario`),
    CONSTRAINT `fk_tck_afectado` FOREIGN KEY (`id_usuario_afectado`) REFERENCES `usuarios`(`id_usuario`),
    CONSTRAINT `fk_tck_depto` FOREIGN KEY (`id_departamento`) REFERENCES `departamentos`(`id_departamento`),
    CONSTRAINT `fk_tck_equipo` FOREIGN KEY (`id_equipo`) REFERENCES `equipos`(`id_equipo`),
    CONSTRAINT `fk_tck_catfalla` FOREIGN KEY (`id_categoria_falla`) REFERENCES `categorias_falla`(`id_categoria_falla`),
    CONSTRAINT `fk_tck_jefe` FOREIGN KEY (`id_jefe_asignador`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `ticket_asignaciones` (
    `id_asignacion` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_ticket` INT UNSIGNED NOT NULL,
    `id_personal_soporte` INT UNSIGNED NOT NULL,
    `rol_en_ticket` ENUM('Tecnico_Principal', 'Tecnico_Apoyo') NOT NULL DEFAULT 'Tecnico_Principal',
    `fue_autoasignado` TINYINT(1) NOT NULL DEFAULT 0,
    `activo_en_ticket` TINYINT(1) NOT NULL DEFAULT 1,
    `fecha_asignacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_asig_tecnico` (`id_personal_soporte`, `activo_en_ticket`),
    CONSTRAINT `fk_asig_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets`(`id_ticket`) ON DELETE CASCADE,
    CONSTRAINT `fk_asig_tecnico` FOREIGN KEY (`id_personal_soporte`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `ticket_comentarios` (
    `id_comentario` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_ticket` INT UNSIGNED NOT NULL,
    `id_usuario_autor` INT UNSIGNED NOT NULL,
    `mensaje_texto` TEXT NOT NULL,
    `es_nota_interna_privada` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Solo soporte, 0=Visible al usuario común',
    `fecha_comentario` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_com_ticket` (`id_ticket`),
    CONSTRAINT `fk_com_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets`(`id_ticket`) ON DELETE CASCADE,
    CONSTRAINT `fk_com_autor` FOREIGN KEY (`id_usuario_autor`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `ticket_adjuntos` (
    `id_adjunto` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_ticket` INT UNSIGNED NOT NULL,
    `id_usuario_subida` INT UNSIGNED NOT NULL,
    `nombre_archivo_original` VARCHAR(150) NOT NULL,
    `extension_archivo` VARCHAR(10) NOT NULL,
    `peso_archivo_kb` INT UNSIGNED NOT NULL,
    `ruta_almacenamiento` VARCHAR(255) NOT NULL,
    `fecha_subida` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_adj_ticket` FOREIGN KEY (`id_ticket`) REFERENCES `tickets`(`id_ticket`) ON DELETE CASCADE,
    CONSTRAINT `fk_adj_usuario` FOREIGN KEY (`id_usuario_subida`) REFERENCES `usuarios`(`id_usuario`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =========================================================================
-- 6. MÓDULO 2 y 6: AUDITORÍA (LOGS, CHANGELOGS) E HISTORIAL DE REPORTES
-- =========================================================================

CREATE TABLE `logs_sistema_accesos` (
    `id_log_acceso` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_usuario` INT UNSIGNED NULL,
    `username_intento` VARCHAR(60) NOT NULL,
    `tipo_evento` ENUM('Login_Exitoso', 'Login_Fallido', 'Logout', 'Bloqueo_Seguridad', 'Navegacion_Modulo') NOT NULL,
    `id_modulo` TINYINT UNSIGNED NULL,
    `descripcion_accion` VARCHAR(255) NOT NULL,
    `direccion_ip` VARCHAR(45) NOT NULL,
    `navegador_user_agent` VARCHAR(255) NULL,
    `fecha_evento` DATE GENERATED ALWAYS AS (DATE(`fecha_hora`)) STORED,
    `hora_evento` TIME GENERATED ALWAYS AS (TIME(`fecha_hora`)) STORED,
    `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_fecha_tipo` (`fecha_evento`, `tipo_evento`),
    CONSTRAINT `fk_log_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL,
    CONSTRAINT `fk_log_modulo` FOREIGN KEY (`id_modulo`) REFERENCES `modulos_sistema`(`id_modulo`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `changelogs_auditoria` (
    `id_changelog` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_usuario_autor` INT UNSIGNED NULL,
    `tabla_afectada` VARCHAR(50) NOT NULL,
    `id_registro_afectado` INT UNSIGNED NOT NULL,
    `tipo_operacion` ENUM('CREACION', 'MODIFICACION', 'ELIMINACION_LOGICA') NOT NULL,
    `campo_modificado` VARCHAR(60) NOT NULL,
    `valor_anterior` TEXT NULL,
    `valor_nuevo` TEXT NULL,
    `motivo_cambio` VARCHAR(255) NULL,
    `fecha_hora_cambio` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_changelog_entidad` (`tabla_afectada`, `id_registro_afectado`),
    CONSTRAINT `fk_chg_autor` FOREIGN KEY (`id_usuario_autor`) REFERENCES `usuarios`(`id_usuario`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `historial_reportes_generados` (
    `id_reporte` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `id_usuario_solicitante` INT UNSIGNED NOT NULL,
    `tipo_reporte` ENUM('Errores_Por_Departamento', 'Rendimiento_Tecnicos', 'Equipos_Con_Mas_Fallas', 'Resumen_Mensual') NOT NULL,
    `fecha_inicio_filtro` DATE NOT NULL,
    `fecha_fin_filtro` DATE NOT NULL,
    `id_departamento_filtro` SMALLINT UNSIGNED NULL COMMENT 'NULL equivale a Todos los departamentos',
    `formato_exportacion` ENUM('PDF', 'Excel_XLSX', 'CSV') NOT NULL,
    `fecha_generacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_rep_usuario` FOREIGN KEY (`id_usuario_solicitante`) REFERENCES `usuarios`(`id_usuario`) ON DELETE CASCADE,
    CONSTRAINT `fk_rep_depto` FOREIGN KEY (`id_departamento_filtro`) REFERENCES `departamentos`(`id_departamento`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================================
-- 7. TRIGGER DE AUDITORÍA AUTOMÁTICA EN KANBAN (SEGURIDAD E INTEGRIDAD)
-- =========================================================================

DELIMITER $$
CREATE TRIGGER `trg_ticket_kanban_changelog`
AFTER UPDATE ON `tickets`
FOR EACH ROW
BEGIN
    -- Auditar cambios de columna en el Tablero Kanban
    IF OLD.estado_columna_kanban <> NEW.estado_columna_kanban THEN
        INSERT INTO `changelogs_auditoria` 
        (`id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`, `motivo_cambio`)
        VALUES 
        (NEW.id_jefe_asignador, 'tickets', NEW.id_ticket, 'MODIFICACION', 'estado_columna_kanban', OLD.estado_columna_kanban, NEW.estado_columna_kanban, NEW.motivo_pausa_espera);
    END IF;

    -- Auditar cambios de prioridad oficial
    IF OLD.prioridad_oficial <> NEW.prioridad_oficial THEN
        INSERT INTO `changelogs_auditoria` 
        (`id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`)
        VALUES 
        (NEW.id_jefe_asignador, 'tickets', NEW.id_ticket, 'MODIFICACION', 'prioridad_oficial', OLD.prioridad_oficial, NEW.prioridad_oficial);
    END IF;
END$$
DELIMITER ;

-- =========================================================================
-- 8. VISTA CONSOLIDADA PARA EL MÓDULO 6 (DASHBOARD ESTADÍSTICO)
-- =========================================================================

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
    -- Cálculo de tiempos en minutos
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

-- =========================================================================
-- 9. DATOS SEMILLA (SEEDERS) PARA LA DEFENSA DEL MIÉRCOLES
-- =========================================================================

INSERT INTO `roles` (`id_rol`, `slug_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Superadmin', 'Superadministrador', 'Control total de seguridad, permisos, cuentas de personal, registros de acceso e historial de cambios'),
(2, 'Jefe_Soporte', 'Jefe de Departamento de Soporte', 'Acepta y asigna solicitudes en el tablero de trabajo global, gestiona equipos y consulta el panel de estadísticas institucional'),
(3, 'Personal_Soporte', 'Personal de Soporte Técnico', 'Atiende sus propios casos asignados en el tablero de trabajo y consulta sus indicadores personales'),
(4, 'Usuario_Comun', 'Usuario Común', 'Registra solicitudes de soporte técnico y visualiza el progreso de atención en su portal');

INSERT INTO `modulos_sistema` (`id_modulo`, `clave_modulo`, `nombre_modulo`, `icono_argon`, `orden_menu`) VALUES
(1, 'Mod1_Auth', 'Mi cuenta y perfil', 'ni ni-single-02', 6),
(2, 'Mod2_Admin', 'Seguridad, usuarios y auditoría', 'ni ni-key-25', 5),
(3, 'Mod3_Inventario', 'Departamentos y equipos', 'ni ni-laptop', 4),
(4, 'Mod4_Portal', 'Mis solicitudes de soporte', 'ni ni-send', 1),
(5, 'Mod5_Kanban', 'Tablero de trabajo', 'ni ni-bullet-list-67', 2),
(6, 'Mod6_Dashboard', 'Panel de estadísticas', 'ni ni-chart-bar-32', 3);

-- Permisos por Rol (Matriz RBAC)
INSERT INTO `roles_permisos` (`id_rol`, `id_modulo`, `permiso_ver`, `permiso_crear`, `permiso_editar`, `permiso_eliminar`, `permiso_asignar_tickets`) VALUES
-- Superadmin
(1, 1, 1, 1, 1, 1, 1), (1, 2, 1, 1, 1, 1, 1), (1, 3, 1, 1, 1, 1, 1), (1, 5, 1, 1, 1, 1, 1), (1, 6, 1, 1, 1, 1, 1),
-- Jefe de Soporte
(2, 1, 1, 0, 1, 0, 0), (2, 3, 1, 1, 1, 0, 0), (2, 5, 1, 1, 1, 0, 1), (2, 6, 1, 1, 0, 0, 0),
-- Personal de Soporte
(3, 1, 1, 0, 1, 0, 0), (3, 5, 1, 0, 1, 0, 0), (3, 6, 1, 0, 0, 0, 0),
-- Usuario Común
(4, 1, 1, 0, 1, 0, 0), (4, 4, 1, 1, 0, 0, 0);

INSERT INTO `departamentos` (`id_departamento`, `codigo_departamento`, `nombre_departamento`, `sede_edificio`, `piso_oficina`, `numero_oficina`, `telefono_extension`) VALUES
(1, 'DEP-SOP', 'Departamento de Soporte Técnico e Informática', 'Sede Central', 'Piso 1', 'Oficina 102', 'Ext-102'),
(2, 'DEP-CONT', 'Departamento de Contabilidad y Finanzas', 'Sede Central', 'Piso 2', 'Oficina 204', 'Ext-204'),
(3, 'DEP-RRHH', 'Departamento de Recursos Humanos', 'Sede Central', 'Piso 2', 'Oficina 208', 'Ext-208'),
(4, 'DEP-OPER', 'Dirección de Operaciones y Atención', 'Edificio Anexo', 'Planta Baja', 'Oficina PB-04', 'Ext-304');

-- Contraseña de todos los usuarios de prueba: "123456" (Hash BCRYPT estándar compatible con password_verify de PHP)
INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `id_departamento`, `cedula_empleado`, `username`, `correo_institucional`, `password_hash`, `nombres`, `apellidos`, `cargo_empleado`, `telefono_extension`, `pregunta_seguridad`, `respuesta_seguridad_hash`) VALUES
(1, 1, 1, 'V-15000111', 'superadmin', 'admin@comunidad.gob.ve', '$2y$10$SX/Yc9cl80v39k2YxiLvUOylYJOmV96iC5f21lKg47xttUDuuJSoG', 'Carlos', 'Mendoza', 'Administrador de Seguridad TI', '101', '¿Nombre de primera mascota?', '$2y$10$2SBF/fmFmmw9v6PhIPOyiOIMKL96e8TW01gVs4e1Q.HxV9fx494g6'),
(2, 2, 1, 'V-18222333', 'jefesoporte', 'jefe.soporte@comunidad.gob.ve', '$2y$10$SX/Yc9cl80v39k2YxiLvUOylYJOmV96iC5f21lKg47xttUDuuJSoG', 'Roberto', 'Salazar', 'Jefe de Soporte Técnico', '102', '¿Ciudad de nacimiento de su madre?', '$2y$10$5DvpiUbadVl4OI/2VoGlweHNLI4R2RnkZmoYDSki9.ZwEWeMkA1e6'),
(3, 3, 1, 'V-22444555', 'tecnico1', 'miguel.torres@comunidad.gob.ve', '$2y$10$SX/Yc9cl80v39k2YxiLvUOylYJOmV96iC5f21lKg47xttUDuuJSoG', 'Miguel', 'Torres', 'Técnico de Soporte Nivel 2', '103', '¿Equipo de fútbol favorito?', '$2y$10$eJHP9dwHg0IddORjjuhIduqkWb2RG7tQOaGh.O8o1ZfN24pRyoVEW'),
(4, 3, 1, 'V-24666777', 'tecnico2', 'laura.gomez@comunidad.gob.ve', '$2y$10$SX/Yc9cl80v39k2YxiLvUOylYJOmV96iC5f21lKg47xttUDuuJSoG', 'Laura', 'Gómez', 'Especialista en Redes e Impresoras', '104', '¿Nombre de su abuelo?', '$2y$10$IKxAgN.IbJi7eJpfTUqTk.a7km2hHs4JAfTvhdxkZRHikOE.nqHli'),
(5, 4, 2, 'V-19888999', 'mrodriguez', 'maria.rodriguez@comunidad.gob.ve', '$2y$10$SX/Yc9cl80v39k2YxiLvUOylYJOmV96iC5f21lKg47xttUDuuJSoG', 'María', 'Rodríguez', 'Analista Contable Senior', '204', '¿Nombre de su esposo?', '$2y$10$TjgTXJcU3liMD4F0ru8/JOzBTxtkPndBDG6VTVBo1COFxgRFp7eTm'),
(6, 4, 3, 'V-21333444', 'jperez', 'juan.perez@comunidad.gob.ve', '$2y$10$SX/Yc9cl80v39k2YxiLvUOylYJOmV96iC5f21lKg47xttUDuuJSoG', 'Juan', 'Pérez', 'Coordinador de Nómina', '208', '¿Primer colegio donde estudió?', '$2y$10$asdQ46y4PTKNoESLJmGNxevsCINkZMDxW1VPVVbXUDBZY6MAVfWfi');

INSERT INTO `equipos` (`id_equipo`, `id_departamento`, `id_usuario_asignado`, `codigo_bien`, `numero_serie`, `categoria_equipo`, `marca`, `modelo`, `procesador_cpu`, `memoria_ram_gb`, `tipo_almacenamiento`, `capacidad_almacenamiento_gb`, `sistema_operativo`, `direccion_ip_fija`, `es_equipo_compartido`, `estado_operativo`) VALUES
(1, 2, 5, 'INV-2026-001', 'SN-HP8839201', 'PC_Escritorio', 'HP', 'ProDesk 400 G7', 'Intel Core i5-10500', 16, 'SSD', 512, 'Windows 11 Pro', '192.168.1.20', 0, 'Operativo'),
(2, 2, NULL, 'INV-2026-002', 'SN-EPSON9921', 'Impresora', 'Epson', 'EcoTank L5290', NULL, NULL, 'No_Aplica', NULL, 'Firmware Red', '192.168.1.25', 1, 'En_Reparacion'),
(3, 3, 6, 'INV-2026-003', 'SN-DELL77412', 'Laptop', 'Dell', 'Latitude 5420', 'Intel Core i7-1185G7', 16, 'NVMe', 512, 'Windows 10 Pro', '192.168.1.40', 0, 'Operativo'),
(4, 4, NULL, 'INV-2026-004', 'SN-CISCO1120', 'Router_Switch', 'Cisco', 'Catalyst 2960-X', NULL, NULL, 'No_Aplica', NULL, 'Cisco IOS', '192.168.1.2', 1, 'Operativo');

INSERT INTO `categorias_falla` (`id_categoria_falla`, `rama_general`, `subcategoria`, `tiempo_estimado_minutos`) VALUES
(1, 'Hardware', 'No enciende / Falla de Fuente de Poder', 180),
(2, 'Software', 'Error de Sistema Contable / Cierre inesperado', 90),
(3, 'Red_Internet', 'Sin conexión a la Intranet / IP conflictiva', 60),
(4, 'Impresoras', 'Atasco de papel o falla de impresión en red', 60),
(5, 'Accesos_Cuentas', 'Bloqueo de cuenta o restablecimiento de credenciales', 30);

-- Tickets distribuidos en las 5 columnas del Kanban para demostración en vivo
INSERT INTO `tickets` (`id_ticket`, `codigo_ticket`, `canal_creacion`, `id_usuario_creador`, `id_usuario_afectado`, `id_departamento`, `id_equipo`, `id_categoria_falla`, `titulo_asunto`, `descripcion_problematica`, `prioridad_sugerida`, `estado_aceptacion_jefe`, `prioridad_oficial`, `estado_columna_kanban`, `motivo_pausa_espera`, `id_jefe_asignador`, `diagnostico_real_tecnico`, `solucion_aplicada_tecnico`, `requirio_cambio_pieza`, `detalle_pieza_cambiada`, `confirmado_por_usuario`, `fecha_creacion`, `fecha_aceptacion_asignacion`, `fecha_inicio_atencion`, `fecha_resolucion_tecnica`) VALUES
-- Columna 1: Por Aceptar (Solo lo ve el Jefe de Soporte y María en su portal)
(1, 'TCK-2026-0001', 'Portal_Web_Usuario', 5, 5, 2, 1, 2, 'El sistema administrativo se cierra al generar balance', 'Al intentar exportar el cierre del mes en mi PC, aparece un error de memoria y se cierra el programa.', 'Alta', 'Pendiente_Revision', 'Media', '1_Por_Aceptar', NULL, NULL, NULL, NULL, 0, NULL, 'Pendiente', DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL, NULL, NULL),
-- Columna 2: Asignado (Asignado a Miguel - tecnico1)
(2, 'TCK-2026-0002', 'Portal_Web_Usuario', 6, 6, 3, 3, 3, 'Sin acceso a la carpeta compartida de Nómina', 'La laptop conecta al Wi-Fi pero no me deja entrar al servidor local de Recursos Humanos.', 'Alta', 'Aceptado', 'Alta', '2_Asignado', NULL, 2, NULL, NULL, 0, NULL, 'Pendiente', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR), NULL, NULL),
-- Columna 3: En Proceso (Atendido por Laura - tecnico2)
(3, 'TCK-2026-0003', 'Portal_Web_Usuario', 5, 5, 2, 2, 4, 'Impresora de Contabilidad no recibe órdenes por red', 'Nadie en la oficina 204 puede imprimir desde ayer en la tarde, parpadea el bombillo de red.', 'Media', 'Aceptado', 'Alta', '3_En_Proceso', NULL, 2, 'Desconfiguración de puerta de enlace en la impresora.', NULL, 0, NULL, 'Pendiente', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 22 HOUR), DATE_SUB(NOW(), INTERVAL 20 HOUR), NULL),
-- Columna 4: En Espera (Asignado a Miguel - esperando repuesto)
(4, 'TCK-2026-0004', 'Llamada_Telefonica', 2, 6, 3, 3, 1, 'Cargador de Laptop Dell no suministra energía', 'El equipo se apagó y el adaptador de corriente huele a quemado.', 'Alta', 'Aceptado', 'Critica', '4_En_Espera', 'En espera de adaptador Dell 65W solicitado a almacén', 2, 'Cortocircuito en fuente externa.', NULL, 1, 'Cargador Dell USB-C 65W', 'Pendiente', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 70 HOUR), DATE_SUB(NOW(), INTERVAL 68 HOUR), NULL),
-- Columna 5: Resuelto (Para que el Dashboard tenga métricas reales)
(5, 'TCK-2026-0005', 'Portal_Web_Usuario', 5, 5, 2, 1, 5, 'Restablecimiento de permisos en módulo tributario', 'No podía ingresar con mi usuario tras la actualización del lunes.', 'Media', 'Aceptado', 'Media', '5_Resuelto', NULL, 2, 'Permisos de sesión caducados.', 'Se refrescaron las credenciales y caché DNS local.', 0, NULL, 'Confirmado_Conforme', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 119 HOUR), DATE_SUB(NOW(), INTERVAL 118 HOUR), DATE_SUB(NOW(), INTERVAL 117 HOUR));

INSERT INTO `ticket_asignaciones` (`id_ticket`, `id_personal_soporte`, `rol_en_ticket`, `fue_autoasignado`, `activo_en_ticket`) VALUES
(2, 3, 'Tecnico_Principal', 0, 1),
(3, 4, 'Tecnico_Principal', 0, 1),
(4, 3, 'Tecnico_Principal', 0, 1),
(5, 3, 'Tecnico_Principal', 0, 1);

INSERT INTO `ticket_comentarios` (`id_ticket`, `id_usuario_autor`, `mensaje_texto`, `es_nota_interna_privada`) VALUES
(3, 4, 'Hola María, ya estoy en el piso 2 revisando la configuración IP de la impresora Epson.', 0),
(3, 4, 'Nota técnica: El switch del piso 2 tenía bloqueada la MAC de la impresora.', 1);

SET FOREIGN_KEY_CHECKS = 1;