<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo de Auditoría y Seguridad Institucional
 * Gestiona las tablas logs_sistema_accesos, changelogs_auditoria e historial_reportes_generados
 */
class Auditoria
{
    private PDO $db;

    public function __construct(?PDO $conexion = null)
    {
        $this->db = $conexion ?? Database::conectar();
    }

    /**
     * Registra un evento en la tabla logs_sistema_accesos mediante sentencia preparada
     */
    public function registrarAcceso(
        ?int $idUsuario,
        string $usernameIntento,
        string $tipoEvento,
        string $descripcionAccion,
        ?int $idModulo = 1
    ): bool {
        $eventosPermitidos = [
            'Login_Exitoso',
            'Login_Fallido',
            'Logout',
            'Bloqueo_Seguridad',
            'Navegacion_Modulo'
        ];
        if (!in_array($tipoEvento, $eventosPermitidos, true)) {
            $tipoEvento = 'Navegacion_Modulo';
        }

        $sql = "INSERT INTO logs_sistema_accesos (
                    id_usuario,
                    username_intento,
                    tipo_evento,
                    id_modulo,
                    descripcion_accion,
                    direccion_ip,
                    navegador_user_agent,
                    fecha_hora
                ) VALUES (
                    :id_usuario,
                    :username_intento,
                    :tipo_evento,
                    :id_modulo,
                    :descripcion_accion,
                    :direccion_ip,
                    :navegador_user_agent,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_usuario'           => $idUsuario,
            ':username_intento'     => mb_substr(trim($usernameIntento), 0, 60, 'UTF-8'),
            ':tipo_evento'          => $tipoEvento,
            ':id_modulo'            => $idModulo,
            ':descripcion_accion'   => mb_substr(trim($descripcionAccion), 0, 255, 'UTF-8'),
            ':direccion_ip'         => obtener_ip_cliente(),
            ':navegador_user_agent' => obtener_navegador_cliente()
        ]);
    }

    /**
     * Registra un cambio de datos en la tabla changelogs_auditoria
     */
    public function registrarCambio(
        ?int $idUsuarioAutor,
        string $tablaAfectada,
        int $idRegistroAfectado,
        string $tipoOperacion,
        string $campoModificado,
        ?string $valorAnterior = null,
        ?string $valorNuevo = null,
        ?string $motivoCambio = null
    ): bool {
        $operacionesPermitidas = ['CREACION', 'MODIFICACION', 'ELIMINACION_LOGICA'];
        if (!in_array($tipoOperacion, $operacionesPermitidas, true)) {
            $tipoOperacion = 'MODIFICACION';
        }

        $sql = "INSERT INTO changelogs_auditoria (
                    id_usuario_autor,
                    tabla_afectada,
                    id_registro_afectado,
                    tipo_operacion,
                    campo_modificado,
                    valor_anterior,
                    valor_nuevo,
                    motivo_cambio,
                    fecha_hora_cambio
                ) VALUES (
                    :id_usuario_autor,
                    :tabla_afectada,
                    :id_registro_afectado,
                    :tipo_operacion,
                    :campo_modificado,
                    :valor_anterior,
                    :valor_nuevo,
                    :motivo_cambio,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_usuario_autor'     => $idUsuarioAutor,
            ':tabla_afectada'       => mb_substr(trim($tablaAfectada), 0, 50, 'UTF-8'),
            ':id_registro_afectado' => $idRegistroAfectado,
            ':tipo_operacion'       => $tipoOperacion,
            ':campo_modificado'     => mb_substr(trim($campoModificado), 0, 60, 'UTF-8'),
            ':valor_anterior'       => $valorAnterior,
            ':valor_nuevo'          => $valorNuevo,
            ':motivo_cambio'        => $motivoCambio !== null ? mb_substr(trim($motivoCambio), 0, 255, 'UTF-8') : null
        ]);
    }

    /**
     * Obtiene los últimos registros de acceso de un usuario específico para su vista de cuenta
     */
    public function obtenerHistorialAccesosUsuario(int $idUsuario, string $username, int $limite = 25): array
    {
        $sql = "SELECT
                    l.id_log_acceso,
                    l.username_intento,
                    l.tipo_evento,
                    l.descripcion_accion,
                    l.direccion_ip,
                    l.navegador_user_agent,
                    l.fecha_evento,
                    l.hora_evento,
                    l.fecha_hora,
                    m.nombre_modulo
                FROM logs_sistema_accesos l
                LEFT JOIN modulos_sistema m ON l.id_modulo = m.id_modulo
                WHERE l.id_usuario = :id_usuario OR l.username_intento = :username
                ORDER BY l.id_log_acceso DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtiene las modificaciones de auditoría asociadas a un usuario
     */
    public function obtenerCambiosUsuario(int $idUsuario, int $limite = 15): array
    {
        $sql = "SELECT
                    c.id_changelog,
                    c.tabla_afectada,
                    c.id_registro_afectado,
                    c.tipo_operacion,
                    c.campo_modificado,
                    c.valor_anterior,
                    c.valor_nuevo,
                    c.motivo_cambio,
                    c.fecha_hora_cambio,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_autor
                FROM changelogs_auditoria c
                LEFT JOIN usuarios u ON c.id_usuario_autor = u.id_usuario
                WHERE (c.tabla_afectada = 'usuarios' AND c.id_registro_afectado = :id_reg)
                   OR c.id_usuario_autor = :id_autor
                ORDER BY c.id_changelog DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_reg', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':id_autor', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Consulta global de registros de acceso con filtros dinámicos para el módulo de seguridad
     */
    public function listarAccesosGlobal(array $filtros = [], int $limite = 1000): array
    {
        $condiciones = ['1 = 1'];
        $parametros = [];

        if (!empty($filtros['tipo_evento'])) {
            $condiciones[] = 'l.tipo_evento = :tipo_evento';
            $parametros[':tipo_evento'] = $filtros['tipo_evento'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $condiciones[] = 'l.fecha_evento >= :fecha_desde';
            $parametros[':fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $condiciones[] = 'l.fecha_evento <= :fecha_hasta';
            $parametros[':fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['busqueda'])) {
            $condiciones[] = '(l.username_intento LIKE :busq1 OR l.descripcion_accion LIKE :busq2 OR l.direccion_ip LIKE :busq3)';
            $termino = '%' . trim($filtros['busqueda']) . '%';
            $parametros[':busq1'] = $termino;
            $parametros[':busq2'] = $termino;
            $parametros[':busq3'] = $termino;
        }

        $whereSql = implode(' AND ', $condiciones);

        $sql = "SELECT
                    l.id_log_acceso,
                    l.id_usuario,
                    l.username_intento,
                    l.tipo_evento,
                    l.id_modulo,
                    l.descripcion_accion,
                    l.direccion_ip,
                    l.navegador_user_agent,
                    l.fecha_evento,
                    l.hora_evento,
                    l.fecha_hora,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_completo,
                    r.nombre_rol,
                    m.nombre_modulo
                FROM logs_sistema_accesos l
                LEFT JOIN usuarios u ON l.id_usuario = u.id_usuario
                LEFT JOIN roles r ON u.id_rol = r.id_rol
                LEFT JOIN modulos_sistema m ON l.id_modulo = m.id_modulo
                WHERE {$whereSql}
                ORDER BY l.id_log_acceso DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Consulta global del historial de cambios con filtros para el módulo de seguridad
     */
    public function listarCambiosGlobal(array $filtros = [], int $limite = 1000): array
    {
        $condiciones = ['1 = 1'];
        $parametros = [];

        if (!empty($filtros['tabla_afectada'])) {
            $condiciones[] = 'c.tabla_afectada = :tabla_afectada';
            $parametros[':tabla_afectada'] = $filtros['tabla_afectada'];
        }

        if (!empty($filtros['tipo_operacion'])) {
            $condiciones[] = 'c.tipo_operacion = :tipo_operacion';
            $parametros[':tipo_operacion'] = $filtros['tipo_operacion'];
        }

        if (!empty($filtros['busqueda'])) {
            $condiciones[] = '(c.campo_modificado LIKE :busq1 OR c.valor_nuevo LIKE :busq2 OR c.motivo_cambio LIKE :busq3)';
            $termino = '%' . trim($filtros['busqueda']) . '%';
            $parametros[':busq1'] = $termino;
            $parametros[':busq2'] = $termino;
            $parametros[':busq3'] = $termino;
        }

        $whereSql = implode(' AND ', $condiciones);

        $sql = "SELECT
                    c.id_changelog,
                    c.id_usuario_autor,
                    c.tabla_afectada,
                    c.id_registro_afectado,
                    c.tipo_operacion,
                    c.campo_modificado,
                    c.valor_anterior,
                    c.valor_nuevo,
                    c.motivo_cambio,
                    c.fecha_hora_cambio,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_autor,
                    u.username AS username_autor
                FROM changelogs_auditoria c
                LEFT JOIN usuarios u ON c.id_usuario_autor = u.id_usuario
                WHERE {$whereSql}
                ORDER BY c.id_changelog DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        foreach ($parametros as $clave => $valor) {
            $stmt->bindValue($clave, $valor, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Registra la generación o exportación de un reporte estadístico
     */
    public function registrarReporteGenerado(
        int $idUsuarioSolicitante,
        string $tipoReporte,
        string $fechaInicio,
        string $fechaFin,
        ?int $idDepartamento,
        string $formatoExportacion
    ): bool {
        $sql = "INSERT INTO historial_reportes_generados (
                    id_usuario_solicitante,
                    tipo_reporte,
                    fecha_inicio_filtro,
                    fecha_fin_filtro,
                    id_departamento_filtro,
                    formato_exportacion,
                    fecha_generacion
                ) VALUES (
                    :id_usuario_solicitante,
                    :tipo_reporte,
                    :fecha_inicio_filtro,
                    :fecha_fin_filtro,
                    :id_departamento_filtro,
                    :formato_exportacion,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_usuario_solicitante' => $idUsuarioSolicitante,
            ':tipo_reporte'           => $tipoReporte,
            ':fecha_inicio_filtro'    => $fechaInicio,
            ':fecha_fin_filtro'       => $fechaFin,
            ':id_departamento_filtro' => $idDepartamento,
            ':formato_exportacion'    => $formatoExportacion
        ]);
    }

    /**
     * Genera una copia de seguridad SQL completa y nativa usando exclusivamente PDO
     * Incluye las 15 tablas en orden de integridad referencial, la vista vw_metricas_dashboard
     * y el disparador trg_ticket_kanban_changelog.
     */
    public function generarRespaldoBaseDatos(string $directorioBackups): array
    {
        $dirLimpio = rtrim($directorioBackups, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($dirLimpio)) {
            mkdir($dirLimpio, 0755, true);
        }

        $marcaTiempo   = date('Ymd_His');
        $nombreArchivo = "respaldo_sistema_{$marcaTiempo}.sql";
        $rutaCompleta  = $dirLimpio . $nombreArchivo;

        $versionMysql = (string)$this->db->query('SELECT VERSION()')->fetchColumn();
        $nombreBd     = defined('DB_NAME') ? DB_NAME : 'db_tickets_soporte';

        // Orden estricto de las 15 tablas respetando dependencias de claves foráneas
        $ordenTablas = [
            'roles',
            'modulos_sistema',
            'roles_permisos',
            'departamentos',
            'usuarios',
            'solicitudes_recuperacion_clave',
            'equipos',
            'categorias_falla',
            'tickets',
            'ticket_asignaciones',
            'ticket_comentarios',
            'ticket_adjuntos',
            'logs_sistema_accesos',
            'changelogs_auditoria',
            'historial_reportes_generados'
        ];

        // Verificar si existen tablas base adicionales en el esquema
        $stmtTablas = $this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tablasEnEsquema = [];
        while ($filaT = $stmtTablas->fetch(PDO::FETCH_NUM)) {
            $tablasEnEsquema[] = (string)$filaT[0];
        }

        $tablasVolcar = [];
        foreach ($ordenTablas as $tOrden) {
            if (in_array($tOrden, $tablasEnEsquema, true)) {
                $tablasVolcar[] = $tOrden;
            }
        }
        foreach ($tablasEnEsquema as $tExtra) {
            if (!in_array($tExtra, $tablasVolcar, true)) {
                $tablasVolcar[] = $tExtra;
            }
        }

        $sql = [];
        $sql[] = '-- =========================================================================';
        $sql[] = '-- COPIA DE SEGURIDAD INSTITUCIONAL DE BASE DE DATOS';
        $sql[] = '-- Base de datos: ' . $nombreBd;
        $sql[] = '-- Fecha y hora de generación: ' . date('Y-m-d H:i:s');
        $sql[] = '-- Motor SQL: ' . $versionMysql . ' | Generador: PDO Nativo PHP ' . PHP_VERSION;
        $sql[] = '-- =========================================================================';
        $sql[] = '';
        $sql[] = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;';
        $sql[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $sql[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
        $sql[] = '';

        $totalFilasRespaldadas = 0;

        foreach ($tablasVolcar as $tabla) {
            $sql[] = '-- -------------------------------------------------------------------------';
            $sql[] = "-- Estructura y datos de la tabla `{$tabla}`";
            $sql[] = '-- -------------------------------------------------------------------------';
            $sql[] = "DROP TABLE IF EXISTS `{$tabla}`;";

            $stmtCreate = $this->db->query("SHOW CREATE TABLE `{$tabla}`");
            $filaCreate = $stmtCreate->fetch(PDO::FETCH_ASSOC);
            $ddlTabla   = (string)($filaCreate['Create Table'] ?? array_values($filaCreate)[1] ?? '');
            $sql[]      = $ddlTabla . ';';
            $sql[]      = '';

            // Identificar columnas físicas insertables (excluyendo columnas GENERATED STORED/VIRTUAL)
            $stmtCols = $this->db->query("SHOW COLUMNS FROM `{$tabla}`");
            $columnasInsertables = [];
            while ($col = $stmtCols->fetch(PDO::FETCH_ASSOC)) {
                $extra = strtoupper((string)($col['Extra'] ?? ''));
                if (!str_contains($extra, 'GENERATED')) {
                    $columnasInsertables[] = (string)$col['Field'];
                }
            }

            if (!empty($columnasInsertables)) {
                $listaColsSql = implode(', ', array_map(static fn(string $c): string => "`{$c}`", $columnasInsertables));
                $stmtDatos    = $this->db->query("SELECT {$listaColsSql} FROM `{$tabla}`");
                $filasTabla   = $stmtDatos->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($filasTabla)) {
                    $lotes = array_chunk($filasTabla, 100);
                    foreach ($lotes as $lote) {
                        $valoresLote = [];
                        foreach ($lote as $filaDato) {
                            $camposEscapados = [];
                            foreach ($columnasInsertables as $nombreCol) {
                                $val = $filaDato[$nombreCol] ?? null;
                                if ($val === null) {
                                    $camposEscapados[] = 'NULL';
                                } else {
                                    $camposEscapados[] = $this->db->quote((string)$val);
                                }
                            }
                            $valoresLote[] = '(' . implode(', ', $camposEscapados) . ')';
                            $totalFilasRespaldadas++;
                        }
                        $sql[] = "INSERT INTO `{$tabla}` ({$listaColsSql}) VALUES\n" . implode(",\n", $valoresLote) . ';';
                    }
                    $sql[] = '';
                }
            }
        }

        // Incluir el disparador de auditoría trg_ticket_kanban_changelog
        $sql[] = '-- -------------------------------------------------------------------------';
        $sql[] = '-- Disparador de auditoría automática trg_ticket_kanban_changelog';
        $sql[] = '-- -------------------------------------------------------------------------';
        $sql[] = 'DROP TRIGGER IF EXISTS `trg_ticket_kanban_changelog`;';
        $sql[] = 'DELIMITER $$';
        $sql[] = 'CREATE TRIGGER `trg_ticket_kanban_changelog`';
        $sql[] = 'AFTER UPDATE ON `tickets`';
        $sql[] = 'FOR EACH ROW';
        $sql[] = 'BEGIN';
        $sql[] = '    IF OLD.estado_columna_kanban <> NEW.estado_columna_kanban THEN';
        $sql[] = '        INSERT INTO `changelogs_auditoria`';
        $sql[] = '        (`id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`, `motivo_cambio`)';
        $sql[] = '        VALUES';
        $sql[] = "        (NEW.id_jefe_asignador, 'tickets', NEW.id_ticket, 'MODIFICACION', 'estado_columna_kanban', OLD.estado_columna_kanban, NEW.estado_columna_kanban, NEW.motivo_pausa_espera);";
        $sql[] = '    END IF;';
        $sql[] = '    IF OLD.prioridad_oficial <> NEW.prioridad_oficial THEN';
        $sql[] = '        INSERT INTO `changelogs_auditoria`';
        $sql[] = '        (`id_usuario_autor`, `tabla_afectada`, `id_registro_afectado`, `tipo_operacion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`)';
        $sql[] = '        VALUES';
        $sql[] = "        (NEW.id_jefe_asignador, 'tickets', NEW.id_ticket, 'MODIFICACION', 'prioridad_oficial', OLD.prioridad_oficial, NEW.prioridad_oficial);";
        $sql[] = '    END IF;';
        $sql[] = 'END$$';
        $sql[] = 'DELIMITER ;';
        $sql[] = '';

        // Incluir la vista consolidada vw_metricas_dashboard
        $sql[] = '-- -------------------------------------------------------------------------';
        $sql[] = '-- Vista consolidada de estadísticas vw_metricas_dashboard';
        $sql[] = '-- -------------------------------------------------------------------------';
        $sql[] = 'DROP VIEW IF EXISTS `vw_metricas_dashboard`;';
        $sql[] = 'CREATE OR REPLACE VIEW `vw_metricas_dashboard` AS';
        $sql[] = 'SELECT';
        $sql[] = '    t.id_ticket,';
        $sql[] = '    t.codigo_ticket,';
        $sql[] = '    t.anio_creacion AS anio_periodo,';
        $sql[] = '    t.mes_creacion AS mes_periodo,';
        $sql[] = '    WEEK(t.fecha_creacion, 1) AS semana_del_anio,';
        $sql[] = '    DATE(t.fecha_creacion) AS dia_fecha,';
        $sql[] = '    d.id_departamento,';
        $sql[] = '    d.nombre_departamento,';
        $sql[] = '    e.id_equipo,';
        $sql[] = '    e.codigo_bien AS codigo_bien_equipo,';
        $sql[] = '    e.categoria_equipo,';
        $sql[] = '    cf.rama_general AS categoria_falla,';
        $sql[] = '    cf.subcategoria AS subcategoria_falla,';
        $sql[] = '    t.estado_columna_kanban,';
        $sql[] = '    t.prioridad_oficial,';
        $sql[] = '    ta.id_personal_soporte AS id_tecnico_resolutor,';
        $sql[] = "    CONCAT(utec.nombres, ' ', utec.apellidos) AS nombre_tecnico,";
        $sql[] = '    TIMESTAMPDIFF(MINUTE, t.fecha_creacion, COALESCE(t.fecha_inicio_atencion, NOW())) AS tiempo_respuesta_minutos,';
        $sql[] = '    CASE';
        $sql[] = '        WHEN t.fecha_resolucion_tecnica IS NOT NULL AND t.fecha_inicio_atencion IS NOT NULL';
        $sql[] = '        THEN GREATEST(0, TIMESTAMPDIFF(MINUTE, t.fecha_inicio_atencion, t.fecha_resolucion_tecnica) - t.tiempo_acumulado_espera_min)';
        $sql[] = '        ELSE NULL';
        $sql[] = '    END AS tiempo_resolucion_minutos,';
        $sql[] = '    t.tiempo_acumulado_espera_min AS tiempo_en_espera_minutos,';
        $sql[] = '    CASE';
        $sql[] = '        WHEN t.fecha_resolucion_tecnica IS NOT NULL';
        $sql[] = '             AND (TIMESTAMPDIFF(MINUTE, t.fecha_inicio_atencion, t.fecha_resolucion_tecnica) - t.tiempo_acumulado_espera_min) <= cf.tiempo_estimado_minutos';
        $sql[] = "        THEN 'Si'";
        $sql[] = "        ELSE 'No'";
        $sql[] = '    END AS cumplio_expectativa_tiempo';
        $sql[] = 'FROM `tickets` t';
        $sql[] = 'INNER JOIN `departamentos` d ON t.id_departamento = d.id_departamento';
        $sql[] = 'INNER JOIN `equipos` e ON t.id_equipo = e.id_equipo';
        $sql[] = 'INNER JOIN `categorias_falla` cf ON t.id_categoria_falla = cf.id_categoria_falla';
        $sql[] = "LEFT JOIN `ticket_asignaciones` ta ON t.id_ticket = ta.id_ticket AND ta.rol_en_ticket = 'Tecnico_Principal' AND ta.activo_en_ticket = 1";
        $sql[] = 'LEFT JOIN `usuarios` utec ON ta.id_personal_soporte = utec.id_usuario';
        $sql[] = 'WHERE t.eliminado_en IS NULL;';
        $sql[] = '';
        $sql[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $sql[] = '';

        $contenidoFinal = implode("\n", $sql);
        $bytesEscritos  = @file_put_contents($rutaCompleta, $contenidoFinal);

        if ($bytesEscritos === false) {
            return [
                'exito'   => false,
                'mensaje' => 'No fue posible escribir el archivo de respaldo en el directorio del servidor.'
            ];
        }

        return [
            'exito'          => true,
            'nombre_archivo' => $nombreArchivo,
            'ruta_completa'  => $rutaCompleta,
            'peso_bytes'     => (int)$bytesEscritos,
            'peso_kb'        => round(((int)$bytesEscritos) / 1024, 2),
            'total_tablas'   => count($tablasVolcar),
            'total_filas'    => $totalFilasRespaldadas
        ];
    }

    /**
     * Lista los archivos de respaldo SQL almacenados en database/backups/ ordenados del más reciente al más antiguo
     */
    public function listarRespaldosExistentes(string $directorioBackups): array
    {
        $dirLimpio = rtrim($directorioBackups, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($dirLimpio)) {
            return [];
        }

        $archivos = glob($dirLimpio . '*.sql') ?: [];
        $lista = [];

        foreach ($archivos as $rutaArchivo) {
            if (!is_file($rutaArchivo)) {
                continue;
            }
            $nombre    = basename($rutaArchivo);
            $pesoBytes = (int)@filesize($rutaArchivo);
            $fechaTs   = (int)@filemtime($rutaArchivo);
            $pesoKb    = round($pesoBytes / 1024, 2);
            $pesoTexto = $pesoKb >= 1024
                ? number_format($pesoKb / 1024, 2, ',', '.') . ' MB'
                : number_format($pesoKb, 2, ',', '.') . ' KB';

            $lista[] = [
                'nombre_archivo' => $nombre,
                'fecha_ts'       => $fechaTs,
                'fecha_sql'      => date('Y-m-d H:i:s', $fechaTs),
                'peso_bytes'     => $pesoBytes,
                'peso_kb'        => $pesoKb,
                'peso_legible'   => $pesoTexto
            ];
        }

        usort($lista, static fn(array $a, array $b): int => $b['fecha_ts'] <=> $a['fecha_ts']);
        return $lista;
    }

    /**
     * Obtiene en tiempo real las características técnicas del servidor, uso de disco,
     * parámetros de configuración, extensiones activas y estado de las tablas en information_schema
     */
    public function obtenerDiagnosticoServidor(string $raizProyecto): array
    {
        $versionMotorSql = (string)$this->db->query('SELECT VERSION()')->fetchColumn();
        $nombreBd        = defined('DB_NAME') ? DB_NAME : 'db_tickets_soporte';

        $discoTotalBytes = (float)@disk_total_space($raizProyecto);
        $discoLibreBytes = (float)@disk_free_space($raizProyecto);
        $discoUsadoBytes = max(0.0, $discoTotalBytes - $discoLibreBytes);

        $porcentajeUsado = $discoTotalBytes > 0
            ? round(($discoUsadoBytes / $discoTotalBytes) * 100, 1)
            : 0.0;
        $porcentajeLibre = $discoTotalBytes > 0
            ? round(($discoLibreBytes / $discoTotalBytes) * 100, 1)
            : 0.0;

        // Consultar tamaño y metadatos de todas las tablas y vistas de db_tickets_soporte
        $sqlEsquema = "SELECT
                            TABLE_NAME AS nombre_tabla,
                            TABLE_TYPE AS tipo_objeto,
                            COALESCE(ENGINE, 'Vista SQL') AS motor,
                            COALESCE(TABLE_ROWS, 0) AS filas_estimadas,
                            ROUND(COALESCE(DATA_LENGTH, 0) / 1024, 2) AS datos_kb,
                            ROUND(COALESCE(INDEX_LENGTH, 0) / 1024, 2) AS indices_kb,
                            ROUND((COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0)) / 1024, 2) AS total_kb,
                            COALESCE(TABLE_COLLATION, 'utf8mb4_unicode_ci') AS colacion
                       FROM information_schema.TABLES
                       WHERE TABLE_SCHEMA = :esquema
                       ORDER BY (COALESCE(DATA_LENGTH, 0) + COALESCE(INDEX_LENGTH, 0)) DESC, TABLE_NAME ASC";

        $stmtEsquema = $this->db->prepare($sqlEsquema);
        $stmtEsquema->execute([':esquema' => $nombreBd]);
        $tablasInfo = $stmtEsquema->fetchAll(PDO::FETCH_ASSOC);

        $pesoTotalBdKb   = 0.0;
        $totalFilasExact = 0;

        foreach ($tablasInfo as &$tItem) {
            $nombreObj = (string)$tItem['nombre_tabla'];
            $esVista   = strtoupper((string)$tItem['tipo_objeto']) === 'VIEW';
            $pesoTotalBdKb += (float)$tItem['total_kb'];

            try {
                if (preg_match('/^[a-zA-Z0-9_]+$/', $nombreObj)) {
                    $conteoExacto = (int)$this->db->query("SELECT COUNT(*) FROM `{$nombreObj}`")->fetchColumn();
                    $tItem['filas_exactas'] = $conteoExacto;
                    if (!$esVista) {
                        $totalFilasExact += $conteoExacto;
                    }
                } else {
                    $tItem['filas_exactas'] = (int)$tItem['filas_estimadas'];
                }
            } catch (Throwable) {
                $tItem['filas_exactas'] = (int)$tItem['filas_estimadas'];
            }
        }
        unset($tItem);

        $limpiarParentesis = static fn(string $txt): string => trim(
            (string)preg_replace('/\s+/', ' ', str_replace(['(', ')'], ['· ', ''], $txt))
        );

        return [
            'sistema_operativo' => $limpiarParentesis(php_uname('s') . ' ' . php_uname('r')),
            'arquitectura'      => $limpiarParentesis(php_uname('m')),
            'nombre_nodo'       => $limpiarParentesis(php_uname('n')),
            'version_php'       => PHP_VERSION,
            'interfaz_sapi'     => PHP_SAPI,
            'servidor_web'      => $limpiarParentesis((string)($_SERVER['SERVER_SOFTWARE'] ?? 'Servidor local Apache WampServer')),
            'version_mysql'     => $limpiarParentesis($versionMotorSql),
            'disco_total_gb'    => round($discoTotalBytes / (1024 ** 3), 2),
            'disco_libre_gb'    => round($discoLibreBytes / (1024 ** 3), 2),
            'disco_usado_gb'    => round($discoUsadoBytes / (1024 ** 3), 2),
            'disco_usado_pct'   => $porcentajeUsado,
            'disco_libre_pct'   => $porcentajeLibre,
            'parametros_php'    => [
                'limite_memoria'       => (string)ini_get('memory_limit'),
                'maximo_subida'        => (string)ini_get('upload_max_filesize'),
                'maximo_envio_post'    => (string)ini_get('post_max_size'),
                'tiempo_max_ejecucion' => (string)ini_get('max_execution_time') . ' segundos',
                'zona_horaria'         => date_default_timezone_get()
            ],
            'modulos_criticos'  => [
                'Conector PDO MySQL' => extension_loaded('pdo_mysql'),
                'Cifrado OpenSSL'    => extension_loaded('openssl'),
                'Cadenas Multibyte'  => extension_loaded('mbstring'),
                'Inspector Fileinfo' => extension_loaded('fileinfo')
            ],
            'tablas_bd'         => $tablasInfo,
            'peso_total_bd_kb'  => round($pesoTotalBdKb, 2),
            'total_filas_bd'    => $totalFilasExact
        ];
    }

    /**
     * Calcula el conteo de registros antiguos en logs_sistema_accesos y el espacio ocupado
     * por los archivos adjuntos de solicitudes y fotografías de perfil
     */
    public function obtenerResumenMantenimiento(string $raizProyecto): array
    {
        $totalLogs = (int)$this->db->query('SELECT COUNT(*) FROM logs_sistema_accesos')->fetchColumn();
        $antiguos30 = (int)$this->db->query('SELECT COUNT(*) FROM logs_sistema_accesos WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 30 DAY)')->fetchColumn();
        $antiguos60 = (int)$this->db->query('SELECT COUNT(*) FROM logs_sistema_accesos WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 60 DAY)')->fetchColumn();
        $antiguos90 = (int)$this->db->query('SELECT COUNT(*) FROM logs_sistema_accesos WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL 90 DAY)')->fetchColumn();

        $filaAdj = $this->db->query('SELECT COUNT(*) AS cantidad, COALESCE(SUM(peso_archivo_kb), 0) AS total_kb FROM ticket_adjuntos')->fetch(PDO::FETCH_ASSOC);
        $cantidadAdjuntosBd = (int)($filaAdj['cantidad'] ?? 0);
        $pesoAdjuntosBdKb   = (float)($filaAdj['total_kb'] ?? 0);

        $dirAdjuntos = rtrim($raizProyecto, '/\\') . '/public/assets/uploads/tickets';
        $dirPerfiles = rtrim($raizProyecto, '/\\') . '/public/assets/uploads/perfiles';
        $dirImg      = rtrim($raizProyecto, '/\\') . '/public/assets/img';

        $calcularTamanoCarpeta = static function (string $rutaDir, string $prefijoFiltro = ''): array {
            if (!is_dir($rutaDir)) {
                return ['archivos' => 0, 'bytes' => 0];
            }
            $conteo = 0;
            $bytes  = 0;
            foreach (scandir($rutaDir) ?: [] as $item) {
                if ($item === '.' || $item === '..' || $item === '.htaccess') {
                    continue;
                }
                if ($prefijoFiltro !== '' && !str_starts_with($item, $prefijoFiltro)) {
                    continue;
                }
                $rutaCompleta = $rutaDir . DIRECTORY_SEPARATOR . $item;
                if (is_file($rutaCompleta)) {
                    $conteo++;
                    $bytes += (int)@filesize($rutaCompleta);
                }
            }
            return ['archivos' => $conteo, 'bytes' => $bytes];
        };

        $fisicoAdjuntos = $calcularTamanoCarpeta($dirAdjuntos);
        $fisicoPerfiles = $calcularTamanoCarpeta($dirPerfiles);
        $fisicoAvataresPrevios = $calcularTamanoCarpeta($dirImg, 'avatar_usuario_');

        $bytesAdjuntosFinal = max((int)($pesoAdjuntosBdKb * 1024), $fisicoAdjuntos['bytes']);
        $bytesPerfilesFinal = $fisicoPerfiles['bytes'] + $fisicoAvataresPrevios['bytes'];
        $totalPerfilesSubidos = $fisicoPerfiles['archivos'] + $fisicoAvataresPrevios['archivos'];

        return [
            'total_logs_acceso'      => $totalLogs,
            'logs_mayores_30_dias'   => $antiguos30,
            'logs_mayores_60_dias'   => $antiguos60,
            'logs_mayores_90_dias'   => $antiguos90,
            'cantidad_adjuntos'      => max($cantidadAdjuntosBd, $fisicoAdjuntos['archivos']),
            'espacio_adjuntos_kb'    => round($bytesAdjuntosFinal / 1024, 2),
            'cantidad_fotos_perfil'  => $totalPerfilesSubidos,
            'espacio_perfiles_kb'    => round($bytesPerfilesFinal / 1024, 2),
            'espacio_total_subidas_kb' => round(($bytesAdjuntosFinal + $bytesPerfilesFinal) / 1024, 2)
        ];
    }

    /**
     * Depura registros antiguos de logs_sistema_accesos conservando los últimos N días indicados
     */
    public function depurarLogsAccesoAntiguos(int $diasConservar): array
    {
        if (!in_array($diasConservar, [30, 60, 90], true)) {
            $diasConservar = 60;
        }

        $totalAntes = (int)$this->db->query('SELECT COUNT(*) FROM logs_sistema_accesos')->fetchColumn();

        $sql = 'DELETE FROM logs_sistema_accesos WHERE fecha_hora < DATE_SUB(NOW(), INTERVAL :dias DAY)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dias', $diasConservar, PDO::PARAM_INT);
        $stmt->execute();

        $eliminados   = $stmt->rowCount();
        $totalDespues = max(0, $totalAntes - $eliminados);

        return [
            'dias_conservados' => $diasConservar,
            'total_antes'      => $totalAntes,
            'eliminados'       => $eliminados,
            'total_despues'    => $totalDespues
        ];
    }
}
