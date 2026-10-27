<?php
declare(strict_types=1);

$tituloSeccion = $tituloPagina ?? 'Panel institucional';
$rutaFoto = !empty($_SESSION['foto_perfil_ruta']) ? (string)$_SESSION['foto_perfil_ruta'] : 'assets/img/default-avatar.png';
?>
<header class="barra-superior-argon">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
            <button type="button"
                    class="btn btn-barra-superior flex-shrink-0"
                    id="botonAlternarMenuLateral"
                    title="Mostrar u ocultar menú lateral"
                    aria-label="Alternar menú de navegación">
                <i class="ni ni-menu" aria-hidden="true"></i>
            </button>
            <h2 class="h5 mb-0 fw-semibold text-truncate titulo-modulo-topbar" title="<?php echo e($tituloSeccion); ?>">
                <?php echo e($tituloSeccion); ?>
            </h2>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <?php if (!empty($_SESSION['nombre_departamento'])): ?>
                <span class="insignia-suave insignia-suave-neutra d-none d-md-inline-flex text-truncate" style="max-width: 240px;">
                    <?php echo e((string)$_SESSION['nombre_departamento']); ?>
                </span>
            <?php endif; ?>

            <a href="<?php echo e(ruta_url('auth', 'perfil')); ?>"
               class="btn btn-barra-superior d-flex align-items-center gap-2 text-decoration-none usuario-pill-topbar"
               title="Ir a mi cuenta y preferencias">
                <img src="<?php echo e(recurso_publico($rutaFoto)); ?>"
                     alt="Foto de cuenta"
                     class="avatar-perfil-barra flex-shrink-0">
                <span class="d-none d-sm-inline text-truncate nombre-usuario-topbar">
                    <?php echo e(($_SESSION['nombres'] ?? 'Mi cuenta') . ' ' . ($_SESSION['apellidos'] ?? '')); ?>
                </span>
            </a>
        </div>
    </div>
</header>
