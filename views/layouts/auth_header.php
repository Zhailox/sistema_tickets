<?php
declare(strict_types=1);

$tituloDocumento = isset($tituloPagina) ? $tituloPagina . ' — ' . APP_NOMBRE : APP_NOMBRE;
$alertaFlash = consumir_alerta();
?>
<!DOCTYPE html>
<html lang="es" data-tema="claro" data-letra="normal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($tituloDocumento); ?></title>
    <script>
        (function () {
            try {
                var tema = localStorage.getItem('tema_institucional') || 'claro';
                var letra = localStorage.getItem('tamano_letra_institucional') || 'normal';
                document.documentElement.setAttribute('data-tema', tema === 'oscuro' ? 'oscuro' : 'claro');
                document.documentElement.setAttribute('data-letra', letra === 'grande' ? 'grande' : 'normal');
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="<?php echo e(recurso_publico('assets/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(recurso_publico('assets/css/argon-institucional.css')); ?>">
</head>
<body class="pagina-acceso">
<header class="encabezado-acceso">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <span class="marca-icono-escudo" aria-hidden="true">ST</span>
                <div>
                    <h1 class="h5 fw-bold mb-0"><?php echo e(APP_NOMBRE); ?></h1>
                    <p class="small texto-secundario mb-0"><?php echo e(APP_INSTITUCION); ?></p>
                </div>
            </div>
            <span class="insignia-suave insignia-suave-neutra">Red interna institucional</span>
        </div>
    </div>
</header>

<main class="container contenedor-tarjeta-acceso">
    <?php if ($alertaFlash !== null): ?>
        <?php
        $claseAlerta = match ($alertaFlash['tipo']) {
            'exito'       => 'alert-success',
            'error'       => 'alert-danger',
            'advertencia' => 'alert-warning',
            default       => 'alert-info',
        };
        ?>
        <div class="row justify-content-center mb-3">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="alert <?php echo $claseAlerta; ?> alert-dismissible fade show shadow-sm mb-0" role="alert">
                    <div class="fw-semibold"><?php echo e($alertaFlash['mensaje']); ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
