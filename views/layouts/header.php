<?php
declare(strict_types=1);

$tituloDocumento = isset($tituloPagina) ? $tituloPagina . ' — ' . APP_NOMBRE : APP_NOMBRE;
$alertaFlash = consumir_alerta();
?>
<!DOCTYPE html>
<html lang="es" data-tema="claro" data-letra="normal" data-sidebar="expandido">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($tituloDocumento); ?></title>
    <script>
        (function () {
            try {
                var tema = localStorage.getItem('tema_institucional') || 'claro';
                var letra = localStorage.getItem('tamano_letra_institucional') || 'normal';
                var sidebar = localStorage.getItem('sidebar_institucional') || 'expandido';
                document.documentElement.setAttribute('data-tema', tema === 'oscuro' ? 'oscuro' : 'claro');
                document.documentElement.setAttribute('data-letra', letra === 'grande' ? 'grande' : 'normal');
                document.documentElement.setAttribute('data-sidebar', sidebar === 'colapsado' ? 'colapsado' : 'expandido');
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="<?php echo e(recurso_publico('assets/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(recurso_publico('assets/css/argon-institucional.css')); ?>">
</head>
<body>
<div class="contenedor-aplicacion">
    <div class="capa-fondo-menu-movil" id="capaFondoMenuMovil" aria-hidden="true"></div>
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="contenedor-principal">
        <?php require __DIR__ . '/topbar.php'; ?>

        <main class="area-contenido-vista">
            <?php if ($alertaFlash !== null): ?>
                <?php
                $claseAlerta = match ($alertaFlash['tipo']) {
                    'exito'       => 'alert-success',
                    'error'       => 'alert-danger',
                    'advertencia' => 'alert-warning',
                    default       => 'alert-info',
                };
                ?>
                <div class="alert <?php echo $claseAlerta; ?> alert-dismissible fade show shadow-sm mb-3" role="alert">
                    <div class="fw-semibold"><?php echo e($alertaFlash['mensaje']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                </div>
            <?php endif; ?>
