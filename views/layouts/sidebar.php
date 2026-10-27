<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/Usuario.php';

$idUsuarioActual = (int)($_SESSION['id_usuario'] ?? 0);
$idRolActual     = (int)($_SESSION['id_rol'] ?? 0);
$slugRolActual   = (string)($_SESSION['slug_rol'] ?? '');
$controladorAct  = strtolower((string)($_GET['c'] ?? 'auth'));

$modeloMenuUsuario = new Usuario();
$estructuraRol = $modeloMenuUsuario->obtenerPermisosYMenuPorRol($idRolActual);
$_SESSION['permisos_modulos'] = $estructuraRol['permisos'];
$itemsMenu = $estructuraRol['menu'];
$contadoresMenu = $modeloMenuUsuario->obtenerContadoresMenuLateral($idUsuarioActual, $slugRolActual);
?>
<aside class="menu-lateral-argon" id="menuLateralInstitucional" aria-label="Menú principal del sistema">
    <div class="marca-institucional">
        <span class="marca-icono-escudo" aria-hidden="true">ST</span>
        <div class="overflow-hidden flex-grow-1 min-w-0">
            <h1 class="titulo-sistema text-truncate"><?php echo e(APP_NOMBRE); ?></h1>
            <span class="subtitulo-sistema text-truncate"><?php echo e(APP_INSTITUCION); ?></span>
        </div>
        <button type="button"
                class="btn btn-sm btn-barra-superior d-lg-none flex-shrink-0"
                id="botonCerrarMenuMovil"
                title="Cerrar menú"
                aria-label="Cerrar menú lateral">
            ×
        </button>
    </div>

    <div class="resumen-usuario-lateral">
        <div class="fw-semibold text-truncate"><?php echo e(($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? '')); ?></div>
        <div class="small texto-secundario text-truncate"><?php echo e($_SESSION['nombre_rol'] ?? ''); ?></div>
    </div>

    <ul class="lista-navegacion-lateral">
        <?php foreach ($itemsMenu as $item): ?>
            <?php
            $claveMod = (string)$item['clave_modulo'];
            $esActivo = ($controladorAct === strtolower((string)$item['controlador']));
            $cantidadPendiente = (int)($contadoresMenu[$claveMod] ?? 0);
            ?>
            <li class="item-navegacion">
                <a href="<?php echo e(ruta_url((string)$item['controlador'], (string)$item['accion'])); ?>"
                   class="enlace-navegacion <?php echo $esActivo ? 'activo' : ''; ?>">
                    <span class="grupo-icono-texto text-truncate">
                        <i class="<?php echo e((string)$item['icono_argon']); ?>" aria-hidden="true"></i>
                        <span class="text-truncate"><?php echo e((string)$item['nombre_modulo']); ?></span>
                    </span>
                    <?php if ($cantidadPendiente > 0): ?>
                        <span class="insignia-contador-menu" title="Pendientes de atención">
                            <?php echo $cantidadPendiente; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="pie-menu-lateral">
        <a href="<?php echo e(ruta_url('auth', 'logout')); ?>"
           class="btn btn-outline-danger btn-sm w-100"
           data-confirmar-mensaje="¿Deseas cerrar tu sesión actual en el sistema?">
            Cerrar sesión
        </a>
    </div>
</aside>
