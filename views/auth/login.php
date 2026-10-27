<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/auth_header.php';
$tokenCsrf = obtener_token_csrf();
?>

<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card mb-3">
            <div class="card-header text-center py-3">
                <h2 class="h5 fw-bold mb-1">Iniciar sesión</h2>
                <p class="small mb-0 texto-secundario">Ingresa tus credenciales para acceder al sistema</p>
            </div>

            <div class="card-body">
                <form action="<?php echo e(ruta_url('auth', 'login')); ?>" method="POST" autocomplete="on" novalidate>
                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                    <div class="mb-3">
                        <label for="identificador_acceso" class="form-label">Usuario, correo institucional o cédula</label>
                        <input type="text"
                               class="form-control"
                               id="identificador_acceso"
                               name="identificador"
                               placeholder="Ejemplo: mrodriguez o V-19888999"
                               required
                               autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="clave_acceso" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password"
                                   class="form-control"
                                   id="clave_acceso"
                                   name="clave"
                                   placeholder="Escribe tu contraseña"
                                   required>
                            <button type="button"
                                    class="btn btn-secondary"
                                    data-alternar-clave="#clave_acceso">
                                Mostrar
                            </button>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-institucional">
                            Ingresar al sistema
                        </button>
                    </div>

                    <div class="text-center pt-2 border-top">
                        <a href="<?php echo e(ruta_url('auth', 'recuperar')); ?>"
                           class="small fw-semibold text-decoration-none"
                           style="color: var(--color-primario);">
                            ¿Olvidaste tu contraseña o tu cuenta está bloqueada?
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel plegable de acceso rápido para pruebas de roles durante la defensa -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
                <span class="small fw-semibold">Perfiles de prueba por rol institucional</span>
                <button class="btn btn-sm btn-secondary"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#panelCuentasDemostracion"
                        aria-expanded="false"
                        aria-controls="panelCuentasDemostracion">
                    Ver cuentas
                </button>
            </div>
            <div class="collapse" id="panelCuentasDemostracion">
                <div class="card-body p-3">
                    <p class="small texto-secundario mb-2">
                        Selecciona cualquier perfil para completar los campos con la clave de prueba 123456:
                    </p>
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <button type="button"
                                    class="btn btn-sm btn-secondary w-100 text-start justify-content-start"
                                    data-llenar-usuario="superadmin"
                                    data-llenar-clave="123456">
                                Administrador de Seguridad
                            </button>
                        </div>
                        <div class="col-12 col-sm-6">
                            <button type="button"
                                    class="btn btn-sm btn-secondary w-100 text-start justify-content-start"
                                    data-llenar-usuario="jefesoporte"
                                    data-llenar-clave="123456">
                                Jefe de Soporte Técnico
                            </button>
                        </div>
                        <div class="col-12 col-sm-6">
                            <button type="button"
                                    class="btn btn-sm btn-secondary w-100 text-start justify-content-start"
                                    data-llenar-usuario="tecnico1"
                                    data-llenar-clave="123456">
                                Técnico Miguel Ángel
                            </button>
                        </div>
                        <div class="col-12 col-sm-6">
                            <button type="button"
                                    class="btn btn-sm btn-secondary w-100 text-start justify-content-start"
                                    data-llenar-usuario="tecnico2"
                                    data-llenar-clave="123456">
                                Técnica Laura Valentina
                            </button>
                        </div>
                        <div class="col-12 col-sm-6">
                            <button type="button"
                                    class="btn btn-sm btn-secondary w-100 text-start justify-content-start"
                                    data-llenar-usuario="mrodriguez"
                                    data-llenar-clave="123456">
                                Contabilidad · María Rodríguez
                            </button>
                        </div>
                        <div class="col-12 col-sm-6">
                            <button type="button"
                                    class="btn btn-sm btn-secondary w-100 text-start justify-content-start"
                                    data-llenar-usuario="jperez"
                                    data-llenar-clave="123456">
                                Recursos Humanos · Juan Pérez
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/auth_footer.php'; ?>
