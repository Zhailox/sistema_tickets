<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo Principal de Solicitudes de Soporte Técnico y Gestión del Tablero de Trabajo
 * Interactúa con: tickets, categorias_falla, ticket_asignaciones, ticket_comentarios y ticket_adjuntos
 */
class Ticket
{
    private PDO $db;

    public function __construct(?PDO $conexion = null)
    {
        $this->db = $conexion ?? Database::conectar();
    }

    /**
     * Lista las categorías de falla técnica disponibles
     */
    public function listarCategoriasFalla(bool $soloActivas = true): array
    {
        $condicion = $soloActivas ? 'WHERE activo = 1' : '';
        $sql = "SELECT *
                FROM categorias_falla
                {$condicion}
                ORDER BY rama_general ASC, subcategoria ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Genera el siguiente código correlativo institucional en formato TCK-AAAA-NNNN
     */
    public function generarCodigoTicket(): string
    {
        $anioActual = date('Y');
        $prefijo = "TCK-{$anioActual}-";

        $sql = "SELECT codigo_ticket
                FROM tickets
                WHERE codigo_ticket LIKE :prefijo
                ORDER BY id_ticket DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefijo' => $prefijo . '%']);
        $ultimoCodigo = $stmt->fetchColumn();

        $siguienteNumero = 1;
        if ($ultimoCodigo && preg_match('/-(\d+)$/', (string)$ultimoCodigo, $coincidencias)) {
            $siguienteNumero = ((int)$coincidencias[1]) + 1;
        } else {
            $stmtMax = $this->db->query("SELECT COALESCE(MAX(id_ticket), 0) + 1 FROM tickets");
            $siguienteNumero = (int)$stmtMax->fetchColumn();
        }

        return $prefijo . str_pad((string)$siguienteNumero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registra una nueva solicitud de soporte en la tabla tickets
     */
    public function crearTicket(array $datos): array
    {
        $codigo = $this->generarCodigoTicket();

        // Verificar colisión eventual de código
        $stmtVerif = $this->db->prepare("SELECT COUNT(*) FROM tickets WHERE codigo_ticket = :cod");
        $stmtVerif->execute([':cod' => $codigo]);
        if ((int)$stmtVerif->fetchColumn() > 0) {
            $codigo = 'TCK-' . date('Y') . '-' . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }

        $canal = $datos['canal_creacion'] ?? 'Portal_Web_Usuario';
        if (!in_array($canal, ['Portal_Web_Usuario', 'Llamada_Telefonica', 'Presencial'], true)) {
            $canal = 'Portal_Web_Usuario';
        }

        $prioridadSugerida = $datos['prioridad_sugerida'] ?? 'Media';
        if (!in_array($prioridadSugerida, ['Baja', 'Media', 'Alta'], true)) {
            $prioridadSugerida = 'Media';
        }

        $prioridadOficial = $datos['prioridad_oficial'] ?? $prioridadSugerida;
        if (!in_array($prioridadOficial, ['Baja', 'Media', 'Alta', 'Critica'], true)) {
            $prioridadOficial = 'Media';
        }

        $estadoAceptacion = $datos['estado_aceptacion_jefe'] ?? 'Pendiente_Revision';
        $estadoColumna    = $datos['estado_columna_kanban'] ?? '1_Por_Aceptar';
        $idJefeAsignador  = !empty($datos['id_jefe_asignador']) ? (int)$datos['id_jefe_asignador'] : null;
        $fechaAceptacion  = ($estadoAceptacion === 'Aceptado') ? date('Y-m-d H:i:s') : null;

        $sql = "INSERT INTO tickets (
                    codigo_ticket,
                    canal_creacion,
                    id_usuario_creador,
                    id_usuario_afectado,
                    id_departamento,
                    id_equipo,
                    id_categoria_falla,
                    titulo_asunto,
                    descripcion_problematica,
                    prioridad_sugerida,
                    estado_aceptacion_jefe,
                    prioridad_oficial,
                    estado_columna_kanban,
                    id_jefe_asignador,
                    fecha_creacion,
                    fecha_aceptacion_asignacion
                ) VALUES (
                    :codigo_ticket,
                    :canal_creacion,
                    :id_usuario_creador,
                    :id_usuario_afectado,
                    :id_departamento,
                    :id_equipo,
                    :id_categoria_falla,
                    :titulo_asunto,
                    :descripcion_problematica,
                    :prioridad_sugerida,
                    :estado_aceptacion_jefe,
                    :prioridad_oficial,
                    :estado_columna_kanban,
                    :id_jefe_asignador,
                    NOW(),
                    :fecha_aceptacion_asignacion
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_ticket'               => $codigo,
            ':canal_creacion'              => $canal,
            ':id_usuario_creador'          => (int)$datos['id_usuario_creador'],
            ':id_usuario_afectado'         => (int)$datos['id_usuario_afectado'],
            ':id_departamento'             => (int)$datos['id_departamento'],
            ':id_equipo'                   => (int)$datos['id_equipo'],
            ':id_categoria_falla'          => (int)$datos['id_categoria_falla'],
            ':titulo_asunto'               => mb_substr(trim($datos['titulo_asunto']), 0, 120, 'UTF-8'),
            ':descripcion_problematica'    => trim($datos['descripcion_problematica']),
            ':prioridad_sugerida'          => $prioridadSugerida,
            ':estado_aceptacion_jefe'      => $estadoAceptacion,
            ':prioridad_oficial'           => $prioridadOficial,
            ':estado_columna_kanban'       => $estadoColumna,
            ':id_jefe_asignador'           => $idJefeAsignador,
            ':fecha_aceptacion_asignacion' => $fechaAceptacion
        ]);

        $idTicket = (int)$this->db->lastInsertId();

        return [
            'id_ticket'     => $idTicket,
            'codigo_ticket' => $codigo
        ];
    }

    /**
     * Lista todas las solicitudes de un usuario común con datos de equipo, técnico y avance
     */
    public function listarSolicitudesPortalUsuario(int $idUsuario): array
    {
        $sql = "SELECT
                    t.*,
                    d.nombre_departamento,
                    d.numero_oficina,
                    e.codigo_bien,
                    e.categoria_equipo,
                    e.marca AS marca_equipo,
                    e.modelo AS modelo_equipo,
                    e.es_equipo_compartido,
                    cf.rama_general,
                    cf.subcategoria,
                    cf.tiempo_estimado_minutos,
                    CONCAT(utec.nombres, ' ', utec.apellidos) AS nombre_tecnico,
                    utec.cargo_empleado AS cargo_tecnico,
                    utec.telefono_extension AS extension_tecnico,
                    (SELECT COUNT(*) FROM ticket_comentarios tc WHERE tc.id_ticket = t.id_ticket AND tc.es_nota_interna_privada = 0) AS total_comentarios_publicos,
                    (SELECT COUNT(*) FROM ticket_adjuntos ta_adj WHERE ta_adj.id_ticket = t.id_ticket) AS total_adjuntos
                FROM tickets t
                INNER JOIN departamentos d ON t.id_departamento = d.id_departamento
                INNER JOIN equipos e ON t.id_equipo = e.id_equipo
                INNER JOIN categorias_falla cf ON t.id_categoria_falla = cf.id_categoria_falla
                LEFT JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket AND ta.rol_en_ticket = 'Tecnico_Principal' AND ta.activo_en_ticket = 1
                LEFT JOIN usuarios utec ON ta.id_personal_soporte = utec.id_usuario
                WHERE t.eliminado_en IS NULL
                  AND (t.id_usuario_afectado = :id_usu1 OR t.id_usuario_creador = :id_usu2)
                ORDER BY
                    CASE
                        WHEN t.estado_columna_kanban = '5_Resuelto' AND t.confirmado_por_usuario = 'Pendiente' THEN 0
                        WHEN t.estado_aceptacion_jefe != 'Rechazado' AND t.estado_columna_kanban != '5_Resuelto' THEN 1
                        ELSE 2
                    END ASC,
                    t.id_ticket DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usu1' => $idUsuario,
            ':id_usu2' => $idUsuario
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene toda la información detallada de un ticket por su ID
     */
    public function obtenerTicketPorId(int $idTicket): ?array
    {
        $sql = "SELECT
                    t.*,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.sede_edificio,
                    d.piso_oficina,
                    d.numero_oficina,
                    d.telefono_extension AS extension_departamento,
                    e.codigo_bien,
                    e.numero_serie,
                    e.categoria_equipo,
                    e.marca AS marca_equipo,
                    e.modelo AS modelo_equipo,
                    e.sistema_operativo,
                    e.direccion_ip_fija,
                    e.estado_operativo AS estado_operativo_equipo,
                    e.es_equipo_compartido,
                    cf.rama_general,
                    cf.subcategoria,
                    cf.tiempo_estimado_minutos,
                    CONCAT(uaf.nombres, ' ', uaf.apellidos) AS nombre_afectado,
                    uaf.cedula_empleado AS cedula_afectado,
                    uaf.cargo_empleado AS cargo_afectado,
                    uaf.correo_institucional AS correo_afectado,
                    uaf.telefono_extension AS extension_afectado,
                    CONCAT(ucr.nombres, ' ', ucr.apellidos) AS nombre_creador,
                    CONCAT(ujf.nombres, ' ', ujf.apellidos) AS nombre_jefe_asignador,
                    ta.id_personal_soporte AS id_tecnico_principal,
                    CONCAT(utec.nombres, ' ', utec.apellidos) AS nombre_tecnico_principal,
                    utec.cargo_empleado AS cargo_tecnico_principal,
                    utec.telefono_extension AS extension_tecnico_principal,
                    ta_ap.id_personal_soporte AS id_tecnico_apoyo,
                    CONCAT(uap.nombres, ' ', uap.apellidos) AS nombre_tecnico_apoyo
                FROM tickets t
                INNER JOIN departamentos d ON t.id_departamento = d.id_departamento
                INNER JOIN equipos e ON t.id_equipo = e.id_equipo
                INNER JOIN categorias_falla cf ON t.id_categoria_falla = cf.id_categoria_falla
                INNER JOIN usuarios uaf ON t.id_usuario_afectado = uaf.id_usuario
                INNER JOIN usuarios ucr ON t.id_usuario_creador = ucr.id_usuario
                LEFT JOIN usuarios ujf ON t.id_jefe_asignador = ujf.id_usuario
                LEFT JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket AND ta.rol_en_ticket = 'Tecnico_Principal' AND ta.activo_en_ticket = 1
                LEFT JOIN usuarios utec ON ta.id_personal_soporte = utec.id_usuario
                LEFT JOIN ticket_asignaciones ta_ap ON t.id_ticket = ta_ap.id_ticket AND ta_ap.rol_en_ticket = 'Tecnico_Apoyo' AND ta_ap.activo_en_ticket = 1
                LEFT JOIN usuarios uap ON ta_ap.id_personal_soporte = uap.id_usuario
                WHERE t.id_ticket = :id_ticket
                  AND t.eliminado_en IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_ticket' => $idTicket]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un técnico específico está asignado activamente a un ticket
     */
    public function tecnicoTieneAsignadoTicket(int $idTicket, int $idTecnico): bool
    {
        $sql = "SELECT COUNT(*)
                FROM ticket_asignaciones
                WHERE id_ticket = :id_ticket
                  AND id_personal_soporte = :id_tecnico
                  AND activo_en_ticket = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_ticket'  => $idTicket,
            ':id_tecnico' => $idTecnico
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Lista los comentarios de un ticket respetando la privacidad de notas internas
     */
    public function listarComentarios(int $idTicket, bool $incluirNotasInternas = false): array
    {
        $filtroPrivacidad = $incluirNotasInternas ? '' : 'AND tc.es_nota_interna_privada = 0';

        $sql = "SELECT
                    tc.id_comentario,
                    tc.id_ticket,
                    tc.id_usuario_autor,
                    tc.mensaje_texto,
                    tc.es_nota_interna_privada,
                    tc.fecha_comentario,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_autor,
                    u.cargo_empleado,
                    u.foto_perfil_ruta,
                    r.slug_rol,
                    r.nombre_rol
                FROM ticket_comentarios tc
                INNER JOIN usuarios u ON tc.id_usuario_autor = u.id_usuario
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE tc.id_ticket = :id_ticket
                  {$filtroPrivacidad}
                ORDER BY tc.id_comentario ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_ticket' => $idTicket]);
        return $stmt->fetchAll();
    }

    /**
     * Agrega un comentario público o nota técnica interna al ticket
     */
    public function agregarComentario(int $idTicket, int $idUsuarioAutor, string $mensaje, int $esNotaInterna = 0): int
    {
        $sql = "INSERT INTO ticket_comentarios (
                    id_ticket,
                    id_usuario_autor,
                    mensaje_texto,
                    es_nota_interna_privada,
                    fecha_comentario
                ) VALUES (
                    :id_ticket,
                    :id_usuario_autor,
                    :mensaje_texto,
                    :es_nota_interna_privada,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_ticket'               => $idTicket,
            ':id_usuario_autor'        => $idUsuarioAutor,
            ':mensaje_texto'           => trim($mensaje),
            ':es_nota_interna_privada' => $esNotaInterna ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Lista los archivos adjuntos asociados a un ticket
     */
    public function listarAdjuntos(int $idTicket): array
    {
        $sql = "SELECT
                    a.*,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_subidor
                FROM ticket_adjuntos a
                INNER JOIN usuarios u ON a.id_usuario_subida = u.id_usuario
                WHERE a.id_ticket = :id_ticket
                ORDER BY a.id_adjunto ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_ticket' => $idTicket]);
        return $stmt->fetchAll();
    }

    /**
     * Registra un archivo adjunto en la tabla ticket_adjuntos
     */
    public function registrarAdjunto(
        int $idTicket,
        int $idUsuarioSubida,
        string $nombreOriginal,
        string $extension,
        int $pesoKb,
        string $rutaAlmacenamiento
    ): int {
        $sql = "INSERT INTO ticket_adjuntos (
                    id_ticket,
                    id_usuario_subida,
                    nombre_archivo_original,
                    extension_archivo,
                    peso_archivo_kb,
                    ruta_almacenamiento,
                    fecha_subida
                ) VALUES (
                    :id_ticket,
                    :id_usuario_subida,
                    :nombre_archivo_original,
                    :extension_archivo,
                    :peso_archivo_kb,
                    :ruta_almacenamiento,
                    NOW()
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_ticket'               => $idTicket,
            ':id_usuario_subida'       => $idUsuarioSubida,
            ':nombre_archivo_original' => mb_substr(trim($nombreOriginal), 0, 150, 'UTF-8'),
            ':extension_archivo'       => mb_substr(strtolower(trim($extension)), 0, 10, 'UTF-8'),
            ':peso_archivo_kb'         => max(1, $pesoKb),
            ':ruta_almacenamiento'     => mb_substr(trim($rutaAlmacenamiento), 0, 255, 'UTF-8')
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Procesa la conformidad o reapertura del ticket por parte del usuario afectado
     */
    public function confirmarSolucionPorUsuario(int $idTicket, string $decision): bool
    {
        if ($decision === 'Confirmado_Conforme') {
            $sql = "UPDATE tickets
                    SET confirmado_por_usuario = 'Confirmado_Conforme',
                        fecha_cierre_definitivo = NOW()
                    WHERE id_ticket = :id_ticket";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id_ticket' => $idTicket]);
        }

        if ($decision === 'Reabierto_No_Funciona') {
            $sql = "UPDATE tickets
                    SET confirmado_por_usuario = 'Reabierto_No_Funciona',
                        estado_columna_kanban = '3_En_Proceso',
                        fecha_cierre_definitivo = NULL,
                        archivado_kanban = 0
                    WHERE id_ticket = :id_ticket";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id_ticket' => $idTicket]);
        }

        return false;
    }

    /**
     * Obtiene los tickets distribuidos en las 5 columnas del Tablero de Trabajo con filtros por mes y año
     */
    public function obtenerTableroTrabajo(array $filtros, string $slugRol, int $idUsuarioActual): array
    {
        $anio = (int)($filtros['anio'] ?? date('Y'));
        $mes  = (int)($filtros['mes'] ?? date('n'));
        $idDepartamento = !empty($filtros['id_departamento']) ? (int)$filtros['id_departamento'] : 0;
        $idTecnicoFiltro = !empty($filtros['id_tecnico']) ? (int)$filtros['id_tecnico'] : 0;
        $prioridadFiltro = !empty($filtros['prioridad']) ? (string)$filtros['prioridad'] : '';
        $incluirArrastre = !isset($filtros['incluir_arrastre']) || (bool)$filtros['incluir_arrastre'];

        $condiciones = ['t.eliminado_en IS NULL'];
        $parametros  = [];

        // Si es Personal_Soporte, visualiza únicamente los casos que tiene asignados
        if ($slugRol === 'Personal_Soporte') {
            $condiciones[] = "EXISTS (
                SELECT 1 FROM ticket_asignaciones ta_rol
                WHERE ta_rol.id_ticket = t.id_ticket
                  AND ta_rol.id_personal_soporte = :id_tecnico_sesion
                  AND ta_rol.activo_en_ticket = 1
            )";
            $parametros[':id_tecnico_sesion'] = $idUsuarioActual;
        } elseif ($idTecnicoFiltro > 0) {
            $condiciones[] = "EXISTS (
                SELECT 1 FROM ticket_asignaciones ta_fil
                WHERE ta_fil.id_ticket = t.id_ticket
                  AND ta_fil.id_personal_soporte = :id_tecnico_filtro
                  AND ta_fil.activo_en_ticket = 1
            )";
            $parametros[':id_tecnico_filtro'] = $idTecnicoFiltro;
        }

        if ($idDepartamento > 0) {
            $condiciones[] = 't.id_departamento = :id_depto';
            $parametros[':id_depto'] = $idDepartamento;
        }

        if ($prioridadFiltro !== '' && in_array($prioridadFiltro, ['Baja', 'Media', 'Alta', 'Critica'], true)) {
            $condiciones[] = 't.prioridad_oficial = :prioridad';
            $parametros[':prioridad'] = $prioridadFiltro;
        }

        // Filtro inteligente por periodo mensual:
        // La columna 5_Resuelto y los rechazados se filtran estrictamente por el mes/año elegido y no archivados.
        // Las columnas operativas 1 a 4 muestran las del mes elegido o también pendientes activos anteriores si incluir_arrastre está activo.
        if ($incluirArrastre) {
            $condiciones[] = "(
                (t.anio_creacion = :anio1 AND t.mes_creacion = :mes1)
                OR (t.estado_columna_kanban != '5_Resuelto' AND t.estado_aceptacion_jefe != 'Rechazado' AND t.archivado_kanban = 0)
            )";
            $parametros[':anio1'] = $anio;
            $parametros[':mes1']  = $mes;
        } else {
            $condiciones[] = '(t.anio_creacion = :anio1 AND t.mes_creacion = :mes1)';
            $parametros[':anio1'] = $anio;
            $parametros[':mes1']  = $mes;
        }

        $whereSql = implode(' AND ', $condiciones);

        $sql = "SELECT
                    t.*,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.numero_oficina,
                    e.codigo_bien,
                    e.categoria_equipo,
                    e.marca AS marca_equipo,
                    e.modelo AS modelo_equipo,
                    cf.rama_general,
                    cf.subcategoria,
                    cf.tiempo_estimado_minutos,
                    CONCAT(uaf.nombres, ' ', uaf.apellidos) AS nombre_afectado,
                    uaf.cargo_empleado AS cargo_afectado,
                    uaf.telefono_extension AS extension_afectado,
                    ta.id_personal_soporte AS id_tecnico_principal,
                    CONCAT(utec.nombres, ' ', utec.apellidos) AS nombre_tecnico_principal,
                    ta_ap.id_personal_soporte AS id_tecnico_apoyo,
                    CONCAT(uap.nombres, ' ', uap.apellidos) AS nombre_tecnico_apoyo,
                    (SELECT COUNT(*) FROM ticket_comentarios tc WHERE tc.id_ticket = t.id_ticket) AS total_comentarios,
                    (SELECT COUNT(*) FROM ticket_adjuntos adj WHERE adj.id_ticket = t.id_ticket) AS total_adjuntos
                FROM tickets t
                INNER JOIN departamentos d ON t.id_departamento = d.id_departamento
                INNER JOIN equipos e ON t.id_equipo = e.id_equipo
                INNER JOIN categorias_falla cf ON t.id_categoria_falla = cf.id_categoria_falla
                INNER JOIN usuarios uaf ON t.id_usuario_afectado = uaf.id_usuario
                LEFT JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket AND ta.rol_en_ticket = 'Tecnico_Principal' AND ta.activo_en_ticket = 1
                LEFT JOIN usuarios utec ON ta.id_personal_soporte = utec.id_usuario
                LEFT JOIN ticket_asignaciones ta_ap ON t.id_ticket = ta_ap.id_ticket AND ta_ap.rol_en_ticket = 'Tecnico_Apoyo' AND ta_ap.activo_en_ticket = 1
                LEFT JOIN usuarios uap ON ta_ap.id_personal_soporte = uap.id_usuario
                WHERE {$whereSql}
                ORDER BY
                    FIELD(t.prioridad_oficial, 'Critica', 'Alta', 'Media', 'Baja') ASC,
                    t.fecha_creacion DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        $filas = $stmt->fetchAll();

        $columnas = [
            '1_Por_Aceptar' => [],
            '2_Asignado'    => [],
            '3_En_Proceso'  => [],
            '4_En_Espera'   => [],
            '5_Resuelto'    => []
        ];
        $rechazados = [];

        foreach ($filas as $ticket) {
            if ($ticket['estado_aceptacion_jefe'] === 'Rechazado') {
                if ((int)$ticket['anio_creacion'] === $anio && (int)$ticket['mes_creacion'] === $mes) {
                    $rechazados[] = $ticket;
                }
                continue;
            }

            $col = (string)$ticket['estado_columna_kanban'];
            if ($col === '5_Resuelto') {
                // En Resuelto solo mostramos los no archivados del mes/año seleccionado
                if ((int)$ticket['archivado_kanban'] === 0 && (int)$ticket['anio_creacion'] === $anio && (int)$ticket['mes_creacion'] === $mes) {
                    $columnas['5_Resuelto'][] = $ticket;
                }
            } elseif (isset($columnas[$col])) {
                $columnas[$col][] = $ticket;
            }
        }

        return [
            'columnas'   => $columnas,
            'rechazados' => $rechazados
        ];
    }

    /**
     * Acepta un ticket, define su prioridad oficial y asigna técnico principal y opcional de apoyo
     */
    public function aceptarYAsignarTicket(
        int $idTicket,
        int $idJefeAsignador,
        string $prioridadOficial,
        int $idTecnicoPrincipal,
        ?int $idTecnicoApoyo = null
    ): bool {
        if (!in_array($prioridadOficial, ['Baja', 'Media', 'Alta', 'Critica'], true)) {
            $prioridadOficial = 'Media';
        }

        $this->db->beginTransaction();
        try {
            $sqlTicket = "UPDATE tickets
                          SET estado_aceptacion_jefe = 'Aceptado',
                              motivo_rechazo = NULL,
                              prioridad_oficial = :prioridad_oficial,
                              estado_columna_kanban = CASE
                                  WHEN estado_columna_kanban = '1_Por_Aceptar' THEN '2_Asignado'
                                  ELSE estado_columna_kanban
                              END,
                              id_jefe_asignador = :id_jefe,
                              fecha_aceptacion_asignacion = COALESCE(fecha_aceptacion_asignacion, NOW())
                          WHERE id_ticket = :id_ticket";

            $stmtTicket = $this->db->prepare($sqlTicket);
            $stmtTicket->execute([
                ':prioridad_oficial' => $prioridadOficial,
                ':id_jefe'           => $idJefeAsignador,
                ':id_ticket'         => $idTicket
            ]);

            // Desactivar asignaciones anteriores activas para renovar limpiamente
            $stmtDesact = $this->db->prepare(
                "UPDATE ticket_asignaciones SET activo_en_ticket = 0 WHERE id_ticket = :id_ticket"
            );
            $stmtDesact->execute([':id_ticket' => $idTicket]);

            // Insertar nuevo técnico principal
            $stmtIns = $this->db->prepare(
                "INSERT INTO ticket_asignaciones (
                    id_ticket,
                    id_personal_soporte,
                    rol_en_ticket,
                    fue_autoasignado,
                    activo_en_ticket,
                    fecha_asignacion
                ) VALUES (
                    :id_ticket,
                    :id_tecnico,
                    :rol,
                    0,
                    1,
                    NOW()
                )"
            );
            $stmtIns->execute([
                ':id_ticket'  => $idTicket,
                ':id_tecnico' => $idTecnicoPrincipal,
                ':rol'        => 'Tecnico_Principal'
            ]);

            // Insertar técnico de apoyo si fue seleccionado y es distinto al principal
            if ($idTecnicoApoyo !== null && $idTecnicoApoyo > 0 && $idTecnicoApoyo !== $idTecnicoPrincipal) {
                $stmtIns->execute([
                    ':id_ticket'  => $idTicket,
                    ':id_tecnico' => $idTecnicoApoyo,
                    ':rol'        => 'Tecnico_Apoyo'
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Error al aceptar y asignar ticket: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Rechaza un ticket desde la columna 1_Por_Aceptar dejando constancia del motivo
     */
    public function rechazarTicket(int $idTicket, int $idJefeAsignador, string $motivoRechazo): bool
    {
        $sql = "UPDATE tickets
                SET estado_aceptacion_jefe = 'Rechazado',
                    motivo_rechazo = :motivo_rechazo,
                    id_jefe_asignador = :id_jefe,
                    fecha_cierre_definitivo = NOW()
                WHERE id_ticket = :id_ticket";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':motivo_rechazo' => mb_substr(trim($motivoRechazo), 0, 255, 'UTF-8'),
            ':id_jefe'        => $idJefeAsignador,
            ':id_ticket'      => $idTicket
        ]);
    }

    /**
     * Actualiza la columna del tablero y los datos técnicos del caso
     */
    public function actualizarEstadoYAvanceTecnico(int $idTicket, string $nuevaColumna, array $datos): bool
    {
        $columnasPermitidas = ['2_Asignado', '3_En_Proceso', '4_En_Espera', '5_Resuelto'];
        if (!in_array($nuevaColumna, $columnasPermitidas, true)) {
            return false;
        }

        $actual = $this->obtenerTicketPorId($idTicket);
        if ($actual === null) {
            return false;
        }

        $diagnostico    = isset($datos['diagnostico_real_tecnico']) && trim((string)$datos['diagnostico_real_tecnico']) !== ''
            ? trim((string)$datos['diagnostico_real_tecnico'])
            : ($actual['diagnostico_real_tecnico'] ?? null);
        $solucion       = isset($datos['solucion_aplicada_tecnico']) && trim((string)$datos['solucion_aplicada_tecnico']) !== ''
            ? trim((string)$datos['solucion_aplicada_tecnico'])
            : ($actual['solucion_aplicada_tecnico'] ?? null);
        $cambioPieza    = isset($datos['requirio_cambio_pieza'])
            ? ((int)$datos['requirio_cambio_pieza'] ? 1 : 0)
            : (int)($actual['requirio_cambio_pieza'] ?? 0);
        $detallePieza   = $cambioPieza === 1
            ? mb_substr(trim((string)($datos['detalle_pieza_cambiada'] ?? $actual['detalle_pieza_cambiada'] ?? '')), 0, 180, 'UTF-8')
            : null;
        $motivoEspera   = $nuevaColumna === '4_En_Espera'
            ? mb_substr(trim((string)($datos['motivo_pausa_espera'] ?? '')), 0, 255, 'UTF-8')
            : null;
        $minutosEsperaExtra = max(0, (int)($datos['minutos_espera_adicionales'] ?? 0));
        $tiempoAcumulado = ((int)($actual['tiempo_acumulado_espera_min'] ?? 0)) + $minutosEsperaExtra;

        $fechaInicio = $actual['fecha_inicio_atencion'];
        if (in_array($nuevaColumna, ['3_En_Proceso', '4_En_Espera', '5_Resuelto'], true) && empty($fechaInicio)) {
            $fechaInicio = date('Y-m-d H:i:s');
        }

        $fechaResolucion = $actual['fecha_resolucion_tecnica'];
        $confirmadoUsuario = $actual['confirmado_por_usuario'] ?? 'Pendiente';
        if ($nuevaColumna === '5_Resuelto') {
            $fechaResolucion = date('Y-m-d H:i:s');
            if ($confirmadoUsuario === 'Reabierto_No_Funciona') {
                $confirmadoUsuario = 'Pendiente';
            }
        }

        $sql = "UPDATE tickets
                SET estado_columna_kanban = :nueva_columna,
                    motivo_pausa_espera = :motivo_espera,
                    diagnostico_real_tecnico = :diagnostico,
                    solucion_aplicada_tecnico = :solucion,
                    requirio_cambio_pieza = :cambio_pieza,
                    detalle_pieza_cambiada = :detalle_pieza,
                    tiempo_acumulado_espera_min = :tiempo_acumulado,
                    fecha_inicio_atencion = :fecha_inicio,
                    fecha_resolucion_tecnica = :fecha_resolucion,
                    confirmado_por_usuario = :confirmado_usuario
                WHERE id_ticket = :id_ticket";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nueva_columna'      => $nuevaColumna,
            ':motivo_espera'      => $motivoEspera,
            ':diagnostico'        => $diagnostico,
            ':solucion'           => $solucion,
            ':cambio_pieza'       => $cambioPieza,
            ':detalle_pieza'      => $detallePieza,
            ':tiempo_acumulado'   => $tiempoAcumulado,
            ':fecha_inicio'       => $fechaInicio,
            ':fecha_resolucion'   => $fechaResolucion,
            ':confirmado_usuario' => $confirmadoUsuario,
            ':id_ticket'          => $idTicket
        ]);
    }

    /**
     * Lista el personal de soporte técnico activo junto con su carga actual de casos
     */
    public function listarTecnicosConCarga(): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.nombres,
                    u.apellidos,
                    u.cargo_empleado,
                    u.telefono_extension,
                    u.estado_conexion,
                    (
                        SELECT COUNT(DISTINCT t.id_ticket)
                        FROM tickets t
                        INNER JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket
                        WHERE t.eliminado_en IS NULL
                          AND ta.id_personal_soporte = u.id_usuario
                          AND ta.activo_en_ticket = 1
                          AND t.estado_columna_kanban IN ('2_Asignado', '3_En_Proceso', '4_En_Espera')
                    ) AS casos_activos,
                    (
                        SELECT COUNT(DISTINCT t2.id_ticket)
                        FROM tickets t2
                        INNER JOIN ticket_asignaciones ta2 ON t2.id_ticket = ta2.id_ticket
                        WHERE t2.eliminado_en IS NULL
                          AND ta2.id_personal_soporte = u.id_usuario
                          AND ta2.activo_en_ticket = 1
                          AND t2.estado_columna_kanban = '5_Resuelto'
                    ) AS casos_resueltos
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.eliminado_en IS NULL
                  AND u.estado_cuenta = 'Activo'
                  AND r.slug_rol = 'Personal_Soporte'
                ORDER BY casos_activos ASC, u.nombres ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Archiva los tickets resueltos de un mes y año para limpiar la columna 5 del tablero
     */
    public function archivarResueltosPorPeriodo(int $anio, int $mes): int
    {
        $sql = "UPDATE tickets
                SET archivado_kanban = 1
                WHERE eliminado_en IS NULL
                  AND estado_columna_kanban = '5_Resuelto'
                  AND archivado_kanban = 0
                  AND anio_creacion = :anio
                  AND mes_creacion = :mes";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':anio' => $anio,
            ':mes'  => $mes
        ]);

        return $stmt->rowCount();
    }

    /**
     * Crea una nueva categoría de falla técnica en el catálogo
     */
    public function crearCategoriaFalla(string $ramaGeneral, string $subcategoria, int $tiempoEstimadoMinutos, int $activo = 1): int
    {
        $ramasValidas = ['Hardware', 'Software', 'Red_Internet', 'Impresoras', 'Accesos_Cuentas'];
        if (!in_array($ramaGeneral, $ramasValidas, true)) {
            $ramaGeneral = 'Hardware';
        }

        $sql = "INSERT INTO categorias_falla (
                    rama_general,
                    subcategoria,
                    tiempo_estimado_minutos,
                    activo
                ) VALUES (
                    :rama,
                    :subcategoria,
                    :tiempo,
                    :activo
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rama'         => $ramaGeneral,
            ':subcategoria' => mb_substr(trim($subcategoria), 0, 100, 'UTF-8'),
            ':tiempo'       => max(5, $tiempoEstimadoMinutos),
            ':activo'       => $activo ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza una categoría de falla existente
     */
    public function actualizarCategoriaFalla(
        int $idCategoriaFalla,
        string $ramaGeneral,
        string $subcategoria,
        int $tiempoEstimadoMinutos,
        int $activo = 1
    ): bool {
        $ramasValidas = ['Hardware', 'Software', 'Red_Internet', 'Impresoras', 'Accesos_Cuentas'];
        if (!in_array($ramaGeneral, $ramasValidas, true)) {
            $ramaGeneral = 'Hardware';
        }

        $sql = "UPDATE categorias_falla
                SET rama_general = :rama,
                    subcategoria = :subcategoria,
                    tiempo_estimado_minutos = :tiempo,
                    activo = :activo
                WHERE id_categoria_falla = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':rama'         => $ramaGeneral,
            ':subcategoria' => mb_substr(trim($subcategoria), 0, 100, 'UTF-8'),
            ':tiempo'       => max(5, $tiempoEstimadoMinutos),
            ':activo'       => $activo ? 1 : 0,
            ':id'           => $idCategoriaFalla
        ]);
    }

    /**
     * Consulta la vista vw_metricas_dashboard aplicando filtros de fecha, departamento y rol
     */
    public function obtenerMetricasDashboard(array $filtros, string $slugRol, int $idUsuarioActual): array
    {
        $condiciones = ['1 = 1'];
        $parametros  = [];

        if (!empty($filtros['fecha_inicio'])) {
            $condiciones[] = 'v.dia_fecha >= :fecha_inicio';
            $parametros[':fecha_inicio'] = $filtros['fecha_inicio'];
        }

        if (!empty($filtros['fecha_fin'])) {
            $condiciones[] = 'v.dia_fecha <= :fecha_fin';
            $parametros[':fecha_fin'] = $filtros['fecha_fin'];
        }

        if (!empty($filtros['id_departamento'])) {
            $condiciones[] = 'v.id_departamento = :id_depto';
            $parametros[':id_depto'] = (int)$filtros['id_departamento'];
        }

        // Si el rol es Personal_Soporte, visualiza únicamente las estadísticas de su propio trabajo
        if ($slugRol === 'Personal_Soporte') {
            $condiciones[] = 'v.id_tecnico_resolutor = :id_tecnico';
            $parametros[':id_tecnico'] = $idUsuarioActual;
        } elseif (!empty($filtros['id_tecnico'])) {
            $condiciones[] = 'v.id_tecnico_resolutor = :id_tecnico';
            $parametros[':id_tecnico'] = (int)$filtros['id_tecnico'];
        }

        $whereSql = implode(' AND ', $condiciones);

        $sql = "SELECT v.*
                FROM vw_metricas_dashboard v
                WHERE {$whereSql}
                ORDER BY v.id_ticket DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);
        $registros = $stmt->fetchAll();

        $totalCasos       = count($registros);
        $totalResueltos   = 0;
        $totalEnProceso   = 0;
        $totalEnEspera    = 0;
        $totalPorAceptar  = 0;
        $sumaRespuesta    = 0;
        $conteoRespuesta  = 0;
        $sumaResolucion   = 0;
        $conteoResolucion = 0;
        $cumplioMetaSi    = 0;

        $porDepartamento  = [];
        $porCategoria     = [];
        $porEtapa         = [
            'Por aceptar' => 0,
            'Asignado'    => 0,
            'En atención' => 0,
            'En espera'   => 0,
            'Resuelto'    => 0
        ];
        $porPrioridad     = [
            'Crítica' => 0,
            'Alta'    => 0,
            'Media'   => 0,
            'Baja'    => 0
        ];
        $porTecnico       = [];
        $porEquipo        = [];

        foreach ($registros as $fila) {
            $col = (string)$fila['estado_columna_kanban'];
            if ($col === '5_Resuelto') {
                $totalResueltos++;
                $porEtapa['Resuelto']++;
            } elseif ($col === '3_En_Proceso') {
                $totalEnProceso++;
                $porEtapa['En atención']++;
            } elseif ($col === '4_En_Espera') {
                $totalEnEspera++;
                $porEtapa['En espera']++;
            } elseif ($col === '2_Asignado') {
                $totalEnProceso++;
                $porEtapa['Asignado']++;
            } else {
                $totalPorAceptar++;
                $porEtapa['Por aceptar']++;
            }

            $prio = texto_estado_legible((string)$fila['prioridad_oficial']);
            if (isset($porPrioridad[$prio])) {
                $porPrioridad[$prio]++;
            }

            if ($fila['tiempo_respuesta_minutos'] !== null) {
                $sumaRespuesta += max(0, (int)$fila['tiempo_respuesta_minutos']);
                $conteoRespuesta++;
            }

            if ($fila['tiempo_resolucion_minutos'] !== null) {
                $sumaResolucion += max(0, (int)$fila['tiempo_resolucion_minutos']);
                $conteoResolucion++;
            }

            if ($col === '5_Resuelto' && $fila['cumplio_expectativa_tiempo'] === 'Si') {
                $cumplioMetaSi++;
            }

            // Agrupar por departamento
            $nomDepto = (string)$fila['nombre_departamento'];
            if (!isset($porDepartamento[$nomDepto])) {
                $porDepartamento[$nomDepto] = ['total' => 0, 'resueltos' => 0];
            }
            $porDepartamento[$nomDepto]['total']++;
            if ($col === '5_Resuelto') {
                $porDepartamento[$nomDepto]['resueltos']++;
            }

            // Agrupar por categoría de falla
            $nomCat = texto_estado_legible((string)$fila['categoria_falla']);
            if (!isset($porCategoria[$nomCat])) {
                $porCategoria[$nomCat] = 0;
            }
            $porCategoria[$nomCat]++;

            // Agrupar por técnico resolutor
            $nomTec = !empty($fila['nombre_tecnico']) ? (string)$fila['nombre_tecnico'] : 'Sin técnico asignado';
            if (!isset($porTecnico[$nomTec])) {
                $porTecnico[$nomTec] = [
                    'nombre_tecnico'    => $nomTec,
                    'total_asignados'   => 0,
                    'total_resueltos'   => 0,
                    'cumplio_meta'      => 0,
                    'suma_resolucion'   => 0,
                    'conteo_resolucion' => 0
                ];
            }
            $porTecnico[$nomTec]['total_asignados']++;
            if ($col === '5_Resuelto') {
                $porTecnico[$nomTec]['total_resueltos']++;
                if ($fila['cumplio_expectativa_tiempo'] === 'Si') {
                    $porTecnico[$nomTec]['cumplio_meta']++;
                }
            }
            if ($fila['tiempo_resolucion_minutos'] !== null) {
                $porTecnico[$nomTec]['suma_resolucion'] += max(0, (int)$fila['tiempo_resolucion_minutos']);
                $porTecnico[$nomTec]['conteo_resolucion']++;
            }

            // Agrupar por equipo informático para detectar reincidencias
            $codBien = (string)$fila['codigo_bien_equipo'];
            if (!isset($porEquipo[$codBien])) {
                $porEquipo[$codBien] = [
                    'id_equipo'           => (int)$fila['id_equipo'],
                    'codigo_bien_equipo'  => $codBien,
                    'categoria_equipo'    => texto_estado_legible((string)$fila['categoria_equipo']),
                    'nombre_departamento' => $nomDepto,
                    'total_fallas'        => 0,
                    'resueltas'           => 0
                ];
            }
            $porEquipo[$codBien]['total_fallas']++;
            if ($col === '5_Resuelto') {
                $porEquipo[$codBien]['resueltas']++;
            }
        }

        uasort($porEquipo, static fn(array $a, array $b): int => $b['total_fallas'] <=> $a['total_fallas']);

        $promedioRespuesta  = $conteoRespuesta > 0 ? (int)round($sumaRespuesta / $conteoRespuesta) : 0;
        $promedioResolucion = $conteoResolucion > 0 ? (int)round($sumaResolucion / $conteoResolucion) : 0;
        $porcentajeMeta     = $totalResueltos > 0 ? (int)round(($cumplioMetaSi / $totalResueltos) * 100) : 100;

        return [
            'registros'           => $registros,
            'kpis'                => [
                'total_casos'         => $totalCasos,
                'total_resueltos'     => $totalResueltos,
                'total_en_atencion'   => $totalEnProceso,
                'total_en_espera'     => $totalEnEspera,
                'total_por_aceptar'   => $totalPorAceptar,
                'promedio_respuesta'  => $promedioRespuesta,
                'promedio_resolucion' => $promedioResolucion,
                'porcentaje_meta_sla' => $porcentajeMeta
            ],
            'por_departamento'    => $porDepartamento,
            'por_categoria'       => $porCategoria,
            'por_etapa'           => $porEtapa,
            'por_prioridad'       => $porPrioridad,
            'por_tecnico'         => array_values($porTecnico),
            'por_equipo'          => array_values($porEquipo)
        ];
    }

    /**
     * Lista el historial reciente de reportes estadísticos generados
     */
    public function listarHistorialReportes(int $limite = 20): array
    {
        $sql = "SELECT
                    h.*,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_solicitante,
                    d.nombre_departamento AS nombre_departamento_filtro
                FROM historial_reportes_generados h
                INNER JOIN usuarios u ON h.id_usuario_solicitante = u.id_usuario
                LEFT JOIN departamentos d ON h.id_departamento_filtro = d.id_departamento
                ORDER BY h.id_reporte DESC
                LIMIT :limite";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
