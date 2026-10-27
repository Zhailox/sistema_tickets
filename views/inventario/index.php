<?php
declare(strict_types=1);

require __DIR__ . '/../layouts/header.php';
$tokenCsrf = obtener_token_csrf();

$totalDeptosActivos = 0;
foreach ($departamentos as $d) {
    if ((int)$d['activo'] === 1) {
        $totalDeptosActivos++;
    }
}

$totalEqOperativos = 0;
$totalEqAtencion   = 0;
foreach ($equipos as $eItem) {
    if ($eItem['estado_operativo'] === 'Operativo') {
        $totalEqOperativos++;
    } elseif (in_array($eItem['estado_operativo'], ['En_Reparacion', 'Danado'], true)) {
        $totalEqAtencion++;
    }
}
?>

<!-- Indicadores rápidos del inventario -->
<div class="row g-3 mb-3">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Departamentos activos</div>
                <div class="h3 fw-bold mt-1 mb-0"><?php echo $totalDeptosActivos; ?></div>
                <div class="small texto-secundario"><?php echo count($departamentos); ?> registrados en total</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Equipos en la consulta</div>
                <div class="h3 fw-bold mt-1 mb-0" style="color: var(--color-primario);"><?php echo count($equipos); ?></div>
                <div class="small texto-secundario">Bienes informáticos institucionales</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">Equipos operativos</div>
                <div class="h3 fw-bold mt-1 mb-0"><?php echo $totalEqOperativos; ?></div>
                <div class="small texto-secundario">En funcionamiento normal</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body py-3">
                <div class="small texto-secundario fw-medium">En reparación o dañados</div>
                <div class="h3 fw-bold mt-1 mb-0" style="color: var(--color-primario);"><?php echo $totalEqAtencion; ?></div>
                <div class="small texto-secundario">Requieren seguimiento técnico</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Hoja de vida e historial de fallas del equipo seleccionado -->
<?php if ($equipoHojaVida !== null): ?>
    <div class="modal fade" id="modalHojaVidaEquipo" tabindex="-1" aria-labelledby="tituloModalHojaVida" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="codigo-monoespaciado"><?php echo e($equipoHojaVida['codigo_bien']); ?></span>
                        <h3 class="modal-title h5 fw-bold mb-0" id="tituloModalHojaVida">
                            Hoja de vida técnica · <?php echo e($equipoHojaVida['marca'] . ' ' . $equipoHojaVida['modelo']); ?>
                        </h3>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="<?php echo e(clase_insignia_estado((string)$equipoHojaVida['estado_operativo'])); ?>">
                            <?php echo e(texto_estado_legible((string)$equipoHojaVida['estado_operativo'])); ?>
                        </span>
                        <a href="<?php echo e(ruta_url('inventario', 'index')); ?>" class="btn btn-sm btn-secondary">
                            Cerrar hoja de vida
                        </a>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3 p-3 rounded border" style="background-color: var(--color-fondo-sutil);">
                        <div class="col-12 col-md-4">
                            <span class="small texto-secundario d-block">Categoría y serie:</span>
                            <span class="fw-semibold">
                                <?php echo e(texto_estado_legible((string)$equipoHojaVida['categoria_equipo']) . ' · ' . $equipoHojaVida['numero_serie']); ?>
                            </span>
                        </div>
                        <div class="col-12 col-md-4">
                            <span class="small texto-secundario d-block">Ubicación asignada:</span>
                            <span class="fw-semibold">
                                <?php echo e($equipoHojaVida['nombre_departamento'] . ' · ' . $equipoHojaVida['numero_oficina']); ?>
                            </span>
                        </div>
                        <div class="col-12 col-md-4">
                            <span class="small texto-secundario d-block">Responsable o modalidad:</span>
                            <span class="fw-semibold">
                                <?php echo !empty($equipoHojaVida['nombre_usuario_asignado']) ? e($equipoHojaVida['nombre_usuario_asignado']) : 'Equipo compartido del área'; ?>
                            </span>
                        </div>
                    </div>

                    <h4 class="h6 fw-bold mb-2">Historial de fallas e intervenciones técnicas registradas</h4>
                    <?php if (empty($historialEquipo)): ?>
                        <div class="p-4 text-center texto-secundario small border rounded">
                            Este equipo no presenta reportes de fallas en el historial.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover align-middle mb-0" id="tablaHojaVidaEquipoInv" data-paginacion="true">
                                <thead>
                                    <tr>
                                        <th>Solicitud</th>
                                        <th>Fecha</th>
                                        <th>Problema reportado</th>
                                        <th>Diagnóstico y solución técnica</th>
                                        <th>Pieza reemplazada</th>
                                        <th>Técnico</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historialEquipo as $hItem): ?>
                                        <tr>
                                            <td><span class="codigo-monoespaciado"><?php echo e($hItem['codigo_ticket']); ?></span></td>
                                            <td class="small"><?php echo e(formatear_fecha_esp((string)$hItem['fecha_creacion'], false)); ?></td>
                                            <td>
                                                <div class="fw-semibold"><?php echo e($hItem['titulo_asunto']); ?></div>
                                                <div class="small texto-secundario"><?php echo e($hItem['subcategoria']); ?></div>
                                            </td>
                                            <td class="small">
                                                <div><strong>Diagnóstico:</strong> <?php echo e($hItem['diagnostico_real_tecnico'] ?: 'En revisión'); ?></div>
                                                <div><strong>Solución:</strong> <?php echo e($hItem['solucion_aplicada_tecnico'] ?: 'En proceso'); ?></div>
                                            </td>
                                            <td class="small">
                                                <?php if ((int)$hItem['requirio_cambio_pieza'] === 1): ?>
                                                    <span class="insignia-suave insignia-suave-alta"><?php echo e($hItem['detalle_pieza_cambiada'] ?: 'Sí'); ?></span>
                                                <?php else: ?>
                                                    <span class="texto-secundario">Sin cambio de pieza</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small fw-semibold"><?php echo e($hItem['nombre_tecnico'] ?: 'Sin asignar'); ?></td>
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
<?php endif; ?>

<div class="card">
    <div class="card-header pb-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'equipos' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-inv-equipos"
                        type="button"
                        role="tab">
                    Inventario de equipos informáticos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'departamentos' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-inv-departamentos"
                        type="button"
                        role="tab">
                    Departamentos y oficinas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $pestanaActiva === 'categorias' ? 'active' : ''; ?>"
                        data-bs-toggle="tab"
                        data-bs-target="#tab-inv-categorias"
                        type="button"
                        role="tab">
                    Categorías de falla y tiempos meta
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">
            <!-- Pestaña 1: Gestión de Equipos -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'equipos' ? 'show active' : ''; ?>"
                 id="tab-inv-equipos"
                 role="tabpanel">
                <div class="row g-3 fila-inicio-alineada">
                    <div class="col-12 col-xl-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>
                                    <?php echo $equipoEdicion ? 'Editar equipo ' . e($equipoEdicion['codigo_bien']) : 'Registrar nuevo equipo'; ?>
                                </span>
                                <?php if ($equipoEdicion): ?>
                                    <a href="<?php echo e(ruta_url('inventario', 'index')); ?>" class="btn btn-sm btn-secondary">
                                        Nuevo
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <form action="<?php echo e(ruta_url('inventario', 'guardarEquipo')); ?>" method="POST">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                    <input type="hidden" name="id_equipo" value="<?php echo (int)($equipoEdicion['id_equipo'] ?? 0); ?>">

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label for="codigo_bien_eq" class="form-label">Código de bien</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="codigo_bien_eq"
                                                   name="codigo_bien"
                                                   value="<?php echo e($equipoEdicion['codigo_bien'] ?? ''); ?>"
                                                   placeholder="INV-2026-005"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="numero_serie_eq" class="form-label">Número de serie</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="numero_serie_eq"
                                                   name="numero_serie"
                                                   value="<?php echo e($equipoEdicion['numero_serie'] ?? ''); ?>"
                                                   placeholder="SN-HP00192"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="categoria_equipo_eq" class="form-label">Categoría</label>
                                            <select class="form-select" id="categoria_equipo_eq" name="categoria_equipo" required>
                                                <?php
                                                $catEqOpciones = [
                                                    'PC_Escritorio' => 'Computadora de escritorio',
                                                    'Laptop'        => 'Computadora portátil',
                                                    'Impresora'     => 'Impresora',
                                                    'Router_Switch' => 'Conmutador o enrutador de red',
                                                    'Servidor'      => 'Servidor',
                                                    'Periferico'    => 'Periférico'
                                                ];
                                                $catEqActual = $equipoEdicion['categoria_equipo'] ?? 'PC_Escritorio';
                                                ?>
                                                <?php foreach ($catEqOpciones as $valCat => $txtCat): ?>
                                                    <option value="<?php echo $valCat; ?>" <?php echo $catEqActual === $valCat ? 'selected' : ''; ?>>
                                                        <?php echo e($txtCat); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-6">
                                            <label for="estado_operativo_eq" class="form-label">Estado operativo</label>
                                            <select class="form-select" id="estado_operativo_eq" name="estado_operativo">
                                                <?php
                                                $estEqActual = $equipoEdicion['estado_operativo'] ?? 'Operativo';
                                                foreach (['Operativo' => 'Operativo', 'En_Reparacion' => 'En reparación', 'Danado' => 'Dañado', 'Desincorporado' => 'Desincorporado'] as $vEst => $tEst):
                                                ?>
                                                    <option value="<?php echo $vEst; ?>" <?php echo $estEqActual === $vEst ? 'selected' : ''; ?>>
                                                        <?php echo e($tEst); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-6">
                                            <label for="marca_eq" class="form-label">Marca</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="marca_eq"
                                                   name="marca"
                                                   value="<?php echo e($equipoEdicion['marca'] ?? ''); ?>"
                                                   placeholder="HP, Dell, Epson"
                                                   required>
                                        </div>

                                        <div class="col-6">
                                            <label for="modelo_eq" class="form-label">Modelo</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="modelo_eq"
                                                   name="modelo"
                                                   value="<?php echo e($equipoEdicion['modelo'] ?? ''); ?>"
                                                   placeholder="ProDesk 400 G7"
                                                   required>
                                        </div>

                                        <div class="col-12">
                                            <label for="id_departamento_eq" class="form-label">Departamento donde se ubica</label>
                                            <select class="form-select"
                                                    id="id_departamento_eq"
                                                    name="id_departamento"
                                                    data-autocompletable="true"
                                                    data-placeholder="Buscar departamento u oficina..."
                                                    required>
                                                <option value="">Selecciona el departamento</option>
                                                <?php foreach ($departamentos as $depItem): ?>
                                                    <option value="<?php echo (int)$depItem['id_departamento']; ?>"
                                                            data-titulo="<?php echo e($depItem['nombre_departamento']); ?>"
                                                            data-subtexto="<?php echo e($depItem['codigo_departamento'] . ' · ' . $depItem['sede_edificio'] . ' · ' . $depItem['numero_oficina']); ?>"
                                                            <?php echo (int)($equipoEdicion['id_departamento'] ?? 0) === (int)$depItem['id_departamento'] ? 'selected' : ''; ?>>
                                                        <?php echo e($depItem['nombre_departamento'] . ' · ' . $depItem['numero_oficina']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-12">
                                            <div class="form-check my-1">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       id="es_equipo_compartido_eq"
                                                       name="es_equipo_compartido"
                                                       value="1"
                                                       <?php echo (int)($equipoEdicion['es_equipo_compartido'] ?? 0) === 1 ? 'checked' : ''; ?>>
                                                <label class="form-check-label small fw-semibold" for="es_equipo_compartido_eq">
                                                    Es un equipo de uso compartido en la oficina
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="id_usuario_asignado_eq" class="form-label">Custodio o funcionario responsable</label>
                                            <select class="form-select"
                                                    id="id_usuario_asignado_eq"
                                                    name="id_usuario_asignado"
                                                    data-autocompletable="true"
                                                    data-placeholder="Buscar funcionario por nombre, cédula o departamento...">
                                                <option value="" data-titulo="Sin funcionario individual asignado" data-subtexto="Equipo general del departamento">Sin funcionario individual asignado</option>
                                                <?php foreach ($usuariosActivos as $usuItem): ?>
                                                    <option value="<?php echo (int)$usuItem['id_usuario']; ?>"
                                                            data-depto="<?php echo (int)$usuItem['id_departamento']; ?>"
                                                            data-titulo="<?php echo e($usuItem['nombres'] . ' ' . $usuItem['apellidos'] . ' · ' . ($usuItem['cedula_empleado'] ?? '')); ?>"
                                                            data-subtexto="<?php echo e(($usuItem['cargo_empleado'] ?? 'Funcionario') . ' · ' . $usuItem['nombre_departamento']); ?>"
                                                            <?php echo (int)($equipoEdicion['id_usuario_asignado'] ?? 0) === (int)$usuItem['id_usuario'] ? 'selected' : ''; ?>>
                                                        <?php echo e($usuItem['nombres'] . ' ' . $usuItem['apellidos']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-6">
                                            <label for="procesador_eq" class="form-label">Procesador</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="procesador_eq"
                                                   name="procesador_cpu"
                                                   value="<?php echo e($equipoEdicion['procesador_cpu'] ?? ''); ?>"
                                                   placeholder="Core i5">
                                        </div>

                                        <div class="col-6">
                                            <label for="ram_eq" class="form-label">Memoria RAM en GB</label>
                                            <input type="number"
                                                   class="form-control"
                                                   id="ram_eq"
                                                   name="memoria_ram_gb"
                                                   min="0"
                                                   value="<?php echo e($equipoEdicion['memoria_ram_gb'] ?? ''); ?>">
                                        </div>

                                        <div class="col-6">
                                            <label for="tipo_alm_eq" class="form-label">Tipo de disco</label>
                                            <select class="form-select" id="tipo_alm_eq" name="tipo_almacenamiento">
                                                <?php $tAlm = $equipoEdicion['tipo_almacenamiento'] ?? 'No_Aplica'; ?>
                                                <option value="No_Aplica" <?php echo $tAlm === 'No_Aplica' ? 'selected' : ''; ?>>No aplica</option>
                                                <option value="SSD" <?php echo $tAlm === 'SSD' ? 'selected' : ''; ?>>Unidad sólida SSD</option>
                                                <option value="NVMe" <?php echo $tAlm === 'NVMe' ? 'selected' : ''; ?>>Unidad rápida NVMe</option>
                                                <option value="HDD" <?php echo $tAlm === 'HDD' ? 'selected' : ''; ?>>Disco mecánico HDD</option>
                                            </select>
                                        </div>

                                        <div class="col-6">
                                            <label for="cap_alm_eq" class="form-label">Capacidad en GB</label>
                                            <input type="number"
                                                   class="form-control"
                                                   id="cap_alm_eq"
                                                   name="capacidad_almacenamiento_gb"
                                                   min="0"
                                                   value="<?php echo e($equipoEdicion['capacidad_almacenamiento_gb'] ?? ''); ?>">
                                        </div>

                                        <div class="col-6">
                                            <label for="so_eq" class="form-label">Sistema operativo</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="so_eq"
                                                   name="sistema_operativo"
                                                   value="<?php echo e($equipoEdicion['sistema_operativo'] ?? ''); ?>"
                                                   placeholder="Windows 11 Pro">
                                        </div>

                                        <div class="col-6">
                                            <label for="ip_eq" class="form-label">Dirección IP fija</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="ip_eq"
                                                   name="direccion_ip_fija"
                                                   value="<?php echo e($equipoEdicion['direccion_ip_fija'] ?? ''); ?>"
                                                   placeholder="192.168.1.20">
                                        </div>

                                        <div class="col-12">
                                            <label for="mac_eq" class="form-label">Dirección física MAC</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="mac_eq"
                                                   name="direccion_mac"
                                                   value="<?php echo e($equipoEdicion['direccion_mac'] ?? ''); ?>"
                                                   placeholder="00:1A:2B:3C:4D:5E">
                                        </div>
                                    </div>

                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-institucional">
                                            <?php echo $equipoEdicion ? 'Actualizar ficha del equipo' : 'Guardar nuevo equipo'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-8">
                        <div class="barra-filtros-compacta mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <form action="<?php echo e(ruta_url('inventario', 'index')); ?>" method="GET" class="d-flex flex-wrap gap-2 m-0">
                                    <input type="hidden" name="c" value="inventario">
                                    <input type="hidden" name="a" value="index">

                                    <select name="depto" class="form-select form-select-sm" style="min-width: 185px;">
                                        <option value="0">Todos los departamentos</option>
                                        <?php foreach ($departamentos as $depF): ?>
                                            <option value="<?php echo (int)$depF['id_departamento']; ?>"
                                                    <?php echo $filtroDepto === (int)$depF['id_departamento'] ? 'selected' : ''; ?>>
                                                <?php echo e($depF['nombre_departamento']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select name="estado" class="form-select form-select-sm" style="min-width: 150px;">
                                        <option value="">Cualquier estado</option>
                                        <option value="Operativo" <?php echo $filtroEstado === 'Operativo' ? 'selected' : ''; ?>>Operativo</option>
                                        <option value="En_Reparacion" <?php echo $filtroEstado === 'En_Reparacion' ? 'selected' : ''; ?>>En reparación</option>
                                        <option value="Danado" <?php echo $filtroEstado === 'Danado' ? 'selected' : ''; ?>>Dañado</option>
                                        <option value="Desincorporado" <?php echo $filtroEstado === 'Desincorporado' ? 'selected' : ''; ?>>Desincorporado</option>
                                    </select>

                                    <button type="submit" class="btn btn-sm btn-institucional">Filtrar</button>
                                </form>

                                <div style="min-width: 220px; flex: 1 1 220px; max-width: 300px;">
                                    <input type="search"
                                           class="form-control form-control-sm"
                                           data-buscador-tabla="#tablaEquiposInventario"
                                           placeholder="Buscar bien, serie, marca, IP o funcionario...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-hover align-middle mb-0" id="tablaEquiposInventario" data-paginacion="true">
                                <thead>
                                    <tr>
                                        <th>Código y serie</th>
                                        <th>Equipo y especificaciones</th>
                                        <th>Ubicación y responsable</th>
                                        <th>Estado y fallas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($equipos)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 texto-secundario">
                                                No se encontraron equipos registrados para los filtros seleccionados.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($equipos as $eq): ?>
                                            <tr>
                                                <td>
                                                    <div><span class="codigo-monoespaciado"><?php echo e($eq['codigo_bien']); ?></span></div>
                                                    <div class="small texto-secundario mt-1"><?php echo e($eq['numero_serie']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo e($eq['marca'] . ' ' . $eq['modelo']); ?></div>
                                                    <div class="small texto-secundario">
                                                        <?php echo e(texto_estado_legible((string)$eq['categoria_equipo'])); ?>
                                                        <?php if (!empty($eq['direccion_ip_fija'])): ?>
                                                            · IP <?php echo e($eq['direccion_ip_fija']); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo e($eq['nombre_departamento']); ?></div>
                                                    <div class="small texto-secundario">
                                                        <?php echo !empty($eq['nombre_usuario_asignado']) ? e($eq['nombre_usuario_asignado']) : 'Uso compartido en oficina'; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="<?php echo e(clase_insignia_estado((string)$eq['estado_operativo'])); ?>">
                                                        <?php echo e(texto_estado_legible((string)$eq['estado_operativo'])); ?>
                                                    </span>
                                                    <div class="small texto-secundario mt-1">
                                                        <?php echo (int)$eq['total_fallas_reportadas']; ?> reportes históricos
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <a href="<?php echo e(ruta_url('inventario', 'index', ['ver_equipo' => (int)$eq['id_equipo']])); ?>"
                                                           class="btn btn-sm btn-secondary">
                                                            Hoja de vida
                                                        </a>
                                                        <a href="<?php echo e(ruta_url('inventario', 'index', ['editar_equipo' => (int)$eq['id_equipo']])); ?>"
                                                           class="btn btn-sm btn-institucional">
                                                            Editar
                                                        </a>
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

            <!-- Pestaña 2: Gestión de Departamentos y Oficinas -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'departamentos' ? 'show active' : ''; ?>"
                 id="tab-inv-departamentos"
                 role="tabpanel">
                <div class="row g-3 fila-inicio-alineada">
                    <div class="col-12 col-lg-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span>
                                    <?php echo $deptoEdicion ? 'Editar departamento' : 'Nuevo departamento u oficina'; ?>
                                </span>
                                <?php if ($deptoEdicion): ?>
                                    <a href="<?php echo e(ruta_url('inventario', 'index', ['tab' => 'departamentos'])); ?>"
                                       class="btn btn-sm btn-secondary">
                                        Nuevo
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <form action="<?php echo e(ruta_url('inventario', 'guardarDepartamento')); ?>" method="POST">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                    <input type="hidden" name="id_departamento" value="<?php echo (int)($deptoEdicion['id_departamento'] ?? 0); ?>">

                                    <div class="mb-2">
                                        <label for="codigo_depto" class="form-label">Código identificador</label>
                                        <input type="text"
                                               class="form-control"
                                               id="codigo_depto"
                                               name="codigo_departamento"
                                               value="<?php echo e($deptoEdicion['codigo_departamento'] ?? ''); ?>"
                                               placeholder="Ejemplo: DEP-JUR"
                                               required>
                                    </div>

                                    <div class="mb-2">
                                        <label for="nombre_depto" class="form-label">Nombre del departamento</label>
                                        <input type="text"
                                               class="form-control"
                                               id="nombre_depto"
                                               name="nombre_departamento"
                                               value="<?php echo e($deptoEdicion['nombre_departamento'] ?? ''); ?>"
                                               placeholder="Consultoría Jurídica"
                                               required>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label for="sede_depto" class="form-label">Sede o edificio</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="sede_depto"
                                                   name="sede_edificio"
                                                   value="<?php echo e($deptoEdicion['sede_edificio'] ?? 'Sede Central'); ?>"
                                                   required>
                                        </div>
                                        <div class="col-6">
                                            <label for="piso_depto" class="form-label">Piso o nivel</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="piso_depto"
                                                   name="piso_oficina"
                                                   value="<?php echo e($deptoEdicion['piso_oficina'] ?? ''); ?>"
                                                   placeholder="Piso 3"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label for="oficina_depto" class="form-label">Número de oficina</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="oficina_depto"
                                                   name="numero_oficina"
                                                   value="<?php echo e($deptoEdicion['numero_oficina'] ?? ''); ?>"
                                                   placeholder="Oficina 302"
                                                   required>
                                        </div>
                                        <div class="col-6">
                                            <label for="ext_depto" class="form-label">Extensión telefónica</label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="ext_depto"
                                                   name="telefono_extension"
                                                   value="<?php echo e($deptoEdicion['telefono_extension'] ?? ''); ?>"
                                                   placeholder="Ext-302">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="activo_depto" class="form-label">Estado operativo del área</label>
                                        <select class="form-select" id="activo_depto" name="activo">
                                            <option value="1" <?php echo (int)($deptoEdicion['activo'] ?? 1) === 1 ? 'selected' : ''; ?>>Activo</option>
                                            <option value="0" <?php echo (int)($deptoEdicion['activo'] ?? 1) === 0 ? 'selected' : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-institucional">
                                            <?php echo $deptoEdicion ? 'Actualizar departamento' : 'Guardar departamento'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="barra-filtros-compacta mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <h3 class="h5 fw-bold mb-0">Listado de departamentos y oficinas</h3>
                                <div style="min-width: 220px;">
                                    <input type="search"
                                           class="form-control form-control-sm"
                                           data-buscador-tabla="#tablaDepartamentosInv"
                                           placeholder="Buscar departamento u oficina...">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-hover align-middle mb-0" id="tablaDepartamentosInv" data-paginacion="true">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Departamento</th>
                                        <th>Ubicación física</th>
                                        <th>Personal y equipos</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departamentos as $dep): ?>
                                        <tr>
                                            <td><span class="codigo-monoespaciado"><?php echo e($dep['codigo_departamento']); ?></span></td>
                                            <td class="fw-semibold"><?php echo e($dep['nombre_departamento']); ?></td>
                                            <td class="small">
                                                <?php echo e($dep['sede_edificio'] . ' · ' . $dep['piso_oficina'] . ' · ' . $dep['numero_oficina']); ?>
                                                <?php if (!empty($dep['telefono_extension'])): ?>
                                                    <div class="texto-secundario"><?php echo e($dep['telefono_extension']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <div><?php echo (int)$dep['total_usuarios']; ?> funcionarios</div>
                                                <div class="texto-secundario"><?php echo (int)$dep['total_equipos']; ?> equipos</div>
                                            </td>
                                            <td>
                                                <span class="insignia-suave <?php echo (int)$dep['activo'] === 1 ? 'insignia-suave-baja' : 'insignia-suave-neutra'; ?>">
                                                    <?php echo (int)$dep['activo'] === 1 ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo e(ruta_url('inventario', 'index', ['tab' => 'departamentos', 'editar_depto' => (int)$dep['id_departamento']])); ?>"
                                                   class="btn btn-sm btn-secondary">
                                                    Editar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pestaña 3: Catálogo de Categorías de Falla y Tiempos Estimados -->
            <div class="tab-pane fade <?php echo $pestanaActiva === 'categorias' ? 'show active' : ''; ?>"
                 id="tab-inv-categorias"
                 role="tabpanel">
                <div class="row g-3 fila-inicio-alineada">
                    <div class="col-12 col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                Agregar categoría de falla
                            </div>
                            <div class="card-body">
                                <form action="<?php echo e(ruta_url('inventario', 'guardarCategoriaFalla')); ?>" method="POST">
                                    <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                    <input type="hidden" name="id_categoria_falla" value="0">

                                    <div class="mb-2">
                                        <label for="rama_general_cat" class="form-label">Rama general</label>
                                        <select class="form-select" id="rama_general_cat" name="rama_general" required>
                                            <option value="Hardware">Componentes físicos y energía</option>
                                            <option value="Software">Programas y sistemas</option>
                                            <option value="Red_Internet">Red local y conectividad</option>
                                            <option value="Impresoras">Impresoras y escáneres</option>
                                            <option value="Accesos_Cuentas">Accesos y cuentas de usuario</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label for="subcategoria_cat" class="form-label">Descripción de la subcategoría</label>
                                        <input type="text"
                                               class="form-control"
                                               id="subcategoria_cat"
                                               name="subcategoria"
                                               placeholder="Ejemplo: Falla de teclado o monitor sin señal"
                                               required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tiempo_estimado_cat" class="form-label">Tiempo estimado de resolución en minutos</label>
                                        <input type="number"
                                               class="form-control"
                                               id="tiempo_estimado_cat"
                                               name="tiempo_estimado_minutos"
                                               min="10"
                                               max="1440"
                                               value="60"
                                               required>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-institucional">
                                            Guardar categoría de falla
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="barra-filtros-compacta mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <h3 class="h5 fw-bold mb-0">Catálogo activo de categorías y tiempos esperados</h3>
                                <div style="min-width: 220px;">
                                    <input type="search"
                                           class="form-control form-control-sm"
                                           data-buscador-tabla="#tablaCategoriasInv"
                                           placeholder="Buscar rama o subcategoría...">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive border rounded">
                            <table class="table table-hover align-middle mb-0" id="tablaCategoriasInv" data-paginacion="true">
                                <thead>
                                    <tr>
                                        <th>Rama técnica</th>
                                        <th>Subcategoría</th>
                                        <th>Tiempo meta en minutos</th>
                                        <th>Estado</th>
                                        <th>Actualizar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categoriasFalla as $catItem): ?>
                                        <tr>
                                            <form action="<?php echo e(ruta_url('inventario', 'guardarCategoriaFalla')); ?>" method="POST">
                                                <input type="hidden" name="token_csrf" value="<?php echo e($tokenCsrf); ?>">
                                                <input type="hidden" name="id_categoria_falla" value="<?php echo (int)$catItem['id_categoria_falla']; ?>">
                                                <input type="hidden" name="rama_general" value="<?php echo e((string)$catItem['rama_general']); ?>">

                                                <td class="fw-semibold"><?php echo e(texto_estado_legible((string)$catItem['rama_general'])); ?></td>
                                                <td>
                                                    <input type="text"
                                                           name="subcategoria"
                                                           class="form-control form-control-sm"
                                                           value="<?php echo e((string)$catItem['subcategoria']); ?>"
                                                           required>
                                                </td>
                                                <td style="max-width: 110px;">
                                                    <input type="number"
                                                           name="tiempo_estimado_minutos"
                                                           class="form-control form-control-sm"
                                                           min="5"
                                                           value="<?php echo (int)$catItem['tiempo_estimado_minutos']; ?>"
                                                           required>
                                                </td>
                                                <td>
                                                    <select name="activo" class="form-select form-select-sm">
                                                        <option value="1" <?php echo (int)$catItem['activo'] === 1 ? 'selected' : ''; ?>>Activa</option>
                                                        <option value="0" <?php echo (int)$catItem['activo'] === 0 ? 'selected' : ''; ?>>Inactiva</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="submit" class="btn btn-sm btn-secondary">
                                                        Guardar
                                                    </button>
                                                </td>
                                            </form>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Abrir automáticamente el modal de hoja de vida si se solicitó ver un equipo
    var modalHojaEl = document.getElementById('modalHojaVidaEquipo');
    if (modalHojaEl && window.bootstrap && window.bootstrap.Modal) {
        var instHoja = new window.bootstrap.Modal(modalHojaEl);
        instHoja.show();
    }

    // Filtrar custodios según el departamento elegido en el formulario de equipos
    var selectDeptoEq = document.getElementById('id_departamento_eq');
    var selectCustodioEq = document.getElementById('id_usuario_asignado_eq');
    if (selectDeptoEq && selectCustodioEq) {
        selectDeptoEq.addEventListener('change', function () {
            var deptoElegido = selectDeptoEq.value;
            Array.from(selectCustodioEq.options).forEach(function (op, idx) {
                if (idx === 0) return;
                var deptoUsu = op.getAttribute('data-depto');
                op.hidden = Boolean(deptoElegido && deptoUsu && deptoElegido !== deptoUsu);
            });
            if (typeof window.refrescarBuscadorAutocompletable === 'function') {
                window.refrescarBuscadorAutocompletable(selectCustodioEq);
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
