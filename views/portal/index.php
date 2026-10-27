<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
$tokenCsrf = obtener_token_csrf();

$totalActivas = 0;
$totalPorConfirmar = 0;
$totalCerradas = 0;

foreach ($misSolicitudes as $solItem) {
    if ($solItem['estado_aceptacion_jefe'] === 'Rechazado') {
        $totalCerradas++;
    } elseif ($solItem['estado_columna_kanban'] === '5_Resuelto') {
        if ($solItem['confirmado_por_usuario'] === 'Pendiente') {
            $totalPorConfirmar++;
        } else {
            $totalCerradas++;
        }
    } else {
        $totalActivas++;
    }
}
?>

<!-- Resumen superior compacto para el usuario común -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small texto-secundario fw-medium">Solicitudes en atención</div>
                    <div class="h3 fw-bold my-1" style="color: var(--color-primario);"><?php echo $totalActivas; ?></div>
                    <div class="small texto-secundario">Atendidas por el equipo de soporte</div>
                </div>
                <span class="insignia-suave insignia-suave-media">En curso</span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="small texto-secundario fw-medium">Pendientes por tu confirmación</div>
                    <div class="h3 fw-bold my-1"><?php echo $totalPorConfirmar; ?></div>
                    <div class="small texto-secundario">Verifica si tu equipo ya funciona bien</div>
                </div>
                <span class="insignia-suave <?php echo $totalPorConfirmar > 0 ? 'insignia-suave-alta' : 'insignia-suave-neutra'; ?>">
                    <?php echo $totalPorConfirmar > 0 ? 'Por revisar' : 'Al día'; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-body py-3 d-flex flex-column justify-content-center">
                <div class="small texto-secundario fw-medium mb-2">¿Necesitas asistencia técnica en tu oficina?</div>
                <a href="#formularioNuevaSolicitud" class="btn btn-institucional w-100">
                    Reportar un problema nuevo
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($ticketSeleccionado !== null): ?>
    <?php
    $colActual = (string)$ticketSeleccionado['estado_columna_kanban'];
    $esRechazado = ($ticketSeleccionado['estado_aceptacion_jefe'] === 'Rechazado');

    // Cálculo de las 4 etapas horizontales: 1=Enviado, 2=Asignado, 3=En atención, 4=Resuelto
    $etapaNumero = match ($colActual) {
        '1_Por_Aceptar' => 1,
        '2_Asignado'    => 2,
        '3_En_Proceso', '4_En_Espera' => 3,
        '5_Resuelto'    => 4,
        default         => 1,
    };
    $porcentajeBarra = $esRechazado ? 100 : ($etapaNumero * 25);
    ?>

    <!-- Tarjeta principal de seguimiento con Línea de Progreso Horizontal Moderna -->
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="codigo-monoespaciado"><?php echo e($ticketSeleccionado['codigo_ticket']); ?></span>
                <span class="h5 fw-bold mb-0"><?php echo e($ticketSeleccionado['titulo_asunto']); ?></span>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="<?php echo e(clase_insignia_prioridad((string)$ticketSeleccionado['prioridad_oficial'])); ?>">
                    Prioridad <?php echo e(texto_estado_legible((string)$ticketSeleccionado['prioridad_oficial'])); ?>
                </span>
                <span class="small texto-secundario">
                    Enviado el <?php echo e(formatear_fecha_esp((string)$ticketSeleccionado['fecha_creacion'])); ?>
                </span>
            </div>
        </div>

        <div class="card-body">
            <?php if ($esRechazado): ?>
                <div class="alert alert-danger mb-3">
                    <div class="fw-bold mb-1">Esta solicitud fue marcada como no procedente por el Jefe de Soporte</div>
                    <div>Motivo indicado: <?php echo e($ticketSeleccionado['motivo_rechazo'] ?: 'Sin motivo detallado.'); ?></div>
                </div>
            <?php else: ?>
                <!-- Línea de progreso horizontal moderna con pasos numerados -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-semibold texto-secundario">Progreso de atención de tu solicitud</span>
                        <span class="<?php echo e(clase_insignia_estado($colActual)); ?>">
                            <?php
                            echo match ($etapaNumero) {
                                1 => 'Etapa 1 de 4 · Enviado',
                                2 => 'Etapa 2 de 4 · Asignado a especialista',
                                3 => $colActual === '4_En_Espera' ? 'Etapa 3 de 4 · En espera de repuesto o insumo' : 'Etapa 3 de 4 · En atención técnica',
                                4 => 'Etapa 4 de 4 · Resuelto',
                                default => 'Enviado',
                            };
                            ?>
                        </span>
                    </div>

                    <div class="progress" style="height: 6px; border-radius: 999px;" role="progressbar"
                         aria-label="Avance de la solicitud"
                         aria-valuenow="<?php echo $porcentajeBarra; ?>"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <div class="progress-bar"
                             style="width: <?php echo $porcentajeBarra; ?>%; background-color: <?php echo $etapaNumero === 4 ? '#16A34A' : 'var(--color-primario)'; ?>;">
                        </div>
                    </div>

                    <div class="pasos-progreso-solicitud">
                        <div class="paso-etapa-item <?php echo $etapaNumero === 1 ? 'actual' : ($etapaNumero > 1 ? 'completado' : ''); ?>">
                            <span class="paso-etapa-numero">1</span>
                            <div>
                                <div class="fw-semibold">Enviado</div>
                                <div class="small texto-secundario">Recibido en soporte</div>
                            </div>
                        </div>

                        <div class="paso-etapa-item <?php echo $etapaNumero === 2 ? 'actual' : ($etapaNumero > 2 ? 'completado' : ''); ?>">
                            <span class="paso-etapa-numero">2</span>
                            <div>
                                <div class="fw-semibold">Asignado</div>
                                <div class="small texto-secundario">Técnico designado</div>
                            </div>
                        </div>

                        <div class="paso-etapa-item <?php echo $etapaNumero === 3 ? 'actual' : ($etapaNumero > 3 ? 'completado' : ''); ?>">
                            <span class="paso-etapa-numero">3</span>
                            <div>
                                <div class="fw-semibold">En atención</div>
                                <div class="small texto-secundario">Revisión del equipo</div>
                            </div>
                        </div>

                        <div class="paso-etapa-item <?php echo $etapaNumero === 4 ? 'resuelto-final' : ''; ?>">
                            <span class="paso-etapa-numero">4</span>
                            <div>
                                <div class="fw-semibold">Resuelto</div>
                                <div class="small texto-secundario">Solución aplicada</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($colActual === '4_En_Espera' && !empty($ticketSeleccionado['motivo_pausa_espera'])): ?>
                <div class="alert alert-warning mb-3">
                    <div class="fw-semibold">Atención temporalmente en espera de insumo o gestión externa:</div>
                    <div><?php echo e((string)$ticketSeleccionado['motivo_pausa_espera']); ?></div>
                </div>
            <?php endif; ?>

            <!-- Bloque de confirmación del usuario cuando el técnico ya marcó el ticket como Resuelto -->
            <?php if ($colActual === '5_Resuelto' && $ticketSeleccionado['confirmado_por_usuario'] === 'Pendiente'): ?>
                <div class="alert alert-success mb-3">
                    <h3 class="h6 fw-bold mb-2">
                        El especialista indicó que tu solicitud ya fue resuelta. ¿Tu equipo funciona correctamente?
                    </h3>
                    <?php if (!empty($ticketSeleccionado['solucion_aplicada_tecnico'])): ?>
                        <div class="small mb-1">
                            <strong>Solución aplicada:</strong>
                            <?php echo e((string)$ticketSeleccionado['solucion_aplicada_tecnico']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ((int)$ticketSeleccionado['requirio_cambio_pieza'] === 1 && !empty($ticketSeleccionado['detalle_pieza_cambiada'])): ?>
                        <div class="small mb-2">
                            <strong>Componente reemplazado:</strong>
                            <?php echo e((string)$ticketSeleccionado['detalle_pieza_cambiada']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo e(ruta_url('portal', 'confirmar')); ?>" method="POST" class="mt-2">
                        <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                        <input type="hidden" name="id_ticket" value="<?php echo (int)$ticketSeleccionado['id_ticket']; ?>">

                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-6">
                                <input type="text"
                                       class="form-control form-control-sm"
                                       id="observacion_usuario_conf"
                                       name="observacion_usuario"
                                       placeholder="Comentario opcional o detalle si la falla continúa...">
                            </div>
                            <div class="col-12 col-md-6 d-flex flex-wrap gap-2">
                                <button type="submit" name="decision" value="confirmar" class="btn btn-sm btn-institucional">
                                    Sí, confirmar que funciona bien
                                </button>
                                <button type="submit" name="decision" value="reabrir" class="btn btn-sm btn-secondary">
                                    No, la falla continúa
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php elseif ($colActual === '5_Resuelto' && $ticketSeleccionado['confirmado_por_usuario'] === 'Confirmado_Conforme'): ?>
                <div class="alert alert-success mb-3">
                    Confirmaste que la solución aplicada fue satisfactoria. Solicitud cerrada el <?php echo e(formatear_fecha_esp($ticketSeleccionado['fecha_cierre_definitivo'] ?: $ticketSeleccionado['fecha_resolucion_tecnica'])); ?>.
                </div>
            <?php endif; ?>

            <!-- Ficha de detalles del caso y conversación pública (Sin estiramiento vertical) -->
            <div class="row g-3 fila-inicio-alineada">
                <div class="col-12 col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            Información del equipo y diagnóstico técnico
                        </div>
                        <div class="card-body small">
                            <div class="mb-2 pb-2 border-bottom">
                                <span class="texto-secundario d-block">Equipo reportado:</span>
                                <span class="fw-semibold">
                                    <?php echo e($ticketSeleccionado['marca_equipo'] . ' ' . $ticketSeleccionado['modelo_equipo']); ?>
                                </span>
                                · <span class="codigo-monoespaciado"><?php echo e($ticketSeleccionado['codigo_bien']); ?></span>
                            </div>

                            <div class="mb-2 pb-2 border-bottom">
                                <span class="texto-secundario d-block">Categoría del problema:</span>
                                <span class="fw-semibold">
                                    <?php echo e(texto_estado_legible((string)$ticketSeleccionado['rama_general']) . ' · ' . $ticketSeleccionado['subcategoria']); ?>
                                </span>
                            </div>

                            <div class="mb-2 pb-2 border-bottom">
                                <span class="texto-secundario d-block">Especialista asignado:</span>
                                <span class="fw-semibold">
                                    <?php echo e($ticketSeleccionado['nombre_tecnico_principal'] ?: 'Pendiente por asignar'); ?>
                                </span>
                                <?php if (!empty($ticketSeleccionado['extension_tecnico_principal'])): ?>
                                    <span class="texto-secundario d-block">
                                        Extensión de contacto: <?php echo e($ticketSeleccionado['extension_tecnico_principal']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-2">
                                <span class="texto-secundario d-block">Descripción que enviaste:</span>
                                <div class="mt-1"><?php echo nl2br(e((string)$ticketSeleccionado['descripcion_problematica'])); ?></div>
                            </div>

                            <?php if (!empty($ticketSeleccionado['diagnostico_real_tecnico'])): ?>
                                <div class="mt-2 pt-2 border-top">
                                    <span class="texto-secundario d-block">Diagnóstico técnico registrado:</span>
                                    <div class="fw-semibold mt-1"><?php echo nl2br(e((string)$ticketSeleccionado['diagnostico_real_tecnico'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ticketSeleccionado['solucion_aplicada_tecnico'])): ?>
                                <div class="mt-2 pt-2 border-top">
                                    <span class="texto-secundario d-block">Solución técnica aplicada:</span>
                                    <div class="fw-semibold mt-1"><?php echo nl2br(e((string)$ticketSeleccionado['solucion_aplicada_tecnico'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($archivosAdjuntos)): ?>
                                <div class="mt-2 pt-2 border-top">
                                    <span class="texto-secundario d-block mb-1">Archivos adjuntos en esta solicitud:</span>
                                    <div class="d-flex flex-column gap-1">
                                        <?php foreach ($archivosAdjuntos as $adj): ?>
                                            <a href="<?php echo e(recurso_publico((string)$adj['ruta_almacenamiento'])); ?>"
                                               target="_blank"
                                               rel="noopener"
                                               class="fw-semibold text-decoration-none d-inline-flex align-items-center gap-1"
                                               style="color: var(--color-primario);">
                                                <i class="ni ni-paperclip" aria-hidden="true"></i>
                                                <span><?php echo e($adj['nombre_archivo_original']); ?> · <?php echo (int)$adj['peso_archivo_kb']; ?> KB</span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna de conversación pública con caja de chat controlada -->
                <div class="col-12 col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Mensajes con el equipo de soporte técnico</span>
                            <span class="insignia-suave insignia-suave-neutra"><?php echo count($comentariosPublicos); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <div class="contenedor-chat-bitacora border-0 rounded-0">
                                <div class="lista-mensajes-chat">
                                    <?php if (empty($comentariosPublicos)): ?>
                                        <div class="p-4 text-center texto-secundario small my-auto">
                                            Aún no hay mensajes en esta solicitud. Si deseas agregar algún dato adicional para el especialista, escríbelo abajo.
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($comentariosPublicos as $com): ?>
                                            <div class="burbuja-comentario-chat">
                                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                                    <span class="small fw-semibold">
                                                        <?php echo e($com['nombre_autor']); ?> · <span class="texto-secundario fw-normal"><?php echo e($com['nombre_rol']); ?></span>
                                                    </span>
                                                    <span class="texto-secundario" style="font-size: 0.74rem;">
                                                        <?php echo e(formatear_fecha_esp((string)$com['fecha_comentario'])); ?>
                                                    </span>
                                                </div>
                                                <div class="small"><?php echo nl2br(e((string)$com['mensaje_texto'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <form action="<?php echo e(ruta_url('portal', 'comentar')); ?>"
                                      method="POST"
                                      enctype="multipart/form-data"
                                      class="compositor-chat-inferior">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                    <input type="hidden" name="id_ticket" value="<?php echo (int)$ticketSeleccionado['id_ticket']; ?>">

                                    <div class="mb-2">
                                        <textarea class="form-control"
                                                  id="mensaje_nuevo_portal"
                                                  name="mensaje_texto"
                                                  rows="2"
                                                  placeholder="Escribe aquí cualquier información adicional sobre el problema..."></textarea>
                                    </div>

                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div class="contenedor-archivo-estilizado">
                                            <input type="file"
                                                   class="input-archivo-oculto"
                                                   id="adjunto_comentario_portal"
                                                   name="adjunto_comentario"
                                                   accept=".png,.jpg,.jpeg,.webp,.pdf">
                                            <label for="adjunto_comentario_portal" class="boton-adjuntar-archivo">
                                                <i class="ni ni-paperclip" aria-hidden="true"></i>
                                                <span>Adjuntar imagen o documento</span>
                                            </label>
                                            <span class="etiqueta-archivo-seleccionado d-none">
                                                <span class="nombre-archivo-truncado"></span>
                                                <button type="button" class="btn-close boton-quitar-archivo" style="font-size: 0.55rem;" aria-label="Quitar archivo"></button>
                                            </span>
                                        </div>

                                        <button type="submit" class="btn btn-sm btn-institucional ms-auto">
                                            Enviar mensaje
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 fila-inicio-alineada">
    <!-- Columna izquierda: Formulario limpio para reportar un nuevo problema -->
    <div class="col-12 col-xl-5" id="formularioNuevaSolicitud">
        <div class="card">
            <div class="card-header">
                <h3 class="h5 fw-bold mb-0">Reportar un problema en mi oficina</h3>
            </div>
            <div class="card-body">
                <?php if (empty($equiposOficina)): ?>
                    <div class="alert alert-warning mb-0">
                        Tu oficina aún no tiene equipos registrados en el inventario. Comunícate con el Jefe de Soporte para asociar tu equipo.
                    </div>
                <?php else: ?>
                    <form action="<?php echo e(ruta_url('portal', 'crear')); ?>"
                          method="POST"
                          enctype="multipart/form-data"
                          novalidate>
                        <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">

                        <div class="mb-3">
                            <label for="id_equipo_portal" class="form-label">1. ¿Qué equipo de tu oficina presenta la falla?</label>
                            <select class="form-select"
                                    id="id_equipo_portal"
                                    name="id_equipo"
                                    data-autocompletable="true"
                                    data-placeholder="Buscar equipo por código de bien, marca o modelo..."
                                    required>
                                <option value="">Selecciona el equipo afectado</option>
                                <?php foreach ($equiposOficina as $eq): ?>
                                    <?php
                                    $tipoUso = ((int)($eq['id_usuario_asignado'] ?? 0) === (int)$_SESSION['id_usuario'])
                                        ? 'Asignado a ti'
                                        : ((int)$eq['es_equipo_compartido'] === 1 ? 'Equipo compartido de la oficina' : 'Equipo del departamento');
                                    ?>
                                    <option value="<?php echo (int)$eq['id_equipo']; ?>"
                                            data-titulo="<?php echo e($eq['codigo_bien'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']); ?>"
                                            data-subtexto="<?php echo e(texto_estado_legible((string)$eq['categoria_equipo']) . ' · ' . $tipoUso); ?>">
                                        <?php echo e($eq['codigo_bien'] . ' · ' . $eq['marca'] . ' ' . $eq['modelo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="id_categoria_portal" class="form-label">2. ¿Qué tipo de falla ocurre?</label>
                            <select class="form-select"
                                    id="id_categoria_portal"
                                    name="id_categoria_falla"
                                    data-autocompletable="true"
                                    data-placeholder="Buscar categoría de problema..."
                                    required>
                                <option value="">Selecciona la categoría del problema</option>
                                <?php foreach ($categoriasFalla as $cat): ?>
                                    <option value="<?php echo (int)$cat['id_categoria_falla']; ?>"
                                            data-minutos="<?php echo (int)$cat['tiempo_estimado_minutos']; ?>"
                                            data-titulo="<?php echo e($cat['subcategoria']); ?>"
                                            data-subtexto="<?php echo e(texto_estado_legible((string)$cat['rama_general']) . ' · Tiempo estimado: ' . (int)$cat['tiempo_estimado_minutos'] . ' min'); ?>">
                                        <?php echo e(texto_estado_legible((string)$cat['rama_general']) . ' · ' . $cat['subcategoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="small texto-secundario mt-1" id="avisoTiempoEstimadoCat">
                                Selecciona una categoría para conocer el tiempo estimado de respuesta.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="titulo_asunto_portal" class="form-label">3. Resumen corto de lo que sucede</label>
                            <input type="text"
                                   class="form-control"
                                   id="titulo_asunto_portal"
                                   name="titulo_asunto"
                                   maxlength="120"
                                   placeholder="Ejemplo: La impresora no imprime por red o el sistema contable se cierra"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion_portal" class="form-label">4. Explica con tus palabras el problema</label>
                            <textarea class="form-control"
                                      id="descripcion_portal"
                                      name="descripcion_problematica"
                                      rows="3"
                                      placeholder="Indica desde cuándo ocurre y si aparece algún aviso en pantalla"
                                      required></textarea>
                        </div>

                        <div class="row g-3 mb-3 align-items-end">
                            <div class="col-12 col-sm-6">
                                <label for="prioridad_sugerida_portal" class="form-label">Urgencia que consideras</label>
                                <select class="form-select" id="prioridad_sugerida_portal" name="prioridad_sugerida">
                                    <option value="Baja">Baja · Puedo seguir trabajando</option>
                                    <option value="Media" selected>Media · Dificulta mis tareas</option>
                                    <option value="Alta">Alta · Impide trabajar del todo</option>
                                </select>
                            </div>

                            <div class="col-12 col-sm-6">
                                <label class="form-label d-block">Evidencia opcional</label>
                                <div class="contenedor-archivo-estilizado">
                                    <input type="file"
                                           class="input-archivo-oculto"
                                           id="adjunto_solicitud"
                                           name="adjunto_solicitud"
                                           accept=".png,.jpg,.jpeg,.webp,.pdf">
                                    <label for="adjunto_solicitud" class="boton-adjuntar-archivo">
                                        <i class="ni ni-paperclip" aria-hidden="true"></i>
                                        <span>Adjuntar imagen o documento</span>
                                    </label>
                                    <span class="etiqueta-archivo-seleccionado d-none">
                                        <span class="nombre-archivo-truncado"></span>
                                        <button type="button" class="btn-close boton-quitar-archivo" style="font-size: 0.55rem;" aria-label="Quitar archivo"></button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid pt-2 border-top">
                            <button type="submit" class="btn btn-institucional">
                                Enviar solicitud a soporte técnico
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Columna derecha: Listado de todas las solicitudes del usuario con filtro rápido (Altura independiente) -->
    <div class="col-12 col-xl-7">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="h5 fw-bold mb-0">Historial de mis solicitudes</h3>
                <div style="min-width: 220px;">
                    <input type="search"
                           class="form-control form-control-sm"
                           data-buscador-tabla="#tablaMisSolicitudesPortal"
                           placeholder="Buscar por código, equipo o asunto...">
                </div>
            </div>

            <div class="card-body p-0">
                <?php if (empty($misSolicitudes)): ?>
                    <div class="p-4 text-center texto-secundario small">
                        Aún no has enviado solicitudes de soporte técnico. Usa el formulario de la izquierda cuando necesites asistencia con un equipo de tu oficina.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaMisSolicitudesPortal" data-paginacion="true">
                            <thead>
                                <tr>
                                    <th>Código y fecha</th>
                                    <th>Equipo y asunto</th>
                                    <th>Etapa de avance</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($misSolicitudes as $item): ?>
                                    <?php
                                    $esSeleccionado = ($ticketSeleccionado !== null && (int)$ticketSeleccionado['id_ticket'] === (int)$item['id_ticket']);
                                    $colItem = (string)$item['estado_columna_kanban'];
                                    $rechazadoItem = ($item['estado_aceptacion_jefe'] === 'Rechazado');
                                    $etapaItem = match ($colItem) {
                                        '1_Por_Aceptar' => 'Enviado',
                                        '2_Asignado'    => 'Asignado',
                                        '3_En_Proceso', '4_En_Espera' => 'En atención',
                                        '5_Resuelto'    => 'Resuelto',
                                        default         => 'Enviado',
                                    };
                                    ?>
                                    <tr class="<?php echo $esSeleccionado ? 'table-active' : ''; ?>">
                                        <td>
                                            <div><span class="codigo-monoespaciado"><?php echo e($item['codigo_ticket']); ?></span></div>
                                            <div class="small texto-secundario mt-1">
                                                <?php echo e(formatear_fecha_esp((string)$item['fecha_creacion'], false)); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo e($item['titulo_asunto']); ?></div>
                                            <div class="small texto-secundario">
                                                <?php echo e($item['codigo_bien'] . ' · ' . $item['marca_equipo'] . ' ' . $item['modelo_equipo']); ?>
                                            </div>
                                            <?php if (!empty($item['nombre_tecnico'])): ?>
                                                <div class="small texto-secundario">
                                                    Técnico: <?php echo e($item['nombre_tecnico']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($rechazadoItem): ?>
                                                <span class="insignia-suave insignia-suave-critica">No procedente</span>
                                            <?php elseif ($colItem === '5_Resuelto' && $item['confirmado_por_usuario'] === 'Pendiente'): ?>
                                                <span class="insignia-suave insignia-suave-alta">Resuelto · Por confirmar</span>
                                            <?php elseif ($colItem === '5_Resuelto'): ?>
                                                <span class="insignia-suave insignia-suave-baja">Resuelto y confirmado</span>
                                            <?php elseif ($colItem === '4_En_Espera'): ?>
                                                <span class="insignia-suave insignia-suave-alta">En espera de insumo</span>
                                            <?php else: ?>
                                                <span class="insignia-suave insignia-suave-media"><?php echo e($etapaItem); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo e(ruta_url('portal', 'index', ['ver' => (int)$item['id_ticket']])); ?>"
                                               class="btn btn-sm <?php echo $esSeleccionado ? 'btn-institucional' : 'btn-secondary'; ?>">
                                                Ver avance
                                            </a>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var selectorCategoria = document.getElementById('id_categoria_portal');
    var avisoTiempo = document.getElementById('avisoTiempoEstimadoCat');
    if (selectorCategoria && avisoTiempo) {
        selectorCategoria.addEventListener('change', function () {
            var opcion = selectorCategoria.options[selectorCategoria.selectedIndex];
            var minutos = opcion ? opcion.getAttribute('data-minutos') : null;
            if (minutos) {
                avisoTiempo.textContent = 'Tiempo estimado de atención para esta falla: ' + minutos + ' minutos.';
                avisoTiempo.className = 'small mt-1 fw-semibold';
            } else {
                avisoTiempo.textContent = 'Selecciona una categoría para conocer el tiempo estimado de respuesta.';
                avisoTiempo.className = 'small mt-1 texto-secundario';
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
