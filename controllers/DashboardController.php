<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Departamento.php';
require_once __DIR__ . '/../models/Auditoria.php';

/**
 * Controlador del Módulo 6: Panel de Estadísticas y Reportes Institucionales
 * Alimentado por la vista SQL vw_metricas_dashboard con filtros por fecha y departamento,
 * gráficos en Chart.js y registro de exportaciones en historial_reportes_generados.
 */
class DashboardController
{
    private Ticket $ticketModel;
    private Departamento $departamentoModel;
    private Auditoria $auditoriaModel;

    public function __construct()
    {
        if (!sesion_activa()) {
            establecer_alerta('advertencia', 'Inicia sesión para consultar las estadísticas del sistema.');
            redirigir('auth', 'login');
        }

        if (!tiene_permiso_modulo('Mod6_Dashboard', 'permiso_ver')) {
            establecer_alerta('error', 'Tu rol no tiene acceso al módulo de estadísticas.');
            redirigir('auth', 'perfil');
        }

        $this->ticketModel       = new Ticket();
        $this->departamentoModel = new Departamento();
        $this->auditoriaModel    = new Auditoria();
    }

    /**
     * Muestra el panel de estadísticas con indicadores, gráficos Chart.js y tablas analíticas
     */
    public function index(): void
    {
        $idUsuarioActual = (int)$_SESSION['id_usuario'];
        $slugRolActual   = (string)$_SESSION['slug_rol'];

        $fechaInicioPorDefecto = date('Y-m-01', strtotime('-1 month'));
        $fechaFinPorDefecto    = date('Y-m-d');

        $fechaInicio = !empty($_GET['fecha_inicio']) ? limpiar_texto($_GET['fecha_inicio']) : $fechaInicioPorDefecto;
        $fechaFin    = !empty($_GET['fecha_fin']) ? limpiar_texto($_GET['fecha_fin']) : $fechaFinPorDefecto;
        $idDepto     = isset($_GET['depto']) ? (int)$_GET['depto'] : 0;
        $idTecnico   = isset($_GET['tecnico']) ? (int)$_GET['tecnico'] : 0;

        $filtros = [
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => $fechaFin,
            'id_departamento' => $idDepto,
            'id_tecnico'      => $idTecnico
        ];

        $metricas          = $this->ticketModel->obtenerMetricasDashboard($filtros, $slugRolActual, $idUsuarioActual);
        $departamentos     = $this->departamentoModel->listarTodos(true);
        $tecnicos          = $this->ticketModel->listarTecnicosConCarga();
        $historialReportes = $this->ticketModel->listarHistorialReportes(15);

        $tituloPagina = 'Panel de estadísticas';
        require __DIR__ . '/../views/dashboard/index.php';
    }

    /**
     * Genera y exporta reportes estadísticos registrando el evento en historial_reportes_generados
     */
    public function exportar(): void
    {
        $idUsuarioActual = (int)$_SESSION['id_usuario'];
        $slugRolActual   = (string)$_SESSION['slug_rol'];

        $tipoReporte = limpiar_texto($_GET['tipo_reporte'] ?? 'Resumen_Mensual');
        $tiposValidos = ['Errores_Por_Departamento', 'Rendimiento_Tecnicos', 'Equipos_Con_Mas_Fallas', 'Resumen_Mensual'];
        if (!in_array($tipoReporte, $tiposValidos, true)) {
            $tipoReporte = 'Resumen_Mensual';
        }

        $formato = limpiar_texto($_GET['formato'] ?? 'CSV');
        if (!in_array($formato, ['PDF', 'Excel_XLSX', 'CSV'], true)) {
            $formato = 'CSV';
        }

        $fechaInicio = !empty($_GET['fecha_inicio']) ? limpiar_texto($_GET['fecha_inicio']) : date('Y-m-01', strtotime('-1 month'));
        $fechaFin    = !empty($_GET['fecha_fin']) ? limpiar_texto($_GET['fecha_fin']) : date('Y-m-d');
        $idDepto     = !empty($_GET['depto']) ? (int)$_GET['depto'] : null;

        $metricas = $this->ticketModel->obtenerMetricasDashboard([
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => $fechaFin,
            'id_departamento' => $idDepto ?? 0
        ], $slugRolActual, $idUsuarioActual);

        // Registrar la generación del reporte en historial_reportes_generados
        $this->auditoriaModel->registrarReporteGenerado(
            $idUsuarioActual,
            $tipoReporte,
            $fechaInicio,
            $fechaFin,
            $idDepto,
            $formato
        );

        $this->auditoriaModel->registrarAcceso(
            $idUsuarioActual,
            (string)$_SESSION['username'],
            'Navegacion_Modulo',
            "Exportó el reporte {$tipoReporte} en formato {$formato}",
            6
        );

        $registros = $metricas['registros'];

        if ($formato === 'CSV') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="reporte_soporte_' . strtolower($tipoReporte) . '_' . date('Ymd_His') . '.csv"');
            echo "\xEF\xBB\xBF";
            $salida = fopen('php://output', 'wb');
            fputcsv($salida, [
                'Código',
                'Fecha',
                'Departamento',
                'Bien informático',
                'Categoría de equipo',
                'Rama de falla',
                'Subcategoría',
                'Etapa',
                'Prioridad',
                'Técnico asignado',
                'Minutos de respuesta',
                'Minutos de resolución',
                'Cumplió tiempo esperado'
            ], ';');

            foreach ($registros as $r) {
                fputcsv($salida, [
                    $r['codigo_ticket'],
                    $r['dia_fecha'],
                    $r['nombre_departamento'],
                    $r['codigo_bien_equipo'],
                    texto_estado_legible((string)$r['categoria_equipo']),
                    texto_estado_legible((string)$r['categoria_falla']),
                    $r['subcategoria_falla'],
                    texto_estado_legible((string)$r['estado_columna_kanban']),
                    texto_estado_legible((string)$r['prioridad_oficial']),
                    $r['nombre_tecnico'] ?: 'Sin asignar',
                    $r['tiempo_respuesta_minutos'] ?? 0,
                    $r['tiempo_resolucion_minutos'] ?? 'En curso',
                    $r['cumplio_expectativa_tiempo']
                ], ';');
            }
            fclose($salida);
            exit;
        }

        if ($formato === 'Excel_XLSX') {
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="reporte_soporte_' . strtolower($tipoReporte) . '_' . date('Ymd_His') . '.xls"');
            echo "\xEF\xBB\xBF";
            echo '<table border="1">';
            echo '<tr><th colspan="10" style="background:#C62828;color:#FFFFFF;">' . e(APP_NOMBRE) . ' — Reporte ' . e(texto_estado_legible($tipoReporte)) . '</th></tr>';
            echo '<tr><th>Código</th><th>Fecha</th><th>Departamento</th><th>Bien</th><th>Categoría Falla</th><th>Subcategoría</th><th>Etapa</th><th>Prioridad</th><th>Técnico</th><th>Cumplió Meta</th></tr>';
            foreach ($registros as $r) {
                echo '<tr>';
                echo '<td>' . e($r['codigo_ticket']) . '</td>';
                echo '<td>' . e($r['dia_fecha']) . '</td>';
                echo '<td>' . e($r['nombre_departamento']) . '</td>';
                echo '<td>' . e($r['codigo_bien_equipo']) . '</td>';
                echo '<td>' . e(texto_estado_legible((string)$r['categoria_falla'])) . '</td>';
                echo '<td>' . e($r['subcategoria_falla']) . '</td>';
                echo '<td>' . e(texto_estado_legible((string)$r['estado_columna_kanban'])) . '</td>';
                echo '<td>' . e(texto_estado_legible((string)$r['prioridad_oficial'])) . '</td>';
                echo '<td>' . e($r['nombre_tecnico'] ?: 'Sin asignar') . '</td>';
                echo '<td>' . e($r['cumplio_expectativa_tiempo']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            exit;
        }

        // Vista institucional imprimible para guardado directo en PDF
        require __DIR__ . '/../views/dashboard/reporte_imprimible.php';
        exit;
    }
}
