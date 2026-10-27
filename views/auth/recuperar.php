<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/auth_header.php';
$tokenCsrf = obtener_token_csrf();
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card">
            <div class="card-header py-3">
                <h2 class="h6 fw-bold mb-1">Recuperación de contraseña en red interna</h2>
                <p class="mb-0 small texto-secundario">
                    <?php if ($usuarioEnRecuperacion === null): ?>
                        Paso 1 de 2 — Identifica tu cuenta institucional
                    <?php else: ?>
                        Paso 2 de 2 — Responde tu pregunta de seguridad o solicita asistencia
                    <?php endif; ?>
                </p>
            </div>

            <div class="card-body p-4">
                <?php if ($usuarioEnRecuperacion === null): ?>
                    <form action="<?php echo e(ruta_url('auth', 'recuperar')); ?>" method="POST" novalidate>
                        <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                        <input type="hidden" name="etapa_recuperacion" value="identificar">

                        <div class="mb-3">
                            <label for="identificador_recuperar" class="form-label">
                                Usuario, correo institucional o número de cédula
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="identificador_recuperar"
                                   name="identificador"
                                   placeholder="Ejemplo: mrodriguez o V-19888999"
                                   required
                                   autofocus>
                        </div>

                        <div class="d-grid gap-2 mb-3">
                            <button type="submit" class="btn btn-institucional">
                                Buscar mi cuenta
                            </button>
                        </div>

                        <div class="text-center pt-2 border-top">
                            <a href="<?php echo e(ruta_url('auth', 'login')); ?>"
                               class="small fw-semibold text-decoration-none texto-secundario">
                                Volver al inicio de sesión
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <?php
                    $tienePregunta = !empty($usuarioEnRecuperacion['pregunta_seguridad']);
                    $bloqueoActual = $this->usuarioModel->evaluarBloqueoTemporal($usuarioEnRecuperacion);
                    ?>
                    <div class="p-3 rounded mb-3 border" style="background-color: var(--color-fondo-alterno);">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="fw-bold">
                                    <?php echo e($usuarioEnRecuperacion['nombres'] . ' ' . $usuarioEnRecuperacion['apellidos']); ?>
                                </div>
                                <div class="small texto-secundario">
                                    <?php echo e($usuarioEnRecuperacion['cargo_empleado']); ?> — <?php echo e($usuarioEnRecuperacion['nombre_departamento']); ?>
                                </div>
                            </div>
                            <a href="<?php echo e(ruta_url('auth', 'recuperar', ['reiniciar' => 1])); ?>"
                               class="btn btn-sm btn-outline-secondary">
                                Cambiar cuenta
                            </a>
                        </div>

                        <?php if ($bloqueoActual['bloqueado']): ?>
                            <div class="alert alert-warning mt-2 mb-0 py-2 small">
                                Tu cuenta tiene un bloqueo temporal activo. Al responder tu pregunta de seguridad se desbloqueará de inmediato.
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($tienePregunta): ?>
                        <form action="<?php echo e(ruta_url('auth', 'recuperar')); ?>" method="POST" novalidate class="mb-3">
                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                            <input type="hidden" name="etapa_recuperacion" value="validar_pregunta">

                            <div class="mb-3">
                                <label class="form-label texto-secundario">Pregunta de seguridad registrada</label>
                                <div class="p-2 px-3 rounded border fw-semibold">
                                    <?php echo e((string)$usuarioEnRecuperacion['pregunta_seguridad']); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="respuesta_seguridad" class="form-label">Tu respuesta de seguridad</label>
                                <input type="text"
                                       class="form-control"
                                       id="respuesta_seguridad"
                                       name="respuesta_seguridad"
                                       placeholder="Escribe aquí tu respuesta"
                                       required
                                       autofocus>
                            </div>

                            <div class="mb-3">
                                <label for="nueva_clave_rec" class="form-label">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control"
                                           id="nueva_clave_rec"
                                           name="nueva_clave"
                                           data-medidor-clave="si"
                                           placeholder="Mínimo 6 caracteres"
                                           required>
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            data-alternar-clave="#nueva_clave_rec">
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

                            <div class="mb-3">
                                <label for="confirmar_clave_rec" class="form-label">Confirmar nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control"
                                           id="confirmar_clave_rec"
                                           name="confirmar_clave"
                                           data-confirmar-clave="si"
                                           placeholder="Repite la nueva contraseña"
                                           required>
                                    <button type="button"
                                            class="btn btn-outline-secondary"
                                            data-alternar-clave="#confirmar_clave_rec">
                                        Mostrar
                                    </button>
                                </div>
                                <div class="small mt-1 texto-secundario" id="textoCoincidenciaClave"></div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-institucional">
                                    Guardar nueva contraseña y desbloquear cuenta
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-3 small">
                            Esta cuenta aún no tiene una pregunta de seguridad configurada. Solicita abajo el restablecimiento asistido por el equipo de soporte.
                        </div>
                    <?php endif; ?>

                    <div class="border-top pt-3">
                        <h3 class="small fw-bold mb-1">¿No recuerdas tu respuesta de seguridad?</h3>
                        <p class="small texto-secundario mb-2">
                            Puedes enviar una solicitud interna para que el Jefe de Soporte o el Administrador de Seguridad restablezca tu acceso.
                        </p>
                        <form action="<?php echo e(ruta_url('auth', 'recuperar')); ?>" method="POST">
                            <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                            <input type="hidden" name="etapa_recuperacion" value="solicitar_asistencia">
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                Solicitar restablecimiento asistido a soporte técnico
                            </button>
                        </form>
                    </div>

                    <div class="text-center pt-3 mt-3 border-top">
                        <a href="<?php echo e(ruta_url('auth', 'login')); ?>"
                           class="small fw-semibold text-decoration-none texto-secundario">
                            Cancelar y volver al inicio de sesión
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/auth_footer.php'; ?>
