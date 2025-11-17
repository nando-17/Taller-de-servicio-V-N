<?php

declare(strict_types=1);

$orden = $orden ?? [];
$tecnicosDisponibles = $tecnicosDisponibles ?? [];
$historial = $historial ?? [];
$servicioItems = $servicioItems ?? [];
$repuestoItems = $repuestoItems ?? [];
$catalogoServicios = $catalogoServicios ?? [];
$catalogoRepuestos = $catalogoRepuestos ?? [];
$estadosDisponibles = $estadosDisponibles ?? [];
$factura = $factura ?? null;
$pagos = $pagos ?? [];
$montoPagado = $montoPagado ?? 0.0;
$totales = $totales ?? ['servicios' => 0, 'repuestos' => 0, 'general' => 0];
$errors = $errors ?? ['status' => [], 'service_item' => [], 'repuesto_item' => [], 'deliver' => []];
$userRole = \App\Core\Session::getUserRole();
$canAssign = in_array($userRole, ['ADMIN', 'OPERADOR'], true);
$canManageServices = in_array($userRole, ['ADMIN', 'TECNICO'], true);
$canManageRepuestos = in_array($userRole, ['ADMIN', 'TECNICO'], true);
$canRemoveRepuesto = $userRole === 'ADMIN';
$canEmitInvoice = in_array($userRole, ['ADMIN', 'OPERADOR'], true);
$canDeliver = in_array($userRole, ['ADMIN', 'OPERADOR'], true);

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1">Gestión de Orden <?= htmlspecialchars((string)($orden['codigo'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
        <p class="text-muted mb-0">Cliente: <?= htmlspecialchars((string)($orden['cliente_nombre'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> · Estado actual: <span class="badge bg-primary"><?= htmlspecialchars((string)($orden['estado_nombre'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></p>
    </div>
    <div class="text-end small">
        <p class="mb-1">Técnico asignado: <?= htmlspecialchars((string)($orden['tecnico_completo'] ?: 'Sin asignar'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
        <?php if ($canAssign): ?>
            <a class="btn btn-outline-secondary btn-sm" href="/ordenservicio/assign/<?= (int)($orden['id'] ?? 0); ?>">
                <i class="fa-solid fa-user-gear me-1"></i>Reasignar técnico
            </a>
        <?php endif; ?>
        <a href="/ordenservicio/pdf/<?= (int)($orden['id'] ?? 0); ?>" class="btn btn-outline-primary btn-sm mt-2">
            <i class="fa-solid fa-download me-1"></i>Descargar PDF
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm mb-4" id="estado">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="fa-solid fa-arrow-right-arrow-left me-2 text-primary"></i>Gestionar estado</h2>
                <form action="/ordenservicio/estado/<?= (int)($orden['id'] ?? 0); ?>" method="POST" class="row gy-3">
                    <div class="col-md-6">
                        <label for="estado_id" class="form-label">Nuevo estado</label>
                        <select id="estado_id" name="estado_id" class="form-select<?= !empty($errors['status']['estado_id'] ?? '') ? ' is-invalid' : ''; ?>" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($estadosDisponibles as $estado): ?>
                                <option value="<?= (int)$estado['id']; ?>"><?= htmlspecialchars($estado['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['status']['estado_id'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['status']['estado_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="comentario" class="form-label">Comentario (opcional)</label>
                        <input type="text" id="comentario" name="comentario" class="form-control" placeholder="Detalle el motivo del cambio">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Actualizar estado</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="fa-solid fa-screwdriver-wrench me-2 text-primary"></i>Servicios aplicados</h2>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Servicio</th>
                                <th class="text-center">Cantidad</th>
                                <th class="text-end">P. unit.</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($servicioItems)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Sin servicios registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($servicioItems as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['servicio_nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                        <?php if (!empty($item['descripcion'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($item['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= htmlspecialchars((string)$item['cantidad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td class="text-end">S/ <?= number_format((float)($item['precio_unitario'] ?? 0), 2); ?></td>
                                    <td class="text-end">S/ <?= number_format((float)($item['total_linea'] ?? 0), 2); ?></td>
                                    <td class="text-end">
                                        <?php if ($canManageServices): ?>
                                            <form action="/ordenservicio/<?= (int)$orden['id']; ?>/servicios/<?= (int)$item['id']; ?>/eliminar" method="POST" class="d-inline">
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($canManageServices): ?>
                <form action="/ordenservicio/<?= (int)$orden['id']; ?>/servicios" method="POST" class="row gy-3">
                    <div class="col-md-6">
                        <label for="servicio_id" class="form-label">Servicio</label>
                        <select id="servicio_id" name="servicio_id" class="form-select<?= !empty($errors['service_item']['servicio_id'] ?? '') ? ' is-invalid' : ''; ?>" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($catalogoServicios as $servicio): ?>
                                <option value="<?= (int)$servicio['id']; ?>" data-precio="<?= htmlspecialchars((string)($servicio['precio_base'] ?? '0'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($servicio['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> (S/ <?= number_format((float)($servicio['precio_base'] ?? 0), 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['service_item']['servicio_id'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['service_item']['servicio_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="cantidad_servicio" class="form-label">Cantidad</label>
                        <input type="number" step="0.1" min="0.1" id="cantidad_servicio" name="cantidad" class="form-control<?= !empty($errors['service_item']['cantidad'] ?? '') ? ' is-invalid' : ''; ?>" value="1" required>
                        <?php if (!empty($errors['service_item']['cantidad'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['service_item']['cantidad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="precio_servicio" class="form-label">Precio (S/)</label>
                        <input type="number" step="0.01" min="0" id="precio_servicio" name="precio_unitario" class="form-control<?= !empty($errors['service_item']['precio_unitario'] ?? '') ? ' is-invalid' : ''; ?>">
                        <?php if (!empty($errors['service_item']['precio_unitario'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['service_item']['precio_unitario'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label for="descripcion_servicio" class="form-label">Descripción adicional</label>
                        <input type="text" id="descripcion_servicio" name="descripcion" class="form-control" placeholder="Notas del trabajo realizado (opcional)">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-plus me-1"></i>Agregar servicio</button>
                    </div>
                </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">Solo los perfiles de administrador o técnico pueden agregar o eliminar servicios.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" id="repuestos">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="fa-solid fa-gears me-2 text-primary"></i>Repuestos consumidos</h2>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Repuesto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">P. unit.</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Stock</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($repuestoItems)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Sin repuestos registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($repuestoItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['repuesto_nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td class="text-center"><?= htmlspecialchars((string)$item['cantidad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td class="text-end">S/ <?= number_format((float)($item['precio_unitario'] ?? 0), 2); ?></td>
                                    <td class="text-end">S/ <?= number_format((float)($item['total_linea'] ?? 0), 2); ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars((string)($item['stock_actual'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                                    <td class="text-end">
                                        <?php if ($canRemoveRepuesto): ?>
                                            <form action="/ordenservicio/<?= (int)$orden['id']; ?>/repuestos/<?= (int)$item['id']; ?>/eliminar" method="POST" class="d-inline">
                                                <button type="submit" class="btn btn-link text-danger p-0" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($canManageRepuestos): ?>
                <form action="/ordenservicio/<?= (int)$orden['id']; ?>/repuestos" method="POST" class="row gy-3">
                    <div class="col-md-6">
                        <label for="repuesto_id" class="form-label">Repuesto</label>
                        <select id="repuesto_id" name="repuesto_id" class="form-select<?= !empty($errors['repuesto_item']['repuesto_id'] ?? '') ? ' is-invalid' : ''; ?>" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($catalogoRepuestos as $repuesto): ?>
                                <option value="<?= (int)$repuesto['id']; ?>" data-stock="<?= htmlspecialchars((string)($repuesto['stock'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" data-precio="<?= htmlspecialchars((string)($repuesto['precio_venta'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($repuesto['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> (Stock: <?= htmlspecialchars((string)($repuesto['stock'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['repuesto_item']['repuesto_id'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['repuesto_item']['repuesto_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="cantidad_repuesto" class="form-label">Cantidad</label>
                        <input type="number" step="0.01" min="0.01" id="cantidad_repuesto" name="cantidad" class="form-control<?= !empty($errors['repuesto_item']['cantidad'] ?? '') ? ' is-invalid' : ''; ?>" value="1" required>
                        <?php if (!empty($errors['repuesto_item']['cantidad'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['repuesto_item']['cantidad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <label for="precio_repuesto" class="form-label">Precio (S/)</label>
                        <input type="number" step="0.01" min="0" id="precio_repuesto" name="precio_unitario" class="form-control<?= !empty($errors['repuesto_item']['precio_unitario'] ?? '') ? ' is-invalid' : ''; ?>">
                        <?php if (!empty($errors['repuesto_item']['precio_unitario'] ?? '')): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['repuesto_item']['precio_unitario'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-plus me-1"></i>Agregar repuesto</button>
                    </div>
                </form>
                <?php else: ?>
                    <p class="text-muted small mb-0">Solo los perfiles de administrador o técnico pueden agregar repuestos a la orden.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="fa-solid fa-calculator me-2 text-primary"></i>Resumen económico</h2>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Mano de obra
                        <span class="fw-semibold">S/ <?= number_format((float)$totales['servicios'], 2); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Repuestos
                        <span class="fw-semibold">S/ <?= number_format((float)$totales['repuestos'], 2); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Total estimado
                        <span class="fw-bold text-primary">S/ <?= number_format((float)$totales['general'], 2); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Costo estimado registrado
                        <span>S/ <?= number_format((float)($orden['costo_estimado'] ?? 0), 2); ?></span>
                    </li>
                </ul>
                <?php if ($canManageServices): ?>
                    <a href="/ordenservicio/diagnose/<?= (int)$orden['id']; ?>" class="btn btn-outline-secondary w-100"><i class="fa-solid fa-stethoscope me-1"></i>Actualizar diagnóstico y presupuesto</a>
                <?php else: ?>
                    <p class="text-muted small mb-0">Solo los perfiles autorizados pueden registrar diagnósticos.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="fa-solid fa-file-invoice-dollar me-2 text-primary"></i>Comprobante de pago</h2>
                <?php if ($factura): ?>
                    <p class="mb-2"><strong>Tipo:</strong> <?= htmlspecialchars($factura['tipo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> · <strong>Número:</strong> <?= htmlspecialchars(trim(($factura['serie'] ?? '') . '-' . ($factura['numero'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                    <p class="mb-2"><strong>Total:</strong> S/ <?= number_format((float)$factura['total'], 2); ?></p>
                    <p class="mb-3"><strong>Estado:</strong> <span class="badge bg-<?= $factura['estado'] === 'PAGADA' ? 'success' : ($factura['estado'] === 'ANULADA' ? 'secondary' : 'warning'); ?>"><?= htmlspecialchars($factura['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></p>
                    <div class="mb-3">
                        <h3 class="h6">Pagos registrados</h3>
                        <?php if (empty($pagos)): ?>
                            <p class="text-muted small mb-0">No hay pagos asociados.</p>
                        <?php else: ?>
                            <ul class="list-unstyled small mb-0">
                                <?php foreach ($pagos as $pago): ?>
                                    <li class="d-flex justify-content-between border-bottom py-1">
                                        <span><?= htmlspecialchars($pago['metodo_pago'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> (<?= htmlspecialchars($pago['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)</span>
                                        <span>S/ <?= number_format((float)$pago['monto'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="fw-semibold">Total pagado</span>
                            <span class="fw-semibold">S/ <?= number_format((float)$montoPagado, 2); ?></span>
                        </div>
                    </div>
                    <?php if ($canEmitInvoice): ?>
                        <a href="/facturacion" class="btn btn-outline-primary w-100">Ver comprobante</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-3">Aún no se ha emitido un comprobante para esta orden.</p>
                    <?php if ($canEmitInvoice): ?>
                        <a href="/facturacion" class="btn btn-outline-primary w-100"><i class="fa-solid fa-file-circle-plus me-1"></i>Emitir comprobante</a>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Solicita al área administrativa la emisión del comprobante.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canDeliver): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3"><i class="fa-solid fa-box-open me-2 text-primary"></i>Entrega de equipo</h2>
                    <?php if (!empty($errors['deliver'])): ?>
                        <div class="alert alert-danger py-2">
                            <?php foreach ($errors['deliver'] as $error): ?>
                                <div><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <form action="/ordenservicio/<?= (int)$orden['id']; ?>/entregar" method="POST">
                        <p class="small text-muted">Verifica la identidad del cliente y los accesorios antes de marcar como entregado. Se actualizará la fecha de entrega real.</p>
                        <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-handshake me-1"></i>Confirmar entrega</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm" id="historial">
            <div class="card-body">
                <h2 class="h5 mb-3"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Historial de estados</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Comentario</th>
                            <th>Responsable</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($historial)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Sin movimientos registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historial as $evento): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$evento['fecha_cambio'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$evento['estado_nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)$evento['comentario'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars((string)($evento['usuario_nombre'] ?? 'Sistema'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const servicioSelect = document.getElementById('servicio_id');
        const precioServicio = document.getElementById('precio_servicio');
        if (servicioSelect && precioServicio) {
            servicioSelect.addEventListener('change', () => {
                const selected = servicioSelect.options[servicioSelect.selectedIndex];
                const precio = selected.getAttribute('data-precio');
                if (precio) {
                    precioServicio.value = parseFloat(precio).toFixed(2);
                }
            });
        }

        const repuestoSelect = document.getElementById('repuesto_id');
        const precioRepuesto = document.getElementById('precio_repuesto');
        if (repuestoSelect && precioRepuesto) {
            repuestoSelect.addEventListener('change', () => {
                const selected = repuestoSelect.options[repuestoSelect.selectedIndex];
                const precio = selected.getAttribute('data-precio');
                if (precio) {
                    precioRepuesto.value = parseFloat(precio).toFixed(2);
                }
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
