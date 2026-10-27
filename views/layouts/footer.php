        </main>

        <footer class="pie-pagina-institucional d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div><?php echo e(APP_NOMBRE); ?> — <?php echo e(APP_INSTITUCION); ?></div>
            <div>Red interna institucional — <?php echo date('Y'); ?></div>
        </footer>
    </div>
</div>

<!-- Modal global de confirmación antes de acciones importantes -->
<div class="modal fade" id="modalConfirmacionInstitucional" tabindex="-1" aria-labelledby="tituloModalConfirmacion" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title h6 fw-bold mb-0" id="tituloModalConfirmacion">Confirmar acción</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar ventana"></button>
            </div>
            <div class="modal-body">
                <p id="textoModalConfirmacion" class="mb-0">¿Deseas continuar con esta acción?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-institucional btn-sm" id="botonConfirmarAccionModal">Sí, continuar</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo e(recurso_publico('assets/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo e(recurso_publico('js/app.js')); ?>"></script>
</body>
</html>
