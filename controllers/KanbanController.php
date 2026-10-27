<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Equipo.php';
require_once __DIR__ . '/../models/Departamento.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Controlador del Módulo 5: Tablero de Trabajo de 5 Columnas
 * Gestiona la recepción, aceptación o rechazo, asignación técnica, cambios de etapa,
 * diagnóstico, solución, notas privadas y archivo mensual de solicitudes.
 */
class KanbanController
{
    private Ticket $ticketModel;
    private Equipo $equipoModel;
    private Departamento $departamentoModel;
    private Usuario $usuarioModel;
    private Auditoria $auditoriaModel;

    public function __construct()
    {
        if (!sesion_activa()) {
            establecer_alerta('advertencia', 'Inicia sesión para acceder al tablero de trabajo.');
            redirigir('auth', 'login');
        }

        if (!tiene_permiso_modulo('Mod5_Kanban', 'permiso_ver')) {
            establecer_alerta('error', 'Tu rol no tiene acceso al tablero operativo de soporte.');
            redirigir('auth', 'perfil');
        }

        $this->ticketModel       = new Ticket();
        $this->equipoModel       = new Equipo();
        $this->departamentoModel = new Departamento();
        $this->usuarioModel      = new Usuario();
        $this->auditoriaModel    = new Auditoria();
    }

    /**
     * Muestra el tablero de trabajo de 5 columnas con filtro mensual y modal de gestión
     */
    public function index(): void
    {
        $idUsuarioActual = (int)$_SESSION['id_usuario'];
        $slugRolActual   = (string)$_SESSION['slug_rol'];

        $anioFiltro = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');
        $mesFiltro  = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
        if ($mesFiltro < 1 || $mesFiltro > 12) {
            $mesFiltro = (int)date('n');
        }
        if ($anioFiltro < 2020 || $anioFiltro > 2035) {
            $anioFiltro = (int)date('Y');
        }

        $idDeptoFiltro    = isset($_GET['depto']) ? (int)$_GET['depto'] : 0;
        $idTecnicoFiltro  = isset($_GET['tecnico']) ? (int)$_GET['tecnico'] : 0;
        $prioridadFiltro  = isset($_GET['prioridad']) ? limpiar_texto($_GET['prioridad']) : '';
        $incluirArrastre  = !isset($_GET['arrastre']) || $_GET['arrastre'] === '1';

        $filtrosTablero = [
            'anio'             => $anioFiltro,
            'mes'              => $mesFiltro,
            'id_departamento'  => $idDeptoFiltro,
            'id_tecnico'       => $idTecnicoFiltro,
            'prioridad'        => $prioridadFiltro,
            'incluir_arrastre' => $incluirArrastre
        ];

        $datosTablero    = $this->ticketModel->obtenerTableroTrabajo($filtrosTablero, $slugRolActual, $idUsuarioActual);
        $columnasTablero = $datosTablero['columnas'];
        $rechazadosMes   = $datosTablero['rechazados'];

        $departamentosActivos = $this->departamentoModel->listarTodos(true);
        $tecnicosConCarga     = $this->ticketModel->listarTecnicosConCarga();
        $categoriasFalla      = $this->ticketModel->listarCategoriasFalla(true);

        // Si es Jefe de Soporte o Superadmin, cargar usuarios y equipos para registro manual telefónico o presencial
        $usuariosActivos = [];
        $equiposActivos  = [];
        if ($slugRolActual === 'Superadmin' || $slugRolActual === 'Jefe_Soporte') {
            $usuariosActivos = $this->usuarioModel->listarUsuariosAdmin(false);
            $equiposActivos  = $this->equipoModel->listarTodos();
        }

        // Si se solicitó abrir el detalle de un ticket específico en el panel operativo
        $idDetalle = isset($_GET['detalle']) ? (int)$_GET['detalle'] : 0;
        $ticketDetalle = null;
        $comentariosDetalle = [];
        $adjuntosDetalle = [];

        if ($idDetalle > 0) {
            $candidato = $this->ticketModel->obtenerTicketPorId($idDetalle);
            if ($candidato !== null) {
                $puedeVer = ($slugRolActual === 'Superadmin' || $slugRolActual === 'Jefe_Soporte')
                    || $this->ticketModel->tecnicoTieneAsignadoTicket($idDetalle, $idUsuarioActual);
                if ($puedeVer) {
                    $ticketDetalle      = $candidato;
                    $comentariosDetalle = $this->ticketModel->listarComentarios($idDetalle, true);
                    $adjuntosDetalle    = $this->ticketModel->listarAdjuntos($idDetalle);
                }
            }
        }

        $tituloPagina = 'Tablero de trabajo';
        require __DIR__ . '/../views/kanban/index.php';
    }

    /**
     * Acción exclusiva para Jefe de Soporte y Superadmin: Aceptar ticket, definir prioridad oficial y asignar técnico
     */
    public function asignar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('kanban', 'index');
        }

        $slugRol = (string)$_SESSION['slug_rol'];
        if ($slugRol !== 'Superadmin' && $slugRol !== 'Jefe_Soporte' && !tiene_permiso_modulo('Mod5_Kanban', 'permiso_asignar_tickets')) {
            establecer_alerta('error', 'Solo el Jefe de Soporte o el Superadministrador pueden aceptar y asignar solicitudes.');
            redirigir('kanban', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad del formulario caducó. Intenta nuevamente.');
            redirigir('kanban', 'index');
        }

        $idTicket           = (int)($_POST['id_ticket'] ?? 0);
        $prioridadOficial   = limpiar_texto($_POST['prioridad_oficial'] ?? 'Media');
        $idTecnicoPrincipal = (int)($_POST['id_tecnico_principal'] ?? 0);
        $idTecnicoApoyo     = !empty($_POST['id_tecnico_apoyo']) ? (int)$_POST['id_tecnico_apoyo'] : null;
        $instruccionInicial = limpiar_texto($_POST['instruccion_jefe'] ?? '');

        if ($idTicket <= 0 || $idTecnicoPrincipal <= 0) {
            establecer_alerta('advertencia', 'Selecciona el técnico principal que atenderá la solicitud.');
            redirigir('kanban', 'index', ['detalle' => $idTicket]);
        }

        $ticket = $this->ticketModel->obtenerTicketPorId($idTicket);
        if ($ticket === null) {
            establecer_alerta('error', 'No se encontró la solicitud indicada.');
            redirigir('kanban', 'index');
        }

        $idJefe = (int)$_SESSION['id_usuario'];
        $exito  = $this->ticketModel->aceptarYAsignarTicket(
            $idTicket,
            $idJefe,
            $prioridadOficial,
            $idTecnicoPrincipal,
            $idTecnicoApoyo
        );

        if (!$exito) {
            establecer_alerta('error', 'No fue posible guardar la asignación del técnico. Verifica los datos.');
            redirigir('kanban', 'index', ['detalle' => $idTicket]);
        }

        if ($instruccionInicial !== '') {
            $this->ticketModel->agregarComentario(
                $idTicket,
                $idJefe,
                'Instrucción de asignación del Jefe de Soporte: ' . $instruccionInicial,
                1
            );
        }

        $tecnicoDatos = $this->usuarioModel->obtenerPorId($idTecnicoPrincipal);
        $nombreTec = $tecnicoDatos ? ($tecnicoDatos['nombres'] . ' ' . $tecnicoDatos['apellidos']) : 'Técnico #' . $idTecnicoPrincipal;

        $this->auditoriaModel->registrarCambio(
            $idJefe,
            'ticket_asignaciones',
            $idTicket,
            'MODIFICACION',
            'id_personal_soporte',
            $ticket['nombre_tecnico_principal'] ?: 'Sin asignar',
            $nombreTec,
            "Aceptación y asignación del caso {$ticket['codigo_ticket']} con prioridad {$prioridadOficial}"
        );

        $this->auditoriaModel->registrarAcceso(
            $idJefe,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Aceptó y asignó la solicitud {$ticket['codigo_ticket']} a {$nombreTec}",
            5
        );

        establecer_alerta('exito', "La solicitud {$ticket['codigo_ticket']} fue asignada a {$nombreTec} con prioridad {$prioridadOficial}.");
        redirigir('kanban', 'index', ['detalle' => $idTicket]);
    }

    /**
     * Acción exclusiva para Jefe de Soporte y Superadmin: Rechazar solicitud improcedente con motivo obligatorio
     */
    public function rechazar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('kanban', 'index');
        }

        $slugRol = (string)$_SESSION['slug_rol'];
        if ($slugRol !== 'Superadmin' && $slugRol !== 'Jefe_Soporte') {
            establecer_alerta('error', 'No tienes permiso para rechazar solicitudes.');
            redirigir('kanban', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'La validación del formulario caducó.');
            redirigir('kanban', 'index');
        }

        $idTicket      = (int)($_POST['id_ticket'] ?? 0);
        $motivoRechazo = limpiar_texto($_POST['motivo_rechazo'] ?? '');

        if ($idTicket <= 0 || $motivoRechazo === '') {
            establecer_alerta('advertencia', 'Debes escribir el motivo por el cual se rechaza la solicitud.');
            redirigir('kanban', 'index', ['detalle' => $idTicket]);
        }

        $ticket = $this->ticketModel->obtenerTicketPorId($idTicket);
        if ($ticket === null) {
            establecer_alerta('error', 'La solicitud indicada no existe.');
            redirigir('kanban', 'index');
        }

        $idJefe = (int)$_SESSION['id_usuario'];
        $this->ticketModel->rechazarTicket($idTicket, $idJefe, $motivoRechazo);

        $this->ticketModel->agregarComentario(
            $idTicket,
            $idJefe,
            'Solicitud marcada como no procedente. Motivo: ' . $motivoRechazo,
            0
        );

        $this->auditoriaModel->registrarCambio(
            $idJefe,
            'tickets',
            $idTicket,
            'MODIFICACION',
            'estado_aceptacion_jefe',
            (string)$ticket['estado_aceptacion_jefe'],
            'Rechazado',
            $motivoRechazo
        );

        $this->auditoriaModel->registrarAcceso(
            $idJefe,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Rechazó la solicitud {$ticket['codigo_ticket']}: {$motivoRechazo}",
            5
        );

        establecer_alerta('informacion', "La solicitud {$ticket['codigo_ticket']} fue rechazada y se notificó el motivo al usuario.");
        redirigir('kanban', 'index');
    }

    /**
     * Acción para Personal de Soporte, Jefe de Soporte y Superadmin:
     * Mover ticket entre columnas 2_Asignado, 3_En_Proceso, 4_En_Espera y 5_Resuelto,
     * registrando diagnóstico, solución, cambio de pieza y estado operativo del equipo.
     */
    public function mover(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('kanban', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('kanban', 'index');
        }

        $idUsuarioActual = (int)$_SESSION['id_usuario'];
        $idRolActual     = (int)($_SESSION['id_rol'] ?? 0);
        $slugRolActual   = (string)$_SESSION['slug_rol'];
        $idTicket        = (int)($_POST['id_ticket'] ?? 0);
        $nuevaColumna    = limpiar_texto($_POST['estado_columna_kanban'] ?? '');

        $columnasValidas = ['2_Asignado', '3_En_Proceso', '4_En_Espera', '5_Resuelto'];
        if (!in_array($nuevaColumna, $columnasValidas, true)) {
            establecer_alerta('error', 'La etapa seleccionada en el tablero no es válida.');
            redirigir('kanban', 'index');
        }

        $ticket = $idTicket > 0 ? $this->ticketModel->obtenerTicketPorId($idTicket) : null;
        if ($ticket === null) {
            establecer_alerta('error', 'No se encontró la solicitud.');
            redirigir('kanban', 'index');
        }

        // Prevención estricta de IDOR: Validar en ticket_asignaciones que el técnico (Rol ID 3 / Personal_Soporte) sea dueño del caso
        if (($slugRolActual === 'Personal_Soporte' || $idRolActual === 3) && !$this->ticketModel->tecnicoTieneAsignadoTicket($idTicket, $idUsuarioActual)) {
            $this->auditoriaModel->registrarAcceso(
                $idUsuarioActual,
                (string)$_SESSION['username'],
                'Bloqueo_Seguridad',
                "Intento bloqueado de modificar la solicitud #{$idTicket} sin asignación activa en ticket_asignaciones",
                5
            );
            establecer_alerta('error', 'Solo puedes mover, diagnosticar o resolver las solicitudes que tienes asignadas.');
            redirigir('kanban', 'index');
        }

        $motivoEspera    = limpiar_texto($_POST['motivo_pausa_espera'] ?? '');
        $diagnostico     = limpiar_texto($_POST['diagnostico_real_tecnico'] ?? '');
        $solucion        = limpiar_texto($_POST['solucion_aplicada_tecnico'] ?? '');
        $requirioPieza   = !empty($_POST['requirio_cambio_pieza']) ? 1 : 0;
        $detallePieza    = limpiar_texto($_POST['detalle_pieza_cambiada'] ?? '');
        $minutosEspera   = max(0, (int)($_POST['minutos_espera_adicionales'] ?? 0));
        $estadoEquipo    = limpiar_texto($_POST['estado_operativo_equipo'] ?? '');

        if ($nuevaColumna === '4_En_Espera' && $motivoEspera === '') {
            establecer_alerta('advertencia', 'Para pasar la solicitud a la columna En espera debes indicar el motivo de la pausa.');
            redirigir('kanban', 'index', ['detalle' => $idTicket]);
        }

        if ($nuevaColumna === '5_Resuelto') {
            $diagFinal = $diagnostico !== '' ? $diagnostico : (string)($ticket['diagnostico_real_tecnico'] ?? '');
            $solFinal  = $solucion !== '' ? $solucion : (string)($ticket['solucion_aplicada_tecnico'] ?? '');

            if ($diagFinal === '' || $solFinal === '') {
                establecer_alerta('advertencia', 'Para marcar la solicitud como Resuelta debes anotar el diagnóstico real y la solución aplicada.');
                redirigir('kanban', 'index', ['detalle' => $idTicket]);
            }

            if ($requirioPieza === 1 && $detallePieza === '' && empty($ticket['detalle_pieza_cambiada'])) {
                establecer_alerta('advertencia', 'Indicaste que hubo cambio de pieza. Especifica qué componente fue reemplazado.');
                redirigir('kanban', 'index', ['detalle' => $idTicket]);
            }
        }

        $columnaPrevia = (string)$ticket['estado_columna_kanban'];

        $this->ticketModel->actualizarEstadoYAvanceTecnico($idTicket, $nuevaColumna, [
            'motivo_pausa_espera'        => $motivoEspera,
            'diagnostico_real_tecnico'   => $diagnostico,
            'solucion_aplicada_tecnico'  => $solucion,
            'requirio_cambio_pieza'      => $requirioPieza,
            'detalle_pieza_cambiada'     => $detallePieza,
            'minutos_espera_adicionales' => $minutosEspera
        ]);

        // Actualizar estado operativo del equipo si se solicitó o si pasó a Resuelto
        $idEquipo = (int)$ticket['id_equipo'];
        if ($estadoEquipo !== '' && in_array($estadoEquipo, ['Operativo', 'En_Reparacion', 'Danado'], true)) {
            $this->equipoModel->actualizarEstadoOperativo($idEquipo, $estadoEquipo);
        } elseif ($nuevaColumna === '5_Resuelto' && $ticket['estado_operativo_equipo'] === 'En_Reparacion') {
            $this->equipoModel->actualizarEstadoOperativo($idEquipo, 'Operativo');
        }

        // Registrar auditoría complementaria con el autor real del cambio técnico
        if ($columnaPrevia !== $nuevaColumna || $diagnostico !== '' || $solucion !== '') {
            $this->auditoriaModel->registrarCambio(
                $idUsuarioActual,
                'tickets',
                $idTicket,
                'MODIFICACION',
                'avance_tecnico_kanban',
                texto_estado_legible($columnaPrevia),
                texto_estado_legible($nuevaColumna),
                $nuevaColumna === '5_Resuelto' ? ('Solución: ' . $solucion) : ($motivoEspera ?: 'Actualización de avance técnico')
            );
        }

        $this->auditoriaModel->registrarAcceso(
            $idUsuarioActual,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Actualizó la solicitud {$ticket['codigo_ticket']} a la etapa " . texto_estado_legible($nuevaColumna),
            5
        );

        establecer_alerta(
            'exito',
            "La solicitud {$ticket['codigo_ticket']} se actualizó a la columna " . texto_estado_legible($nuevaColumna) . "."
        );

        $mantenerDetalle = isset($_POST['cerrar_panel']) ? [] : ['detalle' => $idTicket];
        redirigir('kanban', 'index', $mantenerDetalle);
    }

    /**
     * Agrega comentarios públicos o notas técnicas privadas y adjuntos desde el tablero
     */
    public function comentar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('kanban', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('kanban', 'index');
        }

        $idUsuario     = (int)$_SESSION['id_usuario'];
        $idRolActual   = (int)($_SESSION['id_rol'] ?? 0);
        $slugRol       = (string)$_SESSION['slug_rol'];
        $idTicket      = (int)($_POST['id_ticket'] ?? 0);
        $mensaje       = limpiar_texto($_POST['mensaje_texto'] ?? '');
        $esNotaPrivada = !empty($_POST['es_nota_interna_privada']) ? 1 : 0;

        $ticket = $idTicket > 0 ? $this->ticketModel->obtenerTicketPorId($idTicket) : null;
        if ($ticket === null) {
            establecer_alerta('error', 'Solicitud no encontrada.');
            redirigir('kanban', 'index');
        }

        // Prevención estricta de IDOR en comentarios y adjuntos para Personal_Soporte (Rol ID 3)
        if (($slugRol === 'Personal_Soporte' || $idRolActual === 3) && !$this->ticketModel->tecnicoTieneAsignadoTicket($idTicket, $idUsuario)) {
            $this->auditoriaModel->registrarAcceso(
                $idUsuario,
                (string)$_SESSION['username'],
                'Bloqueo_Seguridad',
                "Intento bloqueado de comentar en la solicitud #{$idTicket} sin asignación activa en ticket_asignaciones",
                5
            );
            establecer_alerta('error', 'Solo puedes comentar o adjuntar soportes en solicitudes asignadas a ti.');
            redirigir('kanban', 'index');
        }

        // Procesar archivo adjunto con validación de firma MIME real (finfo) y nombre físico aleatorio
        $subioAdjunto = false;
        if (!empty($_FILES['adjunto_kanban']['name']) && ($_FILES['adjunto_kanban']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $mimesPermitidos = [
                'png'  => ['image/png'],
                'jpg'  => ['image/jpeg', 'image/pjpeg'],
                'jpeg' => ['image/jpeg', 'image/pjpeg'],
                'webp' => ['image/webp'],
                'pdf'  => ['application/pdf']
            ];

            $resultadoSubida = procesar_archivo_subido_seguro(
                $_FILES['adjunto_kanban'],
                __DIR__ . '/../public/assets/uploads/tickets',
                'assets/uploads/tickets',
                $mimesPermitidos,
                5 * 1024 * 1024
            );

            if (!$resultadoSubida['exito']) {
                establecer_alerta('advertencia', (string)$resultadoSubida['mensaje']);
                redirigir('kanban', 'index', ['detalle' => $idTicket]);
            }

            $this->ticketModel->registrarAdjunto(
                $idTicket,
                $idUsuario,
                (string)$resultadoSubida['nombre_original'],
                (string)$resultadoSubida['extension'],
                (int)$resultadoSubida['peso_kb'],
                (string)$resultadoSubida['ruta_relativa']
            );
            $subioAdjunto = true;
        }

        if ($mensaje === '' && $subioAdjunto) {
            $mensaje = 'Se adjuntó un archivo técnico de soporte.';
        }

        if ($mensaje === '') {
            establecer_alerta('advertencia', 'Escribe el contenido del comentario o nota técnica.');
            redirigir('kanban', 'index', ['detalle' => $idTicket]);
        }

        $this->ticketModel->agregarComentario($idTicket, $idUsuario, $mensaje, $esNotaPrivada);

        $tipoTexto = $esNotaPrivada ? 'nota interna para soporte' : 'mensaje público para el solicitante';
        $this->auditoriaModel->registrarAcceso(
            $idUsuario,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Registró {$tipoTexto} en la solicitud {$ticket['codigo_ticket']}",
            5
        );

        establecer_alerta('exito', 'Se guardó tu anotación en la solicitud.');
        redirigir('kanban', 'index', ['detalle' => $idTicket]);
    }

    /**
     * Permite al Jefe de Soporte o Superadmin registrar tickets recibidos por llamada telefónica o presencial
     */
    public function registrarManual(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('kanban', 'index');
        }

        $slugRol = (string)$_SESSION['slug_rol'];
        if ($slugRol !== 'Superadmin' && $slugRol !== 'Jefe_Soporte') {
            establecer_alerta('error', 'Solo la jefatura de soporte puede registrar solicitudes telefónicas o presenciales.');
            redirigir('kanban', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('kanban', 'index');
        }

        $idCreador         = (int)$_SESSION['id_usuario'];
        $canalCreacion     = limpiar_texto($_POST['canal_creacion'] ?? 'Llamada_Telefonica');
        $idUsuarioAfectado = (int)($_POST['id_usuario_afectado'] ?? 0);
        $idEquipo          = (int)($_POST['id_equipo'] ?? 0);
        $idCategoriaFalla  = (int)($_POST['id_categoria_falla'] ?? 0);
        $tituloAsunto      = limpiar_texto($_POST['titulo_asunto'] ?? '');
        $descripcion       = limpiar_texto($_POST['descripcion_problematica'] ?? '');
        $prioridadOficial  = limpiar_texto($_POST['prioridad_oficial'] ?? 'Media');
        $idTecnicoAsignar  = (int)($_POST['id_tecnico_principal'] ?? 0);

        if ($idUsuarioAfectado <= 0 || $idEquipo <= 0 || $idCategoriaFalla <= 0 || $tituloAsunto === '' || $descripcion === '') {
            establecer_alerta('advertencia', 'Completa el funcionario afectado, el equipo, la categoría y la descripción de la falla.');
            redirigir('kanban', 'index');
        }

        $usuarioAfec = $this->usuarioModel->obtenerPorId($idUsuarioAfectado);
        $equipoDatos = $this->equipoModel->obtenerPorId($idEquipo);
        if ($usuarioAfec === null || $equipoDatos === null) {
            establecer_alerta('error', 'Verifica que el funcionario y el equipo seleccionados sean válidos.');
            redirigir('kanban', 'index');
        }

        $asignarDirecto = ($idTecnicoAsignar > 0);

        $creado = $this->ticketModel->crearTicket([
            'canal_creacion'           => $canalCreacion,
            'id_usuario_creador'       => $idCreador,
            'id_usuario_afectado'      => $idUsuarioAfectado,
            'id_departamento'          => (int)$equipoDatos['id_departamento'],
            'id_equipo'                => $idEquipo,
            'id_categoria_falla'       => $idCategoriaFalla,
            'titulo_asunto'            => $tituloAsunto,
            'descripcion_problematica' => $descripcion,
            'prioridad_sugerida'       => in_array($prioridadOficial, ['Baja', 'Media', 'Alta'], true) ? $prioridadOficial : 'Alta',
            'prioridad_oficial'        => $prioridadOficial,
            'estado_aceptacion_jefe'   => $asignarDirecto ? 'Aceptado' : 'Pendiente_Revision',
            'estado_columna_kanban'    => $asignarDirecto ? '2_Asignado' : '1_Por_Aceptar',
            'id_jefe_asignador'        => $idCreador
        ]);

        $idNuevoTicket = (int)$creado['id_ticket'];
        $codigoTicket  = (string)$creado['codigo_ticket'];

        if ($asignarDirecto) {
            $this->ticketModel->aceptarYAsignarTicket($idNuevoTicket, $idCreador, $prioridadOficial, $idTecnicoAsignar, null);
        }

        $this->auditoriaModel->registrarCambio(
            $idCreador,
            'tickets',
            $idNuevoTicket,
            'CREACION',
            'codigo_ticket',
            null,
            $codigoTicket,
            "Registro manual por {$canalCreacion} para {$usuarioAfec['nombres']} {$usuarioAfec['apellidos']}"
        );

        $this->auditoriaModel->registrarAcceso(
            $idCreador,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Registró la solicitud {$codigoTicket} vía " . texto_estado_legible($canalCreacion),
            5
        );

        establecer_alerta('exito', "Se registró la solicitud {$codigoTicket} correctamente en el tablero.");
        redirigir('kanban', 'index', ['detalle' => $idNuevoTicket]);
    }

    /**
     * Permite al Jefe de Soporte o Superadmin archivar los tickets resueltos de un mes para mantener limpia la columna 5
     */
    public function archivarMes(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('kanban', 'index');
        }

        $slugRol = (string)$_SESSION['slug_rol'];
        if ($slugRol !== 'Superadmin' && $slugRol !== 'Jefe_Soporte') {
            establecer_alerta('error', 'Solo el Jefe de Soporte puede archivar el cierre mensual del tablero.');
            redirigir('kanban', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'Código de seguridad inválido.');
            redirigir('kanban', 'index');
        }

        $anio = (int)($_POST['anio'] ?? date('Y'));
        $mes  = (int)($_POST['mes'] ?? date('n'));

        $cantidad = $this->ticketModel->archivarResueltosPorPeriodo($anio, $mes);

        $this->auditoriaModel->registrarAcceso(
            (int)$_SESSION['id_usuario'],
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Archivó {$cantidad} solicitudes resueltas del periodo {$mes}/{$anio} en el tablero",
            5
        );

        if ($cantidad > 0) {
            establecer_alerta('exito', "Se archivaron {$cantidad} solicitudes resueltas del mes seleccionado para limpiar la columna.");
        } else {
            establecer_alerta('informacion', 'No había solicitudes resueltas pendientes de archivar en ese mes.');
        }

        redirigir('kanban', 'index', ['anio' => $anio, 'mes' => $mes]);
    }
}
