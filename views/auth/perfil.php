<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
$tokenCsrf = obtener_token_csrf();
$seccionActiva = ($_GET['seccion'] ?? 'datos') === 'seguridad' ? 'seguridad' : (($_GET['seccion'] ?? '') === 'actividad' ? 'actividad' : 'datos');
$rutaFotoPerfil = !empty($usuario['foto_perfil_ruta']) ? (string)$usuario['foto_perfil_ruta'] : 'assets/img/default-avatar.png';
?>

<!-- Tarjetas superiores de resumen rápido de la cuenta -->
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Nivel de acceso institucional</div>
                <div class="h5 fw-bold mt-1 mb-1"><?php echo e($usuario['nombre_rol']); ?></div>
                <span class="insignia-suave insignia-suave-baja">Cuenta activa</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Ubicación administrativa</div>
                <div class="h6 fw-bold mt-1 mb-1 text-truncate" title="<?php echo e($usuario['nombre_departamento']); ?>">
                    <?php echo e($usuario['nombre_departamento']); ?>
                </div>
                <div class="small texto-secundario">
                    <?php echo e($usuario['sede_edificio'] . ' · ' . $usuario['piso_oficina'] . ' · ' . $usuario['numero_oficina']); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Equipos en tu oficina</div>
                <div class="h4 fw-bold mt-1 mb-0" style="color: var(--color-primario);">
                    <?php echo (int)$resumenActividad['equipos_asignados']; ?>
                </div>
                <div class="small texto-secundario">Asignados o compartidos en el área</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Solicitudes vinculadas</div>
                <div class="h4 fw-bold mt-1 mb-0">
                    <?php echo (int)$resumenActividad['solicitudes_activas']; ?> activas
                </div>
                <div class="small texto-secundario">
                    <?php echo (int)$resumenActividad['solicitudes_resueltas']; ?> resueltas en historial
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($solicitudesRecuperacionPendientes)): ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Solicitudes pendientes de restablecimiento de contraseña en intranet</span>
            <span class="insignia-suave insignia-suave-critica"><?php echo count($solicitudesRecuperacionPendientes); ?> pendientes</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Funcionario solicitante</th>
                            <th>Departamento</th>
                            <th>Fecha de solicitud</th>
                            <th>Clave temporal y acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudesRecuperacionPendientes as $sol): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo e($sol['nombres'] . ' ' . $sol['apellidos']); ?></div>
                                    <div class="small texto-secundario">
                                        Usuario: <?php echo e($sol['username']); ?> · Cédula: <?php echo e($sol['cedula_empleado']); ?>
                                    </div>
                                </td>
                                <td><?php echo e($sol['nombre_departamento']); ?></td>
                                <td class="small"><?php echo e(formatear_fecha_esp((string)$sol['fecha_solicitud'])); ?></td>
                                <td>
                                    <form action="<?php echo e(ruta_url('auth', 'resolverRecuperacion')); ?>"
                                          method="POST"
                                          class="d-flex flex-wrap gap-2 align-items-center">
                                        <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                        <input type="hidden" name="id_solicitud" value="<?php echo (int)$sol['id_solicitud']; ?>">
                                        <input type="hidden" name="origen" value="perfil">
                                        <input type="text"
                                               name="clave_temporal"
                                               class="form-control form-control-sm"
                                               style="max-width: 130px;"
                                               value="123456"
                                               required>
                                        <button type="submit"
                                                name="decision"
                                                value="aprobar"
                                                class="btn btn-sm btn-institucional">
                                            Restablecer acceso
                                        </button>
                                        <button type="submit"
                                                name="decision"
                                                value="rechazar"
                                                class="btn btn-sm btn-secondary">
                                            Rechazar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 fila-inicio-alineada">
    <!-- Columna izquierda: Ficha del funcionario, Preferencias de visualización y Equipos asociados -->
    <div class="col-12 col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center p-3">
                <img src="<?php echo e(recurso_publico($rutaFotoPerfil)); ?>"
                     alt="Fotografía del funcionario"
                     class="avatar-perfil-grande mb-2">
                <h3 class="h5 fw-bold mb-1"><?php echo e($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></h3>
                <div class="small texto-secundario mb-2"><?php echo e($usuario['cargo_empleado']); ?></div>
                <span class="insignia-suave insignia-suave-neutra"><?php echo e($usuario['nombre_rol']); ?></span>

                <hr class="my-3">

                <div class="text-start small">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="texto-secundario">Cédula:</span>
                        <span class="fw-semibold"><?php echo e($usuario['cedula_empleado']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="texto-secundario">Usuario:</span>
                        <span class="fw-semibold"><?php echo e($usuario['username']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="texto-secundario">Correo:</span>
                        <span class="fw-semibold text-truncate ms-2"><?php echo e($usuario['correo_institucional']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="texto-secundario">Extensión:</span>
                        <span class="fw-semibold"><?php echo e($usuario['telefono_extension'] ?: 'No registrada'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between pt-1">
                        <span class="texto-secundario">Último ingreso:</span>
                        <span class="fw-semibold text-end ms-2"><?php echo e(formatear_fecha_esp($usuario['fecha_ultimo_acceso'] ?? null)); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Preferencias de visualización y accesibilidad (Persistente en localStorage) -->
        <div class="card mb-3">
            <div class="card-header">
                Preferencias de visualización y accesibilidad
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom">
                    <div>
                        <div class="fw-semibold">Modo oscuro de interfaz</div>
                        <div class="small texto-secundario" id="estadoActualModoOscuro">Descanso visual para jornadas prolongadas</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                                class="btn btn-sm btn-secondary"
                                data-accion="alternar-tema">
                            <span class="texto-boton-tema">Activar modo oscuro</span>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">Tamaño de letra aumentado</div>
                        <div class="small texto-secundario" id="estadoActualLetraGrande">Escala tipográfica ampliada para lectura cómoda</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                                class="btn btn-sm btn-secondary"
                                data-accion="alternar-letra">
                            <span class="texto-boton-letra">Activar letra aumentada</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Equipos informáticos de tu oficina
            </div>
            <div class="card-body p-0">
                <?php if (empty($resumenActividad['lista_equipos'])): ?>
                    <div class="p-3 text-center small texto-secundario">
                        No tienes equipos registrados directamente en tu área todavía.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($resumenActividad['lista_equipos'] as $eq): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold"><?php echo e($eq['marca'] . ' ' . $eq['modelo']); ?></div>
                                        <div class="small texto-secundario">
                                            <span class="codigo-monoespaciado"><?php echo e($eq['codigo_bien']); ?></span>
                                            · <?php echo e(texto_estado_legible($eq['categoria_equipo'])); ?>
                                        </div>
                                        <?php if (!empty($eq['direccion_ip_fija'])): ?>
                                            <div class="small texto-secundario mt-1">Dirección IP: <?php echo e($eq['direccion_ip_fija']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="<?php echo e(clase_insignia_estado((string)$eq['estado_operativo'])); ?>">
                                        <?php echo e(texto_estado_legible($eq['estado_operativo'])); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Columna derecha: Pestañas de edición de datos, seguridad y registros de actividad -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header pb-0 border-bottom-0">
                <ul class="nav nav-tabs" id="pestanasPerfilUsuario" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $seccionActiva === 'datos' ? 'active' : ''; ?>"
                                id="tab-datos-btn"
                                data-bs-toggle="tab"
                                data-bs-target="#panel-datos-personales"
                                type="button"
                                role="tab">
                            Datos personales
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $seccionActiva === 'seguridad' ? 'active' : ''; ?>"
                                id="tab-seguridad-btn"
                                data-bs-toggle="tab"
                                data-bs-target="#panel-seguridad-cuenta"
                                type="button"
                                role="tab">
                            Contraseña y seguridad
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $seccionActiva === 'actividad' ? 'active' : ''; ?>"
                                id="tab-actividad-btn"
                                data-bs-toggle="tab"
                                data-bs-target="#panel-actividad-cuenta"
                                type="button"
                                role="tab">
                            Actividad reciente
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content">
                    <!-- Pestaña 1: Edición de datos personales -->
                    <div class="tab-pane fade <?php echo $seccionActiva === 'datos' ? 'show active' : ''; ?>"
                         id="panel-datos-personales"
                         role="tabpanel">
                        <h3 class="h5 fw-bold mb-3">Actualizar información de mi cuenta</h3>

                        <form action="<?php echo e(ruta_url('auth', 'actualizarPerfil')); ?>"
                              method="POST"
                              enctype="multipart/form-data"
                              novalidate>
                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label">Cédula del funcionario</label>
                                    <input type="text"
                                           class="form-control"
                                           value="<?php echo e($usuario['cedula_empleado']); ?>"
                                           readonly>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label">Nombre de usuario institucional</label>
                                    <input type="text"
                                           class="form-control"
                                           value="<?php echo e($usuario['username']); ?>"
                                           readonly>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="nombres_perfil" class="form-label">Nombres</label>
                                    <input type="text"
                                           class="form-control"
                                           id="nombres_perfil"
                                           name="nombres"
                                           value="<?php echo e($usuario['nombres']); ?>"
                                           required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="apellidos_perfil" class="form-label">Apellidos</label>
                                    <input type="text"
                                           class="form-control"
                                           id="apellidos_perfil"
                                           name="apellidos"
                                           value="<?php echo e($usuario['apellidos']); ?>"
                                           required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="correo_perfil" class="form-label">Correo institucional</label>
                                    <input type="email"
                                           class="form-control"
                                           id="correo_perfil"
                                           name="correo_institucional"
                                           value="<?php echo e($usuario['correo_institucional']); ?>"
                                           required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="extension_perfil" class="form-label">Extensión telefónica</label>
                                    <input type="text"
                                           class="form-control"
                                           id="extension_perfil"
                                           name="telefono_extension"
                                           value="<?php echo e($usuario['telefono_extension'] ?? ''); ?>"
                                           placeholder="Ejemplo: 204">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="cargo_perfil" class="form-label">Cargo que desempeña</label>
                                    <input type="text"
                                           class="form-control"
                                           id="cargo_perfil"
                                           name="cargo_empleado"
                                           value="<?php echo e($usuario['cargo_empleado']); ?>"
                                           required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label d-block">Fotografía de perfil</label>
                                    <div class="contenedor-archivo-estilizado">
                                        <input type="file"
                                               class="input-archivo-oculto"
                                               id="foto_perfil"
                                               name="foto_perfil"
                                               accept=".png,.jpg,.jpeg,.webp">
                                        <label for="foto_perfil" class="boton-adjuntar-archivo">
                                            <i class="ni ni-paperclip" aria-hidden="true"></i>
                                            <span>Seleccionar imagen de perfil</span>
                                        </label>
                                        <span class="etiqueta-archivo-seleccionado d-none">
                                            <span class="nombre-archivo-truncado"></span>
                                            <button type="button" class="btn-close boton-quitar-archivo" style="font-size: 0.55rem;" aria-label="Quitar archivo"></button>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-top d-flex justify-content-end">
                                <button type="submit" class="btn btn-institucional">
                                    Guardar cambios personales
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Pestaña 2: Cambio de contraseña y pregunta de seguridad -->
                    <div class="tab-pane fade <?php echo $seccionActiva === 'seguridad' ? 'show active' : ''; ?>"
                         id="panel-seguridad-cuenta"
                         role="tabpanel">
                        <div class="mb-4">
                            <h3 class="h5 fw-bold mb-3">Cambiar mi contraseña de acceso</h3>
                            <form action="<?php echo e(ruta_url('auth', 'cambiarClave')); ?>" method="POST" novalidate>
                                <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="clave_actual_perfil" class="form-label">Contraseña actual</label>
                                        <div class="input-group">
                                            <input type="password"
                                                   class="form-control"
                                                   id="clave_actual_perfil"
                                                   name="clave_actual"
                                                   placeholder="Escribe tu contraseña actual"
                                                   required>
                                            <button type="button"
                                                    class="btn btn-secondary"
                                                    data-alternar-clave="#clave_actual_perfil">
                                                Mostrar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="nueva_clave_perfil" class="form-label">Nueva contraseña</label>
                                        <div class="input-group">
                                            <input type="password"
                                                   class="form-control"
                                                   id="nueva_clave_perfil"
                                                   name="nueva_clave"
                                                   data-medidor-clave="si"
                                                   placeholder="Mínimo 6 caracteres"
                                                   required>
                                            <button type="button"
                                                    class="btn btn-secondary"
                                                    data-alternar-clave="#nueva_clave_perfil">
                                                Mostrar
                                            </button>
                                        </div>
                                        <div class="indicador-fortaleza mt-2">
                                            <div class="indicador-fortaleza-barra" id="barraFortalezaClave"></div>
                                        </div>
                                        <div class="small mt-1 texto-secundario" id="textoFortalezaClave">
                                            Escribe al menos 6 caracteres.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="confirmar_clave_perfil" class="form-label">Confirmar nueva contraseña</label>
                                        <div class="input-group">
                                            <input type="password"
                                                   class="form-control"
                                                   id="confirmar_clave_perfil"
                                                   name="confirmar_clave"
                                                   data-confirmar-clave="si"
                                                   placeholder="Repite la nueva contraseña"
                                                   required>
                                            <button type="button"
                                                    class="btn btn-secondary"
                                                    data-alternar-clave="#confirmar_clave_perfil">
                                                Mostrar
                                            </button>
                                        </div>
                                        <div class="small mt-1 texto-secundario" id="textoCoincidenciaClave"></div>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-institucional">
                                        Actualizar contraseña
                                    </button>
                                </div>
                            </form>
                        </div>

                        <hr class="my-3">

                        <div>
                            <h3 class="h5 fw-bold mb-1">Pregunta de seguridad para recuperación en red interna</h3>
                            <p class="small texto-secundario mb-3">
                                Esta pregunta te permite recuperar tu acceso de inmediato si olvidas tu contraseña o tu cuenta se bloquea.
                            </p>

                            <form action="<?php echo e(ruta_url('auth', 'actualizarSeguridad')); ?>" method="POST" novalidate>
                                <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label for="pregunta_seguridad_perfil" class="form-label">Pregunta de seguridad</label>
                                        <input type="text"
                                               class="form-control"
                                               id="pregunta_seguridad_perfil"
                                               name="pregunta_seguridad"
                                               list="listaPreguntasSugeridas"
                                               value="<?php echo e($usuario['pregunta_seguridad'] ?? ''); ?>"
                                               placeholder="Escribe o selecciona una pregunta secreta"
                                               required>
                                        <datalist id="listaPreguntasSugeridas">
                                            <option value="¿Nombre de primera mascota?"></option>
                                            <option value="¿Ciudad de nacimiento de su madre?"></option>
                                            <option value="¿Primer colegio donde estudió?"></option>
                                            <option value="¿Nombre de su abuelo?"></option>
                                            <option value="¿Equipo deportivo favorito?"></option>
                                        </datalist>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="respuesta_seguridad_perfil" class="form-label">Nueva respuesta secreta</label>
                                        <input type="text"
                                               class="form-control"
                                               id="respuesta_seguridad_perfil"
                                               name="respuesta_seguridad"
                                               placeholder="Escribe tu respuesta"
                                               required>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label for="clave_conf_seguridad" class="form-label">Confirma con tu contraseña actual</label>
                                        <input type="password"
                                               class="form-control"
                                               id="clave_conf_seguridad"
                                               name="clave_confirmacion"
                                               placeholder="Escribe tu contraseña actual"
                                               required>
                                    </div>
                                </div>

                                <div class="mt-3 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-secondary">
                                        Guardar pregunta y respuesta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Pestaña 3: Historial de actividad y accesos del propio usuario -->
                    <div class="tab-pane fade <?php echo $seccionActiva === 'actividad' ? 'show active' : ''; ?>"
                         id="panel-actividad-cuenta"
                         role="tabpanel">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h3 class="h5 fw-bold mb-0">Registros de acceso de mi cuenta</h3>
                            <div style="min-width: 240px;">
                                <input type="search"
                                       class="form-control"
                                       data-buscador-tabla="#tablaAccesosPropios"
                                       placeholder="Buscar por fecha, evento o dirección IP">
                            </div>
                        </div>

                        <div class="table-responsive border rounded mb-4">
                            <table class="table table-hover align-middle mb-0" id="tablaAccesosPropios" data-paginacion="true">
                                <thead>
                                    <tr>
                                        <th>Fecha y hora</th>
                                        <th>Evento</th>
                                        <th>Detalle</th>
                                        <th>Dirección IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($historialAccesos)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 texto-secundario">
                                                Aún no hay eventos registrados para tu cuenta.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($historialAccesos as $log): ?>
                                            <tr>
                                                <td class="small fw-medium">
                                                    <?php echo e(formatear_fecha_esp((string)$log['fecha_hora'])); ?>
                                                </td>
                                                <td>
                                                    <span class="<?php echo e(clase_insignia_estado((string)$log['tipo_evento'])); ?>">
                                                        <?php echo e(texto_estado_legible((string)$log['tipo_evento'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo e((string)$log['descripcion_accion']); ?></td>
                                                <td class="small texto-secundario"><span class="codigo-monoespaciado"><?php echo e((string)$log['direccion_ip']); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($historialCambios)): ?>
                            <h4 class="h6 fw-bold mb-2">Historial reciente de modificaciones en mi ficha</h4>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-hover align-middle mb-0" id="tablaCambiosPropios" data-paginacion="true">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Campo actualizado</th>
                                            <th>Motivo o detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historialCambios as $cambio): ?>
                                            <tr>
                                                <td class="small"><?php echo e(formatear_fecha_esp((string)$cambio['fecha_hora_cambio'])); ?></td>
                                                <td class="fw-semibold"><?php echo e(texto_estado_legible((string)$cambio['campo_modificado'])); ?></td>
                                                <td class="small texto-secundario"><?php echo e((string)($cambio['motivo_cambio'] ?: 'Actualización de registro')); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
