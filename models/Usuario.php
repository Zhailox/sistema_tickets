<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

/**
 * Modelo de Usuarios, Autenticación, Permisos por Rol y Recuperación de Credenciales
 * Interactúa con las tablas: usuarios, roles, roles_permisos, modulos_sistema,
 * departamentos y solicitudes_recuperacion_clave
 */
class Usuario
{
    private PDO $db;

    public function __construct(?PDO $conexion = null)
    {
        $this->db = $conexion ?? Database::conectar();
    }

    /**
     * Busca un usuario activo o suspendido por nombre de usuario, correo institucional o cédula
     */
    public function buscarPorIdentificador(string $identificador): ?array
    {
        $limpio = trim($identificador);
        if ($limpio === '') {
            return null;
        }

        $sql = "SELECT
                    u.*,
                    r.slug_rol,
                    r.nombre_rol,
                    r.descripcion AS descripcion_rol,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.sede_edificio,
                    d.piso_oficina,
                    d.numero_oficina,
                    d.telefono_extension AS extension_departamento
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                INNER JOIN departamentos d ON u.id_departamento = d.id_departamento
                WHERE u.eliminado_en IS NULL
                  AND (u.username = :id1 OR u.correo_institucional = :id2 OR u.cedula_empleado = :id3)
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id1' => $limpio,
            ':id2' => $limpio,
            ':id3' => $limpio
        ]);

        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Obtiene la ficha completa de un usuario por su identificador único
     */
    public function obtenerPorId(int $idUsuario): ?array
    {
        $sql = "SELECT
                    u.*,
                    r.slug_rol,
                    r.nombre_rol,
                    r.descripcion AS descripcion_rol,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.sede_edificio,
                    d.piso_oficina,
                    d.numero_oficina,
                    d.telefono_extension AS extension_departamento
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                INNER JOIN departamentos d ON u.id_departamento = d.id_departamento
                WHERE u.id_usuario = :id_usuario
                  AND u.eliminado_en IS NULL
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Verifica la contraseña ingresada usando password_verify con soporte de migración de semilla
     */
    public function verificarCredenciales(array $usuario, string $clavePlana): bool
    {
        $hashActual = (string)($usuario['password_hash'] ?? '');
        if ($hashActual === '') {
            return false;
        }

        if (password_verify($clavePlana, $hashActual)) {
            if (password_needs_rehash($hashActual, PASSWORD_BCRYPT)) {
                $this->actualizarClave((int)$usuario['id_usuario'], $clavePlana);
            }
            return true;
        }

        // Compatibilidad automática si se reimportó el hash de demostración original y se escribe 123456
        $hashSemillaAntiguo = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        if ($hashActual === $hashSemillaAntiguo && $clavePlana === '123456') {
            $this->actualizarClave((int)$usuario['id_usuario'], '123456');
            return true;
        }

        return false;
    }

    /**
     * Evalúa si el usuario tiene un bloqueo temporal vigente por intentos fallidos
     */
    public function evaluarBloqueoTemporal(array $usuario): array
    {
        $bloqueadoHasta = $usuario['bloqueado_hasta'] ?? null;
        if (empty($bloqueadoHasta)) {
            return ['bloqueado' => false, 'minutos_restantes' => 0];
        }

        $tiempoFin = strtotime((string)$bloqueadoHasta);
        $ahora = time();

        if ($tiempoFin !== false && $tiempoFin > $ahora) {
            $minutosRestantes = (int)ceil(($tiempoFin - $ahora) / 60);
            return [
                'bloqueado'         => true,
                'minutos_restantes' => max(1, $minutosRestantes),
                'bloqueado_hasta'   => $bloqueadoHasta
            ];
        }

        // Si el tiempo de bloqueo ya venció, se limpian los contadores automáticamente
        $this->reiniciarIntentosFallidos((int)$usuario['id_usuario']);
        return ['bloqueado' => false, 'minutos_restantes' => 0];
    }

    /**
     * Incrementa el contador de intentos fallidos y aplica bloqueo temporal al llegar al límite
     */
    public function registrarIntentoFallido(int $idUsuario, int $intentosPrevios): array
    {
        $nuevosIntentos = $intentosPrevios + 1;
        $maxIntentos = defined('MAX_INTENTOS_FALLIDOS') ? MAX_INTENTOS_FALLIDOS : 3;
        $minutosBloqueo = defined('MINUTOS_BLOQUEO_CUENTA') ? MINUTOS_BLOQUEO_CUENTA : 15;

        if ($nuevosIntentos >= $maxIntentos) {
            $sql = "UPDATE usuarios
                    SET intentos_fallidos = :intentos,
                        bloqueado_hasta = DATE_ADD(NOW(), INTERVAL :minutos MINUTE)
                    WHERE id_usuario = :id_usuario";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':intentos'   => $nuevosIntentos,
                ':minutos'    => $minutosBloqueo,
                ':id_usuario' => $idUsuario
            ]);

            return [
                'bloqueado_ahora'    => true,
                'intentos_acumulados'=> $nuevosIntentos,
                'intentos_restantes' => 0,
                'minutos_bloqueo'    => $minutosBloqueo
            ];
        }

        $sql = "UPDATE usuarios
                SET intentos_fallidos = :intentos,
                    bloqueado_hasta = NULL
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':intentos'   => $nuevosIntentos,
            ':id_usuario' => $idUsuario
        ]);

        return [
            'bloqueado_ahora'    => false,
            'intentos_acumulados'=> $nuevosIntentos,
            'intentos_restantes' => max(0, $maxIntentos - $nuevosIntentos),
            'minutos_bloqueo'    => 0
        ];
    }

    /**
     * Limpia el contador de intentos fallidos y desbloquea la cuenta
     */
    public function reiniciarIntentosFallidos(int $idUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET intentos_fallidos = 0,
                    bloqueado_hasta = NULL
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_usuario' => $idUsuario]);
    }

    /**
     * Actualiza el estado de conexión y último acceso al iniciar sesión correctamente
     */
    public function registrarIngresoExitoso(int $idUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET intentos_fallidos = 0,
                    bloqueado_hasta = NULL,
                    estado_conexion = 'Online',
                    fecha_ultimo_acceso = NOW()
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_usuario' => $idUsuario]);
    }

    /**
     * Marca al usuario como desconectado al cerrar su sesión
     */
    public function registrarCierreSesion(int $idUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET estado_conexion = 'Offline'
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_usuario' => $idUsuario]);
    }

    /**
     * Obtiene los permisos del rol y construye la estructura de navegación en español natural
     */
    public function obtenerPermisosYMenuPorRol(int $idRol): array
    {
        $sql = "SELECT
                    m.id_modulo,
                    m.clave_modulo,
                    m.nombre_modulo,
                    m.icono_argon,
                    m.orden_menu,
                    rp.permiso_ver,
                    rp.permiso_crear,
                    rp.permiso_editar,
                    rp.permiso_eliminar,
                    rp.permiso_asignar_tickets
                FROM roles_permisos rp
                INNER JOIN modulos_sistema m ON rp.id_modulo = m.id_modulo
                WHERE rp.id_rol = :id_rol
                ORDER BY m.orden_menu ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_rol' => $idRol]);
        $filas = $stmt->fetchAll();

        $mapaRutas = [
            'Mod1_Auth'       => ['c' => 'auth',       'a' => 'perfil', 'etiqueta' => 'Mi cuenta y perfil'],
            'Mod2_Admin'      => ['c' => 'admin',      'a' => 'index',  'etiqueta' => 'Seguridad y usuarios'],
            'Mod3_Inventario' => ['c' => 'inventario', 'a' => 'index',  'etiqueta' => 'Departamentos y equipos'],
            'Mod4_Portal'     => ['c' => 'portal',     'a' => 'index',  'etiqueta' => 'Mis solicitudes de soporte'],
            'Mod5_Kanban'     => ['c' => 'kanban',     'a' => 'index',  'etiqueta' => 'Tablero de trabajo'],
            'Mod6_Dashboard'  => ['c' => 'dashboard',  'a' => 'index',  'etiqueta' => 'Panel de estadísticas'],
        ];

        $permisos = [];
        $menu = [];

        foreach ($filas as $fila) {
            $clave = (string)$fila['clave_modulo'];
            $configRuta = $mapaRutas[$clave] ?? ['c' => 'auth', 'a' => 'perfil', 'etiqueta' => $fila['nombre_modulo']];

            $permisos[$clave] = [
                'id_modulo'               => (int)$fila['id_modulo'],
                'clave_modulo'            => $clave,
                'nombre_modulo'           => $configRuta['etiqueta'],
                'icono_argon'             => (string)$fila['icono_argon'],
                'orden_menu'              => (int)$fila['orden_menu'],
                'controlador'             => $configRuta['c'],
                'accion'                  => $configRuta['a'],
                'permiso_ver'             => (int)$fila['permiso_ver'] === 1,
                'permiso_crear'           => (int)$fila['permiso_crear'] === 1,
                'permiso_editar'          => (int)$fila['permiso_editar'] === 1,
                'permiso_eliminar'        => (int)$fila['permiso_eliminar'] === 1,
                'permiso_asignar_tickets' => (int)$fila['permiso_asignar_tickets'] === 1,
            ];

            if ((int)$fila['permiso_ver'] === 1) {
                $menu[] = $permisos[$clave];
            }
        }

        return [
            'permisos' => $permisos,
            'menu'     => $menu
        ];
    }

    /**
     * Calcula los contadores vivos para mostrar insignias informativas en el menú lateral
     */
    public function obtenerContadoresMenuLateral(int $idUsuario, string $slugRol): array
    {
        $contadores = [
            'Mod1_Auth'       => 0,
            'Mod2_Admin'      => 0,
            'Mod3_Inventario' => 0,
            'Mod4_Portal'     => 0,
            'Mod5_Kanban'     => 0,
            'Mod6_Dashboard'  => 0,
        ];

        try {
            if ($slugRol === 'Superadmin' || $slugRol === 'Jefe_Soporte') {
                // Solicitudes nuevas en columna 1_Por_Aceptar
                $stmtKanban = $this->db->query(
                    "SELECT COUNT(*) FROM tickets WHERE eliminado_en IS NULL AND estado_columna_kanban = '1_Por_Aceptar'"
                );
                $contadores['Mod5_Kanban'] = (int)$stmtKanban->fetchColumn();

                // Solicitudes de recuperación de clave pendientes más cuentas con bloqueo activo
                $stmtSeg = $this->db->query(
                    "SELECT
                        (SELECT COUNT(*) FROM solicitudes_recuperacion_clave WHERE estado_solicitud = 'Pendiente')
                        +
                        (SELECT COUNT(*) FROM usuarios WHERE eliminado_en IS NULL AND bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW())"
                );
                $alertasSeguridad = (int)$stmtSeg->fetchColumn();
                $contadores['Mod2_Admin'] = $alertasSeguridad;
                if ($slugRol === 'Jefe_Soporte') {
                    $contadores['Mod1_Auth'] = $alertasSeguridad;
                }

                // Equipos actualmente en reparación o dañados
                $stmtEq = $this->db->query(
                    "SELECT COUNT(*) FROM equipos WHERE estado_operativo IN ('En_Reparacion', 'Danado')"
                );
                $contadores['Mod3_Inventario'] = (int)$stmtEq->fetchColumn();
            } elseif ($slugRol === 'Personal_Soporte') {
                // Casos activos asignados al técnico actual
                $stmtTec = $this->db->prepare(
                    "SELECT COUNT(DISTINCT t.id_ticket)
                     FROM tickets t
                     INNER JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket
                     WHERE t.eliminado_en IS NULL
                       AND ta.id_personal_soporte = :id_tecnico
                       AND ta.activo_en_ticket = 1
                       AND t.estado_columna_kanban IN ('2_Asignado', '3_En_Proceso', '4_En_Espera')"
                );
                $stmtTec->execute([':id_tecnico' => $idUsuario]);
                $contadores['Mod5_Kanban'] = (int)$stmtTec->fetchColumn();
            } elseif ($slugRol === 'Usuario_Comun') {
                // Solicitudes en curso o pendientes de confirmación por el usuario
                $stmtUsu = $this->db->prepare(
                    "SELECT COUNT(*)
                     FROM tickets
                     WHERE eliminado_en IS NULL
                       AND id_usuario_afectado = :id_usuario
                       AND estado_aceptacion_jefe != 'Rechazado'
                       AND (estado_columna_kanban != '5_Resuelto' OR confirmado_por_usuario = 'Pendiente')"
                );
                $stmtUsu->execute([':id_usuario' => $idUsuario]);
                $contadores['Mod4_Portal'] = (int)$stmtUsu->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log('Error al calcular contadores laterales: ' . $e->getMessage());
        }

        return $contadores;
    }

    /**
     * Verifica si un correo institucional ya pertenece a otro usuario registrado
     */
    public function existeCorreoEnOtroUsuario(string $correo, int $idUsuarioExcluir = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios
                WHERE correo_institucional = :correo
                  AND id_usuario != :id_excluir";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':correo'     => trim($correo),
            ':id_excluir' => $idUsuarioExcluir
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verifica si un nombre de usuario ya está ocupado por otro registro
     */
    public function existeUsernameEnOtroUsuario(string $username, int $idUsuarioExcluir = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios
                WHERE username = :username
                  AND id_usuario != :id_excluir";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':username'   => trim($username),
            ':id_excluir' => $idUsuarioExcluir
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Verifica si una cédula de empleado ya está registrada en otro usuario
     */
    public function existeCedulaEnOtroUsuario(string $cedula, int $idUsuarioExcluir = 0): bool
    {
        $sql = "SELECT COUNT(*) FROM usuarios
                WHERE cedula_empleado = :cedula
                  AND id_usuario != :id_excluir";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cedula'     => trim($cedula),
            ':id_excluir' => $idUsuarioExcluir
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Actualiza los datos básicos de la cuenta propia del usuario
     */
    public function actualizarDatosPerfil(int $idUsuario, array $datos): bool
    {
        $sql = "UPDATE usuarios
                SET nombres = :nombres,
                    apellidos = :apellidos,
                    correo_institucional = :correo_institucional,
                    cargo_empleado = :cargo_empleado,
                    telefono_extension = :telefono_extension,
                    foto_perfil_ruta = :foto_perfil_ruta
                WHERE id_usuario = :id_usuario
                  AND eliminado_en IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombres'              => mb_substr(trim($datos['nombres']), 0, 80, 'UTF-8'),
            ':apellidos'            => mb_substr(trim($datos['apellidos']), 0, 80, 'UTF-8'),
            ':correo_institucional' => mb_substr(trim($datos['correo_institucional']), 0, 120, 'UTF-8'),
            ':cargo_empleado'       => mb_substr(trim($datos['cargo_empleado']), 0, 80, 'UTF-8'),
            ':telefono_extension'   => $datos['telefono_extension'] !== '' ? mb_substr(trim($datos['telefono_extension']), 0, 25, 'UTF-8') : null,
            ':foto_perfil_ruta'     => mb_substr(trim($datos['foto_perfil_ruta']), 0, 255, 'UTF-8'),
            ':id_usuario'           => $idUsuario
        ]);
    }

    /**
     * Actualiza la contraseña cifrada con BCRYPT y limpia bloqueos previos
     */
    public function actualizarClave(int $idUsuario, string $nuevaClavePlana): bool
    {
        $nuevoHash = password_hash($nuevaClavePlana, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios
                SET password_hash = :password_hash,
                    intentos_fallidos = 0,
                    bloqueado_hasta = NULL
                WHERE id_usuario = :id_usuario";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password_hash' => $nuevoHash,
            ':id_usuario'    => $idUsuario
        ]);
    }

    /**
     * Configura o actualiza la pregunta y respuesta de seguridad para recuperación en intranet
     */
    public function actualizarPreguntaSeguridad(int $idUsuario, string $pregunta, string $respuestaPlana): bool
    {
        $respuestaNormalizada = normalizar_respuesta_seguridad($respuestaPlana);
        $hashRespuesta = password_hash($respuestaNormalizada, PASSWORD_BCRYPT);

        $sql = "UPDATE usuarios
                SET pregunta_seguridad = :pregunta_seguridad,
                    respuesta_seguridad_hash = :respuesta_seguridad_hash
                WHERE id_usuario = :id_usuario";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':pregunta_seguridad'       => mb_substr(trim($pregunta), 0, 150, 'UTF-8'),
            ':respuesta_seguridad_hash' => $hashRespuesta,
            ':id_usuario'               => $idUsuario
        ]);
    }

    /**
     * Verifica la respuesta a la pregunta de seguridad ignorando diferencias de mayúsculas o acentos
     */
    public function verificarRespuestaSeguridad(array $usuario, string $respuestaIngresada): bool
    {
        $normalizada = normalizar_respuesta_seguridad($respuestaIngresada);
        if ($normalizada === '') {
            return false;
        }

        $hashGuardado = (string)($usuario['respuesta_seguridad_hash'] ?? '');
        if ($hashGuardado !== '') {
            if (password_verify($normalizada, $hashGuardado) || password_verify(trim($respuestaIngresada), $hashGuardado)) {
                return true;
            }
        }

        // Respaldo para usuarios semilla si se reimportó la base sin hashes de respuesta inicializados
        $respuestasSemilla = [
            1 => 'rex',
            2 => 'caracas',
            3 => 'madrid',
            4 => 'antonio',
            5 => 'carlos',
            6 => 'bolivar'
        ];
        $idUsuario = (int)($usuario['id_usuario'] ?? 0);
        if ($hashGuardado === '' && isset($respuestasSemilla[$idUsuario]) && $normalizada === $respuestasSemilla[$idUsuario]) {
            $this->actualizarPreguntaSeguridad(
                $idUsuario,
                (string)$usuario['pregunta_seguridad'],
                $respuestasSemilla[$idUsuario]
            );
            return true;
        }

        return false;
    }

    /**
     * Registra una solicitud en la tabla solicitudes_recuperacion_clave
     */
    public function registrarSolicitudRecuperacion(
        int $idUsuarioAfectado,
        string $metodoRecuperacion,
        int $validacionSuperada,
        string $estadoSolicitud,
        ?int $idUsuarioValidador = null
    ): int {
        $metodosValidos = ['Correo_Automatico', 'Chat_P2P_Soporte', 'Reset_Manual'];
        if (!in_array($metodoRecuperacion, $metodosValidos, true)) {
            $metodoRecuperacion = 'Chat_P2P_Soporte';
        }

        $estadosValidos = ['Pendiente', 'Validada', 'Rechazada', 'Completada'];
        if (!in_array($estadoSolicitud, $estadosValidos, true)) {
            $estadoSolicitud = 'Pendiente';
        }

        $fechaResolucion = in_array($estadoSolicitud, ['Completada', 'Rechazada', 'Validada'], true)
            ? date('Y-m-d H:i:s')
            : null;

        $sql = "INSERT INTO solicitudes_recuperacion_clave (
                    id_usuario_afectado,
                    metodo_recuperacion,
                    validacion_seguridad_superada,
                    id_usuario_validador,
                    estado_solicitud,
                    fecha_solicitud,
                    fecha_resolucion
                ) VALUES (
                    :id_usuario_afectado,
                    :metodo_recuperacion,
                    :validacion_seguridad_superada,
                    :id_usuario_validador,
                    :estado_solicitud,
                    NOW(),
                    :fecha_resolucion
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usuario_afectado'           => $idUsuarioAfectado,
            ':metodo_recuperacion'           => $metodoRecuperacion,
            ':validacion_seguridad_superada' => $validacionSuperada ? 1 : 0,
            ':id_usuario_validador'          => $idUsuarioValidador,
            ':estado_solicitud'              => $estadoSolicitud,
            ':fecha_resolucion'              => $fechaResolucion
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Busca si el usuario ya cuenta con una solicitud de recuperación pendiente de atención
     */
    public function obtenerSolicitudRecuperacionPendiente(int $idUsuarioAfectado): ?array
    {
        $sql = "SELECT *
                FROM solicitudes_recuperacion_clave
                WHERE id_usuario_afectado = :id_usuario
                  AND estado_solicitud = 'Pendiente'
                ORDER BY id_solicitud DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuarioAfectado]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Lista las solicitudes de recuperación de clave pendientes o recientes para supervisión
     */
    public function listarSolicitudesRecuperacion(bool $soloPendientes = false, int $limite = 30): array
    {
        $condicion = $soloPendientes ? "WHERE s.estado_solicitud = 'Pendiente'" : "";

        $sql = "SELECT
                    s.*,
                    u.username,
                    u.cedula_empleado,
                    u.correo_institucional,
                    u.nombres,
                    u.apellidos,
                    u.cargo_empleado,
                    u.intentos_fallidos,
                    u.bloqueado_hasta,
                    d.nombre_departamento,
                    CONCAT(val.nombres, ' ', val.apellidos) AS nombre_validador
                FROM solicitudes_recuperacion_clave s
                INNER JOIN usuarios u ON s.id_usuario_afectado = u.id_usuario
                INNER JOIN departamentos d ON u.id_departamento = d.id_departamento
                LEFT JOIN usuarios val ON s.id_usuario_validador = val.id_usuario
                {$condicion}
                ORDER BY
                    CASE WHEN s.estado_solicitud = 'Pendiente' THEN 0 ELSE 1 END ASC,
                    s.id_solicitud DESC
                LIMIT :limite";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Resuelve una solicitud de recuperación asistida asignando una clave temporal o rechazándola
     */
    public function resolverSolicitudRecuperacionAsistida(
        int $idSolicitud,
        int $idUsuarioValidador,
        string $accionResolucion,
        string $nuevaClaveTemporal = '123456'
    ): ?array {
        $sqlBusq = "SELECT s.*, u.username, u.nombres, u.apellidos
                    FROM solicitudes_recuperacion_clave s
                    INNER JOIN usuarios u ON s.id_usuario_afectado = u.id_usuario
                    WHERE s.id_solicitud = :id_solicitud
                    LIMIT 1";
        $stmtBusq = $this->db->prepare($sqlBusq);
        $stmtBusq->execute([':id_solicitud' => $idSolicitud]);
        $solicitud = $stmtBusq->fetch();

        if (!$solicitud || $solicitud['estado_solicitud'] !== 'Pendiente') {
            return null;
        }

        $idAfectado = (int)$solicitud['id_usuario_afectado'];

        if ($accionResolucion === 'aprobar') {
            $this->actualizarClave($idAfectado, $nuevaClaveTemporal);
            $nuevoEstado = 'Completada';
        } else {
            $nuevoEstado = 'Rechazada';
        }

        $sqlUpd = "UPDATE solicitudes_recuperacion_clave
                   SET id_usuario_validador = :id_validador,
                       estado_solicitud = :estado_solicitud,
                       fecha_resolucion = NOW()
                   WHERE id_solicitud = :id_solicitud";
        $stmtUpd = $this->db->prepare($sqlUpd);
        $stmtUpd->execute([
            ':id_validador'     => $idUsuarioValidador,
            ':estado_solicitud' => $nuevoEstado,
            ':id_solicitud'     => $idSolicitud
        ]);

        $solicitud['nuevo_estado'] = $nuevoEstado;
        return $solicitud;
    }

    /**
     * Obtiene indicadores resumidos del trabajo o actividad del usuario para su vista de cuenta
     */
    public function obtenerResumenActividadUsuario(int $idUsuario, string $slugRol, int $idDepartamento): array
    {
        $resumen = [
            'equipos_asignados'     => 0,
            'solicitudes_totales'   => 0,
            'solicitudes_activas'   => 0,
            'solicitudes_resueltas' => 0,
            'lista_equipos'         => []
        ];

        try {
            // Equipos asignados directamente al usuario o compartidos en su departamento
            $stmtEq = $this->db->prepare(
                "SELECT codigo_bien, categoria_equipo, marca, modelo, direccion_ip_fija, es_equipo_compartido, estado_operativo
                 FROM equipos
                 WHERE id_usuario_asignado = :id_usu
                    OR (id_departamento = :id_depto AND es_equipo_compartido = 1)
                 ORDER BY es_equipo_compartido ASC, id_equipo ASC"
            );
            $stmtEq->execute([
                ':id_usu'   => $idUsuario,
                ':id_depto' => $idDepartamento
            ]);
            $resumen['lista_equipos'] = $stmtEq->fetchAll();
            $resumen['equipos_asignados'] = count($resumen['lista_equipos']);

            if ($slugRol === 'Personal_Soporte') {
                $stmtTck = $this->db->prepare(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN t.estado_columna_kanban != '5_Resuelto' THEN 1 ELSE 0 END) AS activas,
                        SUM(CASE WHEN t.estado_columna_kanban = '5_Resuelto' THEN 1 ELSE 0 END) AS resueltas
                     FROM tickets t
                     INNER JOIN ticket_asignaciones ta ON t.id_ticket = ta.id_ticket
                     WHERE t.eliminado_en IS NULL
                       AND ta.id_personal_soporte = :id_usu
                       AND ta.activo_en_ticket = 1"
                );
                $stmtTck->execute([':id_usu' => $idUsuario]);
            } elseif ($slugRol === 'Jefe_Soporte' || $slugRol === 'Superadmin') {
                $stmtTck = $this->db->query(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN estado_columna_kanban != '5_Resuelto' THEN 1 ELSE 0 END) AS activas,
                        SUM(CASE WHEN estado_columna_kanban = '5_Resuelto' THEN 1 ELSE 0 END) AS resueltas
                     FROM tickets
                     WHERE eliminado_en IS NULL"
                );
            } else {
                $stmtTck = $this->db->prepare(
                    "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN estado_columna_kanban != '5_Resuelto' THEN 1 ELSE 0 END) AS activas,
                        SUM(CASE WHEN estado_columna_kanban = '5_Resuelto' THEN 1 ELSE 0 END) AS resueltas
                     FROM tickets
                     WHERE eliminado_en IS NULL
                       AND id_usuario_afectado = :id_usu"
                );
                $stmtTck->execute([':id_usu' => $idUsuario]);
            }

            $datosTck = $stmtTck->fetch();
            if ($datosTck) {
                $resumen['solicitudes_totales']   = (int)($datosTck['total'] ?? 0);
                $resumen['solicitudes_activas']   = (int)($datosTck['activas'] ?? 0);
                $resumen['solicitudes_resueltas'] = (int)($datosTck['resueltas'] ?? 0);
            }
        } catch (PDOException $e) {
            error_log('Error al consultar resumen de cuenta: ' . $e->getMessage());
        }

        return $resumen;
    }

    /**
     * Lista todos los usuarios activos o con baja lógica para administración general
     */
    public function listarUsuariosAdmin(bool $incluirEliminados = false): array
    {
        $condicion = $incluirEliminados ? "" : "WHERE u.eliminado_en IS NULL";
        $sql = "SELECT
                    u.*,
                    r.slug_rol,
                    r.nombre_rol,
                    d.codigo_departamento,
                    d.nombre_departamento,
                    d.sede_edificio
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                INNER JOIN departamentos d ON u.id_departamento = d.id_departamento
                {$condicion}
                ORDER BY u.eliminado_en IS NOT NULL ASC, u.id_rol ASC, u.apellidos ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un usuario por su ID incluyendo aquellos con baja lógica para gestión administrativa
     */
    public function obtenerPorIdIncluirEliminados(int $idUsuario): ?array
    {
        $sql = "SELECT
                    u.*,
                    r.slug_rol,
                    r.nombre_rol,
                    d.codigo_departamento,
                    d.nombre_departamento
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                INNER JOIN departamentos d ON u.id_departamento = d.id_departamento
                WHERE u.id_usuario = :id_usuario
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Crea un nuevo funcionario desde el panel de seguridad del Superadministrador
     */
    public function crearUsuarioAdmin(array $datos): int
    {
        $hashClave = password_hash((string)$datos['clave_plana'], PASSWORD_BCRYPT);
        $pregunta  = !empty($datos['pregunta_seguridad']) ? trim((string)$datos['pregunta_seguridad']) : '¿Nombre de primera mascota?';
        $respuesta = !empty($datos['respuesta_seguridad']) ? normalizar_respuesta_seguridad((string)$datos['respuesta_seguridad']) : 'soporte';
        $hashResp  = password_hash($respuesta, PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (
                    id_rol,
                    id_departamento,
                    cedula_empleado,
                    username,
                    correo_institucional,
                    password_hash,
                    nombres,
                    apellidos,
                    cargo_empleado,
                    telefono_extension,
                    pregunta_seguridad,
                    respuesta_seguridad_hash,
                    estado_cuenta
                ) VALUES (
                    :id_rol,
                    :id_departamento,
                    :cedula_empleado,
                    :username,
                    :correo_institucional,
                    :password_hash,
                    :nombres,
                    :apellidos,
                    :cargo_empleado,
                    :telefono_extension,
                    :pregunta_seguridad,
                    :respuesta_seguridad_hash,
                    :estado_cuenta
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_rol'                   => (int)$datos['id_rol'],
            ':id_departamento'          => (int)$datos['id_departamento'],
            ':cedula_empleado'          => mb_substr(strtoupper(trim((string)$datos['cedula_empleado'])), 0, 20, 'UTF-8'),
            ':username'                 => mb_substr(strtolower(trim((string)$datos['username'])), 0, 50, 'UTF-8'),
            ':correo_institucional'     => mb_substr(strtolower(trim((string)$datos['correo_institucional'])), 0, 120, 'UTF-8'),
            ':password_hash'            => $hashClave,
            ':nombres'                  => mb_substr(trim((string)$datos['nombres']), 0, 80, 'UTF-8'),
            ':apellidos'                => mb_substr(trim((string)$datos['apellidos']), 0, 80, 'UTF-8'),
            ':cargo_empleado'           => mb_substr(trim((string)$datos['cargo_empleado']), 0, 80, 'UTF-8'),
            ':telefono_extension'       => $datos['telefono_extension'] !== '' ? mb_substr(trim((string)$datos['telefono_extension']), 0, 25, 'UTF-8') : null,
            ':pregunta_seguridad'       => mb_substr($pregunta, 0, 150, 'UTF-8'),
            ':respuesta_seguridad_hash' => $hashResp,
            ':estado_cuenta'            => in_array($datos['estado_cuenta'] ?? 'Activo', ['Activo', 'Suspendido', 'Inactivo'], true) ? $datos['estado_cuenta'] : 'Activo'
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza la ficha administrativa de un usuario por parte del Superadministrador
     */
    public function actualizarUsuarioAdmin(int $idUsuario, array $datos): bool
    {
        $sql = "UPDATE usuarios
                SET id_rol = :id_rol,
                    id_departamento = :id_departamento,
                    cedula_empleado = :cedula_empleado,
                    username = :username,
                    correo_institucional = :correo_institucional,
                    nombres = :nombres,
                    apellidos = :apellidos,
                    cargo_empleado = :cargo_empleado,
                    telefono_extension = :telefono_extension,
                    estado_cuenta = :estado_cuenta
                WHERE id_usuario = :id_usuario";

        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            ':id_rol'               => (int)$datos['id_rol'],
            ':id_departamento'      => (int)$datos['id_departamento'],
            ':cedula_empleado'      => mb_substr(strtoupper(trim((string)$datos['cedula_empleado'])), 0, 20, 'UTF-8'),
            ':username'             => mb_substr(strtolower(trim((string)$datos['username'])), 0, 50, 'UTF-8'),
            ':correo_institucional' => mb_substr(strtolower(trim((string)$datos['correo_institucional'])), 0, 120, 'UTF-8'),
            ':nombres'              => mb_substr(trim((string)$datos['nombres']), 0, 80, 'UTF-8'),
            ':apellidos'            => mb_substr(trim((string)$datos['apellidos']), 0, 80, 'UTF-8'),
            ':cargo_empleado'       => mb_substr(trim((string)$datos['cargo_empleado']), 0, 80, 'UTF-8'),
            ':telefono_extension'   => $datos['telefono_extension'] !== '' ? mb_substr(trim((string)$datos['telefono_extension']), 0, 25, 'UTF-8') : null,
            ':estado_cuenta'        => in_array($datos['estado_cuenta'] ?? 'Activo', ['Activo', 'Suspendido', 'Inactivo'], true) ? $datos['estado_cuenta'] : 'Activo',
            ':id_usuario'           => $idUsuario
        ]);

        if (!empty($datos['nueva_clave_admin'])) {
            $this->actualizarClave($idUsuario, (string)$datos['nueva_clave_admin']);
        }

        return $ok;
    }

    /**
     * Aplica baja lógica registrando la fecha y hora en eliminado_en
     */
    public function aplicarBajaLogica(int $idUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET eliminado_en = NOW(),
                    estado_cuenta = 'Inactivo',
                    estado_conexion = 'Offline'
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_usuario' => $idUsuario]);
    }

    /**
     * Restaura un usuario que tenía baja lógica en eliminado_en
     */
    public function restaurarBajaLogica(int $idUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET eliminado_en = NULL,
                    estado_cuenta = 'Activo',
                    intentos_fallidos = 0,
                    bloqueado_hasta = NULL
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_usuario' => $idUsuario]);
    }

    /**
     * Cuenta cuántos Superadministradores activos existen para impedir dejar el sistema sin administración
     */
    public function contarSuperadminsActivos(): int
    {
        $sql = "SELECT COUNT(*)
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                WHERE u.eliminado_en IS NULL
                  AND u.estado_cuenta = 'Activo'
                  AND r.slug_rol = 'Superadmin'";
        return (int)$this->db->query($sql)->fetchColumn();
    }

    /**
     * Lista todos los roles institucionales definidos
     */
    public function listarRoles(): array
    {
        $sql = "SELECT
                    r.*,
                    (SELECT COUNT(*) FROM usuarios u WHERE u.id_rol = r.id_rol AND u.eliminado_en IS NULL) AS total_usuarios
                FROM roles r
                ORDER BY r.id_rol ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Lista todos los módulos del sistema ordenados por su posición en el menú
     */
    public function listarModulosSistema(): array
    {
        $sql = "SELECT * FROM modulos_sistema ORDER BY orden_menu ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Devuelve la matriz completa de roles_permisos indexada por id_rol e id_modulo
     */
    public function obtenerMatrizPermisosCompleta(): array
    {
        $sql = "SELECT * FROM roles_permisos";
        $filas = $this->db->query($sql)->fetchAll();
        $matriz = [];
        foreach ($filas as $f) {
            $idRol = (int)$f['id_rol'];
            $idMod = (int)$f['id_modulo'];
            $matriz[$idRol][$idMod] = [
                'id_rol_permiso'          => (int)$f['id_rol_permiso'],
                'permiso_ver'             => (int)$f['permiso_ver'] === 1,
                'permiso_crear'           => (int)$f['permiso_crear'] === 1,
                'permiso_editar'          => (int)$f['permiso_editar'] === 1,
                'permiso_eliminar'        => (int)$f['permiso_eliminar'] === 1,
                'permiso_asignar_tickets' => (int)$f['permiso_asignar_tickets'] === 1,
            ];
        }
        return $matriz;
    }

    /**
     * Inserta o actualiza los permisos de un rol sobre un módulo específico
     */
    public function guardarPermisosRolModulo(int $idRol, int $idModulo, array $permisos): bool
    {
        $sql = "INSERT INTO roles_permisos (
                    id_rol,
                    id_modulo,
                    permiso_ver,
                    permiso_crear,
                    permiso_editar,
                    permiso_eliminar,
                    permiso_asignar_tickets
                ) VALUES (
                    :id_rol,
                    :id_modulo,
                    :ver,
                    :crear,
                    :editar,
                    :eliminar,
                    :asignar
                ) ON DUPLICATE KEY UPDATE
                    permiso_ver = VALUES(permiso_ver),
                    permiso_crear = VALUES(permiso_crear),
                    permiso_editar = VALUES(permiso_editar),
                    permiso_eliminar = VALUES(permiso_eliminar),
                    permiso_asignar_tickets = VALUES(permiso_asignar_tickets)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_rol'    => $idRol,
            ':id_modulo' => $idModulo,
            ':ver'       => !empty($permisos['permiso_ver']) ? 1 : 0,
            ':crear'     => !empty($permisos['permiso_crear']) ? 1 : 0,
            ':editar'    => !empty($permisos['permiso_editar']) ? 1 : 0,
            ':eliminar'  => !empty($permisos['permiso_eliminar']) ? 1 : 0,
            ':asignar'   => !empty($permisos['permiso_asignar_tickets']) ? 1 : 0,
        ]);
    }

    /**
     * Lista los funcionarios que actualmente tienen sesión activa en línea (estado_conexion = 'Online')
     */
    public function listarUsuariosEnLinea(): array
    {
        $sql = "SELECT
                    u.id_usuario,
                    u.id_rol,
                    u.id_departamento,
                    u.cedula_empleado,
                    u.username,
                    u.nombres,
                    u.apellidos,
                    u.cargo_empleado,
                    u.foto_perfil_ruta,
                    u.estado_cuenta,
                    u.estado_conexion,
                    u.fecha_ultimo_acceso,
                    r.slug_rol,
                    r.nombre_rol,
                    d.codigo_departamento,
                    d.nombre_departamento
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                INNER JOIN departamentos d ON u.id_departamento = d.id_departamento
                WHERE u.eliminado_en IS NULL
                  AND u.estado_conexion = 'Online'
                ORDER BY u.fecha_ultimo_acceso DESC, u.id_usuario ASC";

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Fuerza el cierre de sesión de un usuario cambiando su estado_conexion a Offline en la base de datos
     */
    public function forzarCierreSesionUsuario(int $idUsuario): bool
    {
        $sql = "UPDATE usuarios
                SET estado_conexion = 'Offline'
                WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_usuario' => $idUsuario]);
    }

    /**
     * Consulta rápida del estado de cuenta, baja lógica y conexión para validar la sesión en cada petición
     */
    public function verificarEstadoSesionViva(int $idUsuario): ?array
    {
        $sql = "SELECT id_usuario, estado_cuenta, eliminado_en, estado_conexion
                FROM usuarios
                WHERE id_usuario = :id_usuario
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }
}
