<?php

declare(strict_types=1);

$servicios = $servicios ?? [];
$repuestos = $repuestos ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

$serviceErrors = $errors['servicio'] ?? [];
$repuestoErrors = $errors['repuesto'] ?? [];
$serviceEditErrors = $errors['servicio_edit'] ?? [];
$repuestoEditErrors = $errors['repuesto_edit'] ?? [];

$serviceOld = $old['servicio'] ?? [];
$repuestoOld = $old['repuesto'] ?? [];
$serviceEditOld = $old['servicio_edit'] ?? null;
$repuestoEditOld = $old['repuesto_edit'] ?? null;

$serviciosActivos = array_filter($servicios, static fn(array $servicio): bool => (int)($servicio['activo'] ?? 0) === 1);
$repuestosActivos = array_filter($repuestos, static fn(array $repuesto): bool => (int)($repuesto['activo'] ?? 0) === 1);
$repuestosBajoStock = array_filter($repuestos, static fn(array $repuesto): bool => (int)($repuesto['stock'] ?? 0) < (int)($repuesto['stock_minimo'] ?? 0));

ob_start();
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-tags me-2 text-primary"></i>Catálogos maestros</h1>
        <p class="text-muted mb-0">Gestiona los servicios y repuestos autorizados para el taller.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="search" id="catalogoFiltro" class="form-control" placeholder="Filtrar por nombre" aria-label="Filtrar catálogo">
        </div>
        <a href="/catalogos/pdf" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-download me-2"></i>Descargar PDF</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Servicios activos</p>
                <h2 class="h4 mb-0"><?= count($serviciosActivos); ?> <small class="text-muted">/ <?= count($servicios); ?></small></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Repuestos activos</p>
                <h2 class="h4 mb-0"><?= count($repuestosActivos); ?> <small class="text-muted">/ <?= count($repuestos); ?></small></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Repuestos bajo mínimo</p>
                <h2 class="h4 mb-0 text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= count($repuestosBajoStock); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Servicios</h2>
                <form action="/catalogos/servicios" method="POST" class="row gy-3 mb-4">
                    <div class="col-md-6">
                        <label for="servicio_nombre" class="form-label">Nombre<span class="text-danger"> *</span></label>
                        <input type="text" id="servicio_nombre" name="nombre" class="form-control<?= !empty($serviceErrors['nombre'] ?? '') ? ' is-invalid' : ''; ?>" value="<?= htmlspecialchars($serviceOld['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                        <?php if (!empty($serviceErrors['nombre'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($serviceErrors['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="servicio_precio" class="form-label">Precio base</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" min="0" id="servicio_precio" name="precio_base" class="form-control<?= !empty($serviceErrors['precio_base'] ?? '') ? ' is-invalid' : ''; ?>" value="<?= htmlspecialchars((string)($serviceOld['precio_base'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                        </div>
                        <?php if (!empty($serviceErrors['precio_base'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($serviceErrors['precio_base'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label for="servicio_desc" class="form-label">Descripción</label>
                        <input type="text" id="servicio_desc" name="descripcion" class="form-control" placeholder="Opcional" value="<?= htmlspecialchars($serviceOld['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Agregar servicio</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="tablaServicios">
                        <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-end">Precio</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($servicios)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay servicios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($servicios as $servicio): ?>
                                <?php $isActivo = (int)($servicio['activo'] ?? 0) === 1; ?>
                                <tr data-filter-item="<?= htmlspecialchars(strtolower(($servicio['nombre'] ?? '') . ' ' . ($servicio['descripcion'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($servicio['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                        <?php if (!empty($servicio['descripcion'])): ?><div class="small text-muted"><?= htmlspecialchars($servicio['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                                    </td>
                                    <td class="text-end">S/ <?= number_format((float)($servicio['precio_base'] ?? 0), 2); ?></td>
                                    <td><span class="badge bg-<?= $isActivo ? 'success' : 'secondary'; ?>"><?= $isActivo ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarServicio"
                                                    data-id="<?= (int)($servicio['id'] ?? 0); ?>"
                                                    data-nombre="<?= htmlspecialchars($servicio['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                                    data-precio="<?= htmlspecialchars((string)($servicio['precio_base'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                                    data-descripcion="<?= htmlspecialchars($servicio['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <form action="/catalogos/servicios/<?= (int)($servicio['id'] ?? 0); ?>/toggle" method="POST" class="d-inline">
                                                <button type="submit" class="btn btn-outline-<?= $isActivo ? 'warning' : 'success'; ?>" title="<?= $isActivo ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fa-solid <?= $isActivo ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
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

    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Repuestos</h2>
                <form action="/catalogos/repuestos" method="POST" class="row gy-3 mb-4">
                    <div class="col-md-6">
                        <label for="repuesto_nombre" class="form-label">Nombre<span class="text-danger"> *</span></label>
                        <input type="text" id="repuesto_nombre" name="nombre" class="form-control<?= !empty($repuestoErrors['nombre_repuesto'] ?? $repuestoErrors['nombre'] ?? '') ? ' is-invalid' : ''; ?>" value="<?= htmlspecialchars($repuestoOld['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                        <?php if (!empty($repuestoErrors['nombre_repuesto'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($repuestoErrors['nombre_repuesto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                        <?php if (!empty($repuestoErrors['nombre'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($repuestoErrors['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="repuesto_codigo" class="form-label">Código</label>
                        <input type="text" id="repuesto_codigo" name="codigo" class="form-control" value="<?= htmlspecialchars($repuestoOld['codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Opcional">
                    </div>
                    <div class="col-md-4">
                        <label for="repuesto_costo" class="form-label">Costo</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" min="0" id="repuesto_costo" name="precio_costo" class="form-control<?= !empty($repuestoErrors['precio'] ?? $repuestoErrors['precio_costo'] ?? '') ? ' is-invalid' : ''; ?>" value="<?= htmlspecialchars((string)($repuestoOld['precio_costo'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                        </div>
                        <?php if (!empty($repuestoErrors['precio'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($repuestoErrors['precio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                        <?php if (!empty($repuestoErrors['precio_costo'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($repuestoErrors['precio_costo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="repuesto_precio" class="form-label">Precio venta</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" min="0" id="repuesto_precio" name="precio_venta" class="form-control<?= !empty($repuestoErrors['precio_venta'] ?? '') ? ' is-invalid' : ''; ?>" value="<?= htmlspecialchars((string)($repuestoOld['precio_venta'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                        </div>
                        <?php if (!empty($repuestoErrors['precio_venta'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($repuestoErrors['precio_venta'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <label for="repuesto_stock" class="form-label">Stock mínimo</label>
                        <input type="number" min="0" id="repuesto_stock" name="stock_minimo" class="form-control<?= !empty($repuestoErrors['stock_minimo'] ?? '') ? ' is-invalid' : ''; ?>" value="<?= htmlspecialchars((string)($repuestoOld['stock_minimo'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                        <?php if (!empty($repuestoErrors['stock_minimo'] ?? '')): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($repuestoErrors['stock_minimo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Agregar repuesto</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="tablaRepuestos">
                        <thead class="table-light">
                        <tr>
                            <th>Repuesto</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Mínimo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($repuestos)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No hay repuestos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($repuestos as $repuesto): ?>
                                <?php
                                $activo = (int)($repuesto['activo'] ?? 0) === 1;
                                $stock = (int)($repuesto['stock'] ?? 0);
                                $minimo = (int)($repuesto['stock_minimo'] ?? 0);
                                $alertaStock = $stock < $minimo;
                                ?>
                                <tr data-filter-item="<?= htmlspecialchars(strtolower(($repuesto['nombre'] ?? '') . ' ' . ($repuesto['codigo'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($repuesto['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                        <?php if (!empty($repuesto['codigo'])): ?><div class="small text-muted">Código: <?= htmlspecialchars($repuesto['codigo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                                    </td>
                                    <td class="text-center<?= $alertaStock ? ' text-warning fw-semibold' : ''; ?>"><?= $stock; ?></td>
                                    <td class="text-center"><?= $minimo; ?></td>
                                    <td><span class="badge bg-<?= $activo ? 'success' : 'secondary'; ?>"><?= $activo ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarRepuesto"
                                                    data-id="<?= (int)($repuesto['id'] ?? 0); ?>"
                                                    data-nombre="<?= htmlspecialchars($repuesto['nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                                    data-codigo="<?= htmlspecialchars($repuesto['codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                                    data-preciocosto="<?= htmlspecialchars((string)($repuesto['precio_costo'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                                    data-precioventa="<?= htmlspecialchars((string)($repuesto['precio_venta'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                                    data-stockminimo="<?= htmlspecialchars((string)($repuesto['stock_minimo'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <form action="/catalogos/repuestos/<?= (int)($repuesto['id'] ?? 0); ?>/toggle" method="POST" class="d-inline">
                                                <button type="submit" class="btn btn-outline-<?= $activo ? 'warning' : 'success'; ?>" title="<?= $activo ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fa-solid <?= $activo ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
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
</div>

<!-- Modal edición servicio -->
<div class="modal fade" id="modalEditarServicio" tabindex="-1" aria-labelledby="modalEditarServicioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="formEditarServicio">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalEditarServicioLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Editar servicio</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($serviceEditErrors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($serviceEditErrors as $error): ?>
                            <div><?= htmlspecialchars((string)$error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="servicio_edit_nombre" class="form-label">Nombre</label>
                    <input type="text" id="servicio_edit_nombre" name="nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="servicio_edit_precio" class="form-label">Precio base</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" min="0" id="servicio_edit_precio" name="precio_base" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="servicio_edit_desc" class="form-label">Descripción</label>
                    <textarea id="servicio_edit_desc" name="descripcion" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal edición repuesto -->
<div class="modal fade" id="modalEditarRepuesto" tabindex="-1" aria-labelledby="modalEditarRepuestoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="formEditarRepuesto">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalEditarRepuestoLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Editar repuesto</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($repuestoEditErrors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($repuestoEditErrors as $error): ?>
                            <div><?= htmlspecialchars((string)$error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="repuesto_edit_nombre" class="form-label">Nombre</label>
                    <input type="text" id="repuesto_edit_nombre" name="nombre" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="repuesto_edit_codigo" class="form-label">Código</label>
                    <input type="text" id="repuesto_edit_codigo" name="codigo" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="repuesto_edit_costo" class="form-label">Precio costo</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" min="0" id="repuesto_edit_costo" name="precio_costo" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="repuesto_edit_venta" class="form-label">Precio venta</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" step="0.01" min="0" id="repuesto_edit_venta" name="precio_venta" class="form-control" required>
                    </div>
                </div>
                <div class="mb-0">
                    <label for="repuesto_edit_stock" class="form-label">Stock mínimo</label>
                    <input type="number" min="0" id="repuesto_edit_stock" name="stock_minimo" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php
$prefillServicio = json_encode($serviceEditOld, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($prefillServicio === false) {
    $prefillServicio = 'null';
}
$prefillRepuesto = json_encode($repuestoEditOld, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
if ($prefillRepuesto === false) {
    $prefillRepuesto = 'null';
}
?>

<?php
$styles = <<<HTML
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
HTML;

$scripts = <<<HTML
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const hasJquery = typeof window.jQuery !== 'undefined';
        const baseOptions = {
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            pageLength: 5,
            lengthMenu: [[5, 10, 25, -1], [5, 10, 25, 'Todos']],
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'>>" +
                 "t" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        };

        let serviciosTable = null;
        let repuestosTable = null;

        if (hasJquery) {
            const $ = window.jQuery;
            const serviciosTableEl = $('#tablaServicios');
            if (serviciosTableEl.length) {
                serviciosTable = serviciosTableEl.DataTable(Object.assign({}, baseOptions, {
                    columnDefs: [
                        { targets: -1, orderable: false, searchable: false }
                    ]
                }));
            }

            const repuestosTableEl = $('#tablaRepuestos');
            if (repuestosTableEl.length) {
                repuestosTable = repuestosTableEl.DataTable(Object.assign({}, baseOptions, {
                    columnDefs: [
                        { targets: -1, orderable: false, searchable: false }
                    ]
                }));
            }
        }

        const filterInput = document.getElementById('catalogoFiltro');
        if (filterInput) {
            filterInput.addEventListener('input', () => {
                const term = filterInput.value;
                if (serviciosTable) {
                    serviciosTable.search(term).draw();
                }
                if (repuestosTable) {
                    repuestosTable.search(term).draw();
                }
            });
        }

        const bootstrapLib = window.bootstrap || null;

        const servicioModalEl = document.getElementById('modalEditarServicio');
        const servicioForm = document.getElementById('formEditarServicio');
        const servicioModal = servicioModalEl && bootstrapLib ? bootstrapLib.Modal.getOrCreateInstance(servicioModalEl) : null;
        if (servicioModalEl) {
            servicioModalEl.addEventListener('show.bs.modal', event => {
                const trigger = event.relatedTarget;
                if (!trigger) { return; }
                const id = trigger.getAttribute('data-id');
                const nombre = trigger.getAttribute('data-nombre') || '';
                const precio = trigger.getAttribute('data-precio') || '';
                const descripcion = trigger.getAttribute('data-descripcion') || '';
                if (servicioForm && id) {
                    servicioForm.setAttribute('action', '/catalogos/servicios/' + id + '/actualizar');
                    servicioForm.querySelector('#servicio_edit_nombre').value = nombre;
                    servicioForm.querySelector('#servicio_edit_precio').value = parseFloat(precio || '0').toFixed(2);
                    servicioForm.querySelector('#servicio_edit_desc').value = descripcion;
                }
            });
        }

        const repuestoModalEl = document.getElementById('modalEditarRepuesto');
        const repuestoForm = document.getElementById('formEditarRepuesto');
        const repuestoModal = repuestoModalEl && bootstrapLib ? bootstrapLib.Modal.getOrCreateInstance(repuestoModalEl) : null;
        if (repuestoModalEl) {
            repuestoModalEl.addEventListener('show.bs.modal', event => {
                const trigger = event.relatedTarget;
                if (!trigger) { return; }
                const id = trigger.getAttribute('data-id');
                if (repuestoForm && id) {
                    repuestoForm.setAttribute('action', '/catalogos/repuestos/' + id + '/actualizar');
                    repuestoForm.querySelector('#repuesto_edit_nombre').value = trigger.getAttribute('data-nombre') || '';
                    repuestoForm.querySelector('#repuesto_edit_codigo').value = trigger.getAttribute('data-codigo') || '';
                    repuestoForm.querySelector('#repuesto_edit_costo').value = parseFloat(trigger.getAttribute('data-preciocosto') || '0').toFixed(2);
                    repuestoForm.querySelector('#repuesto_edit_venta').value = parseFloat(trigger.getAttribute('data-precioventa') || '0').toFixed(2);
                    repuestoForm.querySelector('#repuesto_edit_stock').value = trigger.getAttribute('data-stockminimo') || '0';
                }
            });
        }

        const servicioPrefill = {$prefillServicio};
        if (servicioPrefill && servicioModal && servicioForm) {
            const { id, nombre, precio_base: precioBase, descripcion } = servicioPrefill;
            if (id) {
                servicioForm.setAttribute('action', '/catalogos/servicios/' + id + '/actualizar');
                servicioForm.querySelector('#servicio_edit_nombre').value = nombre || '';
                servicioForm.querySelector('#servicio_edit_precio').value = typeof precioBase === 'number' ? precioBase.toFixed(2) : (precioBase || '0');
                servicioForm.querySelector('#servicio_edit_desc').value = descripcion || '';
                servicioModal.show();
            }
        }

        const repuestoPrefill = {$prefillRepuesto};
        if (repuestoPrefill && repuestoModal && repuestoForm) {
            const { id, nombre, codigo, precio_costo: precioCosto, precio_venta: precioVenta, stock_minimo: stockMinimo } = repuestoPrefill;
            if (id) {
                repuestoForm.setAttribute('action', '/catalogos/repuestos/' + id + '/actualizar');
                repuestoForm.querySelector('#repuesto_edit_nombre').value = nombre || '';
                repuestoForm.querySelector('#repuesto_edit_codigo').value = codigo || '';
                repuestoForm.querySelector('#repuesto_edit_costo').value = typeof precioCosto === 'number' ? precioCosto.toFixed(2) : (precioCosto || '0');
                repuestoForm.querySelector('#repuesto_edit_venta').value = typeof precioVenta === 'number' ? precioVenta.toFixed(2) : (precioVenta || '0');
                repuestoForm.querySelector('#repuesto_edit_stock').value = stockMinimo ?? '0';
                repuestoModal.show();
            }
        }
    });
</script>
HTML;
?>

<?php
$content = ob_get_clean();

require __DIR__ . '/../layouts/main.php';
