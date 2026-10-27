<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
$esGlobal = ($slugRolActual === 'Superadmin' || $slugRolActual === 'Jefe_Soporte');
$kpis = $metricas['kpis'];

$etiquetasDepto = array_keys($metricas['por_departamento']);
$totalesDepto   = array_values(array_map(static fn(array $d): int => $d['total'], $metricas['por_departamento']));
$resueltosDepto = array_values(array_map(static fn(array $d): int => $d['resueltos'], $metricas['por_departamento']));

$etiquetasCat = array_keys($metricas['por_categoria']);
$valoresCat   = array_values($metricas['por_categoria']);

$etiquetasEtapa = array_keys($metricas['por_etapa']);
$valoresEtapa   = array_values($metricas['por_etapa']);

$etiquetasPrio = array_keys($metricas['por_prioridad']);
$valoresPrio   = array_values($metricas['por_prioridad']);
?>

<!-- Barra de filtros estadísticos y exportación auditable -->
<div class="barra-filtros-compacta mb-3">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-xl-7">
            <form action="<?php echo e(ruta_url('dashboard', 'index')); ?>" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="c" value="dashboard">
                <input type="hidden" name="a" value="index">

                <div class="col-6 col-md-3">
                    <label for="fecha_inicio_dash" class="form-label mb-1">Fecha desde</label>
                    <input type="date"
                           class="form-control form-control-sm"
                           id="fecha_inicio_dash"
                           name="fecha_inicio"
                           value="<?php echo e($fechaInicio); ?>">
                </div>

                <div class="col-6 col-md-3">
                    <label for="fecha_fin_dash" class="form-label mb-1">Fecha hasta</label>
                    <input type="date"
                           class="form-control form-control-sm"
                           id="fecha_fin_dash"
                           name="fecha_fin"
                           value="<?php echo e($fechaFin); ?>">
                </div>

                <?php if ($esGlobal): ?>
                    <div class="col-12 col-md-4">
                        <label for="depto_dash" class="form-label mb-1">Departamento</label>
                        <select class="form-select form-select-sm"
                                id="depto_dash"
                                name="depto"
                                data-autocompletable="true"
                                data-placeholder="Buscar departamento...">
                            <option value="0" data-secundario="Consolidado general institucional">Todos los departamentos</option>
                            <?php foreach ($departamentos as $depItem): ?>
                                <option value="<?php echo (int)$depItem['id_departamento']; ?>"
                                        data-secundario="Unidad administrativa"
                                        <?php echo $idDepto === (int)$depItem['id_departamento'] ? 'selected' : ''; ?>>
                                    <?php echo e($depItem['nombre_departamento']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-institucional">
                        Aplicar
                    </button>
                </div>
            </form>
        </div>

        <div class="col-12 col-xl-5 ps-xl-3">
            <form action="<?php echo e(ruta_url('dashboard', 'exportar')); ?>" method="GET" target="_blank" class="row g-2 align-items-end">
                <input type="hidden" name="c" value="dashboard">
                <input type="hidden" name="a" value="exportar">
                <input type="hidden" name="fecha_inicio" value="<?php echo e($fechaInicio); ?>">
                <input type="hidden" name="fecha_fin" value="<?php echo e($fechaFin); ?>">
                <input type="hidden" name="depto" value="<?php echo (int)$idDepto; ?>">

                <div class="col-12 col-sm-5">
                    <label for="tipo_reporte_exp" class="form-label mb-1">Tipo de reporte oficial</label>
                    <select class="form-select form-select-sm" id="tipo_reporte_exp" name="tipo_reporte">
                        <option value="Resumen_Mensual">Resumen general del periodo</option>
                        <option value="Errores_Por_Departamento">Fallas por departamento</option>
                        <option value="Rendimiento_Tecnicos">Rendimiento de técnicos</option>
                        <option value="Equipos_Con_Mas_Fallas">Equipos con más fallas</option>
                    </select>
                </div>

                <div class="col-6 col-sm-4">
                    <label for="formato_exp" class="form-label mb-1">Formato</label>
                    <select class="form-select form-select-sm" id="formato_exp" name="formato">
                        <option value="PDF">Documento imprimible PDF</option>
                        <option value="Excel_XLSX">Hoja de cálculo Excel</option>
                        <option value="CSV">Archivo de datos CSV</option>
                    </select>
                </div>

                <div class="col-6 col-sm-3 d-grid">
                    <button type="submit" class="btn btn-sm btn-outline-institucional">
                        Exportar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tarjetas de indicadores clave alimentadas por vw_metricas_dashboard -->
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-semibold">Solicitudes en el periodo</div>
                <div class="h3 fw-bold my-1" style="color: var(--color-primario);">
                    <?php echo (int)$kpis['total_casos']; ?>
                </div>
                <div class="small texto-secundario">
                    <?php echo $esGlobal ? 'Total institucional registrado' : 'Asignadas a tu gestión técnica'; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-semibold">Solicitudes resueltas</div>
                <div class="h3 fw-bold my-1">
                    <?php echo (int)$kpis['total_resueltos']; ?>
                </div>
                <div class="small texto-secundario">
                    <?php echo (int)($kpis['total_en_atencion'] + $kpis['total_en_espera']); ?> en atención o espera
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-semibold">Tiempo promedio de resolución</div>
                <div class="h3 fw-bold my-1">
                    <?php echo (int)$kpis['promedio_resolucion']; ?> min
                </div>
                <div class="small texto-secundario">
                    Respuesta inicial promedio: <?php echo (int)$kpis['promedio_respuesta']; ?> min
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-semibold">Cumplimiento de tiempo meta</div>
                <div class="d-flex align-items-center gap-2 my-1">
                    <span class="h3 fw-bold mb-0"><?php echo (int)$kpis['porcentaje_meta_sla']; ?>%</span>
                    <span class="insignia-suave <?php echo (int)$kpis['porcentaje_meta_sla'] >= 80 ? 'insignia-suave-baja' : 'insignia-suave-alta'; ?>">
                        <?php echo (int)$kpis['porcentaje_meta_sla'] >= 80 ? 'Óptimo' : 'En seguimiento'; ?>
                    </span>
                </div>
                <div class="small texto-secundario">Resueltas dentro del tiempo estimado</div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos estadísticos con Chart.js -->
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                Solicitudes recibidas y resueltas por departamento
            </div>
            <div class="card-body">
                <div style="height: 270px;">
                    <canvas id="graficoDepartamentos"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                Distribución por categoría de falla técnica
            </div>
            <div class="card-body">
                <div style="height: 270px;">
                    <canvas id="graficoCategorias"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                Solicitudes según etapa actual en el tablero
            </div>
            <div class="card-body">
                <div style="height: 240px;">
                    <canvas id="graficoEtapas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                Solicitudes según nivel de prioridad oficial
            </div>
            <div class="card-body">
                <div style="height: 240px;">
                    <canvas id="graficoPrioridades"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tablas de rendimiento por técnico y reincidencia por equipo -->
<div class="row g-3 mb-3 fila-inicio-alineada">
    <div class="col-12 col-xl-6">
        <div class="card">
            <div class="card-header">
                Rendimiento y tiempos por técnico de soporte
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaRendimientoTecnicos" data-paginacion="true">
                        <thead>
                            <tr>
                                <th>Técnico resolutor</th>
                                <th class="text-center">Asignados</th>
                                <th class="text-center">Resueltos</th>
                                <th class="text-center">Promedio resolución</th>
                                <th class="text-center">En tiempo meta</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($metricas['por_tecnico'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 texto-secundario">
                                        Sin datos de asignaciones técnicas en este periodo.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($metricas['por_tecnico'] as $tMet): ?>
                                    <?php
                                    $promTec = $tMet['conteo_resolucion'] > 0
                                        ? (int)round($tMet['suma_resolucion'] / $tMet['conteo_resolucion'])
                                        : 0;
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?php echo e($tMet['nombre_tecnico']); ?></td>
                                        <td class="text-center"><?php echo (int)$tMet['total_asignados']; ?></td>
                                        <td class="text-center fw-bold"><?php echo (int)$tMet['total_resueltos']; ?></td>
                                        <td class="text-center small"><?php echo $promTec > 0 ? $promTec . ' min' : 'En curso'; ?></td>
                                        <td class="text-center">
                                            <span class="insignia-suave insignia-suave-baja"><?php echo (int)$tMet['cumplio_meta']; ?> casos</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card">
            <div class="card-header">
                Equipos informáticos con mayor incidencia de fallas
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaEquiposIncidencia" data-paginacion="true">
                        <thead>
                            <tr>
                                <th>Código de bien</th>
                                <th>Categoría</th>
                                <th>Departamento</th>
                                <th class="text-center">Fallas</th>
                                <th>Ficha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($metricas['por_equipo'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 texto-secundario">
                                        Sin equipos reportados en este rango de fechas.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($metricas['por_equipo'] as $eqMet): ?>
                                    <tr>
                                        <td class="fw-bold codigo-monoespaciado"><?php echo e($eqMet['codigo_bien_equipo']); ?></td>
                                        <td class="small"><?php echo e($eqMet['categoria_equipo']); ?></td>
                                        <td class="small"><?php echo e($eqMet['nombre_departamento']); ?></td>
                                        <td class="text-center">
                                            <span class="insignia-suave insignia-suave-critica"><?php echo (int)$eqMet['total_fallas']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($esGlobal): ?>
                                                <a href="<?php echo e(ruta_url('inventario', 'index', ['ver_equipo' => (int)$eqMet['id_equipo']])); ?>"
                                                   class="btn btn-sm btn-outline-secondary">
                                                    Ver equipo
                                                </a>
                                            <?php else: ?>
                                                <span class="small texto-secundario"><?php echo (int)$eqMet['resueltas']; ?> resueltas</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla consolidada de vw_metricas_dashboard con buscador instantáneo -->
<div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-bold">Detalle consolidado de métricas y tiempos de atención</span>
        <div style="min-width: 260px;">
            <input type="search"
                   class="form-control form-control-sm"
                   data-buscador-tabla="#tablaDetalleMetricas"
                   placeholder="Buscar por código, departamento, bien o técnico">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tablaDetalleMetricas" data-paginacion="true">
                <thead>
                    <tr>
                        <th>Solicitud y fecha</th>
                        <th>Departamento</th>
                        <th>Bien y categoría de falla</th>
                        <th>Etapa y prioridad</th>
                        <th>Técnico asignado</th>
                        <th>Tiempos en minutos</th>
                        <th>Cumplió meta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($metricas['registros'])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 texto-secundario">
                                No encontramos solicitudes en el periodo consultado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($metricas['registros'] as $filaM): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold codigo-monoespaciado"><?php echo e($filaM['codigo_ticket']); ?></div>
                                    <div class="small texto-secundario"><?php echo e(formatear_fecha_esp((string)$filaM['dia_fecha'], false)); ?></div>
                                </td>
                                <td class="small fw-semibold"><?php echo e($filaM['nombre_departamento']); ?></td>
                                <td>
                                    <div class="fw-semibold small"><?php echo e($filaM['codigo_bien_equipo'] . ' — ' . texto_estado_legible((string)$filaM['categoria_falla'])); ?></div>
                                    <div class="small texto-secundario"><?php echo e($filaM['subcategoria_falla']); ?></div>
                                </td>
                                <td>
                                    <span class="<?php echo e(clase_insignia_prioridad((string)$filaM['prioridad_oficial'])); ?>">
                                        <?php echo e(texto_estado_legible((string)$filaM['prioridad_oficial'])); ?>
                                    </span>
                                    <div class="small texto-secundario mt-1"><?php echo e(texto_estado_legible((string)$filaM['estado_columna_kanban'])); ?></div>
                                </td>
                                <td class="small fw-semibold"><?php echo e($filaM['nombre_tecnico'] ?: 'Sin asignar'); ?></td>
                                <td class="small">
                                    <div>Respuesta: <strong><?php echo (int)$filaM['tiempo_respuesta_minutos']; ?> min</strong></div>
                                    <div>
                                        Resolución:
                                        <strong>
                                            <?php echo $filaM['tiempo_resolucion_minutos'] !== null ? ((int)$filaM['tiempo_resolucion_minutos'] . ' min') : 'En atención'; ?>
                                        </strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="insignia-suave <?php echo $filaM['cumplio_expectativa_tiempo'] === 'Si' ? 'insignia-suave-baja' : 'insignia-suave-neutra'; ?>">
                                        <?php echo $filaM['cumplio_expectativa_tiempo'] === 'Si' ? 'Sí cumplió' : 'En curso o fuera de meta'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Historial reciente de reportes exportados -->
<?php if (!empty($historialReportes)): ?>
    <div class="card">
        <div class="card-header">
            Historial de reportes estadísticos generados
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaHistorialReportes" data-paginacion="true">
                    <thead>
                        <tr>
                            <th>Fecha de generación</th>
                            <th>Solicitante</th>
                            <th>Tipo de reporte</th>
                            <th>Rango consultado</th>
                            <th>Departamento</th>
                            <th>Formato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historialReportes as $rep): ?>
                            <tr>
                                <td class="small"><?php echo e(formatear_fecha_esp((string)$rep['fecha_generacion'])); ?></td>
                                <td class="fw-semibold small"><?php echo e($rep['nombre_solicitante']); ?></td>
                                <td class="small"><?php echo e(texto_estado_legible((string)$rep['tipo_reporte'])); ?></td>
                                <td class="small texto-secundario">
                                    <?php echo e($rep['fecha_inicio_filtro'] . ' al ' . $rep['fecha_fin_filtro']); ?>
                                </td>
                                <td class="small"><?php echo e($rep['nombre_departamento_filtro'] ?: 'Todos los departamentos'); ?></td>
                                <td><span class="insignia-suave insignia-suave-neutra"><?php echo e(texto_estado_legible((string)$rep['formato_exportacion'])); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="<?php echo e(recurso_publico('assets/js/chart.umd.min.js')); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Chart === 'undefined') {
        return;
    }

    var etiquetasDepto = <?php echo json_encode($etiquetasDepto, JSON_UNESCAPED_UNICODE); ?>;
    var totalesDepto   = <?php echo json_encode($totalesDepto); ?>;
    var resueltosDepto = <?php echo json_encode($resueltosDepto); ?>;

    var etiquetasCat   = <?php echo json_encode($etiquetasCat, JSON_UNESCAPED_UNICODE); ?>;
    var valoresCat     = <?php echo json_encode($valoresCat); ?>;

    var etiquetasEtapa = <?php echo json_encode($etiquetasEtapa, JSON_UNESCAPED_UNICODE); ?>;
    var valoresEtapa   = <?php echo json_encode($valoresEtapa); ?>;

    var etiquetasPrio  = <?php echo json_encode($etiquetasPrio, JSON_UNESCAPED_UNICODE); ?>;
    var valoresPrio    = <?php echo json_encode($valoresPrio); ?>;

    var instanciasGraficos = [];

    function obtenerColoresTema() {
        var esOscuro = document.documentElement.getAttribute('data-tema') === 'oscuro';
        return {
            texto: esOscuro ? '#94A3B8' : '#475569',
            rejilla: esOscuro ? '#334155' : '#E2E8F0'
        };
    }

    function aplicarTemaGraficos() {
        var c = obtenerColoresTema();
        window.Chart.defaults.color = c.texto;
        window.Chart.defaults.borderColor = c.rejilla;
        instanciasGraficos.forEach(function (g) {
            g.update();
        });
    }

    var cInicial = obtenerColoresTema();
    window.Chart.defaults.color = cInicial.texto;
    window.Chart.defaults.borderColor = cInicial.rejilla;

    var ctxDepto = document.getElementById('graficoDepartamentos');
    if (ctxDepto) {
        instanciasGraficos.push(new Chart(ctxDepto, {
            type: 'bar',
            data: {
                labels: etiquetasDepto.length ? etiquetasDepto : ['Sin registros'],
                datasets: [
                    {
                        label: 'Solicitudes registradas',
                        data: totalesDepto.length ? totalesDepto : [0],
                        backgroundColor: '#B91C1C',
                        borderRadius: 4
                    },
                    {
                        label: 'Solicitudes resueltas',
                        data: resueltosDepto.length ? resueltosDepto : [0],
                        backgroundColor: '#334155',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        }));
    }

    var ctxCat = document.getElementById('graficoCategorias');
    if (ctxCat) {
        instanciasGraficos.push(new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: etiquetasCat.length ? etiquetasCat : ['Sin registros'],
                datasets: [{
                    data: valoresCat.length ? valoresCat : [1],
                    backgroundColor: ['#B91C1C', '#334155', '#0284C7', '#15803D', '#64748B']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        }));
    }

    var ctxEtapa = document.getElementById('graficoEtapas');
    if (ctxEtapa) {
        instanciasGraficos.push(new Chart(ctxEtapa, {
            type: 'bar',
            data: {
                labels: etiquetasEtapa,
                datasets: [{
                    label: 'Cantidad de solicitudes',
                    data: valoresEtapa,
                    backgroundColor: ['#64748B', '#0284C7', '#334155', '#B45309', '#15803D'],
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        }));
    }

    var ctxPrio = document.getElementById('graficoPrioridades');
    if (ctxPrio) {
        instanciasGraficos.push(new Chart(ctxPrio, {
            type: 'bar',
            data: {
                labels: etiquetasPrio,
                datasets: [{
                    label: 'Solicitudes por prioridad',
                    data: valoresPrio,
                    backgroundColor: ['#B91C1C', '#B45309', '#0284C7', '#15803D'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        }));
    }

    document.addEventListener('cambioTemaInstitucional', aplicarTemaGraficos);
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
