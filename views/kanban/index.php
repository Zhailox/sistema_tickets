<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
$tokenCsrf = obtener_token_csrf();
$esJefeOSuper = ($slugRolActual === 'Superadmin' || $slugRolActual === 'Jefe_Soporte');

$nombresMeses = [
    1  => 'Enero',      2  => 'Febrero',   3  => 'Marzo',      4  => 'Abril',
    5  => 'Mayo',       6  => 'Junio',     7  => 'Julio',      8  => 'Agosto',
    9  => 'Septiembre', 10 => 'Octubre',   11 => 'Noviembre',  12 => 'Diciembre'
];

$definicionColumnas = [
    '1_Por_Aceptar' => ['titulo' => '1. Por aceptar',  'badge' => 'insignia-suave-neutra'],
    '2_Asignado'    => ['titulo' => '2. Asignado',     'badge' => 'insignia-suave-media'],
    '3_En_Proceso'  => ['titulo' => '3. En atención',  'badge' => 'insignia-suave-media'],
    '4_En_Espera'   => ['titulo' => '4. En espera',    'badge' => 'insignia-suave-alta'],
    '5_Resuelto'    => ['titulo' => '5. Resuelto',     'badge' => 'insignia-suave-baja'],
];
?>

<!-- Barra superior compacta en una sola fila de filtros, buscador instantáneo y acciones -->
<div class="barra-filtros-kanban mb-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
            <div style="min-width: 220px; max-width: 280px;" class="flex-grow-1">
                <input type="search"
                       class="form-control form-control-sm"
                       id="buscadorTarjetasTablero"
                       placeholder="Buscar por código, asunto, equipo o solicitante...">
            </div>

            <form action="<?php echo e(ruta_url('kanban', 'index')); ?>" method="GET" class="d-flex flex-wrap align-items-center gap-2 m-0">
                <input type="hidden" name="c" value="kanban">
                <input type="hidden" name="a" value="index">

                <select class="form-select form-select-sm control-filtro-kanban-mes" id="filtro_mes_kanban" name="mes" title="Mes de consulta">
                    <?php foreach ($nombresMeses as $numMes => $nomMes): ?>
                        <option value="<?php echo $numMes; ?>" <?php echo $mesFiltro === $numMes ? 'selected' : ''; ?>>
                            <?php echo e($nomMes); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select class="form-select form-select-sm control-filtro-kanban-anio" id="filtro_anio_kanban" name="anio" title="Año de consulta">
                    <?php for ($a = (int)date('Y') + 1; $a >= 2024; $a--): ?>
                        <option value="<?php echo $a; ?>" <?php echo $anioFiltro === $a ? 'selected' : ''; ?>>
                            <?php echo $a; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <?php if ($esJefeOSuper): ?>
                    <select class="form-select form-select-sm control-filtro-kanban-depto" id="filtro_depto_kanban" name="depto" title="Filtrar por departamento">
                        <option value="0">Todos los departamentos</option>
                        <?php foreach ($departamentosActivos as $dep): ?>
                            <option value="<?php echo (int)$dep['id_departamento']; ?>"
                                    <?php echo $idDeptoFiltro === (int)$dep['id_departamento'] ? 'selected' : ''; ?>>
                                <?php echo e($dep['nombre_departamento']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select class="form-select form-select-sm control-filtro-kanban-tec" id="filtro_tec_kanban" name="tecnico" title="Filtrar por técnico">
                        <option value="0">Todo el equipo técnico</option>
                        <?php foreach ($tecnicosConCarga as $tec): ?>
                            <option value="<?php echo (int)$tec['id_usuario']; ?>"
                                    <?php echo $idTecnicoFiltro === (int)$tec['id_usuario'] ? 'selected' : ''; ?>>
                                <?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <select class="form-select form-select-sm control-filtro-kanban-prio" id="filtro_prio_kanban" name="prioridad" title="Filtrar por prioridad">
                    <option value="">Todas las prioridades</option>
                    <option value="Critica" <?php echo $prioridadFiltro === 'Critica' ? 'selected' : ''; ?>>Crítica</option>
                    <option value="Alta" <?php echo $prioridadFiltro === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                    <option value="Media" <?php echo $prioridadFiltro === 'Media' ? 'selected' : ''; ?>>Media</option>
                    <option value="Baja" <?php echo $prioridadFiltro === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                </select>

                <button type="submit" class="btn btn-sm btn-institucional">
                    Filtrar
                </button>
                <a href="<?php echo e(ruta_url('kanban', 'index')); ?>" class="btn btn-sm btn-secondary">
                    Limpiar
                </a>
            </form>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if ($esJefeOSuper): ?>
                <button type="button"
                        class="btn btn-sm btn-institucional"
                        data-bs-toggle="modal"
                        data-bs-target="#modalRegistroManualTicket">
                    Registrar solicitud telefónica o presencial
                </button>

                <?php if (!empty($columnasTablero['5_Resuelto'])): ?>
                    <form action="<?php echo e(ruta_url('kanban', 'archivarMes')); ?>"
                          method="POST"
                          class="m-0"
                          data-confirmar-mensaje="¿Deseas archivar las solicitudes resueltas de este mes para limpiar la quinta columna? Los datos seguirán disponibles en las estadísticas.">
                        <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                        <input type="hidden" name="anio" value="<?php echo $anioFiltro; ?>">
                        <input type="hidden" name="mes" value="<?php echo $mesFiltro; ?>">
                        <button type="submit" class="btn btn-sm btn-secondary">
                            Archivar resueltos de <?php echo e($nombresMeses[$mesFiltro] ?? ''); ?>
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <span class="insignia-suave insignia-suave-neutra">
                    Mis casos asignados
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Barra de navegación rápida entre etapas para teléfonos móviles y tablets -->
<div class="barra-salto-columnas-movil d-flex d-lg-none" aria-label="Ir directamente a una etapa del tablero">
    <?php foreach ($definicionColumnas as $claveColNav => $metaColNav): ?>
        <?php $conteoNav = count($columnasTablero[$claveColNav] ?? []); ?>
        <button type="button"
                class="boton-salto-columna"
                data-ir-columna="#columna-kanban-<?php echo e($claveColNav); ?>">
            <span><?php echo e($metaColNav['titulo']); ?></span>
            <span class="insignia-suave <?php echo e($metaColNav['badge']); ?>"><?php echo $conteoNav; ?></span>
        </button>
    <?php endforeach; ?>
</div>

<!-- Tablero Operativo de 5 Columnas con Auto-Compactación de Columnas Vacías -->
<div class="contenedor-tablero-cinco-columnas">
    <?php foreach ($definicionColumnas as $claveCol => $metaCol): ?>
        <?php
        $listaTarjetas = $columnasTablero[$claveCol] ?? [];
        $esColumnaVacia = empty($listaTarjetas);
        ?>
        <section class="columna-tablero-institucional <?php echo $esColumnaVacia ? 'columna-vacia' : ''; ?>"
                 id="columna-kanban-<?php echo e($claveCol); ?>"
                 aria-label="<?php echo e($metaCol['titulo']); ?>">
            <div class="encabezado-columna-tablero">
                <h3 class="titulo-columna-kanban" title="<?php echo e($metaCol['titulo']); ?>">
                    <?php echo e($metaCol['titulo']); ?>
                </h3>
                <span class="insignia-suave <?php echo e($metaCol['badge']); ?>">
                    <?php echo count($listaTarjetas); ?>
                </span>
            </div>

            <div class="cuerpo-columna-tablero">
                <?php if ($esColumnaVacia): ?>
                    <div class="p-3 text-center texto-secundario small">
                        <?php if ($claveCol === '1_Por_Aceptar' && !$esJefeOSuper): ?>
                            Etapa de revisión inicial a cargo del Jefe de Soporte.
                        <?php else: ?>
                            Sin casos en esta etapa.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($listaTarjetas as $tck): ?>
                        <?php
                        $idTck = (int)$tck['id_ticket'];
                        $prio  = (string)$tck['prioridad_oficial'];
                        $esActivaEnPanel = ($ticketDetalle !== null && (int)$ticketDetalle['id_ticket'] === $idTck);
                        ?>
                        <article class="tarjeta-caso-tablero <?php echo $esActivaEnPanel ? 'activa-seleccionada' : ''; ?>"
                                 data-tarjeta-filtrable="si">
                            <div class="d-flex justify-content-between align-items-center gap-1 mb-2">
                                <span class="codigo-monoespaciado"><?php echo e($tck['codigo_ticket']); ?></span>
                                <span class="<?php echo e(clase_insignia_prioridad($prio)); ?>">
                                    <?php echo e(texto_estado_legible($prio)); ?>
                                </span>
                            </div>

                            <h4 class="tarjeta-caso-titulo" title="<?php echo e($tck['titulo_asunto']); ?>">
                                <?php echo e($tck['titulo_asunto']); ?>
                            </h4>

                            <div class="tarjeta-caso-meta">
                                <div class="tarjeta-caso-meta-item" title="<?php echo e($tck['nombre_departamento']); ?>">
                                    <i class="ni ni-building" aria-hidden="true"></i>
                                    <span class="text-truncate"><?php echo e($tck['nombre_departamento']); ?></span>
                                </div>
                                <div class="tarjeta-caso-meta-item" title="<?php echo e($tck['nombre_afectado']); ?>">
                                    <i class="ni ni-single-02" aria-hidden="true"></i>
                                    <span class="text-truncate"><?php echo e($tck['nombre_afectado']); ?></span>
                                </div>
                                <div class="tarjeta-caso-meta-item" title="<?php echo e($tck['marca_equipo'] . ' ' . $tck['modelo_equipo'] . ' · ' . $tck['codigo_bien']); ?>">
                                    <i class="ni ni-laptop" aria-hidden="true"></i>
                                    <span class="text-truncate"><?php echo e($tck['codigo_bien'] . ' · ' . $tck['marca_equipo'] . ' ' . $tck['modelo_equipo']); ?></span>
                                </div>
                            </div>

                            <?php if ($claveCol === '4_En_Espera' && !empty($tck['motivo_pausa_espera'])): ?>
                                <div class="small p-1 px-2 rounded mt-2" style="background-color: #FFFBEB; border: 1px solid #FDE68A; color: #92400E; font-size: 0.76rem;">
                                    Pausa: <?php echo e($tck['motivo_pausa_espera']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($claveCol === '5_Resuelto'): ?>
                                <div class="mt-2">
                                    <?php if ($tck['confirmado_por_usuario'] === 'Confirmado_Conforme'): ?>
                                        <span class="insignia-suave insignia-suave-baja">Confirmado conforme</span>
                                    <?php else: ?>
                                        <span class="insignia-suave insignia-suave-alta">Pendiente de conformidad</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="tarjeta-caso-pie">
                                <div class="text-truncate" title="<?php echo e($tck['nombre_tecnico_principal'] ?? 'Sin asignar'); ?>">
                                    <?php if (!empty($tck['nombre_tecnico_principal'])): ?>
                                        <span class="fw-medium"><?php echo e($tck['nombre_tecnico_principal']); ?></span>
                                    <?php else: ?>
                                        <span class="texto-secundario">Sin asignar</span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                    <a href="<?php echo e(ruta_url('kanban', 'index', ['anio' => $anioFiltro, 'mes' => $mesFiltro, 'detalle' => $idTck])); ?>"
                                       class="btn btn-sm btn-secondary">
                                        <?php echo $claveCol === '1_Por_Aceptar' && $esJefeOSuper ? 'Revisar' : 'Gestionar'; ?>
                                    </a>

                                    <?php if ($claveCol === '2_Asignado'): ?>
                                        <form action="<?php echo e(ruta_url('kanban', 'mover')); ?>" method="POST" class="m-0">
                                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                            <input type="hidden" name="id_ticket" value="<?php echo $idTck; ?>">
                                            <input type="hidden" name="estado_columna_kanban" value="3_En_Proceso">
                                            <input type="hidden" name="cerrar_panel" value="1">
                                            <button type="submit" class="btn btn-sm btn-institucional" title="Pasar de inmediato a En atención">
                                                Atender
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<?php if ($esJefeOSuper && !empty($rechazadosMes)): ?>
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Solicitudes rechazadas en <?php echo e($nombresMeses[$mesFiltro] ?? ''); ?> <?php echo $anioFiltro; ?></span>
            <span class="insignia-suave insignia-suave-critica"><?php echo count($rechazadosMes); ?></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0" id="tablaRechazadosKanban" data-paginacion="true">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Solicitante y departamento</th>
                            <th>Asunto</th>
                            <th>Motivo del rechazo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rechazadosMes as $rec): ?>
                            <tr>
                                <td><span class="codigo-monoespaciado"><?php echo e($rec['codigo_ticket']); ?></span></td>
                                <td>
                                    <?php echo e($rec['nombre_afectado']); ?> · <span class="texto-secundario"><?php echo e($rec['nombre_departamento']); ?></span>
                                </td>
                                <td><?php echo e($rec['titulo_asunto']); ?></td>
                                <td class="text-danger fw-medium"><?php echo e($rec['motivo_rechazo']); ?></td>
                                <td class="small texto-secundario"><?php echo e(formatear_fecha_esp((string)$rec['fecha_creacion'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Amplio y Estructurado de Gestión del Caso (Sin Estiramiento Vertical) -->
<?php if ($ticketDetalle !== null): ?>
    <?php
    $idTckDet = (int)$ticketDetalle['id_ticket'];
    $colDet   = (string)$ticketDetalle['estado_columna_kanban'];
    ?>
    <div class="modal fade" id="modalGestionCasoActivo" tabindex="-1" aria-labelledby="tituloModalGestionCaso" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="codigo-monoespaciado"><?php echo e($ticketDetalle['codigo_ticket']); ?></span>
                        <h3 class="modal-title h5 fw-bold mb-0" id="tituloModalGestionCaso">
                            <?php echo e($ticketDetalle['titulo_asunto']); ?>
                        </h3>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="<?php echo e(clase_insignia_prioridad((string)$ticketDetalle['prioridad_oficial'])); ?>">
                            Prioridad <?php echo e(texto_estado_legible((string)$ticketDetalle['prioridad_oficial'])); ?>
                        </span>
                        <span class="<?php echo e(clase_insignia_estado($colDet)); ?>">
                            <?php echo e(texto_estado_legible($colDet)); ?>
                        </span>
                        <a href="<?php echo e(ruta_url('kanban', 'index', ['anio' => $anioFiltro, 'mes' => $mesFiltro])); ?>"
                           class="btn btn-sm btn-secondary">
                            Cerrar ficha
                        </a>
                    </div>
                </div>

                <div class="modal-body">
                    <div class="row g-3 fila-inicio-alineada">
                        <!-- Columna 1: Información del solicitante, equipo y tiempos (Altura independiente) -->
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    Ficha del solicitante y equipo
                                </div>
                                <div class="card-body small">
                                    <div class="mb-2 pb-2 border-bottom">
                                        <span class="texto-secundario d-block">Funcionario afectado:</span>
                                        <span class="fw-semibold"><?php echo e($ticketDetalle['nombre_afectado']); ?></span>
                                        <span class="texto-secundario d-block">
                                            <?php echo e($ticketDetalle['cargo_afectado']); ?> · Cédula <?php echo e($ticketDetalle['cedula_afectado']); ?>
                                        </span>
                                    </div>

                                    <div class="mb-2 pb-2 border-bottom">
                                        <span class="texto-secundario d-block">Departamento y ubicación:</span>
                                        <span class="fw-semibold"><?php echo e($ticketDetalle['nombre_departamento']); ?></span>
                                        <span class="texto-secundario d-block">
                                            <?php echo e($ticketDetalle['sede_edificio'] . ' · ' . $ticketDetalle['piso_oficina'] . ' · ' . $ticketDetalle['numero_oficina']); ?>
                                            <?php if (!empty($ticketDetalle['extension_afectado'])): ?>
                                                · Extensión <?php echo e($ticketDetalle['extension_afectado']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <div class="mb-2 pb-2 border-bottom">
                                        <span class="texto-secundario d-block">Equipo informático:</span>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <span class="fw-semibold">
                                                <?php echo e($ticketDetalle['marca_equipo'] . ' ' . $ticketDetalle['modelo_equipo']); ?>
                                            </span>
                                            <span class="<?php echo e(clase_insignia_estado((string)$ticketDetalle['estado_operativo_equipo'])); ?>">
                                                <?php echo e(texto_estado_legible((string)$ticketDetalle['estado_operativo_equipo'])); ?>
                                            </span>
                                        </div>
                                        <span class="texto-secundario d-block mt-1">
                                            Bien: <span class="codigo-monoespaciado"><?php echo e($ticketDetalle['codigo_bien']); ?></span>
                                            · Serie: <?php echo e($ticketDetalle['numero_serie']); ?>
                                        </span>
                                        <?php if (!empty($ticketDetalle['direccion_ip_fija'])): ?>
                                            <span class="texto-secundario d-block mt-1">
                                                Dirección IP: <?php echo e($ticketDetalle['direccion_ip_fija']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-2 pb-2 border-bottom">
                                        <span class="texto-secundario d-block">Categoría de falla y tiempo meta:</span>
                                        <span class="fw-semibold">
                                            <?php echo e(texto_estado_legible((string)$ticketDetalle['rama_general']) . ' · ' . $ticketDetalle['subcategoria']); ?>
                                        </span>
                                        <span class="texto-secundario d-block">
                                            Tiempo estimado: <?php echo (int)$ticketDetalle['tiempo_estimado_minutos']; ?> minutos
                                        </span>
                                    </div>

                                    <div class="mb-2 pb-2 border-bottom">
                                        <span class="texto-secundario d-block">Descripción de la problemática:</span>
                                        <div class="mt-1"><?php echo nl2br(e((string)$ticketDetalle['descripcion_problematica'])); ?></div>
                                    </div>

                                    <div class="texto-secundario">
                                        <div>Canal de ingreso: <strong><?php echo e(texto_estado_legible((string)$ticketDetalle['canal_creacion'])); ?></strong></div>
                                        <div>Prioridad sugerida: <strong><?php echo e($ticketDetalle['prioridad_sugerida']); ?></strong></div>
                                        <div>Fecha de creación: <strong><?php echo e(formatear_fecha_esp((string)$ticketDetalle['fecha_creacion'])); ?></strong></div>
                                        <?php if (!empty($ticketDetalle['fecha_inicio_atencion'])): ?>
                                            <div>Inicio de atención: <strong><?php echo e(formatear_fecha_esp((string)$ticketDetalle['fecha_inicio_atencion'])); ?></strong></div>
                                        <?php endif; ?>
                                        <?php if ((int)$ticketDetalle['tiempo_acumulado_espera_min'] > 0): ?>
                                            <div>Tiempo en espera: <strong><?php echo (int)$ticketDetalle['tiempo_acumulado_espera_min']; ?> minutos</strong></div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($adjuntosDetalle)): ?>
                                        <div class="mt-2 pt-2 border-top">
                                            <span class="texto-secundario d-block mb-1">Archivos adjuntos:</span>
                                            <div class="d-flex flex-column gap-1">
                                                <?php foreach ($adjuntosDetalle as $adj): ?>
                                                    <a href="<?php echo e(recurso_publico((string)$adj['ruta_almacenamiento'])); ?>"
                                                       target="_blank"
                                                       rel="noopener"
                                                       class="fw-semibold text-decoration-none d-inline-flex align-items-center gap-1"
                                                       style="color: var(--color-primario);">
                                                        <i class="ni ni-paperclip" aria-hidden="true"></i>
                                                        <span><?php echo e($adj['nombre_archivo_original']); ?></span>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 2: Asignación del Jefe de Soporte y Avance Técnico (Altura independiente) -->
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <?php echo $esJefeOSuper ? 'Asignación y gestión técnica' : 'Avance técnico, diagnóstico y solución'; ?>
                                </div>
                                <div class="card-body">
                                    <?php if ($esJefeOSuper): ?>
                                        <form action="<?php echo e(ruta_url('kanban', 'asignar')); ?>" method="POST" class="mb-3">
                                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                            <input type="hidden" name="id_ticket" value="<?php echo $idTckDet; ?>">

                                            <div class="mb-2">
                                                <label for="prioridad_oficial_asig" class="form-label">Prioridad oficial</label>
                                                <select class="form-select" id="prioridad_oficial_asig" name="prioridad_oficial" required>
                                                    <?php foreach (['Baja' => 'Baja', 'Media' => 'Media', 'Alta' => 'Alta', 'Critica' => 'Crítica'] as $valP => $txtP): ?>
                                                        <option value="<?php echo $valP; ?>"
                                                                <?php echo $ticketDetalle['prioridad_oficial'] === $valP ? 'selected' : ''; ?>>
                                                            <?php echo e($txtP); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label for="id_tecnico_principal_asig" class="form-label">Técnico principal responsable</label>
                                                <select class="form-select"
                                                        id="id_tecnico_principal_asig"
                                                        name="id_tecnico_principal"
                                                        data-autocompletable="true"
                                                        data-placeholder="Buscar especialista por nombre o carga..."
                                                        required>
                                                    <option value="">Selecciona un técnico de soporte</option>
                                                    <?php foreach ($tecnicosConCarga as $tec): ?>
                                                        <option value="<?php echo (int)$tec['id_usuario']; ?>"
                                                                data-titulo="<?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>"
                                                                data-subtexto="<?php echo e(($tec['cargo_empleado'] ?? 'Especialista de soporte') . ' · ' . (int)$tec['casos_activos'] . ' casos activos'); ?>"
                                                                <?php echo (int)($ticketDetalle['id_tecnico_principal'] ?? 0) === (int)$tec['id_usuario'] ? 'selected' : ''; ?>>
                                                            <?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-2">
                                                <label for="id_tecnico_apoyo_asig" class="form-label">Técnico de apoyo opcional</label>
                                                <select class="form-select"
                                                        id="id_tecnico_apoyo_asig"
                                                        name="id_tecnico_apoyo"
                                                        data-autocompletable="true"
                                                        data-placeholder="Buscar técnico de apoyo opcional...">
                                                    <option value="">Sin técnico de apoyo adicional</option>
                                                    <?php foreach ($tecnicosConCarga as $tec): ?>
                                                        <option value="<?php echo (int)$tec['id_usuario']; ?>"
                                                                data-titulo="<?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>"
                                                                data-subtexto="<?php echo e(($tec['cargo_empleado'] ?? 'Especialista de soporte') . ' · ' . (int)$tec['casos_activos'] . ' casos activos'); ?>"
                                                                <?php echo (int)($ticketDetalle['id_tecnico_apoyo'] ?? 0) === (int)$tec['id_usuario'] ? 'selected' : ''; ?>>
                                                            <?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-3">
                                                <label for="instruccion_jefe_asig" class="form-label">Instrucción interna para el técnico</label>
                                                <input type="text"
                                                       class="form-control"
                                                       id="instruccion_jefe_asig"
                                                       name="instruccion_jefe"
                                                       placeholder="Indicación opcional al asignar">
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-institucional">
                                                    <?php echo $colDet === '1_Por_Aceptar' ? 'Aceptar y pasar a Asignado' : 'Guardar asignación y prioridad'; ?>
                                                </button>
                                            </div>
                                        </form>

                                        <?php if ($colDet === '1_Por_Aceptar'): ?>
                                            <div class="border-top pt-3 mb-2">
                                                <label class="form-label text-danger fw-semibold">Rechazar solicitud improcedente</label>
                                                <form action="<?php echo e(ruta_url('kanban', 'rechazar')); ?>"
                                                      method="POST"
                                                      data-confirmar-mensaje="¿Confirmas que deseas rechazar esta solicitud? El usuario verá el motivo indicado.">
                                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                    <input type="hidden" name="id_ticket" value="<?php echo $idTckDet; ?>">

                                                    <div class="d-flex gap-2">
                                                        <input type="text"
                                                               class="form-control form-control-sm"
                                                               name="motivo_rechazo"
                                                               placeholder="Escribe el motivo del rechazo"
                                                               required>
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            Rechazar
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if ($colDet !== '1_Por_Aceptar' || !$esJefeOSuper): ?>
                                        <div class="<?php echo $esJefeOSuper ? 'border-top pt-3' : ''; ?>">
                                            <?php if ($esJefeOSuper): ?>
                                                <h4 class="h6 fw-bold mb-2">Avance técnico, diagnóstico y solución</h4>
                                            <?php endif; ?>

                                            <form action="<?php echo e(ruta_url('kanban', 'mover')); ?>" method="POST">
                                                <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                <input type="hidden" name="id_ticket" value="<?php echo $idTckDet; ?>">

                                                <div class="mb-2">
                                                    <label for="estado_columna_mover" class="form-label">Mover a la etapa del tablero</label>
                                                    <select class="form-select" id="estado_columna_mover" name="estado_columna_kanban" required>
                                                        <option value="2_Asignado" <?php echo $colDet === '2_Asignado' ? 'selected' : ''; ?>>
                                                            2. Asignado
                                                        </option>
                                                        <option value="3_En_Proceso" <?php echo $colDet === '3_En_Proceso' ? 'selected' : ''; ?>>
                                                            3. En atención
                                                        </option>
                                                        <option value="4_En_Espera" <?php echo $colDet === '4_En_Espera' ? 'selected' : ''; ?>>
                                                            4. En espera de repuesto o insumo
                                                        </option>
                                                        <option value="5_Resuelto" <?php echo $colDet === '5_Resuelto' ? 'selected' : ''; ?>>
                                                            5. Resuelto
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="mb-2" id="bloqueMotivoEsperaKanban">
                                                    <label for="motivo_pausa_espera" class="form-label">Motivo de pausa en espera</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           id="motivo_pausa_espera"
                                                           name="motivo_pausa_espera"
                                                           value="<?php echo e($ticketDetalle['motivo_pausa_espera'] ?? ''); ?>"
                                                           placeholder="Ejemplo: En espera de adaptador solicitado a almacén">
                                                </div>

                                                <div class="mb-2">
                                                    <label for="diagnostico_real_tecnico" class="form-label">Diagnóstico técnico real</label>
                                                    <textarea class="form-control"
                                                              id="diagnostico_real_tecnico"
                                                              name="diagnostico_real_tecnico"
                                                              rows="2"
                                                              placeholder="Causa técnica detectada al revisar el equipo"><?php echo e($ticketDetalle['diagnostico_real_tecnico'] ?? ''); ?></textarea>
                                                </div>

                                                <div class="mb-2">
                                                    <label for="solucion_aplicada_tecnico" class="form-label">Solución técnica aplicada</label>
                                                    <textarea class="form-control"
                                                              id="solucion_aplicada_tecnico"
                                                              name="solucion_aplicada_tecnico"
                                                              rows="2"
                                                              placeholder="Procedimiento realizado para solventar la falla"><?php echo e($ticketDetalle['solucion_aplicada_tecnico'] ?? ''); ?></textarea>
                                                </div>

                                                <div class="mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               id="requirio_cambio_pieza"
                                                               name="requirio_cambio_pieza"
                                                               value="1"
                                                               <?php echo (int)$ticketDetalle['requirio_cambio_pieza'] === 1 ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small fw-semibold" for="requirio_cambio_pieza">
                                                            Requirió reemplazo o cambio de pieza física
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="mb-2" id="bloqueDetallePiezaKanban">
                                                    <label for="detalle_pieza_cambiada" class="form-label">Detalle de la pieza reemplazada</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           id="detalle_pieza_cambiada"
                                                           name="detalle_pieza_cambiada"
                                                           value="<?php echo e($ticketDetalle['detalle_pieza_cambiada'] ?? ''); ?>"
                                                           placeholder="Ejemplo: Fuente de poder ATX 500W o Cargador USB-C">
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label for="estado_operativo_eq_mov" class="form-label">Estado del equipo</label>
                                                        <select class="form-select" id="estado_operativo_eq_mov" name="estado_operativo_equipo">
                                                            <option value="Operativo" <?php echo $ticketDetalle['estado_operativo_equipo'] === 'Operativo' ? 'selected' : ''; ?>>Operativo</option>
                                                            <option value="En_Reparacion" <?php echo $ticketDetalle['estado_operativo_equipo'] === 'En_Reparacion' ? 'selected' : ''; ?>>En reparación</option>
                                                            <option value="Danado" <?php echo $ticketDetalle['estado_operativo_equipo'] === 'Danado' ? 'selected' : ''; ?>>Dañado</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label for="minutos_espera_adic" class="form-label">Minutos extra en pausa</label>
                                                        <input type="number"
                                                               class="form-control"
                                                               id="minutos_espera_adic"
                                                               name="minutos_espera_adicionales"
                                                               min="0"
                                                               value="0">
                                                    </div>
                                                </div>

                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-pizarra">
                                                        Guardar avance técnico
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Columna 3: Caja de Chat Real con Scroll Controlado y Subida Estilizada -->
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Bitácora de comentarios y notas</span>
                                    <span class="insignia-suave insignia-suave-neutra"><?php echo count($comentariosDetalle); ?></span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="contenedor-chat-bitacora border-0 rounded-0">
                                        <div class="lista-mensajes-chat">
                                            <?php if (empty($comentariosDetalle)): ?>
                                                <div class="p-4 text-center texto-secundario small my-auto">
                                                    Aún no hay comentarios ni notas registradas en este caso.
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($comentariosDetalle as $com): ?>
                                                    <?php $esPrivada = ((int)$com['es_nota_interna_privada'] === 1); ?>
                                                    <div class="burbuja-comentario-chat <?php echo $esPrivada ? 'nota-interna-tecnica' : ''; ?>">
                                                        <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                                            <span class="small fw-semibold"><?php echo e($com['nombre_autor']); ?></span>
                                                            <span class="insignia-suave <?php echo $esPrivada ? 'insignia-suave-alta' : 'insignia-suave-media'; ?>">
                                                                <?php echo $esPrivada ? 'Nota interna' : 'Visible al solicitante'; ?>
                                                            </span>
                                                        </div>
                                                        <div class="small mb-1"><?php echo nl2br(e((string)$com['mensaje_texto'])); ?></div>
                                                        <div class="text-end texto-secundario" style="font-size: 0.74rem;">
                                                            <?php echo e(formatear_fecha_esp((string)$com['fecha_comentario'])); ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <form action="<?php echo e(ruta_url('kanban', 'comentar')); ?>"
                                              method="POST"
                                              enctype="multipart/form-data"
                                              class="compositor-chat-inferior">
                                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                            <input type="hidden" name="id_ticket" value="<?php echo $idTckDet; ?>">

                                            <div class="mb-2">
                                                <textarea class="form-control"
                                                          id="mensaje_kanban_com"
                                                          name="mensaje_texto"
                                                          rows="2"
                                                          placeholder="Escribe un mensaje para el solicitante o una nota técnica..."></textarea>
                                            </div>

                                            <div class="form-check mb-2">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="es_nota_interna_privada"
                                                       name="es_nota_interna_privada"
                                                       value="1">
                                                <label class="form-check-label small" for="es_nota_interna_privada">
                                                    Nota interna exclusiva para soporte
                                                </label>
                                            </div>

                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                                <div class="contenedor-archivo-estilizado">
                                                    <input type="file"
                                                           class="input-archivo-oculto"
                                                           id="adjunto_kanban_input"
                                                           name="adjunto_kanban"
                                                           accept=".png,.jpg,.jpeg,.webp,.pdf">
                                                    <label for="adjunto_kanban_input" class="boton-adjuntar-archivo">
                                                        <i class="ni ni-paperclip" aria-hidden="true"></i>
                                                        <span>Adjuntar imagen o documento</span>
                                                    </label>
                                                    <span class="etiqueta-archivo-seleccionado d-none">
                                                        <span class="nombre-archivo-truncado"></span>
                                                        <button type="button" class="btn-close boton-quitar-archivo" style="font-size: 0.55rem;" aria-label="Quitar archivo"></button>
                                                    </span>
                                                </div>

                                                <button type="submit" class="btn btn-sm btn-institucional ms-auto">
                                                    Enviar comentario
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($esJefeOSuper): ?>
    <!-- Modal para que el Jefe de Soporte registre solicitudes recibidas por llamada telefónica o presencial -->
    <div class="modal fade" id="modalRegistroManualTicket" tabindex="-1" aria-labelledby="tituloModalManual" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title h5 fw-bold mb-0" id="tituloModalManual">
                        Registrar solicitud por llamada telefónica o atención presencial
                    </h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form action="<?php echo e(ruta_url('kanban', 'registrarManual')); ?>" method="POST">
                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="canal_creacion_manual" class="form-label">Canal de recepción</label>
                                <select class="form-select" id="canal_creacion_manual" name="canal_creacion" required>
                                    <option value="Llamada_Telefonica">Llamada telefónica</option>
                                    <option value="Presencial">Atención presencial</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="id_usuario_afectado_manual" class="form-label">Funcionario que reporta la falla</label>
                                <select class="form-select"
                                        id="id_usuario_afectado_manual"
                                        name="id_usuario_afectado"
                                        data-autocompletable="true"
                                        data-placeholder="Buscar funcionario por nombre, cédula o departamento..."
                                        required>
                                    <option value="">Selecciona el funcionario</option>
                                    <?php foreach ($usuariosActivos as $usu): ?>
                                        <option value="<?php echo (int)$usu['id_usuario']; ?>"
                                                data-depto="<?php echo (int)$usu['id_departamento']; ?>"
                                                data-titulo="<?php echo e($usu['nombres'] . ' ' . $usu['apellidos'] . ' · ' . ($usu['cedula_empleado'] ?? '')); ?>"
                                                data-subtexto="<?php echo e(($usu['cargo_empleado'] ?? 'Funcionario') . ' · ' . $usu['nombre_departamento']); ?>">
                                            <?php echo e($usu['nombres'] . ' ' . $usu['apellidos']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_equipo_manual" class="form-label">Equipo informático afectado</label>
                                <select class="form-select"
                                        id="id_equipo_manual"
                                        name="id_equipo"
                                        data-autocompletable="true"
                                        data-placeholder="Buscar equipo por código de bien, marca o departamento..."
                                        required>
                                    <option value="">Selecciona el equipo</option>
                                    <?php foreach ($equiposActivos as $eqItem): ?>
                                        <option value="<?php echo (int)$eqItem['id_equipo']; ?>"
                                                data-depto="<?php echo (int)$eqItem['id_departamento']; ?>"
                                                data-titulo="<?php echo e($eqItem['codigo_bien'] . ' · ' . $eqItem['marca'] . ' ' . $eqItem['modelo']); ?>"
                                                data-subtexto="<?php echo e(texto_estado_legible((string)$eqItem['categoria_equipo']) . ' · ' . $eqItem['nombre_departamento']); ?>">
                                            <?php echo e($eqItem['codigo_bien'] . ' · ' . $eqItem['marca'] . ' ' . $eqItem['modelo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_categoria_manual" class="form-label">Categoría de la falla</label>
                                <select class="form-select"
                                        id="id_categoria_manual"
                                        name="id_categoria_falla"
                                        data-autocompletable="true"
                                        data-placeholder="Buscar tipo de falla o problema..."
                                        required>
                                    <option value="">Selecciona la categoría</option>
                                    <?php foreach ($categoriasFalla as $catItem): ?>
                                        <option value="<?php echo (int)$catItem['id_categoria_falla']; ?>"
                                                data-titulo="<?php echo e($catItem['subcategoria']); ?>"
                                                data-subtexto="<?php echo e(texto_estado_legible((string)$catItem['rama_general']) . ' · Meta: ' . (int)$catItem['tiempo_estimado_minutos'] . ' min'); ?>">
                                            <?php echo e(texto_estado_legible((string)$catItem['rama_general']) . ' · ' . $catItem['subcategoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="titulo_asunto_manual" class="form-label">Asunto corto del problema</label>
                                <input type="text"
                                       class="form-control"
                                       id="titulo_asunto_manual"
                                       name="titulo_asunto"
                                       maxlength="120"
                                       placeholder="Ejemplo: Equipo de nómina no enciende tras corte eléctrico"
                                       required>
                            </div>

                            <div class="col-12">
                                <label for="descripcion_manual" class="form-label">Descripción de la falla reportada</label>
                                <textarea class="form-control"
                                          id="descripcion_manual"
                                          name="descripcion_problematica"
                                          rows="3"
                                          placeholder="Describe el reporte recibido por teléfono o presencialmente"
                                          required></textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="prioridad_oficial_manual" class="form-label">Prioridad oficial asignada</label>
                                <select class="form-select" id="prioridad_oficial_manual" name="prioridad_oficial">
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Critica">Crítica</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="id_tecnico_manual" class="form-label">Asignar de inmediato a técnico</label>
                                <select class="form-select"
                                        id="id_tecnico_manual"
                                        name="id_tecnico_principal"
                                        data-autocompletable="true"
                                        data-placeholder="Dejar por aceptar o buscar técnico...">
                                    <option value="0" data-titulo="Dejar en columna Por aceptar" data-subtexto="Pendiente de asignación posterior">Dejar en columna Por aceptar</option>
                                    <?php foreach ($tecnicosConCarga as $tec): ?>
                                        <option value="<?php echo (int)$tec['id_usuario']; ?>"
                                                data-titulo="<?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>"
                                                data-subtexto="<?php echo e((int)$tec['casos_activos'] . ' casos activos en atención'); ?>">
                                            <?php echo e($tec['nombres'] . ' ' . $tec['apellidos']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-institucional">Guardar solicitud en el tablero</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Abrir automáticamente el modal de gestión cuando se selecciona una solicitud
    var modalCasoEl = document.getElementById('modalGestionCasoActivo');
    if (modalCasoEl && window.bootstrap && window.bootstrap.Modal) {
        var modalCaso = new window.bootstrap.Modal(modalCasoEl);
        modalCaso.show();
    }

    // Filtro instantáneo de tarjetas en las 5 columnas del tablero
    var buscador = document.getElementById('buscadorTarjetasTablero');
    if (buscador) {
        buscador.addEventListener('input', function () {
            var termino = (buscador.value || '').trim().toLowerCase();
            var tarjetas = document.querySelectorAll('[data-tarjeta-filtrable="si"]');
            tarjetas.forEach(function (tarjeta) {
                var texto = (tarjeta.textContent || '').toLowerCase();
                if (termino === '' || texto.indexOf(termino) !== -1) {
                    tarjeta.style.display = '';
                } else {
                    tarjeta.style.display = 'none';
                }
            });
        });
    }

    // Al elegir funcionario en el modal de registro manual, filtrar equipos de su departamento y refrescar combobox
    var selectUsuarioManual = document.getElementById('id_usuario_afectado_manual');
    var selectEquipoManual = document.getElementById('id_equipo_manual');
    if (selectUsuarioManual && selectEquipoManual) {
        selectUsuarioManual.addEventListener('change', function () {
            var opcionUsu = selectUsuarioManual.options[selectUsuarioManual.selectedIndex];
            var deptoUsu = opcionUsu ? opcionUsu.getAttribute('data-depto') : null;
            Array.from(selectEquipoManual.options).forEach(function (op, idx) {
                if (idx === 0) return;
                var deptoEq = op.getAttribute('data-depto');
                op.hidden = Boolean(deptoUsu && deptoEq && deptoUsu !== deptoEq);
            });
            selectEquipoManual.value = '';
            if (typeof window.refrescarBuscadorAutocompletable === 'function') {
                window.refrescarBuscadorAutocompletable(selectEquipoManual);
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
