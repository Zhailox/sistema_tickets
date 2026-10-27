<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo de Departamentos y Oficinas Institucionales
 * Interactúa con la tabla departamentos
 */
class Departamento
{
    private PDO $db;

    public function __construct(?PDO $conexion = null)
    {
        $this->db = $conexion ?? Database::conectar();
    }

    /**
     * Lista todos los departamentos con conteo de personal y equipos asociados
     */
    public function listarTodos(bool $soloActivos = false): array
    {
        $condicion = $soloActivos ? 'WHERE d.activo = 1' : '';

        $sql = "SELECT
                    d.*,
                    (SELECT COUNT(*) FROM usuarios u WHERE u.id_departamento = d.id_departamento AND u.eliminado_en IS NULL) AS total_usuarios,
                    (SELECT COUNT(*) FROM equipos e WHERE e.id_departamento = d.id_departamento) AS total_equipos,
                    (SELECT COUNT(*) FROM tickets t WHERE t.id_departamento = d.id_departamento AND t.eliminado_en IS NULL) AS total_tickets
                FROM departamentos d
                {$condicion}
                ORDER BY d.activo DESC, d.nombre_departamento ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un departamento específico por su identificador
     */
    public function obtenerPorId(int $idDepartamento): ?array
    {
        $sql = "SELECT * FROM departamentos WHERE id_departamento = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idDepartamento]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica si un código de departamento ya está registrado
     */
    public function existeCodigo(string $codigo, int $idExcluir = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM departamentos
                WHERE codigo_departamento = :codigo
                  AND id_departamento != :id_excluir";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo'     => strtoupper(trim($codigo)),
            ':id_excluir' => $idExcluir
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Crea un nuevo departamento u oficina institucional
     */
    public function crear(array $datos): int
    {
        $sql = "INSERT INTO departamentos (
                    codigo_departamento,
                    nombre_departamento,
                    sede_edificio,
                    piso_oficina,
                    numero_oficina,
                    telefono_extension,
                    activo
                ) VALUES (
                    :codigo_departamento,
                    :nombre_departamento,
                    :sede_edificio,
                    :piso_oficina,
                    :numero_oficina,
                    :telefono_extension,
                    :activo
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo_departamento' => mb_substr(strtoupper(trim($datos['codigo_departamento'])), 0, 20, 'UTF-8'),
            ':nombre_departamento' => mb_substr(trim($datos['nombre_departamento']), 0, 100, 'UTF-8'),
            ':sede_edificio'       => mb_substr(trim($datos['sede_edificio']), 0, 80, 'UTF-8'),
            ':piso_oficina'        => mb_substr(trim($datos['piso_oficina']), 0, 30, 'UTF-8'),
            ':numero_oficina'      => mb_substr(trim($datos['numero_oficina']), 0, 30, 'UTF-8'),
            ':telefono_extension'  => $datos['telefono_extension'] !== '' ? mb_substr(trim($datos['telefono_extension']), 0, 25, 'UTF-8') : null,
            ':activo'              => isset($datos['activo']) ? (int)$datos['activo'] : 1
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza los datos de un departamento existente
     */
    public function actualizar(int $idDepartamento, array $datos): bool
    {
        $sql = "UPDATE departamentos
                SET codigo_departamento = :codigo_departamento,
                    nombre_departamento = :nombre_departamento,
                    sede_edificio = :sede_edificio,
                    piso_oficina = :piso_oficina,
                    numero_oficina = :numero_oficina,
                    telefono_extension = :telefono_extension,
                    activo = :activo
                WHERE id_departamento = :id_departamento";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':codigo_departamento' => mb_substr(strtoupper(trim($datos['codigo_departamento'])), 0, 20, 'UTF-8'),
            ':nombre_departamento' => mb_substr(trim($datos['nombre_departamento']), 0, 100, 'UTF-8'),
            ':sede_edificio'       => mb_substr(trim($datos['sede_edificio']), 0, 80, 'UTF-8'),
            ':piso_oficina'        => mb_substr(trim($datos['piso_oficina']), 0, 30, 'UTF-8'),
            ':numero_oficina'      => mb_substr(trim($datos['numero_oficina']), 0, 30, 'UTF-8'),
            ':telefono_extension'  => $datos['telefono_extension'] !== '' ? mb_substr(trim($datos['telefono_extension']), 0, 25, 'UTF-8') : null,
            ':activo'              => (int)($datos['activo'] ?? 1),
            ':id_departamento'     => $idDepartamento
        ]);
    }
}
