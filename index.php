<?php
declare(strict_types=1);

/**
 * Enrutador Frontal con Lista Blanca Estricta del Sistema de Gestión de Solicitudes de Soporte Técnico
 * Formato de ruta: index.php?c=controlador&a=accion
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Auditoria.php';

// Limpieza estricta de caracteres especiales para prevenir Path Traversal o Local File Inclusion
$controladorBruto = isset($_GET['c']) && is_string($_GET['c']) ? $_GET['c'] : '';
$accionBruta      = isset($_GET['a']) && is_string($_GET['a']) ? $_GET['a'] : '';

$controladorSolicitado = strtolower((string)preg_replace('/[^a-zA-Z0-9_]/', '', $controladorBruto));
$accionSolicitada      = (string)preg_replace('/[^a-zA-Z0-9_]/', '', $accionBruta);

// Lista blanca estricta de controladores, archivos físicos, módulos RBAC y métodos públicos permitidos
$listaBlancaRutas = [
    'auth' => [
        'clase'     => 'AuthController',
        'archivo'   => __DIR__ . '/controllers/AuthController.php',
        'clave_mod' => 'Mod1_Auth',
        'id_modulo' => 1,
        'acciones'  => [
            'index',
            'login',
            'logout',
            'recuperar',
            'perfil',
            'actualizarPerfil',
            'cambiarClave',
            'actualizarSeguridad',
            'resolverRecuperacion'
        ]
    ],
    'admin' => [
        'clase'     => 'AdminController',
        'archivo'   => __DIR__ . '/controllers/AdminController.php',
        'clave_mod' => 'Mod2_Admin',
        'id_modulo' => 2,
        'acciones'  => [
            'index',
            'guardarUsuario',
            'bajaLogica',
            'desbloquear',
            'guardarPermisos',
            'generarRespaldo',
            'descargarRespaldo',
            'eliminarRespaldo',
            'forzarDesconexion',
            'depurarAccesos'
        ]
    ],
    'inventario' => [
        'clase'     => 'InventarioController',
        'archivo'   => __DIR__ . '/controllers/InventarioController.php',
        'clave_mod' => 'Mod3_Inventario',
        'id_modulo' => 3,
        'acciones'  => [
            'index',
            'guardarDepartamento',
            'guardarEquipo',
            'guardarCategoriaFalla'
        ]
    ],
    'portal' => [
        'clase'     => 'PortalController',
        'archivo'   => __DIR__ . '/controllers/PortalController.php',
        'clave_mod' => 'Mod4_Portal',
        'id_modulo' => 4,
        'acciones'  => [
            'index',
            'crear',
            'comentar',
            'confirmar'
        ]
    ],
    'kanban' => [
        'clase'     => 'KanbanController',
        'archivo'   => __DIR__ . '/controllers/KanbanController.php',
        'clave_mod' => 'Mod5_Kanban',
        'id_modulo' => 5,
        'acciones'  => [
            'index',
            'asignar',
            'rechazar',
            'mover',
            'comentar',
            'registrarManual',
            'archivarMes'
        ]
    ],
    'dashboard' => [
        'clase'     => 'DashboardController',
        'archivo'   => __DIR__ . '/controllers/DashboardController.php',
        'clave_mod' => 'Mod6_Dashboard',
        'id_modulo' => 6,
        'acciones'  => [
            'index',
            'exportar'
        ]
    ]
];

// Si no hay sesión iniciada, permitir únicamente las rutas públicas de autenticación
if (!sesion_activa()) {
    $controlador = 'auth';
    $accionesPublicas = ['login', 'recuperar', 'index', 'logout'];
    if ($controladorSolicitado !== '' && $controladorSolicitado !== 'auth') {
        if (!isset($_SESSION['alerta_sistema'])) {
            establecer_alerta('advertencia', 'Inicia sesión para acceder al sistema.');
        }
        redirigir('auth', 'login');
    }
    $accion = in_array($accionSolicitada, $accionesPublicas, true) ? $accionSolicitada : 'login';
} else {
    // Usuario con sesión activa: definir ruta por defecto según su rol si no indicó controlador
    if ($controladorSolicitado === '') {
        $rutaRol = obtener_ruta_inicio_por_rol((string)$_SESSION['slug_rol']);
        $controlador = $rutaRol['c'];
        $accion      = $rutaRol['a'];
    } else {
        $controlador = $controladorSolicitado;
        $accion      = $accionSolicitada !== '' ? $accionSolicitada : 'index';
    }

    if (!isset($listaBlancaRutas[$controlador])) {
        establecer_alerta('advertencia', 'La sección solicitada no existe. Te redirigimos a tu cuenta.');
        redirigir('auth', 'perfil');
    }

    $infoModulo = $listaBlancaRutas[$controlador];
    if ($controlador !== 'auth' && !tiene_permiso_modulo($infoModulo['clave_mod'], 'permiso_ver')) {
        $auditoria = new Auditoria();
        $auditoria->registrarAcceso(
            (int)$_SESSION['id_usuario'],
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            'Intento de acceso sin permiso al módulo ' . $infoModulo['clave_mod'],
            $infoModulo['id_modulo']
        );
        establecer_alerta('error', 'Tu rol actual no tiene acceso a esa sección del sistema.');
        redirigir('auth', 'perfil');
    }
}

$configuracionRuta = $listaBlancaRutas[$controlador];

// Validar que la acción solicitada esté estrictamente dentro de la lista blanca de métodos públicos del controlador
if (!in_array($accion, $configuracionRuta['acciones'], true)) {
    $accion = 'index';
}

$rutaArchivoControlador = $configuracionRuta['archivo'];
$nombreClaseControlador = $configuracionRuta['clase'];

if (!is_file($rutaArchivoControlador)) {
    http_response_code(404);
    die('No se encontró el módulo solicitado.');
}

require_once $rutaArchivoControlador;

if (!class_exists($nombreClaseControlador)) {
    redirigir('auth', 'perfil');
}

$instanciaControlador = new $nombreClaseControlador();

if (!method_exists($instanciaControlador, $accion)) {
    redirigir('auth', 'perfil');
}

$instanciaControlador->{$accion}();
