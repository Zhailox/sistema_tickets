<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Departamento.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Controlador del Módulo 2: Seguridad, Gestión de Usuarios con Baja Lógica,
 * Matriz de Permisos por Rol, Recuperación de Claves y Auditoría del Sistema
 */
class AdminController
{
    private Usuario $usuarioModel;
    private Departamento $departamentoModel;
    private Auditoria $auditoriaModel;

    public function __construct()
    {
        if (!sesion_activa()) {
            establecer_alerta('advertencia', 'Inicia sesión para acceder al módulo de seguridad.');
            redirigir('auth', 'login');
        }

        if (!tiene_permiso_modulo('Mod2_Admin', 'permiso_ver')) {
            establecer_alerta('error', 'Solo el Superadministrador tiene acceso al módulo de seguridad y auditoría.');
            redirigir('auth', 'perfil');
        }

        $this->usuarioModel      = new Usuario();
        $this->departamentoModel = new Departamento();
        $this->auditoriaModel    = new Auditoria();
    }

    /**
     * Panel integral de administración de usuarios, roles, permisos y registros de auditoría
     */
    public function index(): void
    {
        $pestanaActiva = $_GET['tab'] ?? 'usuarios';

        $usuarios      = $this->usuarioModel->listarUsuariosAdmin(true);
        $departamentos = $this->departamentoModel->listarTodos(false);
        $roles         = $this->usuarioModel->listarRoles();
        $modulos       = $this->usuarioModel->listarModulosSistema();
        $matrizPermisos = $this->usuarioModel->obtenerMatrizPermisosCompleta();
        $solicitudesRecuperacion = $this->usuarioModel->listarSolicitudesRecuperacion(false, 40);

        $usuarioEdicion = null;
        if (!empty($_GET['editar_usuario'])) {
            $usuarioEdicion = $this->usuarioModel->obtenerPorIdIncluirEliminados((int)$_GET['editar_usuario']);
            $pestanaActiva  = 'usuarios';
        }

        // Filtros para los registros de acceso
        $filtrosAccesos = [
            'tipo_evento' => isset($_GET['evento']) ? limpiar_texto($_GET['evento']) : '',
            'fecha_desde' => isset($_GET['desde']) ? limpiar_texto($_GET['desde']) : '',
            'fecha_hasta' => isset($_GET['hasta']) ? limpiar_texto($_GET['hasta']) : '',
            'busqueda'    => isset($_GET['q_acceso']) ? limpiar_texto($_GET['q_acceso']) : ''
        ];
        $registrosAcceso = $this->auditoriaModel->listarAccesosGlobal($filtrosAccesos, 150);

        // Filtros para el historial de cambios
        $filtrosCambios = [
            'tabla_afectada' => isset($_GET['tabla']) ? limpiar_texto($_GET['tabla']) : '',
            'tipo_operacion' => isset($_GET['operacion']) ? limpiar_texto($_GET['operacion']) : '',
            'busqueda'       => isset($_GET['q_cambio']) ? limpiar_texto($_GET['q_cambio']) : ''
        ];
        $historialCambios = $this->auditoriaModel->listarCambiosGlobal($filtrosCambios, 150);

        // Datos de administración de infraestructura, respaldos de base de datos, sesiones activas y mantenimiento
        $raizProyecto         = dirname(__DIR__);
        $directorioBackups    = $raizProyecto . '/database/backups';
        $usuariosEnLinea      = $this->usuarioModel->listarUsuariosEnLinea();
        $respaldosExistentes  = $this->auditoriaModel->listarRespaldosExistentes($directorioBackups);
        $diagnosticoServidor  = $this->auditoriaModel->obtenerDiagnosticoServidor($raizProyecto);
        $resumenMantenimiento = $this->auditoriaModel->obtenerResumenMantenimiento($raizProyecto);

        $tituloPagina = 'Seguridad, usuarios y auditoría';
        require __DIR__ . '/../views/admin/index.php';
    }

    /**
     * Crea o actualiza un funcionario desde el panel administrativo
     */
    public function guardarUsuario(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('admin', 'index');
        }

        $idUsuario       = (int)($_POST['id_usuario'] ?? 0);
        $idRol           = (int)($_POST['id_rol'] ?? 4);
        $idDepartamento  = (int)($_POST['id_departamento'] ?? 0);
        $cedula          = strtoupper(limpiar_texto($_POST['cedula_empleado'] ?? ''));
        $username        = strtolower(limpiar_texto($_POST['username'] ?? ''));
        $correo          = strtolower(limpiar_texto($_POST['correo_institucional'] ?? ''));
        $nombres         = limpiar_texto($_POST['nombres'] ?? '');
        $apellidos       = limpiar_texto($_POST['apellidos'] ?? '');
        $cargo           = limpiar_texto($_POST['cargo_empleado'] ?? '');
        $extension       = limpiar_texto($_POST['telefono_extension'] ?? '');
        $estadoCuenta    = limpiar_texto($_POST['estado_cuenta'] ?? 'Activo');
        $clavePlana      = (string)($_POST['clave_usuario'] ?? '');

        if ($idDepartamento <= 0 || $cedula === '' || $username === '' || $correo === '' || $nombres === '' || $apellidos === '' || $cargo === '') {
            establecer_alerta('advertencia', 'Completa todos los datos obligatorios del funcionario.');
            redirigir('admin', 'index');
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            establecer_alerta('error', 'La dirección de correo institucional no tiene un formato válido.');
            redirigir('admin', 'index');
        }

        if ($this->usuarioModel->existeCedulaEnOtroUsuario($cedula, $idUsuario)) {
            establecer_alerta('error', "La cédula {$cedula} ya pertenece a otro funcionario registrado.");
            redirigir('admin', 'index');
        }

        if ($this->usuarioModel->existeUsernameEnOtroUsuario($username, $idUsuario)) {
            establecer_alerta('error', "El nombre de usuario {$username} ya está en uso.");
            redirigir('admin', 'index');
        }

        if ($this->usuarioModel->existeCorreoEnOtroUsuario($correo, $idUsuario)) {
            establecer_alerta('error', "El correo {$correo} ya pertenece a otra cuenta.");
            redirigir('admin', 'index');
        }

        $idAutor = (int)$_SESSION['id_usuario'];

        if ($idUsuario > 0) {
            $previo = $this->usuarioModel->obtenerPorIdIncluirEliminados($idUsuario);
            if ($previo === null) {
                establecer_alerta('error', 'No se encontró el usuario indicado.');
                redirigir('admin', 'index');
            }

            // Proteger al último Superadministrador activo
            if ($previo['slug_rol'] === 'Superadmin' && ($idRol !== 1 || $estadoCuenta !== 'Activo')) {
                if ($this->usuarioModel->contarSuperadminsActivos() <= 1) {
                    establecer_alerta('error', 'No puedes desactivar ni cambiar el rol del único Superadministrador activo.');
                    redirigir('admin', 'index');
                }
            }

            if ($clavePlana !== '' && mb_strlen($clavePlana, 'UTF-8') < 6) {
                establecer_alerta('advertencia', 'Si deseas asignar una nueva contraseña debe tener mínimo 6 caracteres.');
                redirigir('admin', 'index', ['editar_usuario' => $idUsuario]);
            }

            $this->usuarioModel->actualizarUsuarioAdmin($idUsuario, [
                'id_rol'               => $idRol,
                'id_departamento'      => $idDepartamento,
                'cedula_empleado'      => $cedula,
                'username'             => $username,
                'correo_institucional' => $correo,
                'nombres'              => $nombres,
                'apellidos'            => $apellidos,
                'cargo_empleado'       => $cargo,
                'telefono_extension'   => $extension,
                'estado_cuenta'        => $estadoCuenta,
                'nueva_clave_admin'    => $clavePlana
            ]);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'usuarios',
                $idUsuario,
                'MODIFICACION',
                'ficha_administrativa',
                "{$previo['username']} — Rol {$previo['id_rol']} — {$previo['estado_cuenta']}",
                "{$username} — Rol {$idRol} — {$estadoCuenta}",
                "Actualización administrativa de cuenta para {$nombres} {$apellidos}"
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Actualizó la ficha del funcionario {$username}",
                2
            );

            establecer_alerta('exito', "Los datos del funcionario {$nombres} {$apellidos} fueron actualizados.");
        } else {
            $claveInicial = $clavePlana !== '' ? $clavePlana : '123456';
            if (mb_strlen($claveInicial, 'UTF-8') < 6) {
                establecer_alerta('advertencia', 'La contraseña inicial debe tener al menos 6 caracteres.');
                redirigir('admin', 'index');
            }

            $nuevoId = $this->usuarioModel->crearUsuarioAdmin([
                'id_rol'               => $idRol,
                'id_departamento'      => $idDepartamento,
                'cedula_empleado'      => $cedula,
                'username'             => $username,
                'correo_institucional' => $correo,
                'clave_plana'          => $claveInicial,
                'nombres'              => $nombres,
                'apellidos'            => $apellidos,
                'cargo_empleado'       => $cargo,
                'telefono_extension'   => $extension,
                'estado_cuenta'        => $estadoCuenta,
                'pregunta_seguridad'   => $_POST['pregunta_seguridad'] ?? '¿Nombre de primera mascota?',
                'respuesta_seguridad'  => $_POST['respuesta_seguridad'] ?? 'soporte'
            ]);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'usuarios',
                $nuevoId,
                'CREACION',
                'username',
                null,
                "{$username} — {$nombres} {$apellidos}",
                'Creación de cuenta de usuario en el módulo de seguridad'
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Registró al nuevo funcionario {$username} en el sistema",
                2
            );

            establecer_alerta('exito', "Se creó la cuenta para {$nombres} {$apellidos} correctamente.");
        }

        redirigir('admin', 'index', ['tab' => 'usuarios']);
    }

    /**
     * Aplica baja lógica en eliminado_en o restaura una cuenta dada de baja
     */
    public function bajaLogica(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó.');
            redirigir('admin', 'index');
        }

        $idAutor   = (int)$_SESSION['id_usuario'];
        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        $accion    = ($_POST['accion_baja'] ?? '') === 'restaurar' ? 'restaurar' : 'eliminar';

        if ($idUsuario <= 0) {
            redirigir('admin', 'index');
        }

        if ($idUsuario === $idAutor && $accion === 'eliminar') {
            establecer_alerta('error', 'No puedes dar de baja tu propia cuenta mientras tienes la sesión abierta.');
            redirigir('admin', 'index');
        }

        $usuario = $this->usuarioModel->obtenerPorIdIncluirEliminados($idUsuario);
        if ($usuario === null) {
            establecer_alerta('error', 'El usuario indicado no existe.');
            redirigir('admin', 'index');
        }

        if ($accion === 'eliminar') {
            if ($usuario['slug_rol'] === 'Superadmin' && $this->usuarioModel->contarSuperadminsActivos() <= 1) {
                establecer_alerta('error', 'No se puede aplicar baja lógica al único Superadministrador activo.');
                redirigir('admin', 'index');
            }

            $this->usuarioModel->aplicarBajaLogica($idUsuario);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'usuarios',
                $idUsuario,
                'ELIMINACION_LOGICA',
                'eliminado_en',
                'Cuenta activa',
                date('Y-m-d H:i:s'),
                "Baja lógica aplicada al usuario {$usuario['username']}"
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Aplicó baja lógica a la cuenta {$usuario['username']}",
                2
            );

            establecer_alerta('informacion', "Se aplicó la baja lógica a la cuenta de {$usuario['nombres']} {$usuario['apellidos']}.");
        } else {
            $this->usuarioModel->restaurarBajaLogica($idUsuario);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'usuarios',
                $idUsuario,
                'MODIFICACION',
                'eliminado_en',
                (string)$usuario['eliminado_en'],
                'Cuenta restaurada y activa',
                "Reactivación de cuenta para {$usuario['username']}"
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Restauró la cuenta del funcionario {$usuario['username']}",
                2
            );

            establecer_alerta('exito', "La cuenta de {$usuario['nombres']} {$usuario['apellidos']} fue reactivada.");
        }

        redirigir('admin', 'index', ['tab' => 'usuarios']);
    }

    /**
     * Desbloquea manualmente una cuenta bloqueada por intentos fallidos
     */
    public function desbloquear(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó.');
            redirigir('admin', 'index');
        }

        $idAutor   = (int)$_SESSION['id_usuario'];
        $idUsuario = (int)($_POST['id_usuario'] ?? 0);
        $usuario   = $this->usuarioModel->obtenerPorIdIncluirEliminados($idUsuario);

        if ($usuario !== null) {
            $this->usuarioModel->reiniciarIntentosFallidos($idUsuario);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'usuarios',
                $idUsuario,
                'MODIFICACION',
                'bloqueado_hasta',
                (string)($usuario['bloqueado_hasta'] ?? 'Con intentos fallidos'),
                'Desbloqueado manualmente',
                "Desbloqueo administrativo de la cuenta {$usuario['username']}"
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Desbloqueó manualmente la cuenta {$usuario['username']}",
                2
            );

            establecer_alerta('exito', "La cuenta de {$usuario['nombres']} {$usuario['apellidos']} fue desbloqueada.");
        }

        redirigir('admin', 'index', ['tab' => 'usuarios']);
    }

    /**
     * Guarda los cambios en la matriz de permisos por rol (roles_permisos)
     */
    public function guardarPermisos(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index', ['tab' => 'permisos']);
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('admin', 'index', ['tab' => 'permisos']);
        }

        $idRolSeleccionado = (int)($_POST['id_rol'] ?? 0);
        if ($idRolSeleccionado <= 0) {
            redirigir('admin', 'index', ['tab' => 'permisos']);
        }

        $modulos = $this->usuarioModel->listarModulosSistema();
        $permisosRecibidos = $_POST['permisos'] ?? [];

        foreach ($modulos as $mod) {
            $idMod = (int)$mod['id_modulo'];
            $cfg   = $permisosRecibidos[$idMod] ?? [];

            // Garantizar que Superadmin siempre conserve permiso de ver en Auth y Admin
            $forzarVer = ($idRolSeleccionado === 1 && ($idMod === 1 || $idMod === 2));

            $this->usuarioModel->guardarPermisosRolModulo($idRolSeleccionado, $idMod, [
                'permiso_ver'             => $forzarVer ? 1 : (!empty($cfg['ver']) ? 1 : 0),
                'permiso_crear'           => !empty($cfg['crear']) ? 1 : 0,
                'permiso_editar'          => !empty($cfg['editar']) ? 1 : 0,
                'permiso_eliminar'        => !empty($cfg['eliminar']) ? 1 : 0,
                'permiso_asignar_tickets' => !empty($cfg['asignar']) ? 1 : 0,
            ]);
        }

        $idAutor = (int)$_SESSION['id_usuario'];
        $this->auditoriaModel->registrarCambio(
            $idAutor,
            'roles_permisos',
            $idRolSeleccionado,
            'MODIFICACION',
            'matriz_permisos_rol',
            'Configuración anterior',
            'Matriz de permisos actualizada para el rol ID ' . $idRolSeleccionado,
            'Actualización de permisos desde el módulo de seguridad'
        );

        $this->auditoriaModel->registrarAcceso(
            $idAutor,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Actualizó la matriz de permisos del rol #{$idRolSeleccionado}",
            2
        );

        establecer_alerta('exito', 'Los permisos del rol seleccionado fueron actualizados correctamente.');
        redirigir('admin', 'index', ['tab' => 'permisos']);
    }

    /**
     * Genera en vivo una copia de seguridad SQL completa de db_tickets_soporte en database/backups/ usando PDO
     */
    public function generarRespaldo(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        if ((string)($_SESSION['slug_rol'] ?? '') !== 'Superadmin' && (int)($_SESSION['id_rol'] ?? 0) !== 1) {
            establecer_alerta('error', 'Solo el Superadministrador puede generar copias de seguridad de la base de datos.');
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $directorioBackups = dirname(__DIR__) . '/database/backups';
        $resultado         = $this->auditoriaModel->generarRespaldoBaseDatos($directorioBackups);

        if (!$resultado['exito']) {
            establecer_alerta('error', (string)$resultado['mensaje']);
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $idAutor       = (int)$_SESSION['id_usuario'];
        $nombreArchivo = (string)$resultado['nombre_archivo'];
        $pesoKb        = (float)$resultado['peso_kb'];
        $totalTablas   = (int)$resultado['total_tablas'];
        $totalFilas    = (int)$resultado['total_filas'];

        $this->auditoriaModel->registrarCambio(
            $idAutor,
            'db_tickets_soporte',
            1,
            'CREACION',
            'respaldo_sql',
            'Estado previo sin este respaldo',
            "{$nombreArchivo} — {$pesoKb} KB — {$totalTablas} tablas y {$totalFilas} filas",
            'Generación de copia de seguridad institucional de la base de datos con PDO'
        );

        $this->auditoriaModel->registrarAcceso(
            $idAutor,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Generó la copia de seguridad {$nombreArchivo} de la base de datos",
            2
        );

        establecer_alerta(
            'exito',
            "Se generó correctamente el respaldo {$nombreArchivo} con {$totalTablas} tablas y {$totalFilas} registros."
        );
        redirigir('admin', 'index', ['tab' => 'servidor']);
    }

    /**
     * Descarga de forma controlada y segura un archivo de respaldo SQL validando sesión de Superadmin
     */
    public function descargarRespaldo(): void
    {
        if ((string)($_SESSION['slug_rol'] ?? '') !== 'Superadmin' && (int)($_SESSION['id_rol'] ?? 0) !== 1) {
            establecer_alerta('error', 'Solo el Superadministrador está autorizado para descargar copias de seguridad.');
            redirigir('admin', 'index');
        }

        $archivoBruto  = isset($_GET['archivo']) && is_string($_GET['archivo']) ? basename($_GET['archivo']) : '';
        $nombreArchivo = (string)preg_replace('/[^a-zA-Z0-9_.-]/', '', $archivoBruto);

        if ($nombreArchivo === '' || !preg_match('/^respaldo_sistema_\d{8}_\d{6}\.sql$/', $nombreArchivo)) {
            establecer_alerta('error', 'El archivo de respaldo solicitado no tiene un formato válido.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $rutaCompleta = dirname(__DIR__) . '/database/backups/' . $nombreArchivo;
        if (!is_file($rutaCompleta) || !is_readable($rutaCompleta)) {
            establecer_alerta('error', 'No se encontró el archivo de respaldo solicitado en el servidor.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $idAutor = (int)$_SESSION['id_usuario'];
        $pesoKb  = round(((int)@filesize($rutaCompleta)) / 1024, 2);

        $this->auditoriaModel->registrarCambio(
            $idAutor,
            'db_tickets_soporte',
            1,
            'MODIFICACION',
            'respaldo_sql',
            $nombreArchivo,
            "Descarga autorizada — {$pesoKb} KB",
            'Descarga de copia de seguridad SQL por el Superadministrador'
        );

        $this->auditoriaModel->registrarAcceso(
            $idAutor,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Descargó la copia de seguridad {$nombreArchivo}",
            2
        );

        if (!headers_sent()) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/sql; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . (string)filesize($rutaCompleta));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }

        readfile($rutaCompleta);
        exit;
    }

    /**
     * Elimina un archivo de respaldo SQL antiguo del servidor dejando constancia en auditoría
     */
    public function eliminarRespaldo(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        if ((string)($_SESSION['slug_rol'] ?? '') !== 'Superadmin' && (int)($_SESSION['id_rol'] ?? 0) !== 1) {
            establecer_alerta('error', 'Solo el Superadministrador puede eliminar copias de seguridad.');
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $archivoBruto  = isset($_POST['nombre_archivo']) && is_string($_POST['nombre_archivo']) ? basename($_POST['nombre_archivo']) : '';
        $nombreArchivo = (string)preg_replace('/[^a-zA-Z0-9_.-]/', '', $archivoBruto);

        if ($nombreArchivo === '' || !preg_match('/^respaldo_sistema_\d{8}_\d{6}\.sql$/', $nombreArchivo)) {
            establecer_alerta('error', 'Nombre de respaldo inválido.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $rutaCompleta = dirname(__DIR__) . '/database/backups/' . $nombreArchivo;
        if (!is_file($rutaCompleta)) {
            establecer_alerta('advertencia', 'El respaldo indicado ya no existe en el servidor.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $pesoKb = round(((int)@filesize($rutaCompleta)) / 1024, 2);
        if (!@unlink($rutaCompleta)) {
            establecer_alerta('error', 'No fue posible eliminar el archivo de respaldo del disco.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $idAutor = (int)$_SESSION['id_usuario'];
        $this->auditoriaModel->registrarCambio(
            $idAutor,
            'db_tickets_soporte',
            1,
            'ELIMINACION_LOGICA',
            'respaldo_sql',
            "{$nombreArchivo} — {$pesoKb} KB",
            'Eliminado del almacenamiento del servidor',
            'Depuración manual de copia de seguridad desde el panel de infraestructura'
        );

        $this->auditoriaModel->registrarAcceso(
            $idAutor,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Eliminó la copia de seguridad {$nombreArchivo} del servidor",
            2
        );

        establecer_alerta('informacion', "El respaldo {$nombreArchivo} fue eliminado correctamente.");
        redirigir('admin', 'index', ['tab' => 'servidor']);
    }

    /**
     * Fuerza el cierre de sesión de un usuario conectado cambiando su estado_conexion a Offline
     */
    public function forzarDesconexion(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        if ((string)($_SESSION['slug_rol'] ?? '') !== 'Superadmin' && (int)($_SESSION['id_rol'] ?? 0) !== 1) {
            establecer_alerta('error', 'Solo el Superadministrador puede forzar el cierre de sesiones.');
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $idAutor    = (int)$_SESSION['id_usuario'];
        $idObjetivo = (int)($_POST['id_usuario'] ?? 0);

        if ($idObjetivo <= 0) {
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        if ($idObjetivo === $idAutor) {
            establecer_alerta('advertencia', 'No puedes forzar el cierre de tu propia sesión actual desde este monitor.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $usuario = $this->usuarioModel->obtenerPorIdIncluirEliminados($idObjetivo);
        if ($usuario === null) {
            establecer_alerta('error', 'El funcionario indicado no existe.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $this->usuarioModel->forzarCierreSesionUsuario($idObjetivo);

        $this->auditoriaModel->registrarCambio(
            $idAutor,
            'usuarios',
            $idObjetivo,
            'MODIFICACION',
            'estado_conexion',
            'Online',
            'Offline',
            "Cierre forzado de sesión activa para el funcionario {$usuario['username']}"
        );

        $this->auditoriaModel->registrarAcceso(
            $idAutor,
            (string)$_SESSION['username'],
            'Bloqueo_Seguridad',
            "Forzó el cierre de sesión en línea del usuario {$usuario['username']}",
            2
        );

        establecer_alerta(
            'exito',
            "Se cerró inmediatamente la sesión activa de {$usuario['nombres']} {$usuario['apellidos']}."
        );
        redirigir('admin', 'index', ['tab' => 'servidor']);
    }

    /**
     * Depura registros antiguos de logs_sistema_accesos conservando la ventana de días seleccionada
     */
    public function depurarAccesos(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        if ((string)($_SESSION['slug_rol'] ?? '') !== 'Superadmin' && (int)($_SESSION['id_rol'] ?? 0) !== 1) {
            establecer_alerta('error', 'Solo el Superadministrador puede ejecutar el mantenimiento de registros.');
            redirigir('admin', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó.');
            redirigir('admin', 'index', ['tab' => 'servidor']);
        }

        $diasConservar = (int)($_POST['dias_conservar'] ?? 60);
        if (!in_array($diasConservar, [30, 60, 90], true)) {
            $diasConservar = 60;
        }

        $resultado  = $this->auditoriaModel->depurarLogsAccesoAntiguos($diasConservar);
        $eliminados = (int)$resultado['eliminados'];
        $antes      = (int)$resultado['total_antes'];
        $despues    = (int)$resultado['total_despues'];
        $idAutor    = (int)$_SESSION['id_usuario'];

        $this->auditoriaModel->registrarCambio(
            $idAutor,
            'logs_sistema_accesos',
            1,
            'ELIMINACION_LOGICA',
            'depuracion_mantenimiento',
            "{$antes} registros almacenados",
            "{$despues} registros conservados — {$eliminados} depurados",
            "Limpieza preventiva conservando los últimos {$diasConservar} días de actividad"
        );

        $this->auditoriaModel->registrarAcceso(
            $idAutor,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Ejecutó depuración de registros de acceso conservando los últimos {$diasConservar} días",
            2
        );

        if ($eliminados > 0) {
            establecer_alerta(
                'exito',
                "Se depuraron {$eliminados} registros antiguos de acceso, conservando los últimos {$diasConservar} días."
            );
        } else {
            establecer_alerta(
                'informacion',
                "No existen registros de acceso con más de {$diasConservar} días de antigüedad para depurar."
            );
        }

        redirigir('admin', 'index', ['tab' => 'servidor']);
    }
}
