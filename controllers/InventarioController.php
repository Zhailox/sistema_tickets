<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Departamento.php';
require_once __DIR__ . '/../models/Equipo.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Controlador del Módulo 3: Gestión de Departamentos, Oficinas, Inventario de Equipos
 * Informáticos, Categorías de Falla y Hoja de Vida Técnica por Equipo
 */
class InventarioController
{
    private Departamento $departamentoModel;
    private Equipo $equipoModel;
    private Usuario $usuarioModel;
    private Ticket $ticketModel;
    private Auditoria $auditoriaModel;

    public function __construct()
    {
        if (!sesion_activa()) {
            establecer_alerta('advertencia', 'Inicia sesión para acceder al inventario institucional.');
            redirigir('auth', 'login');
        }

        if (!tiene_permiso_modulo('Mod3_Inventario', 'permiso_ver')) {
            establecer_alerta('error', 'Tu rol actual no tiene acceso a la gestión de departamentos y equipos.');
            redirigir('auth', 'perfil');
        }

        $this->departamentoModel = new Departamento();
        $this->equipoModel       = new Equipo();
        $this->usuarioModel      = new Usuario();
        $this->ticketModel       = new Ticket();
        $this->auditoriaModel    = new Auditoria();
    }

    /**
     * Pantalla principal del inventario de equipos, departamentos y categorías de falla
     */
    public function index(): void
    {
        $filtroDepto     = isset($_GET['depto']) ? (int)$_GET['depto'] : 0;
        $filtroCategoria = isset($_GET['categoria']) ? limpiar_texto($_GET['categoria']) : '';
        $filtroEstado    = isset($_GET['estado']) ? limpiar_texto($_GET['estado']) : '';
        $pestanaActiva   = $_GET['tab'] ?? 'equipos';

        $departamentos   = $this->departamentoModel->listarTodos(false);
        $equipos         = $this->equipoModel->listarTodos([
            'id_departamento'  => $filtroDepto,
            'categoria_equipo' => $filtroCategoria,
            'estado_operativo' => $filtroEstado
        ]);
        $usuariosActivos = $this->usuarioModel->listarUsuariosAdmin(false);
        $categoriasFalla = $this->ticketModel->listarCategoriasFalla(false);

        $equipoEdicion = null;
        if (!empty($_GET['editar_equipo'])) {
            $equipoEdicion = $this->equipoModel->obtenerPorId((int)$_GET['editar_equipo']);
            $pestanaActiva = 'equipos';
        }

        $deptoEdicion = null;
        if (!empty($_GET['editar_depto'])) {
            $deptoEdicion  = $this->departamentoModel->obtenerPorId((int)$_GET['editar_depto']);
            $pestanaActiva = 'departamentos';
        }

        $equipoHojaVida    = null;
        $historialEquipo   = [];
        if (!empty($_GET['ver_equipo'])) {
            $idEqVer = (int)$_GET['ver_equipo'];
            $equipoHojaVida = $this->equipoModel->obtenerPorId($idEqVer);
            if ($equipoHojaVida !== null) {
                $historialEquipo = $this->equipoModel->obtenerHistorialTicketsEquipo($idEqVer);
            }
        }

        $tituloPagina = 'Departamentos y equipos';
        require __DIR__ . '/../views/inventario/index.php';
    }

    /**
     * Registra o actualiza un departamento u oficina institucional
     */
    public function guardarDepartamento(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('inventario', 'index', ['tab' => 'departamentos']);
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('inventario', 'index', ['tab' => 'departamentos']);
        }

        $idDepartamento = (int)($_POST['id_departamento'] ?? 0);
        $codigo         = strtoupper(limpiar_texto($_POST['codigo_departamento'] ?? ''));
        $nombre         = limpiar_texto($_POST['nombre_departamento'] ?? '');
        $sede           = limpiar_texto($_POST['sede_edificio'] ?? '');
        $piso           = limpiar_texto($_POST['piso_oficina'] ?? '');
        $oficina        = limpiar_texto($_POST['numero_oficina'] ?? '');
        $extension      = limpiar_texto($_POST['telefono_extension'] ?? '');
        $activo         = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

        if ($codigo === '' || $nombre === '' || $sede === '' || $piso === '' || $oficina === '') {
            establecer_alerta('advertencia', 'Completa el código, nombre, sede, piso y número de oficina del departamento.');
            redirigir('inventario', 'index', ['tab' => 'departamentos']);
        }

        if ($this->departamentoModel->existeCodigo($codigo, $idDepartamento)) {
            establecer_alerta('error', "El código {$codigo} ya está asignado a otro departamento.");
            redirigir('inventario', 'index', ['tab' => 'departamentos']);
        }

        $idAutor = (int)$_SESSION['id_usuario'];
        $datos   = [
            'codigo_departamento' => $codigo,
            'nombre_departamento' => $nombre,
            'sede_edificio'       => $sede,
            'piso_oficina'        => $piso,
            'numero_oficina'      => $oficina,
            'telefono_extension'  => $extension,
            'activo'              => $activo
        ];

        if ($idDepartamento > 0) {
            $previo = $this->departamentoModel->obtenerPorId($idDepartamento);
            $this->departamentoModel->actualizar($idDepartamento, $datos);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'departamentos',
                $idDepartamento,
                'MODIFICACION',
                'nombre_departamento',
                $previo ? (string)$previo['nombre_departamento'] : 'Registro previo',
                $nombre . ' — ' . $sede . ' ' . $oficina,
                'Actualización de departamento en inventario'
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Actualizó los datos del departamento {$codigo} — {$nombre}",
                3
            );

            establecer_alerta('exito', "El departamento {$nombre} fue actualizado correctamente.");
        } else {
            $nuevoId = $this->departamentoModel->crear($datos);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'departamentos',
                $nuevoId,
                'CREACION',
                'codigo_departamento',
                null,
                $codigo . ' — ' . $nombre,
                'Registro de nuevo departamento u oficina'
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Registró el nuevo departamento {$codigo} — {$nombre}",
                3
            );

            establecer_alerta('exito', "Se registró el departamento {$nombre} exitosamente.");
        }

        redirigir('inventario', 'index', ['tab' => 'departamentos']);
    }

    /**
     * Registra o actualiza un equipo informático en el inventario institucional
     */
    public function guardarEquipo(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('inventario', 'index');
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó. Intenta nuevamente.');
            redirigir('inventario', 'index');
        }

        $idEquipo       = (int)($_POST['id_equipo'] ?? 0);
        $idDepartamento = (int)($_POST['id_departamento'] ?? 0);
        $esCompartido   = !empty($_POST['es_equipo_compartido']) ? 1 : 0;
        $idUsuarioAsig  = ($esCompartido === 1 || empty($_POST['id_usuario_asignado']))
            ? null
            : (int)$_POST['id_usuario_asignado'];
        $codigoBien     = strtoupper(limpiar_texto($_POST['codigo_bien'] ?? ''));
        $numeroSerie    = strtoupper(limpiar_texto($_POST['numero_serie'] ?? ''));
        $categoria      = limpiar_texto($_POST['categoria_equipo'] ?? 'PC_Escritorio');
        $marca          = limpiar_texto($_POST['marca'] ?? '');
        $modelo         = limpiar_texto($_POST['modelo'] ?? '');
        $procesador     = limpiar_texto($_POST['procesador_cpu'] ?? '');
        $ramGb          = !empty($_POST['memoria_ram_gb']) ? (int)$_POST['memoria_ram_gb'] : null;
        $tipoAlm        = limpiar_texto($_POST['tipo_almacenamiento'] ?? 'No_Aplica');
        $capAlmGb       = !empty($_POST['capacidad_almacenamiento_gb']) ? (int)$_POST['capacidad_almacenamiento_gb'] : null;
        $sistemaOp      = limpiar_texto($_POST['sistema_operativo'] ?? '');
        $ipFija         = limpiar_texto($_POST['direccion_ip_fija'] ?? '');
        $mac            = strtoupper(limpiar_texto($_POST['direccion_mac'] ?? ''));
        $estadoOp       = limpiar_texto($_POST['estado_operativo'] ?? 'Operativo');

        if ($idDepartamento <= 0 || $codigoBien === '' || $numeroSerie === '' || $marca === '' || $modelo === '') {
            establecer_alerta('advertencia', 'Indica el departamento, código de bien, número de serie, marca y modelo del equipo.');
            redirigir('inventario', 'index');
        }

        if ($this->equipoModel->existeCodigoBien($codigoBien, $idEquipo)) {
            establecer_alerta('error', "El código de bien {$codigoBien} ya pertenece a otro equipo registrado.");
            redirigir('inventario', 'index');
        }

        if ($this->equipoModel->existeNumeroSerie($numeroSerie, $idEquipo)) {
            establecer_alerta('error', "El número de serie {$numeroSerie} ya está registrado en el inventario.");
            redirigir('inventario', 'index');
        }

        $idAutor = (int)$_SESSION['id_usuario'];
        $datosEquipo = [
            'id_departamento'             => $idDepartamento,
            'id_usuario_asignado'         => $idUsuarioAsig,
            'codigo_bien'                 => $codigoBien,
            'numero_serie'                => $numeroSerie,
            'categoria_equipo'            => $categoria,
            'marca'                       => $marca,
            'modelo'                      => $modelo,
            'procesador_cpu'              => $procesador,
            'memoria_ram_gb'              => $ramGb,
            'tipo_almacenamiento'         => $tipoAlm,
            'capacidad_almacenamiento_gb' => $capAlmGb,
            'sistema_operativo'           => $sistemaOp,
            'direccion_ip_fija'           => $ipFija,
            'direccion_mac'               => $mac,
            'es_equipo_compartido'        => $esCompartido,
            'estado_operativo'            => $estadoOp
        ];

        if ($idEquipo > 0) {
            $previo = $this->equipoModel->obtenerPorId($idEquipo);
            $this->equipoModel->actualizar($idEquipo, $datosEquipo);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'equipos',
                $idEquipo,
                'MODIFICACION',
                'estado_operativo',
                $previo ? ($previo['codigo_bien'] . ' — ' . $previo['estado_operativo']) : 'Previo',
                $codigoBien . ' — ' . $estadoOp,
                "Actualización de ficha técnica del equipo {$marca} {$modelo}"
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Actualizó la ficha técnica del equipo {$codigoBien}",
                3
            );

            establecer_alerta('exito', "Los datos del equipo {$codigoBien} se actualizaron correctamente.");
        } else {
            $nuevoIdEq = $this->equipoModel->crear($datosEquipo);

            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'equipos',
                $nuevoIdEq,
                'CREACION',
                'codigo_bien',
                null,
                $codigoBien . ' — ' . $marca . ' ' . $modelo,
                'Incorporación de equipo al inventario institucional'
            );

            $this->auditoriaModel->registrarAcceso(
                $idAutor,
                (string)$_SESSION['username'],
                'Navegacion_Modulo',
                "Registró el nuevo equipo {$codigoBien} en el inventario",
                3
            );

            establecer_alerta('exito', "El equipo {$marca} {$modelo} con código {$codigoBien} quedó registrado.");
        }

        redirigir('inventario', 'index');
    }

    /**
     * Registra o actualiza una categoría de falla técnica y su tiempo estimado en minutos
     */
    public function guardarCategoriaFalla(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirigir('inventario', 'index', ['tab' => 'categorias']);
        }

        if (!verificar_token_csrf($_POST['token_csrf'] ?? '')) {
            establecer_alerta('error', 'El código de seguridad caducó.');
            redirigir('inventario', 'index', ['tab' => 'categorias']);
        }

        $idCat        = (int)($_POST['id_categoria_falla'] ?? 0);
        $ramaGeneral  = limpiar_texto($_POST['rama_general'] ?? 'Hardware');
        $subcategoria = limpiar_texto($_POST['subcategoria'] ?? '');
        $tiempoMin    = max(5, (int)($_POST['tiempo_estimado_minutos'] ?? 60));
        $activo       = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

        if ($subcategoria === '') {
            establecer_alerta('advertencia', 'Escribe el nombre de la subcategoría de falla.');
            redirigir('inventario', 'index', ['tab' => 'categorias']);
        }

        $idAutor = (int)$_SESSION['id_usuario'];

        if ($idCat > 0) {
            $this->ticketModel->actualizarCategoriaFalla($idCat, $ramaGeneral, $subcategoria, $tiempoMin, $activo);
            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'categorias_falla',
                $idCat,
                'MODIFICACION',
                'tiempo_estimado_minutos',
                'Configuración previa',
                "{$subcategoria} — {$tiempoMin} minutos",
                'Ajuste en catálogo de categorías de falla'
            );
            establecer_alerta('exito', 'La categoría de falla fue actualizada correctamente.');
        } else {
            $nuevoId = $this->ticketModel->crearCategoriaFalla($ramaGeneral, $subcategoria, $tiempoMin, $activo);
            $this->auditoriaModel->registrarCambio(
                $idAutor,
                'categorias_falla',
                $nuevoId,
                'CREACION',
                'subcategoria',
                null,
                "{$ramaGeneral} — {$subcategoria} — {$tiempoMin} minutos",
                'Nueva categoría de falla técnica agregada'
            );
            establecer_alerta('exito', 'Se agregó la nueva categoría de falla al catálogo.');
        }

        redirigir('inventario', 'index', ['tab' => 'categorias']);
    }
}
