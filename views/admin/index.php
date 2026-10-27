<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
$tokenCsrf = obtener_token_csrf();

$totalActivos    = 0;
$totalEliminados = 0;
$totalBloqueados = 0;
$ahoraTs         = time();

foreach ($usuarios as $uItem) {
    if (!empty($uItem['eliminado_en'])) {
        $totalEliminados++;
    } elseif ($uItem['estado_cuenta'] === 'Activo') {
        $totalActivos++;
    }
    if (!empty($uItem['bloqueado_hasta']) && strtotime((string)$uItem['bloqueado_hasta']) > $ahoraTs) {
        $totalBloqueados++;
    }
}

$totalRecupPendientes = 0;
foreach ($solicitudesRecuperacion as $sRec) {
    if ($sRec['estado_solicitud'] === 'Pendiente') {
        $totalRecupPendientes++;
    }
}
?>

<!-- Tarjetas superiores de estado de seguridad -->
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Funcionarios activos</div>
                <div class="h3 fw-bold mt-1 mb-0"><?php echo $totalActivos; ?></div>
                <div class="small texto-secundario"><?php echo count($usuarios); ?> cuentas registradas en total</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Cuentas bloqueadas o de baja</div>
                <div class="h3 fw-bold mt-1 mb-0" style="color: var(--color-primario);"><?php echo $totalBloqueados + $totalEliminados; ?></div>
                <div class="small texto-secundario">
                    <?php echo $totalBloqueados; ?> bloqueos temporales · <?php echo $totalEliminados; ?> bajas lógicas
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Recuperaciones pendientes</div>
                <div class="h3 fw-bold mt-1 mb-0" style="color: var(--color-primario);"><?php echo $totalRecupPendientes; ?></div>
                <div class="small texto-secundario">Solicitudes de clave en intranet</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Eventos auditados en vista</div>
                <div class="h3 fw-bold mt-1 mb-0"><?php echo count($registrosAcceso) + count($historialCambios); ?></div>
                <div class="small texto-secundario">Accesos y cambios registrados</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header pb-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'usuarios' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-admin-usuarios"
                        type="button"
                        role="tab">
                    Gestión de usuarios y cuentas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'permisos' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-admin-permisos"
                        type="button"
                        role="tab">
                    Roles y matriz de permisos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'accesos' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-admin-accesos"
                        type="button"
                        role="tab">
                    Registros de acceso
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'cambios' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-admin-cambios"
                        type="button"
                        role="tab">
                    Historial de cambios
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'servidor' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-admin-servidor"
                        type="button"
                        role="tab">
                    Servidor, respaldos y mantenimiento
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">
            <!-- Pestaña 1: Gestión de Usuarios y Solicitudes de Recuperación -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'usuarios' ? 'show active' : ''; ?>"
                 id="tab-admin-usuarios"
                 role="tabpanel">
                <div class="row g-3 fila-inicio-alineada">
                    <div class="col-12 col-xl-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>
                                    <?php echo $usuarioEdicion ? 'Editar cuenta ' . e($usuarioEdicion['username']) : 'Registrar nuevo funcionario'; ?>
                                </span>
                                <?php if ($usuarioEdicion): ?>
                                    <a href="<?php echo e(ruta_url('admin', 'index')); ?>" class="btn btn-sm btn-secondary">
                                        Nuevo
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <form action="<?php echo e(ruta_url('admin', 'guardarUsuario')); ?>" method="POST">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                    <input type="hidden" name="id_usuario" value="<?php echo (int)($usuarioEdicion['id_usuario'] ?? 0); ?>">

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label for="cedula_usu_adm" class="form-label">Cédula de identidad</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="cedula_usu_adm"
                                                   name="cedula_empleado"
                                                   value="<?php echo e($usuarioEdicion['cedula_empleado'] ?? ''); ?>"
                                                   placeholder="V-20111222"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="username_usu_adm" class="form-label">Nombre de usuario</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="username_usu_adm"
                                                   name="username"
                                                   value="<?php echo e($usuarioEdicion['username'] ?? ''); ?>"
                                                   placeholder="clopez"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="nombres_usu_adm" class="form-label">Nombres</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="nombres_usu_adm"
                                                   name="nombres"
                                                   value="<?php echo e($usuarioEdicion['nombres'] ?? ''); ?>"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="apellidos_usu_adm" class="form-label">Apellidos</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="apellidos_usu_adm"
                                                   name="apellidos"
                                                   value="<?php echo e($usuarioEdicion['apellidos'] ?? ''); ?>"
                                                   required>
                                        </div>

                                        <div class="col-12">
                                            <label for="correo_usu_adm" class="form-label">Correo institucional</label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="correo_usu_adm"
                                                   name="correo_institucional"
                                                   value="<?php echo e($usuarioEdicion['correo_institucional'] ?? ''); ?>"
                                                   placeholder="funcionario@comunidad.gob.ve"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="rol_usu_adm" class="form-label">Rol institucional</label>
                                            <select class="form-select" id="rol_usu_adm" name="id_rol" required>
                                                <?php foreach ($roles as $rolItem): ?>
                                                    <option value="<?php echo (int)$rolItem['id_rol']; ?>"
                                                            <?php echo (int)($usuarioEdicion['id_rol'] ?? 4) === (int)$rolItem['id_rol'] ? 'selected' : ''; ?>>
                                                        <?php echo e($rolItem['nombre_rol']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-6">
                                            <label for="estado_usu_adm" class="form-label">Estado de la cuenta</label>
                                            <select class="form-select" id="estado_usu_adm" name="estado_cuenta">
                                                <?php $estActual = $usuarioEdicion['estado_cuenta'] ?? 'Activo'; ?>
                                                <option value="Activo" <?php echo $estActual === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="Suspendido" <?php echo $estActual === 'Suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                                <option value="Inactivo" <?php echo $estActual === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <label for="depto_usu_adm" class="form-label">Departamento asignado</label>
                                            <select class="form-select"
                                                    id="depto_usu_adm"
                                                    name="id_departamento"
                                                    data-autocompletable="true"
                                                    data-placeholder="Buscar departamento u oficina..."
                                                    required>
                                                <option value="">Selecciona el departamento</option>
                                                <?php foreach ($departamentos as $depItem): ?>
                                                    <option value="<?php echo (int)$depItem['id_departamento']; ?>"
                                                            data-titulo="<?php echo e($depItem['nombre_departamento']); ?>"
                                                            data-subtexto="<?php echo e($depItem['codigo_departamento'] . ' · ' . $depItem['sede_edificio'] . ' · ' . $depItem['numero_oficina']); ?>"
                                                            <?php echo (int)($usuarioEdicion['id_departamento'] ?? 0) === (int)$depItem['id_departamento'] ? 'selected' : ''; ?>>
                                                        <?php echo e($depItem['nombre_departamento']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-8">
                                            <label for="cargo_usu_adm" class="form-label">Cargo que desempeña</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="cargo_usu_adm"
                                                   name="cargo_empleado"
                                                   value="<?php echo e($usuarioEdicion['cargo_empleado'] ?? ''); ?>"
                                                   placeholder="Analista Administrativo"
                                                   required>
                                        </div>

                                        <div class="col-4">
                                            <label for="ext_usu_adm" class="form-label">Extensión</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="ext_usu_adm"
                                                   name="telefono_extension"
                                                   value="<?php echo e($usuarioEdicion['telefono_extension'] ?? ''); ?>"
                                                   placeholder="205">
                                        </div>

                                        <div class="col-12">
                                            <label for="clave_usu_adm" class="form-label">
                                                <?php echo $usuarioEdicion ? 'Nueva contraseña si deseas cambiarla' : 'Contraseña inicial'; ?>
                                            </label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="clave_usu_adm"
                                                   name="clave_usuario"
                                                   value="<?php echo $usuarioEdicion ? '' : '123456'; ?>"
                                                   placeholder="<?php echo $usuarioEdicion ? 'Dejar vacío para conservar la actual' : '123456'; ?>">
                                        </div>

                                        <?php if (!$usuarioEdicion): ?>
                                            <div class="col-6">
                                                <label for="preg_usu_adm" class="form-label">Pregunta de seguridad</label>
                                                <input type="text"
                                                       class="form-control"
                                                       id="preg_usu_adm"
                                                       name="pregunta_seguridad"
                                                       value="¿Nombre de primera mascota?">
                                            </div>
                                            <div class="col-6">
                                                <label for="resp_usu_adm" class="form-label">Respuesta inicial</label>
                                                <input type="text"
                                                       class="form-control"
                                                       id="resp_usu_adm"
                                                       name="respuesta_seguridad"
                                                       value="soporte">
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-institucional">
                                            <?php echo $usuarioEdicion ? 'Guardar cambios del funcionario' : 'Crear cuenta de usuario'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-8">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h3 class="h5 fw-bold mb-0">Directorio institucional de usuarios</h3>
                            <div style="min-width: 240px;">
                                <input type="search"
                                       class="form-control form-control-sm"
                                       data-buscador-tabla="#tablaUsuariosAdmin"
                                       placeholder="Buscar por nombre, usuario, cédula o rol...">
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-hover align-middle mb-0" id="tablaUsuariosAdmin" data-paginacion="true">
                                <thead>
                                    <tr>
                                        <th>Funcionario y cédula</th>
                                        <th>Usuario y rol</th>
                                        <th>Departamento</th>
                                        <th>Estado y conexión</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $uRow): ?>
                                        <?php
                                        $idURow = (int)$uRow['id_usuario'];
                                        $tieneBaja = !empty($uRow['eliminado_en']);
                                        $estaBloq = (!empty($uRow['bloqueado_hasta']) && strtotime((string)$uRow['bloqueado_hasta']) > $ahoraTs);
                                        ?>
                                        <tr class="<?php echo $tieneBaja ? 'opacity-75' : ''; ?>">
                                            <td>
                                                <div class="fw-semibold"><?php echo e($uRow['nombres'] . ' ' . $uRow['apellidos']); ?></div>
                                                <div class="small texto-secundario">
                                                    <span class="codigo-monoespaciado"><?php echo e($uRow['cedula_empleado']); ?></span> · <?php echo e($uRow['cargo_empleado']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($uRow['username']); ?></div>
                                                <span class="insignia-suave insignia-suave-neutra mt-1"><?php echo e($uRow['nombre_rol']); ?></span>
                                            </td>
                                            <td class="small">
                                                <?php echo e($uRow['nombre_departamento']); ?>
                                            </td>
                                            <td>
                                                <?php if ($tieneBaja): ?>
                                                    <span class="insignia-suave insignia-suave-critica">Con baja lógica</span>
                                                    <div class="small texto-secundario mt-1">
                                                        <?php echo e(formatear_fecha_esp((string)$uRow['eliminado_en'], false)); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="<?php echo e(clase_insignia_estado((string)$uRow['estado_cuenta'])); ?>">
                                                            <?php echo e($uRow['estado_cuenta']); ?>
                                                        </span>
                                                        <span class="<?php echo e(clase_insignia_estado((string)$uRow['estado_conexion'])); ?>">
                                                            <?php echo e(texto_estado_legible((string)$uRow['estado_conexion'])); ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($estaBloq || (int)$uRow['intentos_fallidos'] > 0): ?>
                                                        <div class="small text-danger fw-semibold mt-1">
                                                            <?php echo $estaBloq ? 'Bloqueado por intentos' : ((int)$uRow['intentos_fallidos'] . ' intentos fallidos'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a href="<?php echo e(ruta_url('admin', 'index', ['editar_usuario' => $idURow])); ?>"
                                                       class="btn btn-sm btn-secondary">
                                                        Editar
                                                    </a>

                                                    <?php if ($estaBloq || (int)$uRow['intentos_fallidos'] > 0): ?>
                                                        <form action="<?php echo e(ruta_url('admin', 'desbloquear')); ?>" method="POST" class="m-0">
                                                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                            <input type="hidden" name="id_usuario" value="<?php echo $idURow; ?>">
                                                            <button type="submit" class="btn btn-sm btn-institucional">
                                                                Desbloquear
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <?php if ($idURow !== (int)$_SESSION['id_usuario']): ?>
                                                        <form action="<?php echo e(ruta_url('admin', 'bajaLogica')); ?>"
                                                              method="POST"
                                                              class="m-0"
                                                              data-confirmar-mensaje="<?php echo $tieneBaja ? '¿Deseas reactivar la cuenta de este funcionario?' : '¿Confirmas aplicar baja lógica a esta cuenta? El historial del funcionario se conservará intacto.'; ?>">
                                                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                            <input type="hidden" name="id_usuario" value="<?php echo $idURow; ?>">
                                                            <input type="hidden" name="accion_baja" value="<?php echo $tieneBaja ? 'restaurar' : 'eliminar'; ?>">
                                                            <button type="submit" class="btn btn-sm <?php echo $tieneBaja ? 'btn-pizarra' : 'btn-outline-danger'; ?>">
                                                                <?php echo $tieneBaja ? 'Restaurar' : 'Dar de baja'; ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($solicitudesRecuperacion)): ?>
                            <h4 class="h6 fw-bold mt-4 mb-2">Solicitudes de recuperación de contraseña en intranet</h4>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover align-middle mb-0" id="tablaSolicitudesRecuperacionAdmin" data-paginacion="true">
                                    <thead>
                                        <tr>
                                            <th>Funcionario</th>
                                            <th>Método</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Resolución</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($solicitudesRecuperacion as $sRecItem): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?php echo e($sRecItem['nombres'] . ' ' . $sRecItem['apellidos']); ?></div>
                                                    <div class="small texto-secundario"><?php echo e($sRecItem['username']); ?></div>
                                                </td>
                                                <td class="small"><?php echo e(texto_estado_legible((string)$sRecItem['metodo_recuperacion'])); ?></td>
                                                <td>
                                                    <span class="insignia-suave <?php echo $sRecItem['estado_solicitud'] === 'Pendiente' ? 'insignia-suave-alta' : ($sRecItem['estado_solicitud'] === 'Completada' ? 'insignia-suave-baja' : 'insignia-suave-neutra'); ?>">
                                                        <?php echo e($sRecItem['estado_solicitud']); ?>
                                                    </span>
                                                </td>
                                                <td class="small"><?php echo e(formatear_fecha_esp((string)$sRecItem['fecha_solicitud'])); ?></td>
                                                <td>
                                                    <?php if ($sRecItem['estado_solicitud'] === 'Pendiente'): ?>
                                                        <form action="<?php echo e(ruta_url('auth', 'resolverRecuperacion')); ?>"
                                                              method="POST"
                                                              class="d-flex gap-1 align-items-center">
                                                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                            <input type="hidden" name="id_solicitud" value="<?php echo (int)$sRecItem['id_solicitud']; ?>">
                                                            <input type="hidden" name="origen" value="admin">
                                                            <input type="text"
                                                                   name="clave_temporal"
                                                                   class="form-control form-control-sm"
                                                                   style="max-width: 110px;"
                                                                   value="123456"
                                                                   required>
                                                            <button type="submit" name="decision" value="aprobar" class="btn btn-sm btn-institucional">
                                                                Aprobar
                                                            </button>
                                                            <button type="submit" name="decision" value="rechazar" class="btn btn-sm btn-secondary">
                                                                Rechazar
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="small texto-secundario">
                                                            <?php echo e($sRecItem['nombre_validador'] ?: 'Autogestionado'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Pestaña 2: Roles y Matriz de Permisos Granulares -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'permisos' ? 'show active' : ''; ?>"
                 id="tab-admin-permisos"
                 role="tabpanel">
                <h3 class="h5 fw-bold mb-3">Matriz institucional de permisos por rol y módulo</h3>

                <div class="row g-3">
                    <?php foreach ($roles as $rolM): ?>
                        <?php $idRolM = (int)$rolM['id_rol']; ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <span class="h6 fw-bold mb-0"><?php echo e($rolM['nombre_rol']); ?></span>
                                        <span class="small texto-secundario ms-2"><?php echo e($rolM['descripcion']); ?></span>
                                    </div>
                                    <span class="insignia-suave insignia-suave-neutra"><?php echo (int)$rolM['total_usuarios']; ?> usuarios activos</span>
                                </div>
                                <div class="card-body">
                                    <form action="<?php echo e(ruta_url('admin', 'guardarPermisos')); ?>" method="POST">
                                        <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                        <input type="hidden" name="id_rol" value="<?php echo $idRolM; ?>">

                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-2">
                                                <thead>
                                                    <tr>
                                                        <th>Módulo del sistema</th>
                                                        <th class="text-center">Ver módulo</th>
                                                        <th class="text-center">Crear registros</th>
                                                        <th class="text-center">Editar registros</th>
                                                        <th class="text-center">Eliminar o dar de baja</th>
                                                        <th class="text-center">Asignar solicitudes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($modulos as $modItem): ?>
                                                        <?php
                                                        $idModM = (int)$modItem['id_modulo'];
                                                        $permActual = $matrizPermisos[$idRolM][$idModM] ?? [
                                                            'permiso_ver'             => false,
                                                            'permiso_crear'           => false,
                                                            'permiso_editar'          => false,
                                                            'permiso_eliminar'        => false,
                                                            'permiso_asignar_tickets' => false,
                                                        ];
                                                        ?>
                                                        <tr>
                                                            <td class="fw-semibold"><?php echo e($modItem['nombre_modulo']); ?></td>
                                                            <td class="text-center">
                                                                <input type="checkbox"
                                                                       class="form-check-input"
                                                                       name="permisos[<?php echo $idModM; ?>][ver]"
                                                                       value="1"
                                                                       <?php echo !empty($permActual['permiso_ver']) ? 'checked' : ''; ?>>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="checkbox"
                                                                       class="form-check-input"
                                                                       name="permisos[<?php echo $idModM; ?>][crear]"
                                                                       value="1"
                                                                       <?php echo !empty($permActual['permiso_crear']) ? 'checked' : ''; ?>>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="checkbox"
                                                                       class="form-check-input"
                                                                       name="permisos[<?php echo $idModM; ?>][editar]"
                                                                       value="1"
                                                                       <?php echo !empty($permActual['permiso_editar']) ? 'checked' : ''; ?>>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="checkbox"
                                                                       class="form-check-input"
                                                                       name="permisos[<?php echo $idModM; ?>][eliminar]"
                                                                       value="1"
                                                                       <?php echo !empty($permActual['permiso_eliminar']) ? 'checked' : ''; ?>>
                                                            </td>
                                                            <td class="text-center">
                                                                <input type="checkbox"
                                                                       class="form-check-input"
                                                                       name="permisos[<?php echo $idModM; ?>][asignar]"
                                                                       value="1"
                                                                       <?php echo !empty($permActual['permiso_asignar_tickets']) ? 'checked' : ''; ?>>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-sm btn-institucional">
                                                Guardar permisos de <?php echo e($rolM['nombre_rol']); ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pestaña 3: Registros de Acceso del Sistema -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'accesos' ? 'show active' : ''; ?>"
                 id="tab-admin-accesos"
                 role="tabpanel">
                <div class="barra-filtros-compacta mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2">
                        <form action="<?php echo e(ruta_url('admin', 'index')); ?>" method="GET" class="d-flex flex-wrap gap-2 align-items-end m-0">
                            <input type="hidden" name="c" value="admin">
                            <input type="hidden" name="a" value="index">
                            <input type="hidden" name="tab" value="accesos">

                            <div class="flex-grow-1" style="min-width: 175px;">
                                <label class="form-label mb-1">Tipo de evento</label>
                                <select name="evento" class="form-select form-select-sm">
                                    <option value="">Todos los eventos</option>
                                    <option value="Login_Exitoso" <?php echo $filtrosAccesos['tipo_evento'] === 'Login_Exitoso' ? 'selected' : ''; ?>>Inicio de sesión correcto</option>
                                    <option value="Login_Fallido" <?php echo $filtrosAccesos['tipo_evento'] === 'Login_Fallido' ? 'selected' : ''; ?>>Intento de acceso fallido</option>
                                    <option value="Bloqueo_Seguridad" <?php echo $filtrosAccesos['tipo_evento'] === 'Bloqueo_Seguridad' ? 'selected' : ''; ?>>Bloqueo preventivo</option>
                                    <option value="Logout" <?php echo $filtrosAccesos['tipo_evento'] === 'Logout' ? 'selected' : ''; ?>>Cierre de sesión</option>
                                    <option value="Navegacion_Modulo" <?php echo $filtrosAccesos['tipo_evento'] === 'Navegacion_Modulo' ? 'selected' : ''; ?>>Actividad en módulos</option>
                                </select>
                            </div>

                            <div class="flex-grow-1">
                                <label class="form-label mb-1">Desde fecha</label>
                                <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo e($filtrosAccesos['fecha_desde']); ?>">
                            </div>

                            <div class="flex-grow-1">
                                <label class="form-label mb-1">Hasta fecha</label>
                                <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo e($filtrosAccesos['fecha_hasta']); ?>">
                            </div>

                            <button type="submit" class="btn btn-sm btn-institucional">Filtrar registros</button>
                        </form>

                        <div class="flex-grow-1" style="min-width: 240px; max-width: 320px;">
                            <input type="search"
                                   class="form-control form-control-sm"
                                   data-buscador-tabla="#tablaLogsAccesoAdmin"
                                   placeholder="Buscar usuario, acción o dirección IP...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0" id="tablaLogsAccesoAdmin" data-paginacion="true">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Usuario y rol</th>
                                <th>Evento</th>
                                <th>Módulo</th>
                                <th>Descripción de la acción</th>
                                <th>Dirección IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrosAcceso)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 texto-secundario">
                                        No se encontraron registros de acceso con los filtros actuales.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrosAcceso as $logItem): ?>
                                    <tr>
                                        <td class="small fw-medium"><?php echo e(formatear_fecha_esp((string)$logItem['fecha_hora'])); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($logItem['nombre_completo'] ?: $logItem['username_intento']); ?></div>
                                            <div class="small texto-secundario"><?php echo e($logItem['nombre_rol'] ?: $logItem['username_intento']); ?></div>
                                        </td>
                                        <td>
                                            <span class="<?php echo e(clase_insignia_estado((string)$logItem['tipo_evento'])); ?>">
                                                <?php echo e(texto_estado_legible((string)$logItem['tipo_evento'])); ?>
                                            </span>
                                        </td>
                                        <td class="small"><?php echo e($logItem['nombre_modulo'] ?: 'Acceso general'); ?></td>
                                        <td><?php echo e((string)$logItem['descripcion_accion']); ?></td>
                                        <td class="small texto-secundario"><span class="codigo-monoespaciado"><?php echo e((string)$logItem['direccion_ip']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña 4: Historial de Cambios y Auditoría de Datos -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'cambios' ? 'show active' : ''; ?>"
                 id="tab-admin-cambios"
                 role="tabpanel">
                <div class="barra-filtros-compacta mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2">
                        <form action="<?php echo e(ruta_url('admin', 'index')); ?>" method="GET" class="d-flex flex-wrap gap-2 align-items-end m-0">
                            <input type="hidden" name="c" value="admin">
                            <input type="hidden" name="a" value="index">
                            <input type="hidden" name="tab" value="cambios">

                            <div class="flex-grow-1" style="min-width: 165px;">
                                <label class="form-label mb-1">Tabla auditada</label>
                                <select name="tabla" class="form-select form-select-sm">
                                    <option value="">Todas las tablas</option>
                                    <option value="tickets" <?php echo $filtrosCambios['tabla_afectada'] === 'tickets' ? 'selected' : ''; ?>>Solicitudes</option>
                                    <option value="usuarios" <?php echo $filtrosCambios['tabla_afectada'] === 'usuarios' ? 'selected' : ''; ?>>Usuarios</option>
                                    <option value="equipos" <?php echo $filtrosCambios['tabla_afectada'] === 'equipos' ? 'selected' : ''; ?>>Equipos</option>
                                    <option value="departamentos" <?php echo $filtrosCambios['tabla_afectada'] === 'departamentos' ? 'selected' : ''; ?>>Departamentos</option>
                                    <option value="roles_permisos" <?php echo $filtrosCambios['tabla_afectada'] === 'roles_permisos' ? 'selected' : ''; ?>>Permisos</option>
                                </select>
                            </div>

                            <div class="flex-grow-1" style="min-width: 165px;">
                                <label class="form-label mb-1">Operación</label>
                                <select name="operacion" class="form-select form-select-sm">
                                    <option value="">Cualquier operación</option>
                                    <option value="CREACION" <?php echo $filtrosCambios['tipo_operacion'] === 'CREACION' ? 'selected' : ''; ?>>Creación</option>
                                    <option value="MODIFICACION" <?php echo $filtrosCambios['tipo_operacion'] === 'MODIFICACION' ? 'selected' : ''; ?>>Modificación</option>
                                    <option value="ELIMINACION_LOGICA" <?php echo $filtrosCambios['tipo_operacion'] === 'ELIMINACION_LOGICA' ? 'selected' : ''; ?>>Eliminación lógica</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-sm btn-institucional">Filtrar historial</button>
                        </form>

                        <div class="flex-grow-1" style="min-width: 240px; max-width: 320px;">
                            <input type="search"
                                   class="form-control form-control-sm"
                                   data-buscador-tabla="#tablaChangelogsAdmin"
                                   placeholder="Buscar por autor, campo o valor...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0" id="tablaChangelogsAdmin" data-paginacion="true">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Autor</th>
                                <th>Entidad y operación</th>
                                <th>Campo modificado</th>
                                <th>Valor anterior y valor nuevo</th>
                                <th>Motivo o detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historialCambios)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 texto-secundario">
                                        No se encontraron registros en el historial de cambios.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historialCambios as $chg): ?>
                                    <tr>
                                        <td class="small fw-medium"><?php echo e(formatear_fecha_esp((string)$chg['fecha_hora_cambio'])); ?></td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($chg['nombre_autor'] ?: 'Sistema automático'); ?></div>
                                            <div class="small texto-secundario"><?php echo e(($chg['username_autor'] ?? '') === 'superadmin' ? 'Superadministrador' : ($chg['username_autor'] ?: '')); ?></div>
                                        </td>
                                        <td>
                                            <span class="codigo-monoespaciado"><?php echo e(texto_estado_legible((string)$chg['tabla_afectada'])); ?> #<?php echo (int)$chg['id_registro_afectado']; ?></span>
                                            <div class="small fw-semibold mt-1"><?php echo e(texto_estado_legible((string)$chg['tipo_operacion'])); ?></div>
                                        </td>
                                        <td class="fw-semibold"><?php echo e(texto_estado_legible((string)$chg['campo_modificado'])); ?></td>
                                        <td class="small">
                                            <div><span class="texto-secundario">Antes:</span> <?php echo e($chg['valor_anterior'] ?: 'Sin valor'); ?></div>
                                            <div><span class="texto-secundario">Después:</span> <span class="fw-medium"><?php echo e($chg['valor_nuevo'] ?: 'Sin valor'); ?></span></div>
                                        </td>
                                        <td class="small texto-secundario"><?php echo e($chg['motivo_cambio'] ?: 'Registro de auditoría'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña 5: Servidor, Respaldos de Base de Datos, Sesiones Activas y Mantenimiento -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'servidor' ? 'show active' : ''; ?>"
                 id="tab-admin-servidor"
                 role="tabpanel">

                <!-- 1. Monitor de Características y Estado Físico y Lógico del Servidor -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-body py-3">
                                <div class="small texto-secundario fw-medium">Sistema operativo y arquitectura</div>
                                <div class="fw-bold mt-1"><?php echo e($diagnosticoServidor['sistema_operativo']); ?></div>
                                <div class="small texto-secundario mt-1">
                                    Arquitectura <?php echo e($diagnosticoServidor['arquitectura']); ?> · Nodo <?php echo e($diagnosticoServidor['nombre_nodo']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-body py-3">
                                <div class="small texto-secundario fw-medium">Entorno de ejecución y servidor web</div>
                                <div class="fw-bold mt-1">PHP <?php echo e($diagnosticoServidor['version_php']); ?> · <?php echo e($diagnosticoServidor['interfaz_sapi']); ?></div>
                                <div class="small texto-secundario mt-1 text-truncate" title="<?php echo e($diagnosticoServidor['servidor_web']); ?>">
                                    <?php echo e($diagnosticoServidor['servidor_web']); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-body py-3">
                                <div class="small texto-secundario fw-medium">Motor de base de datos relacional</div>
                                <div class="fw-bold mt-1">MySQL <?php echo e($diagnosticoServidor['version_mysql']); ?></div>
                                <div class="small texto-secundario mt-1">
                                    <?php echo e(number_format((float)$diagnosticoServidor['peso_total_bd_kb'], 2, ',', '.')); ?> KB · <?php echo (int)$diagnosticoServidor['total_filas_bd']; ?> registros activos
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small texto-secundario fw-medium">Almacenamiento en disco duro</span>
                                    <span class="insignia-suave <?php echo (float)$diagnosticoServidor['disco_libre_pct'] > 15 ? 'insignia-suave-baja' : 'insignia-suave-alta'; ?>">
                                        <?php echo e(number_format((float)$diagnosticoServidor['disco_libre_pct'], 1, ',', '.')); ?>% disponible
                                    </span>
                                </div>
                                <div class="fw-bold mt-1">
                                    <?php echo e(number_format((float)$diagnosticoServidor['disco_libre_gb'], 2, ',', '.')); ?> GB libres de <?php echo e(number_format((float)$diagnosticoServidor['disco_total_gb'], 2, ',', '.')); ?> GB
                                </div>
                                <div class="progress mt-2" style="height: 7px;">
                                    <div class="progress-bar"
                                         role="progressbar"
                                         style="width: <?php echo (float)$diagnosticoServidor['disco_usado_pct']; ?>%; background-color: var(--color-primario);"
                                         aria-valuenow="<?php echo (float)$diagnosticoServidor['disco_usado_pct']; ?>"
                                         aria-valuemin="0"
                                         aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Parámetros de configuración actuales y estado de módulos críticos -->
                <div class="card mb-3">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>Parámetros de configuración del servidor y módulos críticos de seguridad</span>
                        <span class="small texto-secundario">Zona horaria activa: <strong><?php echo e($diagnosticoServidor['parametros_php']['zona_horaria']); ?></strong></span>
                    </div>
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-lg-6">
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="border rounded px-3 py-2 flex-fill">
                                        <div class="small texto-secundario">Memoria asignada</div>
                                        <div class="fw-bold codigo-monoespaciado"><?php echo e($diagnosticoServidor['parametros_php']['limite_memoria']); ?></div>
                                    </div>
                                    <div class="border rounded px-3 py-2 flex-fill">
                                        <div class="small texto-secundario">Límite por archivo</div>
                                        <div class="fw-bold codigo-monoespaciado"><?php echo e($diagnosticoServidor['parametros_php']['maximo_subida']); ?></div>
                                    </div>
                                    <div class="border rounded px-3 py-2 flex-fill">
                                        <div class="small texto-secundario">Límite de envío</div>
                                        <div class="fw-bold codigo-monoespaciado"><?php echo e($diagnosticoServidor['parametros_php']['maximo_envio_post']); ?></div>
                                    </div>
                                    <div class="border rounded px-3 py-2 flex-fill">
                                        <div class="small texto-secundario">Tiempo de ejecución</div>
                                        <div class="fw-bold codigo-monoespaciado"><?php echo e($diagnosticoServidor['parametros_php']['tiempo_max_ejecucion']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                    <?php foreach ($diagnosticoServidor['modulos_criticos'] as $nombreExt => $estadoExt): ?>
                                        <div class="border rounded px-3 py-2 d-flex align-items-center gap-2">
                                            <span class="small fw-semibold"><?php echo e($nombreExt); ?></span>
                                            <span class="insignia-suave <?php echo $estadoExt ? 'insignia-suave-baja' : 'insignia-suave-critica'; ?>">
                                                <?php echo $estadoExt ? 'Habilitado' : 'Inactivo'; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Gestor de Copias de Seguridad de Base de Datos y 3. Monitor de Sesiones Activas -->
                <div class="row g-3 mb-3 fila-inicio-alineada">
                    <!-- Columna izquierda: Gestor de Copias de Seguridad SQL en Vivo -->
                    <div class="col-12 col-xl-7">
                        <div class="card">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-bold">Gestor de copias de seguridad de la base de datos</div>
                                    <div class="small texto-secundario fw-normal">Volcado íntegro de las 15 tablas, vista de métricas y disparadores con PDO nativo</div>
                                </div>
                                <form action="<?php echo e(ruta_url('admin', 'generarRespaldo')); ?>" method="POST" class="m-0">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                    <button type="submit" class="btn btn-sm btn-institucional">
                                        Generar nuevo respaldo ahora
                                    </button>
                                </form>
                            </div>

                            <div class="card-body">
                                <div class="barra-filtros-compacta mb-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span class="small texto-secundario">
                                            Almacenamiento protegido contra descarga directa en el directorio interno de respaldos
                                        </span>
                                        <div style="min-width: 210px;">
                                            <input type="search"
                                                   class="form-control form-control-sm"
                                                   data-buscador-tabla="#tablaRespaldosSistema"
                                                   placeholder="Buscar respaldo por nombre o fecha...">
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive border rounded">
                                    <table class="table table-hover align-middle mb-0" id="tablaRespaldosSistema" data-paginacion="true">
                                        <thead>
                                            <tr>
                                                <th>Archivo de respaldo</th>
                                                <th>Fecha y hora de creación</th>
                                                <th>Tamaño</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($respaldosExistentes)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 texto-secundario">
                                                        Aún no se han generado copias de seguridad en el servidor. Pulsa el botón superior para crear el primer respaldo.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($respaldosExistentes as $respItem): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="codigo-monoespaciado fw-semibold"><?php echo e($respItem['nombre_archivo']); ?></span>
                                                        </td>
                                                        <td class="small">
                                                            <?php echo e(formatear_fecha_esp((string)$respItem['fecha_sql'])); ?>
                                                        </td>
                                                        <td>
                                                            <span class="insignia-suave insignia-suave-neutra"><?php echo e($respItem['peso_legible']); ?></span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                <a href="<?php echo e(ruta_url('admin', 'descargarRespaldo', ['archivo' => (string)$respItem['nombre_archivo']])); ?>"
                                                                   class="btn btn-sm btn-secondary">
                                                                    Descargar
                                                                </a>
                                                                <form action="<?php echo e(ruta_url('admin', 'eliminarRespaldo')); ?>"
                                                                      method="POST"
                                                                      class="d-inline"
                                                                      data-confirmar-mensaje="¿Deseas eliminar de forma permanente la copia de seguridad <?php echo e($respItem['nombre_archivo']); ?>?">
                                                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                                    <input type="hidden" name="nombre_archivo" value="<?php echo e($respItem['nombre_archivo']); ?>">
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                        Eliminar
                                                                    </button>
                                                                </form>
                                                            </div>
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

                    <!-- Columna derecha: Monitor de Sesiones Activas y Cierre Forzado -->
                    <div class="col-12 col-xl-5">
                        <div class="card">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-bold">Monitor de sesiones activas en línea</div>
                                    <div class="small texto-secundario fw-normal">Control en tiempo real y desconexión inmediata</div>
                                </div>
                                <span class="insignia-suave insignia-suave-baja">
                                    <?php echo count($usuariosEnLinea); ?> en línea
                                </span>
                            </div>

                            <div class="card-body">
                                <div class="mb-3">
                                    <input type="search"
                                           class="form-control form-control-sm"
                                           data-buscador-tabla="#tablaSesionesActivas"
                                           placeholder="Buscar funcionario conectado, departamento o rol...">
                                </div>

                                <div class="table-responsive border rounded">
                                    <table class="table table-hover align-middle mb-0" id="tablaSesionesActivas" data-paginacion="true">
                                        <thead>
                                            <tr>
                                                <th>Funcionario y rol</th>
                                                <th>Departamento y último acceso</th>
                                                <th>Control</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($usuariosEnLinea)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-4 texto-secundario">
                                                        No hay otras cuentas reportadas en línea en este momento.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($usuariosEnLinea as $uOnline): ?>
                                                    <?php $esMiSesion = ((int)$uOnline['id_usuario'] === (int)$_SESSION['id_usuario']); ?>
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold"><?php echo e($uOnline['nombres'] . ' ' . $uOnline['apellidos']); ?></div>
                                                            <div class="small texto-secundario">
                                                                <?php echo e($uOnline['nombre_rol']); ?> · <span class="codigo-monoespaciado"><?php echo e($uOnline['username']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td class="small">
                                                            <div class="fw-medium"><?php echo e($uOnline['nombre_departamento']); ?></div>
                                                            <div class="texto-secundario"><?php echo e(formatear_fecha_esp((string)$uOnline['fecha_ultimo_acceso'])); ?></div>
                                                        </td>
                                                        <td>
                                                            <?php if ($esMiSesion): ?>
                                                                <span class="insignia-suave insignia-suave-baja">Sesión actual</span>
                                                            <?php else: ?>
                                                                <form action="<?php echo e(ruta_url('admin', 'forzarDesconexion')); ?>"
                                                                      method="POST"
                                                                      class="d-inline"
                                                                      data-confirmar-mensaje="¿Deseas forzar el cierre inmediato de la sesión de <?php echo e($uOnline['nombres'] . ' ' . $uOnline['apellidos']); ?>?">
                                                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                                    <input type="hidden" name="id_usuario" value="<?php echo (int)$uOnline['id_usuario']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-institucional">
                                                                        Forzar cierre de sesión
                                                                    </button>
                                                                </form>
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

                <!-- 4. Herramientas de Mantenimiento, Almacenamiento de Archivos y Estado de Tablas SQL -->
                <div class="row g-3 fila-inicio-alineada">
                    <!-- Bloque izquierdo: Depuración Auditada de Registros y Espacio de Subidas -->
                    <div class="col-12 col-xl-4">
                        <div class="card mb-3">
                            <div class="card-header">
                                Mantenimiento y depuración de registros de acceso
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2">
                                    <span class="small texto-secundario">Total actual en registros de acceso</span>
                                    <span class="fw-bold"><?php echo (int)$resumenMantenimiento['total_logs_acceso']; ?> eventos</span>
                                </div>
                                <div class="row g-2 mb-3 text-center">
                                    <div class="col-4">
                                        <div class="border rounded py-2 px-1">
                                            <div class="small texto-secundario">+30 días</div>
                                            <div class="fw-bold"><?php echo (int)$resumenMantenimiento['logs_mayores_30_dias']; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded py-2 px-1">
                                            <div class="small texto-secundario">+60 días</div>
                                            <div class="fw-bold"><?php echo (int)$resumenMantenimiento['logs_mayores_60_dias']; ?></div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded py-2 px-1">
                                            <div class="small texto-secundario">+90 días</div>
                                            <div class="fw-bold"><?php echo (int)$resumenMantenimiento['logs_mayores_90_dias']; ?></div>
                                        </div>
                                    </div>
                                </div>

                                <form action="<?php echo e(ruta_url('admin', 'depurarAccesos')); ?>"
                                      method="POST"
                                      data-confirmar-mensaje="¿Confirmas ejecutar la depuración de registros antiguos de acceso? Esta operación quedará registrada en el historial de cambios.">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                                    <div class="mb-3">
                                        <label for="dias_conservar_mant" class="form-label">Antigüedad a conservar</label>
                                        <select class="form-select" id="dias_conservar_mant" name="dias_conservar">
                                            <option value="30">Conservar últimos 30 días de actividad</option>
                                            <option value="60" selected>Conservar últimos 60 días de actividad</option>
                                            <option value="90">Conservar últimos 90 días de actividad</option>
                                        </select>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-secondary">
                                            Depurar registros antiguos
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                Espacio ocupado por archivos subidos
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <div class="fw-semibold small">Adjuntos de solicitudes</div>
                                        <div class="small texto-secundario"><?php echo (int)$resumenMantenimiento['cantidad_adjuntos']; ?> archivos registrados</div>
                                    </div>
                                    <span class="codigo-monoespaciado fw-bold">
                                        <?php echo e(number_format((float)$resumenMantenimiento['espacio_adjuntos_kb'], 2, ',', '.')); ?> KB
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <div class="fw-semibold small">Fotografías de perfil</div>
                                        <div class="small texto-secundario"><?php echo (int)$resumenMantenimiento['cantidad_fotos_perfil']; ?> imágenes personalizadas</div>
                                    </div>
                                    <span class="codigo-monoespaciado fw-bold">
                                        <?php echo e(number_format((float)$resumenMantenimiento['espacio_perfiles_kb'], 2, ',', '.')); ?> KB
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-1">
                                    <span class="fw-bold small">Volumen total de subidas</span>
                                    <span class="insignia-suave insignia-suave-media">
                                        <?php echo e(number_format((float)$resumenMantenimiento['espacio_total_subidas_kb'], 2, ',', '.')); ?> KB
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque derecho: Tabla de estructura, filas y peso en KB de db_tickets_soporte -->
                    <div class="col-12 col-xl-8">
                        <div class="card">
                            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <div class="fw-bold">Estado de tablas y almacenamiento de la base de datos</div>
                                    <div class="small texto-secundario fw-normal">Dimensionamiento real consultado en el diccionario del motor relacional</div>
                                </div>
                                <div style="min-width: 220px;">
                                    <input type="search"
                                           class="form-control form-control-sm"
                                           data-buscador-tabla="#tablaEstadoTablasBd"
                                           placeholder="Buscar tabla o vista...">
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="tablaEstadoTablasBd" data-paginacion="true">
                                        <thead>
                                            <tr>
                                                <th>Tabla o vista</th>
                                                <th>Tipo y motor</th>
                                                <th class="text-center">Filas actuales</th>
                                                <th class="text-end">Datos en KB</th>
                                                <th class="text-end">Índices en KB</th>
                                                <th class="text-end">Total en KB</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($diagnosticoServidor['tablas_bd'] as $tbInfo): ?>
                                                <?php
                                                $esVistaTabla = strtoupper((string)$tbInfo['tipo_objeto']) === 'VIEW';
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold"><?php echo e(texto_estado_legible((string)$tbInfo['nombre_tabla'])); ?></div>
                                                        <div class="small texto-secundario codigo-monoespaciado">Colación: <?php echo e($tbInfo['colacion']); ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="insignia-suave <?php echo $esVistaTabla ? 'insignia-suave-media' : 'insignia-suave-neutra'; ?>">
                                                            <?php echo $esVistaTabla ? 'Vista de consulta' : 'Tabla principal'; ?>
                                                        </span>
                                                        <div class="small texto-secundario mt-1"><?php echo e($tbInfo['motor']); ?></div>
                                                    </td>
                                                    <td class="text-center fw-bold">
                                                        <?php echo (int)$tbInfo['filas_exactas']; ?>
                                                    </td>
                                                    <td class="text-end small codigo-monoespaciado">
                                                        <?php echo e(number_format((float)$tbInfo['datos_kb'], 2, ',', '.')); ?> KB
                                                    </td>
                                                    <td class="text-end small codigo-monoespaciado">
                                                        <?php echo e(number_format((float)$tbInfo['indices_kb'], 2, ',', '.')); ?> KB
                                                    </td>
                                                    <td class="text-end fw-semibold codigo-monoespaciado">
                                                        <?php echo e(number_format((float)$tbInfo['total_kb'], 2, ',', '.')); ?> KB
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
