<?php
declare(strict_types=1);

/**
 * Configuración global del Sistema de Gestión de Solicitudes de Soporte Técnico
 * Entorno: WampServer 3.4.0 - PHP 8.x POO y PDO Estricto
 */

date_default_timezone_set('America/Caracas');
mb_internal_encoding('UTF-8');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self';");
}

if (session_status() === PHP_SESSION_NONE) {
    $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Parámetros de conexión a MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_tickets_soporte');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Identidad institucional
define('APP_NOMBRE', 'Sistema de Atención y Soporte Técnico');
define('APP_INSTITUCION', 'Unidad Institucional de Tecnología y Sistemas');

// Políticas de seguridad de acceso y sesión en intranet
define('MAX_INTENTOS_FALLIDOS', 3);
define('MINUTOS_BLOQUEO_CUENTA', 15);
define('TIEMPO_INACTIVIDAD_SESION', 1800); // 30 minutos de inactividad máxima

// Cálculo dinámico de ruta base
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '/sistema_tickets';
$scriptDir = rtrim($scriptDir, '/');
define('BASE_URL', $protocolo . $host . $scriptDir . '/');

/**
 * Genera una dirección completa dentro de la aplicación
 */
function ruta_url(string $controlador = 'auth', string $accion = 'login', array $parametros = []): string
{
    $consulta = array_merge(['c' => $controlador, 'a' => $accion], $parametros);
    return BASE_URL . 'index.php?' . http_build_query($consulta);
}

/**
 * Genera la ruta hacia un recurso público estático
 */
function recurso_publico(string $rutaRelativa): string
{
    $rutaLimpia = ltrim($rutaRelativa, '/');
    if (str_starts_with($rutaLimpia, 'public/')) {
        return BASE_URL . $rutaLimpia;
    }
    return BASE_URL . 'public/' . $rutaLimpia;
}

/**
 * Escapa cadenas de texto para impresión segura en vistas HTML
 */
function e(mixed $valor): string
{
    if ($valor === null) {
        return '';
    }
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Limpia espacios innecesarios y caracteres de control en entradas de formularios
 */
function limpiar_texto(?string $valor): string
{
    if ($valor === null) {
        return '';
    }
    return trim(strip_tags($valor));
}

/**
 * Normaliza respuestas de seguridad eliminando tildes, signos y diferencias de mayúsculas
 */
function normalizar_respuesta_seguridad(string $texto): string
{
    $reemplazos = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'ü' => 'u', 'Ü' => 'u', 'ñ' => 'n', 'Ñ' => 'n',
        '¿' => '', '?' => '', '!' => '', '¡' => '',
        '.' => '', ',' => '', ';' => ''
    ];
    $limpio = strtr(trim($texto), $reemplazos);
    $limpio = mb_strtolower($limpio, 'UTF-8');
    $limpio = preg_replace('/\s+/', ' ', $limpio) ?? $limpio;
    return trim($limpio);
}

/**
 * Genera o recupera el código de protección contra envíos externos falsificados
 */
function obtener_token_csrf(): string
{
    if (empty($_SESSION['token_seguridad_csrf'])) {
        $_SESSION['token_seguridad_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['token_seguridad_csrf'];
}

/**
 * Verifica la validez del código de seguridad del formulario
 */
function verificar_token_csrf(?string $tokenRecibido): bool
{
    if (empty($_SESSION['token_seguridad_csrf']) || empty($tokenRecibido)) {
        return false;
    }
    return hash_equals((string)$_SESSION['token_seguridad_csrf'], (string)$tokenRecibido);
}

/**
 * Registra un mensaje temporal en la sesión para mostrarlo en la siguiente pantalla
 */
function establecer_alerta(string $tipo, string $mensaje): void
{
    $_SESSION['alerta_sistema'] = [
        'tipo'    => $tipo,
        'mensaje' => $mensaje
    ];
}

/**
 * Obtiene y limpia el mensaje temporal almacenado en sesión
 */
function consumir_alerta(): ?array
{
    if (!isset($_SESSION['alerta_sistema'])) {
        return null;
    }
    $alerta = $_SESSION['alerta_sistema'];
    unset($_SESSION['alerta_sistema']);
    return $alerta;
}

/**
 * Redirige hacia un controlador y detiene la ejecución actual
 */
function redirigir(string $controlador, string $accion = 'index', array $parametros = []): never
{
    header('Location: ' . ruta_url($controlador, $accion, $parametros));
    exit;
}

/**
 * Indica si existe un usuario con sesión activa, controla la expiración por inactividad (30 minutos)
 * y verifica en base de datos que la cuenta siga activa, sin baja lógica y con conexión autorizada (Online)
 */
function sesion_activa(): bool
{
    if (empty($_SESSION['id_usuario']) || empty($_SESSION['id_rol']) || empty($_SESSION['slug_rol'])) {
        return false;
    }

    $ahora = time();
    if (isset($_SESSION['ultima_actividad_ts']) && ($ahora - (int)$_SESSION['ultima_actividad_ts']) > TIEMPO_INACTIVIDAD_SESION) {
        $_SESSION = [];
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        establecer_alerta(
            'advertencia',
            'Tu sesión se cerró por seguridad tras 30 minutos de inactividad. Ingresa nuevamente tus credenciales.'
        );
        return false;
    }

    $idUsuarioSesion = (int)$_SESSION['id_usuario'];
    $idUsuarioVerificadoBd = $GLOBALS['__id_usuario_verificado_bd'] ?? null;

    if ($idUsuarioVerificadoBd !== $idUsuarioSesion) {
        try {
            require_once __DIR__ . '/Database.php';
            $pdo = Database::conectar();
            $stmt = $pdo->prepare(
                'SELECT estado_cuenta, eliminado_en, estado_conexion FROM usuarios WHERE id_usuario = :id_usuario LIMIT 1'
            );
            $stmt->execute([':id_usuario' => $idUsuarioSesion]);
            $estadoBd = $stmt->fetch();

            if (
                !$estadoBd ||
                $estadoBd['eliminado_en'] !== null ||
                $estadoBd['estado_cuenta'] !== 'Activo' ||
                $estadoBd['estado_conexion'] !== 'Online'
            ) {
                $fueDesconectado = ($estadoBd && $estadoBd['eliminado_en'] === null && $estadoBd['estado_cuenta'] === 'Activo' && $estadoBd['estado_conexion'] === 'Offline');
                $GLOBALS['__id_usuario_verificado_bd'] = null;
                $_SESSION = [];
                if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                establecer_alerta(
                    'advertencia',
                    $fueDesconectado
                        ? 'Tu sesión fue cerrada desde el panel de administración de seguridad. Inicia sesión nuevamente.'
                        : 'Tu cuenta fue suspendida o dada de baja por el administrador del sistema.'
                );
                return false;
            }

            $GLOBALS['__id_usuario_verificado_bd'] = $idUsuarioSesion;
        } catch (Throwable) {
            // En caso de mantenimiento puntual de conexión se conserva la validación de sesión en memoria
        }
    }

    $_SESSION['ultima_actividad_ts'] = $ahora;
    return true;
}

/**
 * Valida y almacena de forma segura un archivo subido verificando extensión, peso,
 * firma MIME real con finfo_open(FILEINFO_MIME_TYPE) / finfo_file() y nombre físico aleatorio
 */
function procesar_archivo_subido_seguro(
    array $archivoFile,
    string $directorioDestinoAbsoluto,
    string $prefijoRutaRelativa,
    array $mapaExtensionesMimePermitidos,
    int $pesoMaximoBytes = 5242880
): array {
    if (empty($archivoFile['name']) || !isset($archivoFile['error']) || (int)$archivoFile['error'] !== UPLOAD_ERR_OK) {
        return ['exito' => false, 'mensaje' => 'No se recibió un archivo válido para procesar.'];
    }

    $tmpName   = (string)($archivoFile['tmp_name'] ?? '');
    $pesoBytes = (int)($archivoFile['size'] ?? 0);

    $esSubidaValida = ($tmpName !== '' && (is_uploaded_file($tmpName) || (PHP_SAPI === 'cli' && is_file($tmpName))));
    if (!$esSubidaValida) {
        return ['exito' => false, 'mensaje' => 'El origen del archivo cargado no es válido.'];
    }

    if ($pesoBytes <= 0 || $pesoBytes > $pesoMaximoBytes) {
        $maxMb = round($pesoMaximoBytes / (1024 * 1024), 1);
        return ['exito' => false, 'mensaje' => "El archivo supera el tamaño máximo permitido de {$maxMb} megabytes."];
    }

    $nombreBruto = basename((string)$archivoFile['name']);
    $nombreOriginal = limpiar_texto((string)preg_replace('/[\x00-\x1F\x7F]/u', '', $nombreBruto));
    if ($nombreOriginal === '') {
        $nombreOriginal = 'archivo_adjunto';
    }
    $nombreOriginal = mb_substr($nombreOriginal, 0, 180, 'UTF-8');

    $extension = strtolower((string)pathinfo($nombreBruto, PATHINFO_EXTENSION));
    if ($extension === '' || !isset($mapaExtensionesMimePermitidos[$extension])) {
        $permitidasTexto = strtoupper(implode(', ', array_keys($mapaExtensionesMimePermitidos)));
        return ['exito' => false, 'mensaje' => "Formato no permitido. Solo se aceptan archivos {$permitidasTexto}."];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return ['exito' => false, 'mensaje' => 'No se pudo inicializar el verificador de seguridad de archivos.'];
    }
    $mimeLeido = @finfo_file($finfo, $tmpName);
    unset($finfo);
    if ($mimeLeido === false) {
        return ['exito' => false, 'mensaje' => 'No fue posible inspeccionar el contenido del archivo enviado.'];
    }
    $mimeReal = strtolower(trim((string)$mimeLeido));

    $mimesValidosParaExtension = $mapaExtensionesMimePermitidos[$extension];
    if (!in_array($mimeReal, $mimesValidosParaExtension, true)) {
        return ['exito' => false, 'mensaje' => 'El contenido real del archivo no coincide con el formato permitido.'];
    }

    // Verificación adicional de estructura interna para imágenes y documentos PDF
    if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
        if (@getimagesize($tmpName) === false) {
            return ['exito' => false, 'mensaje' => 'El archivo seleccionado no es una imagen válida.'];
        }
    } elseif ($extension === 'pdf') {
        $cabeceraPdf = (string)@file_get_contents($tmpName, false, null, 0, 5);
        if ($cabeceraPdf !== '%PDF-') {
            return ['exito' => false, 'mensaje' => 'El documento seleccionado no tiene una estructura PDF válida.'];
        }
    }

    $directorioLimpio = rtrim($directorioDestinoAbsoluto, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($directorioLimpio)) {
        mkdir($directorioLimpio, 0755, true);
    }

    $nombreFisicoSeguro = bin2hex(random_bytes(16)) . '.' . $extension;
    $rutaFisicaCompleta = $directorioLimpio . $nombreFisicoSeguro;

    $movido = is_uploaded_file($tmpName)
        ? move_uploaded_file($tmpName, $rutaFisicaCompleta)
        : @copy($tmpName, $rutaFisicaCompleta);

    if (!$movido) {
        return ['exito' => false, 'mensaje' => 'No fue posible almacenar el archivo en el servidor.'];
    }

    @chmod($rutaFisicaCompleta, 0644);

    return [
        'exito'           => true,
        'nombre_original' => $nombreOriginal,
        'nombre_fisico'   => $nombreFisicoSeguro,
        'extension'       => $extension,
        'mime_type'       => $mimeReal,
        'peso_kb'         => max(1, (int)ceil($pesoBytes / 1024)),
        'ruta_relativa'   => rtrim($prefijoRutaRelativa, '/') . '/' . $nombreFisicoSeguro
    ];
}

/**
 * Devuelve el controlador principal según el rol institucional del usuario
 */
function obtener_ruta_inicio_por_rol(string $slugRol): array
{
    return match ($slugRol) {
        'Superadmin'       => ['c' => 'admin', 'a' => 'index'],
        'Jefe_Soporte'     => ['c' => 'kanban', 'a' => 'index'],
        'Personal_Soporte' => ['c' => 'kanban', 'a' => 'index'],
        'Usuario_Comun'    => ['c' => 'portal', 'a' => 'index'],
        default            => ['c' => 'auth', 'a' => 'perfil'],
    };
}

/**
 * Verifica en la matriz de permisos de la sesión si el usuario puede ejecutar una acción
 */
function tiene_permiso_modulo(string $claveModulo, string $campoPermiso = 'permiso_ver'): bool
{
    if (!sesion_activa()) {
        return false;
    }
    if ($_SESSION['slug_rol'] === 'Superadmin' && $claveModulo !== 'Mod4_Portal') {
        return true;
    }
    $permisos = $_SESSION['permisos_modulos'] ?? [];
    if (!isset($permisos[$claveModulo])) {
        return false;
    }
    return !empty($permisos[$claveModulo][$campoPermiso]);
}

/**
 * Obtiene la dirección IP real desde donde se conecta el cliente en la red local
 */
function obtener_ip_cliente(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (str_contains($ip, ',')) {
        $partes = explode(',', $ip);
        $ip = trim($partes[0]);
    }
    if ($ip === '::1') {
        return '127.0.0.1';
    }
    return substr($ip, 0, 45);
}

/**
 * Obtiene una descripción legible y corta del navegador utilizado
 */
function obtener_navegador_cliente(): string
{
    $agente = $_SERVER['HTTP_USER_AGENT'] ?? 'Navegador institucional en red local';
    return mb_substr($agente, 0, 250, 'UTF-8');
}

/**
 * Formatea una fecha SQL a formato legible en español sin paréntesis
 */
function formatear_fecha_esp(?string $fechaSql, bool $conHora = true): string
{
    if (empty($fechaSql)) {
        return 'Sin registro previo';
    }
    $timestamp = strtotime($fechaSql);
    if ($timestamp === false) {
        return 'Sin registro previo';
    }
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('n', $timestamp)] ?? '';
    $anio = date('Y', $timestamp);

    if (!$conHora) {
        return "{$dia} de {$mes} de {$anio}";
    }

    $hora = date('h:i a', $timestamp);
    return "{$dia} de {$mes} de {$anio} a las {$hora}";
}

/**
 * Traduce valores técnicos de prioridad a su clase visual oficial en estilo Soft Pill según sección 3 de AGENT.md
 */
function clase_insignia_prioridad(?string $prioridad): string
{
    return match ($prioridad) {
        'Critica' => 'insignia-suave insignia-suave-critica',
        'Alta'    => 'insignia-suave insignia-suave-alta',
        'Media'   => 'insignia-suave insignia-suave-media',
        'Baja'    => 'insignia-suave insignia-suave-baja',
        default   => 'insignia-suave insignia-suave-neutra',
    };
}

/**
 * Devuelve la clase de etiqueta Soft Pill correspondiente a estados operativos y de flujo
 */
function clase_insignia_estado(?string $estado): string
{
    return match ($estado) {
        '5_Resuelto', 'Confirmado_Conforme', 'Operativo', 'Activo', 'Online', 'Aceptado', 'Si'
            => 'insignia-suave insignia-suave-baja',
        '3_En_Proceso', '2_Asignado', 'Login_Exitoso'
            => 'insignia-suave insignia-suave-media',
        '4_En_Espera', 'En_Reparacion', 'Pendiente_Revision', 'Suspendido', 'Login_Fallido'
            => 'insignia-suave insignia-suave-alta',
        'Rechazado', 'Reabierto_No_Funciona', 'Danado', 'Bloqueo_Seguridad', 'No'
            => 'insignia-suave insignia-suave-critica',
        default
            => 'insignia-suave insignia-suave-neutra',
    };
}

/**
 * Traduce nombres de columnas o estados técnicos a español claro sin guiones bajos
 */
function texto_estado_legible(?string $estado): string
{
    return match ($estado) {
        '1_Por_Aceptar'         => 'Por aceptar',
        '2_Asignado'            => 'Asignado',
        '3_En_Proceso'          => 'En atención',
        '4_En_Espera'           => 'En espera',
        '5_Resuelto'            => 'Resuelto',
        'Pendiente_Revision'    => 'Pendiente de revisión',
        'Aceptado'              => 'Aceptado',
        'Rechazado'             => 'Rechazado',
        'Confirmado_Conforme'   => 'Confirmado conforme',
        'Reabierto_No_Funciona' => 'Reabierto por falla persistente',
        'Login_Exitoso'         => 'Inicio de sesión correcto',
        'Login_Fallido'         => 'Intento de acceso fallido',
        'Logout'                => 'Cierre de sesión',
        'Bloqueo_Seguridad'     => 'Bloqueo preventivo de cuenta',
        'Navegacion_Modulo'     => 'Acceso a módulo del sistema',
        'Online'                => 'Conectado',
        'Offline'               => 'Desconectado',
        'Chat_P2P_Soporte'      => 'Validación por pregunta de seguridad',
        'Reset_Manual'          => 'Restablecimiento asistido por soporte',
        'Correo_Automatico'     => 'Restablecimiento por correo interno',
        'Hardware'              => 'Componentes físicos',
        'Software'              => 'Programas y sistemas',
        'Red_Internet'          => 'Red y conectividad',
        'Correo_Institucional'  => 'Correo institucional',
        'Soporte_General'       => 'Asistencia técnica general',
        'tickets'                        => 'Solicitudes de soporte',
        'ticket_asignaciones'            => 'Asignaciones de casos',
        'ticket_comentarios'             => 'Comentarios de casos',
        'ticket_adjuntos'                => 'Archivos adjuntos de casos',
        'roles'                          => 'Roles institucionales',
        'modulos_sistema'                => 'Módulos del sistema',
        'roles_permisos'                 => 'Matriz de permisos por rol',
        'departamentos'                  => 'Departamentos y oficinas',
        'usuarios'                       => 'Cuentas de funcionarios',
        'solicitudes_recuperacion_clave' => 'Solicitudes de recuperación de clave',
        'equipos'                        => 'Inventario de equipos informáticos',
        'categorias_falla'               => 'Catálogo de categorías de falla',
        'logs_sistema_accesos'           => 'Registros de acceso al sistema',
        'changelogs_auditoria'           => 'Historial de cambios de auditoría',
        'historial_reportes_generados'   => 'Historial de reportes estadísticos',
        'vw_metricas_dashboard'          => 'Vista consolidada de métricas',
        'db_tickets_soporte'             => 'Base de datos institucional',
        'respaldo_sql'                   => 'Copia de seguridad de base de datos',
        'depuracion_mantenimiento'       => 'Depuración de registros antiguos',
        'estado_conexion'                => 'Estado de sesión en línea',
        'avance_tecnico_kanban'          => 'Avance técnico en tablero',
        'estado_columna_kanban'          => 'Etapa en el tablero',
        'Critica'                        => 'Crítica',
        default                          => str_replace('_', ' ', (string)$estado),
    };
}
