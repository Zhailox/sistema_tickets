<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Equipo.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Controlador del Módulo 4: Portal de Solicitudes del Usuario Común
 * Permite reportar fallas en equipos de su oficina, ver la barra horizontal de avance,
 * intercambiar comentarios públicos, subir adjuntos y confirmar la solución.
 */
class PortalController
{
    private Ticket $ticketModel;
    private Equipo $equipoModel;
    private Auditoria $auditoriaModel;

    public function __construct()
    {
        if (!sesion_activa()) {
            establecer_alerta('advertencia', 'Inicia sesión para consultar tus solicitudes de soporte.');
            redirigir('auth', 'login');
        }

        $this->ticketModel    = new Ticket();
        $this->equipoModel    = new Equipo();
        $this->auditoriaModel = new Auditoria();
    }

    /**
     * Pantalla principal del usuario común con formulario sencillo y barra de progreso horizontal
     */
    public function index(): void
    {
        $idUsuario      = (int)$_SESSION['id_usuario'];
        $idDepartamento = (int)$_SESSION['id_departamento'];

        $equiposOficina  = $this->equipoModel->listarEquiposParaUsuario($idUsuario, $idDepartamento);
        $categoriasFalla = $this->ticketModel->listarCategoriasFalla(true);
        $misSolicitudes  = $this->ticketModel->listarSolicitudesPortalUsuario($idUsuario);

        $idTicketSeleccionado = isset($_GET['ver']) ? (int)$_GET['ver'] : 0;
        $ticketSeleccionado   = null;

        if ($idTicketSeleccionado > 0) {
            foreach ($misSolicitudes as $item) {
                if ((int)$item['id_ticket'] === $idTicketSeleccionado) {
                    $ticketSeleccionado = $this->ticketModel->obtenerTicketPorId($idTicketSeleccionado);
                    break;
                }
            }
        }

        if ($ticketSeleccionado === null && !empty($misSolicitudes)) {
            $idPrimero = (int)$misSolicitudes[0]['id_ticket'];
            $ticketSeleccionado = $this->ticketModel->obtenerTicketPorId($idPrimero);
        }

        $comentariosPublicos = [];
        $archivosAdjuntos    = [];
        if ($ticketSeleccionado !== null) {
            $idTck = (int)$ticketSeleccionado['id_ticket'];
            // El usuario común únicamente visualiza comentarios públicos (es_nota_interna_privada = 0)
            $comentariosPublicos = $this->ticketModel->listarComentarios($idTck, false);
            $archivosAdjuntos    = $this->ticketModel->listarAdjuntos($idTck);
        }

        $tituloPagina = 'Mis solicitudes de soporte';
        require __DIR__ . '/../views/portal/index.php';
    }

    /**
     * Procesa el registro de una nueva solicitud de soporte desde el portal del usuario común
     */
    public function crear(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('portal', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Por favor envía tu solicitud nuevamente.');
            redirigir('portal', 'index');
        }

        $idUsuario         = (int)$_SESSION['id_usuario'];
        $idDepartamento    = (int)$_SESSION['id_departamento'];
        $idEquipo          = (int)($_POST['id_equipo'] ?? 0);
        $idCategoriaFalla  = (int)($_POST['id_categoria_falla'] ?? 0);
        $tituloAsunto      = limpiar_texto($_POST['titulo_asunto'] ?? '');
        $descripcion       = limpiar_texto($_POST['descripcion_problematica'] ?? '');
        $prioridadSugerida = limpiar_texto($_POST['prioridad_sugerida'] ?? 'Media');

        if ($idEquipo <= 0 || $idCategoriaFalla <= 0 || $tituloAsunto === '' || $descripcion === '') {
            establecer_alerta('advertencia', 'Selecciona el equipo afectado, el tipo de problema y describe brevemente lo que ocurre.');
            redirigir('portal', 'index');
        }

        $equipo = $this->equipoModel->obtenerPorId($idEquipo);
        if ($equipo === null || ((int)$equipo['id_departamento'] !== $idDepartamento && (int)($equipo['id_usuario_asignado'] ?? 0) !== $idUsuario)) {
            establecer_alerta('error', 'El equipo seleccionado no corresponde a tu oficina.');
            redirigir('portal', 'index');
        }

        $creado = $this->ticketModel->crearTicket([
            'canal_creacion'           => 'Portal_Web_Usuario',
            'id_usuario_creador'       => $idUsuario,
            'id_usuario_afectado'      => $idUsuario,
            'id_departamento'          => (int)$equipo['id_departamento'],
            'id_equipo'                => $idEquipo,
            'id_categoria_falla'       => $idCategoriaFalla,
            'titulo_asunto'            => $tituloAsunto,
            'descripcion_problematica' => $descripcion,
            'prioridad_sugerida'       => $prioridadSugerida,
            'prioridad_oficial'        => $prioridadSugerida,
            'estado_aceptacion_jefe'   => 'Pendiente_Revision',
            'estado_columna_kanban'    => '1_Por_Aceptar'
        ]);

        $idNuevoTicket = (int)$creado['id_ticket'];
        $codigoTicket  = (string)$creado['codigo_ticket'];

        // Procesar archivo adjunto opcional si fue cargado
        $this->procesarSubidaAdjuntoOpcional($idNuevoTicket, $idUsuario, 'adjunto_solicitud');

        $this->auditoriaModel->registrarCambio(
            $idUsuario,
            'tickets',
            $idNuevoTicket,
            'CREACION',
            'codigo_ticket',
            null,
            $codigoTicket,
            'Nueva solicitud registrada desde el portal del usuario: ' . $tituloAsunto
        );

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Envió la solicitud de soporte {$codigoTicket}",
            4
        );

        establecer_alerta('exito', "Tu solicitud fue enviada al equipo de soporte con el código {$codigoTicket}.");
        redirigir('portal', 'index', ['ver' => $idNuevoTicket]);
    }

    /**
     * Registra un nuevo comentario público del usuario y archivo adjunto opcional
     */
    public function comentar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('portal', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'La sesión del formulario caducó. Intenta enviar tu mensaje de nuevo.');
            redirigir('portal', 'index');
        }

        $idUsuario = (int)$_SESSION['id_usuario'];
        $idTicket  = (int)($_POST['id_ticket'] ?? 0);
        $mensaje   = limpiar_texto($_POST['mensaje_texto'] ?? '');

        $ticket = $idTicket > 0 ? $this->ticketModel->obtenerTicketPorId($idTicket) : null;
        if ($ticket === null || ((int)$ticket['id_usuario_afectado'] !== $idUsuario && (int)$ticket['id_usuario_creador'] !== $idUsuario)) {
            establecer_alerta('error', 'No tienes permiso para comentar en esa solicitud.');
            redirigir('portal', 'index');
        }

        $subioArchivo = $this->procesarSubidaAdjuntoOpcional($idTicket, $idUsuario, 'adjunto_comentario');

        if ($mensaje === '' && $subioArchivo) {
            $mensaje = 'Se adjuntó un archivo de respaldo a la solicitud.';
        }

        if ($mensaje === '') {
            establecer_alerta('advertencia', 'Escribe un mensaje o selecciona un archivo antes de enviar.');
            redirigir('portal', 'index', ['ver' => $idTicket]);
        }

        $this->ticketModel->agregarComentario($idTicket, $idUsuario, $mensaje, 0);

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Agregó un comentario público en la solicitud {$ticket['codigo_ticket']}",
            4
        );

        establecer_alerta('exito', 'Tu mensaje fue enviado al técnico encargado.');
        redirigir('portal', 'index', ['ver' => $idTicket]);
    }

    /**
     * Permite al usuario común confirmar si la solución funcionó o reabrir la solicitud
     */
    public function confirmar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('portal', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'La validación del formulario caducó. Intenta nuevamente.');
            redirigir('portal', 'index');
        }

        $idUsuario   = (int)$_SESSION['id_usuario'];
        $idTicket    = (int)($_POST['id_ticket'] ?? 0);
        $decision    = ($_POST['decision'] ?? '') === 'reabrir' ? 'Reabierto_No_Funciona' : 'Confirmado_Conforme';
        $observacion = limpiar_texto($_POST['observacion_usuario'] ?? '');

        $ticket = $idTicket > 0 ? $this->ticketModel->obtenerTicketPorId($idTicket) : null;
        if ($ticket === null || ((int)$ticket['id_usuario_afectado'] !== $idUsuario && (int)$ticket['id_usuario_creador'] !== $idUsuario)) {
            establecer_alerta('error', 'No se encontró la solicitud indicada.');
            redirigir('portal', 'index');
        }

        if ($decision === 'Reabierto_No_Funciona' && $observacion === '') {
            establecer_alerta('advertencia', 'Indica brevemente qué falla sigue presentando el equipo para que el técnico lo revise.');
            redirigir('portal', 'index', ['ver' => $idTicket]);
        }

        $estadoPrevio = (string)$ticket['confirmado_por_usuario'];
        $this->ticketModel->confirmarSolucionPorUsuario($idTicket, $decision);

        if ($decision === 'Confirmado_Conforme') {
            $textoComentario = $observacion !== ''
                ? 'Confirmación de cierre del solicitante: ' . $observacion
                : 'El solicitante confirmó que el equipo funciona correctamente.';
            $this->ticketModel->agregarComentario($idTicket, $idUsuario, $textoComentario, 0);

            $this->auditoriaModel->registrarCambio(
                $idUsuario,
                'tickets',
                $idTicket,
                'MODIFICACION',
                'confirmado_por_usuario',
                $estadoPrevio,
                'Confirmado_Conforme',
                'El usuario confirmó conformidad con la solución aplicada'
            );

            establecer_alerta('exito', 'Gracias por confirmar. Tu solicitud quedó cerrada satisfactoriamente.');
        } else {
            $this->ticketModel->agregarComentario(
                $idTicket,
                $idUsuario,
                'Solicitud reabierta por el usuario porque la falla continúa: ' . $observacion,
                0
            );

            $this->auditoriaModel->registrarCambio(
                $idUsuario,
                'tickets',
                $idTicket,
                'MODIFICACION',
                'confirmado_por_usuario',
                $estadoPrevio,
                'Reabierto_No_Funciona',
                'Reapertura solicitada por el usuario: ' . $observacion
            );

            establecer_alerta('informacion', 'Tu solicitud regresó a la etapa de atención para que el técnico la revise de inmediato.');
        }

        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Registró decisión {$decision} en la solicitud {$ticket['codigo_ticket']}",
            4
        );

        redirigir('portal', 'index', ['ver' => $idTicket]);
    }

    /**
     * Guarda archivos adjuntos permitidos en disco validando firma MIME real (finfo)
     * y generando nombre físico aleatorio con bin2hex(random_bytes(16))
     */
    private function procesarSubidaAdjuntoOpcional(int $idTicket, int $idUsuario, string $campoArchivo): bool
    {
        if (empty($_FILES[$campoArchivo]['name']) || ($_FILES[$campoArchivo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        $mimesPermitidos = [
            'png'  => ['image/png'],
            'jpg'  => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'webp' => ['image/webp'],
            'pdf'  => ['application/pdf']
        ];

        $resultado = procesar_archivo_subido_seguro(
            $_FILES[$campoArchivo],
            __DIR__ . '/../public/assets/uploads/tickets',
            'assets/uploads/tickets',
            $mimesPermitidos,
            5 * 1024 * 1024
        );

        if (!$resultado['exito']) {
            establecer_alerta('advertencia', (string)$resultado['mensaje']);
            return false;
        }

        $this->ticketModel->registrarAdjunto(
            $idTicket,
            $idUsuario,
            (string)$resultado['nombre_original'],
            (string)$resultado['extension'],
            (int)$resultado['peso_kb'],
            (string)$resultado['ruta_relativa']
        );

        return true;
    }
}
