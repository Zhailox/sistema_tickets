<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Controlador del Módulo 1: Autenticación, Control de Intentos Fallidos,
 * Gestión de Cuenta Propia y Recuperación de Clave en Intranet
 */
class AuthController
{
    private Usuario $usuarioModel;
    private Auditoria $auditoriaModel;

    public function __construct()
    {
        $this->usuarioModel   = new Usuario();
        $this->auditoriaModel = new Auditoria();
    }

    /**
     * Acción por defecto del controlador
     */
    public function index(): void
    {
        if (sesion_activa()) {
            $this->redirigirSegunRolActual();
        }
        $this->login();
    }

    /**
     * Muestra la pantalla de inicio de sesión o procesa el envío de credenciales
     */
    public function login(): void
    {
        if (sesion_activa()) {
            $this->redirigirSegunRolActual();
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->procesarInicioSesion();
            return;
        }

        $tituloPagina = 'Iniciar sesión';
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Valida credenciales, bloqueos por intentos fallidos y registra auditoría de acceso
     */
    private function procesarInicioSesion(): void
    {
        $token = $_POST['token_csrf'] ?? '';
        if (!verificar_token_csrf($token)) {
            establecer_alerta('error', 'La sesión del formulario caducó. Por favor intenta ingresar nuevamente.');
            redirigir('auth', 'login');
        }

        $identificador = limpiar_texto($_POST['identificador'] ?? '');
        $clave         = (string)($_POST['clave'] ?? '');

        if ($identificador === '' || $clave === '') {
            establecer_alerta('advertencia', 'Escribe tu usuario, correo o cédula junto con tu contraseña.');
            redirigir('auth', 'login');
        }

        $usuario = $this->usuarioModel->buscarPorIdentificador($identificador);

        if ($usuario === null) {
            $this->auditoriaModel->registrarAcceso(
                null,
                $identificador,
                'Login_Fallido',
                'Intento de acceso con identificador no registrado en el sistema',
                1
            );
            establecer_alerta('error', 'No encontramos una cuenta registrada con los datos ingresados.');
            redirigir('auth', 'login');
        }

        $idUsuario = (int)$usuario['id_usuario'];
        $username  = (string)$usuario['username'];

        if ($usuario['estado_cuenta'] !== 'Activo') {
            $this->auditoriaModel->registrarAcceso(
                $idUsuario,
                $username,
                'Login_Fallido',
                'Intento de ingreso en cuenta con estado ' . $usuario['estado_cuenta'],
                1
            );
            establecer_alerta('error', 'Tu cuenta no se encuentra activa en este momento. Comunícate con soporte técnico.');
            redirigir('auth', 'login');
        }

        // Evaluar si existe un bloqueo temporal vigente
        $estadoBloqueo = $this->usuarioModel->evaluarBloqueoTemporal($usuario);
        if ($estadoBloqueo['bloqueado']) {
            $minRestantes = (int)$estadoBloqueo['minutos_restantes'];
            $this->auditoriaModel->registrarAcceso(
                $idUsuario,
                $username,
                'Bloqueo_Seguridad',
                "Intento de acceso durante bloqueo preventivo activo. Restan {$minRestantes} minutos",
                1
            );
            establecer_alerta(
                'error',
                "Por seguridad, el acceso a tu cuenta está pausado por {$minRestantes} minutos. Puedes esperar o recuperar tu contraseña con tu pregunta de seguridad."
            );
            redirigir('auth', 'login');
        }

        // Verificar contraseña
        if (!$this->usuarioModel->verificarCredenciales($usuario, $clave)) {
            $intentosPrevios = (int)($usuario['intentos_fallidos'] ?? 0);
            $resultadoFallo  = $this->usuarioModel->registrarIntentoFallido($idUsuario, $intentosPrevios);

            if ($resultadoFallo['bloqueado_ahora']) {
                $minutos = (int)$resultadoFallo['minutos_bloqueo'];
                $this->auditoriaModel->registrarAcceso(
                    $idUsuario,
                    $username,
                    'Bloqueo_Seguridad',
                    "Cuenta bloqueada automáticamente por {$minutos} minutos tras alcanzar el límite de intentos fallidos",
                    1
                );
                $this->auditoriaModel->registrarCambio(
                    $idUsuario,
                    'usuarios',
                    $idUsuario,
                    'MODIFICACION',
                    'bloqueado_hasta',
                    'Sin bloqueo activo',
                    "Bloqueo temporal por {$minutos} minutos",
                    'Tres intentos fallidos de inicio de sesión consecutivos'
                );
                establecer_alerta(
                    'error',
                    "Alcanzaste el límite de intentos fallidos. Tu cuenta quedó protegida temporalmente por {$minutos} minutos. Si olvidaste tu clave, recupérala abajo."
                );
            } else {
                $restantes = (int)$resultadoFallo['intentos_restantes'];
                $this->auditoriaModel->registrarAcceso(
                    $idUsuario,
                    $username,
                    'Login_Fallido',
                    "Contraseña incorrecta. Intentos restantes antes de bloqueo: {$restantes}",
                    1
                );
                $textoIntentos = $restantes === 1 ? 'Te queda 1 intento' : "Te quedan {$restantes} intentos";
                establecer_alerta(
                    'error',
                    "La contraseña ingresada no es correcta. {$textoIntentos} antes del bloqueo temporal de seguridad."
                );
            }

            redirigir('auth', 'login');
        }

        // Inicio de sesión exitoso: regenerar identificador de sesión y token CSRF contra fijación de sesión
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['token_seguridad_csrf'] = bin2hex(random_bytes(32));
        $_SESSION['ultima_actividad_ts']  = time();

        $this->usuarioModel->registrarIngresoExitoso($idUsuario);

        $estructuraRol = $this->usuarioModel->obtenerPermisosYMenuPorRol((int)$usuario['id_rol']);

        $_SESSION['id_usuario']           = $idUsuario;
        $_SESSION['id_rol']               = (int)$usuario['id_rol'];
        $_SESSION['slug_rol']             = (string)$usuario['slug_rol'];
        $_SESSION['nombre_rol']           = (string)$usuario['nombre_rol'];
        $_SESSION['id_departamento']      = (int)$usuario['id_departamento'];
        $_SESSION['codigo_departamento']  = (string)$usuario['codigo_departamento'];
        $_SESSION['nombre_departamento']  = (string)$usuario['nombre_departamento'];
        $_SESSION['username']             = $username;
        $_SESSION['cedula_empleado']      = (string)$usuario['cedula_empleado'];
        $_SESSION['nombres']              = (string)$usuario['nombres'];
        $_SESSION['apellidos']            = (string)$usuario['apellidos'];
        $_SESSION['correo_institucional'] = (string)$usuario['correo_institucional'];
        $_SESSION['cargo_empleado']       = (string)$usuario['cargo_empleado'];
        $_SESSION['foto_perfil_ruta']     = (string)($usuario['foto_perfil_ruta'] ?: 'assets/img/default-avatar.png');
        $_SESSION['permisos_modulos']     = $estructuraRol['permisos'];

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            $username,
            'Login_Exitoso',
            'Inicio de sesión correcto con el rol ' . $usuario['nombre_rol'],
            1
        );

        establecer_alerta('exito', 'Bienvenido nuevamente, ' . $usuario['nombres'] . ' ' . $usuario['apellidos'] . '.');
        $this->redirigirSegunRolActual();
    }

    /**
     * Cierra la sesión actual, cambia el estado a desconectado y registra el evento
     */
    public function logout(): void
    {
        if (sesion_activa()) {
            $idUsuario = (int)$_SESSION['id_usuario'];
            $username  = (string)$_SESSION['username'];

            $this->usuarioModel->registrarCierreSesion($idUsuario);
            $this->auditoriaModel->registrarAcceso(
                $idUsuario,
                $username,
                'Logout',
                'Cierre de sesión realizado por el usuario',
                1
            );
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }
        session_destroy();

        session_start();
        establecer_alerta('exito', 'Cerraste tu sesión correctamente.');
        redirigir('auth', 'login');
    }

    /**
     * Gestiona la recuperación de clave en intranet por pregunta de seguridad o asistencia técnica
     */
    public function recuperar(): void
    {
        if (sesion_activa()) {
            $this->redirigirSegunRolActual();
        }

        if (isset($_GET['reiniciar'])) {
            unset($_SESSION['recuperacion_intranet']);
            redirigir('auth', 'recuperar');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->procesarRecuperacionClave();
            return;
        }

        $usuarioEnRecuperacion = null;
        if (!empty($_SESSION['recuperacion_intranet']['id_usuario'])) {
            $usuarioEnRecuperacion = $this->usuarioModel->obtenerPorId(
                (int)$_SESSION['recuperacion_intranet']['id_usuario']
            );
            if ($usuarioEnRecuperacion === null) {
                unset($_SESSION['recuperacion_intranet']);
            }
        }

        $tituloPagina = 'Recuperar acceso a mi cuenta';
        require __DIR__ . '/../views/auth/recuperar.php';
    }

    /**
     * Procesa los pasos del formulario de recuperación de contraseña en intranet
     */
    private function procesarRecuperacionClave(): void
    {
        $token = $_POST['token_csrf'] ?? '';
        if (!verificar_token_csrf($token)) {
            establecer_alerta('error', 'La sesión del formulario caducó. Intenta nuevamente.');
            redirigir('auth', 'recuperar');
        }

        $etapa = $_POST['etapa_recuperacion'] ?? 'identificar';

        if ($etapa === 'identificar') {
            $identificador = limpiar_texto($_POST['identificador'] ?? '');
            if ($identificador === '') {
                establecer_alerta('advertencia', 'Escribe tu nombre de usuario, correo institucional o cédula.');
                redirigir('auth', 'recuperar');
            }

            $usuario = $this->usuarioModel->buscarPorIdentificador($identificador);
            if ($usuario === null) {
                $this->auditoriaModel->registrarAcceso(
                    null,
                    $identificador,
                    'Login_Fallido',
                    'Consulta de recuperación para una cuenta no existente',
                    1
                );
                establecer_alerta('error', 'No encontramos ningún funcionario registrado con ese dato.');
                redirigir('auth', 'recuperar');
            }

            if ($usuario['estado_cuenta'] !== 'Activo') {
                establecer_alerta('error', 'Esta cuenta está suspendida o inactiva. Acude a la oficina de soporte técnico.');
                redirigir('auth', 'recuperar');
            }

            $_SESSION['recuperacion_intranet'] = [
                'id_usuario' => (int)$usuario['id_usuario'],
                'username'   => (string)$usuario['username'],
                'iniciado'   => time()
            ];

            $this->auditoriaModel->registrarAcceso(
                (int)$usuario['id_usuario'],
                (string)$usuario['username'],
                'Navegacion_Modulo',
                'Inicio de validación por pregunta de seguridad en intranet',
                1
            );

            redirigir('auth', 'recuperar');
        }

        // Validar que exista usuario en sesión de recuperación
        $idUsuarioRec = (int)($_SESSION['recuperacion_intranet']['id_usuario'] ?? 0);
        $usuario = $idUsuarioRec > 0 ? $this->usuarioModel->obtenerPorId($idUsuarioRec) : null;

        if ($usuario === null) {
            establecer_alerta('advertencia', 'Primero identifica tu cuenta para continuar.');
            redirigir('auth', 'recuperar');
        }

        $username = (string)$usuario['username'];

        if ($etapa === 'solicitar_asistencia') {
            $pendiente = $this->usuarioModel->obtenerSolicitudRecuperacionPendiente($idUsuarioRec);
            if ($pendiente !== null) {
                unset($_SESSION['recuperacion_intranet']);
                establecer_alerta(
                    'informacion',
                    'Ya tienes una solicitud de restablecimiento pendiente. El jefe de soporte o administrador la atenderá en breve.'
                );
                redirigir('auth', 'login');
            }

            $this->usuarioModel->registrarSolicitudRecuperacion(
                $idUsuarioRec,
                'Reset_Manual',
                0,
                'Pendiente',
                null
            );

            $this->auditoriaModel->registrarAcceso(
                $idUsuarioRec,
                $username,
                'Navegacion_Modulo',
                'Solicitud de restablecimiento asistido enviada al equipo de soporte',
                1
            );

            unset($_SESSION['recuperacion_intranet']);
            establecer_alerta(
                'exito',
                'Tu solicitud de restablecimiento asistido fue enviada al equipo de soporte técnico.'
            );
            redirigir('auth', 'login');
        }

        if ($etapa === 'validar_pregunta') {
            $respuesta      = limpiar_texto($_POST['respuesta_seguridad'] ?? '');
            $nuevaClave     = (string)($_POST['nueva_clave'] ?? '');
            $confirmarClave = (string)($_POST['confirmar_clave'] ?? '');

            if ($respuesta === '' || $nuevaClave === '' || $confirmarClave === '') {
                establecer_alerta('advertencia', 'Completa la respuesta de seguridad y escribe tu nueva contraseña.');
                redirigir('auth', 'recuperar');
            }

            if (mb_strlen($nuevaClave, 'UTF-8') < 6) {
                establecer_alerta('advertencia', 'La nueva contraseña debe tener al menos 6 caracteres.');
                redirigir('auth', 'recuperar');
            }

            if ($nuevaClave !== $confirmarClave) {
                establecer_alerta('error', 'La nueva contraseña y su confirmación no coinciden.');
                redirigir('auth', 'recuperar');
            }

            if (!$this->usuarioModel->verificarRespuestaSeguridad($usuario, $respuesta)) {
                $this->auditoriaModel->registrarAcceso(
                    $idUsuarioRec,
                    $username,
                    'Login_Fallido',
                    'Respuesta incorrecta en validación de pregunta de seguridad en intranet',
                    1
                );
                establecer_alerta(
                    'error',
                    'La respuesta ingresada no coincide con tus registros de seguridad. Verifica e intenta de nuevo o solicita asistencia abajo.'
                );
                redirigir('auth', 'recuperar');
            }

            // Respuesta correcta: actualizar contraseña, desbloquear cuenta y registrar solicitud completada
            $this->usuarioModel->actualizarClave($idUsuarioRec, $nuevaClave);
            $this->usuarioModel->registrarSolicitudRecuperacion(
                $idUsuarioRec,
                'Chat_P2P_Soporte',
                1,
                'Completada',
                $idUsuarioRec
            );

            $this->auditoriaModel->registrarCambio(
                $idUsuarioRec,
                'usuarios',
                $idUsuarioRec,
                'MODIFICACION',
                'password_hash',
                'Credencial anterior',
                'Nueva credencial definida por el titular',
                'Recuperación de contraseña mediante pregunta de seguridad en intranet'
            );

            $this->auditoriaModel->registrarAcceso(
                $idUsuarioRec,
                $username,
                'Navegacion_Modulo',
                'Contraseña restablecida y cuenta desbloqueada mediante pregunta de seguridad',
                1
            );

            unset($_SESSION['recuperacion_intranet']);
            establecer_alerta('exito', 'Tu contraseña se actualizó correctamente. Ya puedes iniciar sesión con tu nueva clave.');
            redirigir('auth', 'login');
        }

        redirigir('auth', 'recuperar');
    }

    /**
     * Muestra la pantalla de gestión de cuenta propia, seguridad y actividad reciente
     */
    public function perfil(): void
    {
        if (!sesion_activa()) {
            establecer_alerta('advertencia', 'Inicia sesión para acceder a tu cuenta.');
            redirigir('auth', 'login');
        }

        $idUsuario = (int)$_SESSION['id_usuario'];
        $usuario   = $this->usuarioModel->obtenerPorId($idUsuario);

        if ($usuario === null) {
            $this->logout();
            return;
        }

        $slugRol        = (string)$usuario['slug_rol'];
        $idDepartamento = (int)$usuario['id_departamento'];

        $resumenActividad = $this->usuarioModel->obtenerResumenActividadUsuario($idUsuario, $slugRol, $idDepartamento);
        $historialAccesos = $this->auditoriaModel->obtenerHistorialAccesosUsuario($idUsuario, (string)$usuario['username'], 25);
        $historialCambios = $this->auditoriaModel->obtenerCambiosUsuario($idUsuario, 15);

        $solicitudesRecuperacionPendientes = [];
        if ($slugRol === 'Superadmin' || $slugRol === 'Jefe_Soporte') {
            $solicitudesRecuperacionPendientes = $this->usuarioModel->listarSolicitudesRecuperacion(true, 15);
        }

        $tituloPagina = 'Mi cuenta y perfil';
        require __DIR__ . '/../views/auth/perfil.php';
    }

    /**
     * Procesa la actualización de los datos personales y de contacto del usuario autenticado
     */
    public function actualizarPerfil(): void
    {
        if (!sesion_activa() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('auth', 'perfil');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'La validación del formulario caducó. Intenta guardar nuevamente.');
            redirigir('auth', 'perfil');
        }

        $idUsuario = (int)$_SESSION['id_usuario'];
        $actual    = $this->usuarioModel->obtenerPorId($idUsuario);
        if ($actual === null) {
            redirigir('auth', 'logout');
        }

        $nombres   = limpiar_texto($_POST['nombres'] ?? '');
        $apellidos = limpiar_texto($_POST['apellidos'] ?? '');
        $correo    = limpiar_texto($_POST['correo_institucional'] ?? '');
        $cargo     = limpiar_texto($_POST['cargo_empleado'] ?? '');
        $extension = limpiar_texto($_POST['telefono_extension'] ?? '');

        if ($nombres === '' || $apellidos === '' || $correo === '' || $cargo === '') {
            establecer_alerta('advertencia', 'Los nombres, apellidos, correo institucional y cargo son obligatorios.');
            redirigir('auth', 'perfil');
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            establecer_alerta('error', 'Escribe una dirección de correo institucional válida.');
            redirigir('auth', 'perfil');
        }

        if ($this->usuarioModel->existeCorreoEnOtroUsuario($correo, $idUsuario)) {
            establecer_alerta('error', 'Ese correo institucional ya pertenece a otro funcionario registrado.');
            redirigir('auth', 'perfil');
        }

        $rutaFoto = (string)($actual['foto_perfil_ruta'] ?: 'assets/img/default-avatar.png');

        // Procesar carga opcional de nueva imagen de perfil con validación estricta de MIME real (finfo) y nombre aleatorio
        if (!empty($_FILES['foto_perfil']['name']) && ($_FILES['foto_perfil']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $mimesImagenPermitidos = [
                'png'  => ['image/png'],
                'jpg'  => ['image/jpeg', 'image/pjpeg'],
                'jpeg' => ['image/jpeg', 'image/pjpeg'],
                'webp' => ['image/webp']
            ];

            $resultadoSubida = procesar_archivo_subido_seguro(
                $_FILES['foto_perfil'],
                __DIR__ . '/../public/assets/uploads/perfiles',
                'assets/uploads/perfiles',
                $mimesImagenPermitidos,
                2 * 1024 * 1024
            );

            if (!$resultadoSubida['exito']) {
                establecer_alerta('advertencia', (string)$resultadoSubida['mensaje']);
                redirigir('auth', 'perfil');
            }

            $rutaFoto = (string)$resultadoSubida['ruta_relativa'];
        }

        $nuevosDatos = [
            'nombres'              => $nombres,
            'apellidos'            => $apellidos,
            'correo_institucional' => $correo,
            'cargo_empleado'       => $cargo,
            'telefono_extension'   => $extension,
            'foto_perfil_ruta'     => $rutaFoto
        ];

        $this->usuarioModel->actualizarDatosPerfil($idUsuario, $nuevosDatos);

        // Registrar auditoría detallada campo por campo si hubo cambios
        $camposAuditables = [
            'nombres'              => 'Nombres del funcionario',
            'apellidos'            => 'Apellidos del funcionario',
            'correo_institucional' => 'Correo institucional',
            'cargo_empleado'       => 'Cargo institucional',
            'telefono_extension'   => 'Extensión telefónica',
            'foto_perfil_ruta'     => 'Fotografía de perfil'
        ];

        $cambiosRealizados = 0;
        foreach ($camposAuditables as $campo => $etiqueta) {
            $valorPrevio = (string)($actual[$campo] ?? '');
            $valorNuevo  = (string)($nuevosDatos[$campo] ?? '');
            if ($valorPrevio !== $valorNuevo) {
                $this->auditoriaModel->registrarCambio(
                    $idUsuario,
                    'usuarios',
                    $idUsuario,
                    'MODIFICACION',
                    $campo,
                    $valorPrevio !== '' ? $valorPrevio : 'Vacío',
                    $valorNuevo !== '' ? $valorNuevo : 'Vacío',
                    'Actualización de datos en cuenta propia: ' . $etiqueta
                );
                $cambiosRealizados++;
            }
        }

        // Actualizar variables de sesión en vivo
        $_SESSION['nombres']              = $nombres;
        $_SESSION['apellidos']            = $apellidos;
        $_SESSION['correo_institucional'] = $correo;
        $_SESSION['cargo_empleado']       = $cargo;
        $_SESSION['foto_perfil_ruta']     = $rutaFoto;

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$actual['username'],
            'Navegacion_Modulo',
            'Actualización de datos personales en Mi cuenta y perfil',
            1
        );

        if ($cambiosRealizados > 0) {
            establecer_alerta('exito', 'Tus datos personales fueron actualizados correctamente.');
        } else {
            establecer_alerta('informacion', 'No se detectaron cambios respecto a tu información actual.');
        }

        redirigir('auth', 'perfil');
    }

    /**
     * Procesa el cambio de contraseña desde la cuenta propia del usuario
     */
    public function cambiarClave(): void
    {
        if (!sesion_activa() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('auth', 'perfil');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'La validación del formulario caducó. Intenta nuevamente.');
            redirigir('auth', 'perfil');
        }

        $idUsuario = (int)$_SESSION['id_usuario'];
        $usuario   = $this->usuarioModel->obtenerPorId($idUsuario);
        if ($usuario === null) {
            redirigir('auth', 'logout');
        }

        $claveActual    = (string)($_POST['clave_actual'] ?? '');
        $nuevaClave     = (string)($_POST['nueva_clave'] ?? '');
        $confirmarClave = (string)($_POST['confirmar_clave'] ?? '');

        if ($claveActual === '' || $nuevaClave === '' || $confirmarClave === '') {
            establecer_alerta('advertencia', 'Completa tu contraseña actual y escribe dos veces tu nueva contraseña.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        if (!$this->usuarioModel->verificarCredenciales($usuario, $claveActual)) {
            $this->auditoriaModel->registrarAcceso(
                $idUsuario,
                (string)$usuario['username'],
                'Login_Fallido',
                'Contraseña actual incorrecta al intentar cambiar clave desde el perfil',
                1
            );
            establecer_alerta('error', 'La contraseña actual que escribiste no es correcta.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        if (mb_strlen($nuevaClave, 'UTF-8') < 6) {
            establecer_alerta('advertencia', 'La nueva contraseña debe tener mínimo 6 caracteres.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        if ($nuevaClave !== $confirmarClave) {
            establecer_alerta('error', 'La nueva contraseña y su confirmación no coinciden.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        if ($claveActual === $nuevaClave) {
            establecer_alerta('advertencia', 'Elige una contraseña diferente a la que ya estás utilizando.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        $this->usuarioModel->actualizarClave($idUsuario, $nuevaClave);

        // Regenerar identificador de sesión tras cambio de contraseña para prevenir fijación de sesión
        if (!headers_sent() && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['ultima_actividad_ts'] = time();

        $this->auditoriaModel->registrarCambio(
            $idUsuario,
            'usuarios',
            $idUsuario,
            'MODIFICACION',
            'password_hash',
            'Credencial anterior',
            'Credencial renovada por el titular',
            'Cambio voluntario de contraseña desde Mi cuenta y perfil'
        );

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$usuario['username'],
            'Navegacion_Modulo',
            'Renovación exitosa de contraseña desde Mi cuenta y perfil',
            1
        );

        establecer_alerta('exito', 'Actualizamos tu contraseña correctamente.');
        redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
    }

    /**
     * Actualiza la pregunta y respuesta de seguridad para recuperación en intranet
     */
    public function actualizarSeguridad(): void
    {
        if (!sesion_activa() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('auth', 'perfil');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'La validación del formulario caducó. Intenta nuevamente.');
            redirigir('auth', 'perfil');
        }

        $idUsuario = (int)$_SESSION['id_usuario'];
        $usuario   = $this->usuarioModel->obtenerPorId($idUsuario);
        if ($usuario === null) {
            redirigir('auth', 'logout');
        }

        $pregunta    = limpiar_texto($_POST['pregunta_seguridad'] ?? '');
        $respuesta   = limpiar_texto($_POST['respuesta_seguridad'] ?? '');
        $claveActual = (string)($_POST['clave_confirmacion'] ?? '');

        if ($pregunta === '' || $respuesta === '' || $claveActual === '') {
            establecer_alerta('advertencia', 'Escribe la pregunta, tu respuesta secreta y confirma con tu contraseña actual.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        if (!$this->usuarioModel->verificarCredenciales($usuario, $claveActual)) {
            $this->auditoriaModel->registrarAcceso(
                $idUsuario,
                (string)$usuario['username'],
                'Login_Fallido',
                'Contraseña incorrecta al intentar modificar pregunta de seguridad',
                1
            );
            establecer_alerta('error', 'La contraseña actual ingresada no es correcta.');
            redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
        }

        $preguntaAnterior = (string)($usuario['pregunta_seguridad'] ?? 'Sin definir');
        $this->usuarioModel->actualizarPreguntaSeguridad($idUsuario, $pregunta, $respuesta);

        $this->auditoriaModel->registrarCambio(
            $idUsuario,
            'usuarios',
            $idUsuario,
            'MODIFICACION',
            'pregunta_seguridad',
            $preguntaAnterior,
            $pregunta,
            'Actualización de pregunta y respuesta de seguridad para intranet'
        );

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$usuario['username'],
            'Navegacion_Modulo',
            'Actualización de pregunta y respuesta secreta de recuperación',
            1
        );

        establecer_alerta('exito', 'Tu pregunta y respuesta de seguridad quedaron guardadas.');
        redirigir('auth', 'perfil', ['seccion' => 'seguridad']);
    }

    /**
     * Permite a Superadmin o Jefe de Soporte atender solicitudes de recuperación de clave pendientes
     */
    public function resolverRecuperacion(): void
    {
        if (!sesion_activa() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('auth', 'perfil');
        }

        $slugRol = (string)($_SESSION['slug_rol'] ?? '');
        if ($slugRol !== 'Superadmin' && $slugRol !== 'Jefe_Soporte') {
            establecer_alerta('error', 'No tienes permisos para resolver solicitudes de recuperación.');
            redirigir('auth', 'perfil');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('auth', 'perfil');
        }

        $idSolicitud   = (int)($_POST['id_solicitud'] ?? 0);
        $decision      = ($_POST['decision'] ?? '') === 'rechazar' ? 'rechazar' : 'aprobar';
        $claveTemporal = limpiar_texto($_POST['clave_temporal'] ?? '123456');
        if ($claveTemporal === '' || mb_strlen($claveTemporal, 'UTF-8') < 6) {
            $claveTemporal = '123456';
        }

        $idValidador = (int)$_SESSION['id_usuario'];
        $resultado = $this->usuarioModel->resolverSolicitudRecuperacionAsistida(
            $idSolicitud,
            $idValidador,
            $decision,
            $claveTemporal
        );

        if ($resultado === null) {
            establecer_alerta('advertencia', 'La solicitud indicada ya fue atendida o no existe.');
            redirigir('auth', 'perfil');
        }

        $idAfectado = (int)$resultado['id_usuario_afectado'];
        $nombreAfec = $resultado['nombres'] . ' ' . $resultado['apellidos'];

        if ($decision === 'aprobar') {
            $this->auditoriaModel->registrarCambio(
                $idValidador,
                'usuarios',
                $idAfectado,
                'MODIFICACION',
                'password_hash',
                'Credencial bloqueada u olvidada',
                'Restablecimiento asistido aprobado',
                "Solicitud de recuperación #{$idSolicitud} atendida para {$nombreAfec}"
            );
            $this->auditoriaModel->registrarAcceso(
                $idValidador,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Aprobó restablecimiento de clave para el usuario {$resultado['username']}",
                1
            );
            establecer_alerta(
                'exito',
                "Se restableció el acceso para {$nombreAfec} con la clave temporal {$claveTemporal}."
            );
        } else {
            $this->auditoriaModel->registrarAcceso(
                $idValidador,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Rechazó solicitud de recuperación de clave para {$resultado['username']}",
                1
            );
            establecer_alerta('informacion', "La solicitud de {$nombreAfec} fue marcada como rechazada.");
        }

        $controladorRetorno = ($_POST['origen'] ?? '') === 'admin' ? 'admin' : 'auth';
        $accionRetorno      = $controladorRetorno === 'admin' ? 'index' : 'perfil';
        redirigir($controladorRetorno, $accionRetorno);
    }

    /**
     * Redirige al controlador principal del rol si ya existe, o a Mi cuenta y perfil como respaldo limpio
     */
    private function redirigirSegunRolActual(): never
    {
        $slugRol = (string)($_SESSION['slug_rol'] ?? '');
        $destino = obtener_ruta_inicio_por_rol($slugRol);
        $archivoControlador = __DIR__ . '/' . ucfirst($destino['c']) . 'Controller.php';

        if (is_file($archivoControlador)) {
            redirigir($destino['c'], $destino['a']);
        }

        redirigir('auth', 'perfil');
    }
}
