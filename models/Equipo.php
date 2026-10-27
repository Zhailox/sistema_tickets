<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo de Equipos Informáticos por Departamento y Usuario
 * Interactúa con la tabla equipos
 */
class Equipo
{
    private PDO $db;

    public function __construct(?PDO $conexion = null)
    {
        $this->db = $conexion ?? Database::conectar();
    }

    /**
     * Obtiene los equipos disponibles para que un usuario común reporte una solicitud en su oficina
     */
    public function listarEquiposParaUsuario(int $idUsuario, int $idDepartamento): array
    {
        $sql = "SELECT
                    e.*,
                    d.nombre_departamento,
                    d.numero_oficina,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_responsable
                FROM equipos e
                INNER JOIN departamentos d ON e.id_departamento = d.id_departamento
                LEFT JOIN usuarios u ON e.id_usuario_asignado = u.id_usuario
                WHERE e.estado_operativo != 'Desincorporado'
                  AND (e.id_usuario_asignado = :id_usu OR e.id_departamento = :id_depto)
                ORDER BY
                    CASE WHEN e.id_usuario_asignado = :id_usu_ord THEN 0
                         WHEN e.es_equipo_compartido = 1 THEN 1
                         ELSE 2
                    END ASC,
                    e.codigo_bien ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usu'     => $idUsuario,
            ':id_depto'   => $idDepartamento,
            ':id_usu_ord' => $idUsuario
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Lista el inventario completo de equipos con filtros opcionales
     */
    public function listarTodos(array $filtros = []): array
    {
        $condiciones = ['1 = 1'];
        $parametros  = [];

        if (!empty($filtros['id_departamento'])) {
            $condiciones[] = 'e.id_departamento = :id_depto';
            $parametros[':id_depto'] = (int)$filtros['id_departamento'];
        }

        if (!empty($filtros['categoria_equipo'])) {
            $condiciones[] = 'e.categoria_equipo = :categoria';
            $parametros[':categoria'] = (string)$filtros['categoria_equipo'];
        }

        if (!empty($filtros['estado_operativo'])) {
            $condiciones[] = 'e.estado_operativo = :estado';
            $parametros[':estado'] = (string)$filtros['estado_operativo'];
        }

        $whereSql = implode(' AND ', $condiciones);

        $sql = "SELECT
                    e.*,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.sede_edificio,
                    d.piso_oficina,
                    d.numero_oficina,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_usuario_asignado,
                    u.cargo_empleado AS cargo_usuario_asignado,
                    (SELECT COUNT(*) FROM tickets t WHERE t.id_equipo = e.id_equipo AND t.eliminado_en IS NULL) AS total_fallas_reportadas,
                    (SELECT COUNT(*) FROM tickets t WHERE t.id_equipo = e.id_equipo AND t.eliminado_en IS NULL AND t.estado_columna_kanban != '5_Resuelto' AND t.estado_aceptacion_jefe != 'Rechazado') AS fallas_activas
                FROM equipos e
                INNER JOIN departamentos d ON e.id_departamento = d.id_departamento
                LEFT JOIN usuarios u ON e.id_usuario_asignado = u.id_usuario
                WHERE {$whereSql}
                ORDER BY e.id_departamento ASC, e.codigo_bien ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Obtiene un equipo por su identificador primario
     */
    public function obtenerPorId(int $idEquipo): ?array
    {
        $sql = "SELECT
                    e.*,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.sede_edificio,
                    d.piso_oficina,
                    d.numero_oficina,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_usuario_asignado
                FROM equipos e
                INNER JOIN departamentos d ON e.id_departamento = d.id_departamento
                LEFT JOIN usuarios u ON e.id_usuario_asignado = u.id_usuario
                WHERE e.id_equipo = :id_equipo
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_equipo' => $idEquipo]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Cambia el estado operativo de un equipo informático
     */
    public function actualizarEstadoOperativo(int $idEquipo, string $nuevoEstado): bool
    {
        $estadosPermitidos = ['Operativo', 'En_Reparacion', 'Danado', 'Desincorporado'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            return false;
        }

        $sql = "UPDATE equipos
                SET estado_operativo = :estado
                WHERE id_equipo = :id_equipo";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':estado'    => $nuevoEstado,
            ':id_equipo' => $idEquipo
        ]);
    }

    /**
     * Verifica si un código de bien nacional o institucional ya está registrado
     */
    public function existeCodigoBien(string $codigoBien, int $idExcluir = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM equipos
                WHERE codigo_bien = :codigo
                  AND id_equipo != :id_excluir";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo'     => strtoupper(trim($codigoBien)),
            ':id_excluir' => $idExcluir
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verifica si un número de serie de fabricante ya está registrado
     */
    public function existeNumeroSerie(string $numeroSerie, int $idExcluir = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM equipos
                WHERE numero_serie = :serie
                  AND id_equipo != :id_excluir";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':serie'      => strtoupper(trim($numeroSerie)),
            ':id_excluir' => $idExcluir
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Registra un nuevo equipo informático en el inventario
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO equipos (
                    id_departamento,
                    id_usuario_asignado,
                    codigo_bien,
                    numero_serie,
                    categoria_equipo,
                    marca,
                    modelo,
                    procesador_cpu,
                    memoria_ram_gb,
                    tipo_almacenamiento,
                    capacidad_almacenamiento_gb,
                    sistema_operativo,
                    direccion_ip_fija,
                    direccion_mac,
                    es_equipo_compartido,
                    estado_operativo
                ) VALUES (
                    :id_departamento,
                    :id_usuario_asignado,
                    :codigo_bien,
                    :numero_serie,
                    :categoria_equipo,
                    :marca,
                    :modelo,
                    :procesador_cpu,
                    :memoria_ram_gb,
                    :tipo_almacenamiento,
                    :capacidad_almacenamiento_gb,
                    :sistema_operativo,
                    :direccion_ip_fija,
                    :direccion_mac,
                    :es_equipo_compartido,
                    :estado_operativo
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_departamento'             => (int)$datos['id_departamento'],
            ':id_usuario_asignado'         => !empty($datos['id_usuario_asignado']) ? (int)$datos['id_usuario_asignado'] : null,
            ':codigo_bien'                 => mb_substr(strtoupper(trim($datos['codigo_bien'])), 0, 40, 'UTF-8'),
            ':numero_serie'                => mb_substr(strtoupper(trim($datos['numero_serie'])), 0, 80, 'UTF-8'),
            ':categoria_equipo'            => $datos['categoria_equipo'],
            ':marca'                       => mb_substr(trim($datos['marca']), 0, 50, 'UTF-8'),
            ':modelo'                      => mb_substr(trim($datos['modelo']), 0, 80, 'UTF-8'),
            ':procesador_cpu'              => !empty($datos['procesador_cpu']) ? mb_substr(trim($datos['procesador_cpu']), 0, 80, 'UTF-8') : null,
            ':memoria_ram_gb'              => !empty($datos['memoria_ram_gb']) ? (int)$datos['memoria_ram_gb'] : null,
            ':tipo_almacenamiento'         => $datos['tipo_almacenamiento'] ?: 'No_Aplica',
            ':capacidad_almacenamiento_gb' => !empty($datos['capacidad_almacenamiento_gb']) ? (int)$datos['capacidad_almacenamiento_gb'] : null,
            ':sistema_operativo'           => !empty($datos['sistema_operativo']) ? mb_substr(trim($datos['sistema_operativo']), 0, 60, 'UTF-8') : null,
            ':direccion_ip_fija'           => !empty($datos['direccion_ip_fija']) ? mb_substr(trim($datos['direccion_ip_fija']), 0, 45, 'UTF-8') : null,
            ':direccion_mac'               => !empty($datos['direccion_mac']) ? mb_substr(strtoupper(trim($datos['direccion_mac'])), 0, 17, 'UTF-8') : null,
            ':es_equipo_compartido'        => (int)($datos['es_equipo_compartido'] ?? 0),
            ':estado_operativo'            => $datos['estado_operativo'] ?? 'Operativo'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza la ficha técnica de un equipo existente
     */
    public function actualizar(int $idEquipo, array $datos): bool
    {
        $sql = "UPDATE equipos
                SET id_departamento = :id_departamento,
                    id_usuario_asignado = :id_usuario_asignado,
                    codigo_bien = :codigo_bien,
                    numero_serie = :numero_serie,
                    categoria_equipo = :categoria_equipo,
                    marca = :marca,
                    modelo = :modelo,
                    procesador_cpu = :procesador_cpu,
                    memoria_ram_gb = :memoria_ram_gb,
                    tipo_almacenamiento = :tipo_almacenamiento,
                    capacidad_almacenamiento_gb = :capacidad_almacenamiento_gb,
                    sistema_operativo = :sistema_operativo,
                    direccion_ip_fija = :direccion_ip_fija,
                    direccion_mac = :direccion_mac,
                    es_equipo_compartido = :es_equipo_compartido,
                    estado_operativo = :estado_operativo
                WHERE id_equipo = :id_equipo";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_departamento'             => (int)$datos['id_departamento'],
            ':id_usuario_asignado'         => !empty($datos['id_usuario_asignado']) ? (int)$datos['id_usuario_asignado'] : null,
            ':codigo_bien'                 => mb_substr(strtoupper(trim($datos['codigo_bien'])), 0, 40, 'UTF-8'),
            ':numero_serie'                => mb_substr(strtoupper(trim($datos['numero_serie'])), 0, 80, 'UTF-8'),
            ':categoria_equipo'            => $datos['categoria_equipo'],
            ':marca'                       => mb_substr(trim($datos['marca']), 0, 50, 'UTF-8'),
            ':modelo'                      => mb_substr(trim($datos['modelo']), 0, 80, 'UTF-8'),
            ':procesador_cpu'              => !empty($datos['procesador_cpu']) ? mb_substr(trim($datos['procesador_cpu']), 0, 80, 'UTF-8') : null,
            ':memoria_ram_gb'              => !empty($datos['memoria_ram_gb']) ? (int)$datos['memoria_ram_gb'] : null,
            ':tipo_almacenamiento'         => $datos['tipo_almacenamiento'] ?: 'No_Aplica',
            ':capacidad_almacenamiento_gb' => !empty($datos['capacidad_almacenamiento_gb']) ? (int)$datos['capacidad_almacenamiento_gb'] : null,
            ':sistema_operativo'           => !empty($datos['sistema_operativo']) ? mb_substr(trim($datos['sistema_operativo']), 0, 60, 'UTF-8') : null,
            ':direccion_ip_fija'           => !empty($datos['direccion_ip_fija']) ? mb_substr(trim($datos['direccion_ip_fija']), 0, 45, 'UTF-8') : null,
            ':direccion_mac'               => !empty($datos['direccion_mac']) ? mb_substr(strtoupper(trim($datos['direccion_mac'])), 0, 17, 'UTF-8') : null,
            ':es_equipo_compartido'        => (int)($datos['es_equipo_compartido'] ?? 0),
            ':estado_operativo'            => $datos['estado_operativo'] ?? 'Operativo',
            ':id_equipo'                   => $idEquipo
        ]);
    }

    /**
     * Obtiene el historial de solicitudes e intervenciones técnicas de un equipo
     */
    public function obtenerHistorialTicketsEquipo(int $idEquipo): array
    {
        $sql = "SELECT
                    t.id_ticket,
                    t.codigo_ticket,
                    t.titulo_asunto,
                    t.prioridad_oficial,
                    t.estado_columna_kanban,
                    t.estado_aceptacion_jefe,
                    t.diagnostico_real_tecnico,
                    t.solucion_aplicada_tecnico,
                    t.requirio_cambio_pieza,
                    t.detalle_pieza_cambiada,
                    t.fecha_creacion,
                    t.fecha_resolucion_tecnica,
                    cf.rama_general,
                    cf.subcategoria,
                    CONCAT(ua.nombres, ' ', ua.apellidos) AS nombre_afectado,
                    CONCAT(ut.nombres, ' ', ut.apellidos) AS nombre_tecnico
                FROM tickets t
                INNER JOIN categorias_falla cf ON t.id_categoria_falla = cf.id_categoria_falla
                INNER JOIN usuarios ua ON t.id_usuario_afectado = ua.id_usuario
                LEFT JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket AND ta.rol_en_ticket = 'Tecnico_Principal' AND ta.activo_en_ticket = 1
                LEFT JOIN usuarios ut ON ta.id_personal_soporte = ut.id_usuario
                WHERE t.id_equipo = :id_equipo
                  AND t.eliminado_en IS NULL
                ORDER BY t.id_ticket DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_equipo' => $idEquipo]);
        return $stmt->fetchAll();
    }
}
