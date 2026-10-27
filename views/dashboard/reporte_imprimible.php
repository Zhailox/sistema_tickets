<?php
declare(strict_types=1);

$kpis = $metricas['kpis'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Estadístico — <?php echo e(APP_NOMBRE); ?></title>
    <link rel="stylesheet" href="<?php echo e(recurso_publico('assets/css/bootstrap.min.css')); ?>">
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            color: #212529;
            background: #FFFFFF;
            padding: 2rem;
        }
        .encabezado-reporte {
            border-bottom: 3px solid #C62828;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        @media print {
            .ocultar-impresion {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 ocultar-impresion">
        <button type="button" class="btn btn-danger" onclick="window.print();">
            Imprimir o guardar como PDF
        </button>
        <a href="<?php echo e(ruta_url('dashboard', 'index')); ?>" class="btn btn-outline-secondary">
            Volver al panel de estadísticas
        </a>
    </div>

    <div class="encabezado-reporte d-flex justify-content-between align-items-start">
        <div>
            <h1 class="h4 fw-bold mb-1"><?php echo e(APP_NOMBRE); ?></h1>
            <div class="fw-semibold"><?php echo e(APP_INSTITUCION); ?></div>
            <div class="small text-muted">
                Reporte oficial: <?php echo e(texto_estado_legible($tipoReporte)); ?>
            </div>
        </div>
        <div class="text-end small">
            <div><strong>Fecha de emisión:</strong> <?php echo e(formatear_fecha_esp(date('Y-m-d H:i:s'))); ?></div>
            <div><strong>Emitido por:</strong> <?php echo e(($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? '')); ?></div>
            <div><strong>Periodo consultado:</strong> <?php echo e($fechaInicio . ' al ' . $fechaFin); ?></div>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-3">
            <div class="p-3 border rounded">
                <div class="small text-muted">Total solicitudes</div>
                <div class="h4 fw-bold mb-0"><?php echo (int)$kpis['total_casos']; ?></div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-3 border rounded">
                <div class="small text-muted">Resueltas</div>
                <div class="h4 fw-bold mb-0"><?php echo (int)$kpis['total_resueltos']; ?></div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-3 border rounded">
                <div class="small text-muted">Promedio resolución</div>
                <div class="h4 fw-bold mb-0"><?php echo (int)$kpis['promedio_resolucion']; ?> min</div>
            </div>
        </div>
        <div class="col-3">
            <div class="p-3 border rounded">
                <div class="small text-muted">Cumplimiento de meta</div>
                <div class="h4 fw-bold mb-0"><?php echo (int)$kpis['porcentaje_meta_sla']; ?>%</div>
            </div>
        </div>
    </div>

    <h2 class="h6 fw-bold mb-2">Detalle de solicitudes registradas en el periodo</h2>
    <table class="table table-bordered table-sm align-middle">
        <thead>
            <tr>
                <th>Código</th>
                <th>Fecha</th>
                <th>Departamento</th>
                <th>Equipo</th>
                <th>Falla</th>
                <th>Etapa</th>
                <th>Prioridad</th>
                <th>Técnico</th>
                <th>Resolución</th>
                <th>Meta</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($metricas['registros'])): ?>
                <tr>
                    <td colspan="10" class="text-center py-3">No existen solicitudes en este rango de fechas.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($metricas['registros'] as $r): ?>
                    <tr>
                        <td class="fw-bold"><?php echo e($r['codigo_ticket']); ?></td>
                        <td><?php echo e((string)$r['dia_fecha']); ?></td>
                        <td><?php echo e($r['nombre_departamento']); ?></td>
                        <td><?php echo e($r['codigo_bien_equipo']); ?></td>
                        <td><?php echo e($r['subcategoria_falla']); ?></td>
                        <td><?php echo e(texto_estado_legible((string)$r['estado_columna_kanban'])); ?></td>
                        <td><?php echo e(texto_estado_legible((string)$r['prioridad_oficial'])); ?></td>
                        <td><?php echo e($r['nombre_tecnico'] ?: 'Sin asignar'); ?></td>
                        <td><?php echo $r['tiempo_resolucion_minutos'] !== null ? ((int)$r['tiempo_resolucion_minutos'] . ' min') : 'En curso'; ?></td>
                        <td><?php echo $r['cumplio_expectativa_tiempo'] === 'Si' ? 'Sí' : 'No'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
