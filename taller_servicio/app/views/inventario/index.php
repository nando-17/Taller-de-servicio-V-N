<?php

declare(strict_types=1);

$repuestos = $repuestos ?? [];
$movimientos = $movimientos ?? [];
$errors = $errors ?? [];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-warehouse me-2 text-primary"></i>Gestión de Inventario</h1>
        <p class="text-muted mb-0">Control de ingresos, ajustes y kardex de repuestos.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Nuevo movimiento</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="/inventario" method="POST" class="row gy-3">
                    <div class="col-12">
                        <label for="repuesto_id" class="form-label">Repuesto</label>
                        <select id="repuesto_id" name="repuesto_id" class="form-select" required>
                            <option value="">Selecciona un repuesto...</option>
                            <?php foreach ($repuestos as $repuesto): ?>
                                <option value="<?= (int)$repuesto['id']; ?>">
                                    <?= htmlspecialchars($repuesto['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> (Stock: <?= htmlspecialchars((string)($repuesto['stock'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="INGRESO">Ingreso</option>
                            <option value="AJUSTE">Ajuste</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="cantidad" class="form-label">Cantidad</label>
                        <input type="number" step="0.01" id="cantidad" name="cantidad" class="form-control" placeholder="Ej: 10" required>
                        <small class="text-muted">En ajustes puedes indicar valores negativos para disminuir.</small>
                    </div>
                    <div class="col-md-6">
                        <label for="costo_unitario" class="form-label">Costo unitario (S/)</label>
                        <input type="number" step="0.01" min="0" id="costo_unitario" name="costo_unitario" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-12">
                        <label for="motivo" class="form-label">Motivo</label>
                        <input type="text" id="motivo" name="motivo" class="form-control" placeholder="Ej: Compra a proveedor, ajuste por inventario">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-circle-plus me-1"></i>Registrar movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Inventario actual</h2>
                <div class="table-responsive" style="max-height: 360px;">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Repuesto</th>
                            <th>Stock</th>
                            <th>Mínimo</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($repuestos as $repuesto): ?>
                            <tr class="<?= ($repuesto['stock'] ?? 0) < ($repuesto['stock_minimo'] ?? 0) ? 'table-warning' : ''; ?>">
                                <td><?= htmlspecialchars($repuesto['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string)($repuesto['stock'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string)($repuesto['stock_minimo'] ?? 0), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Movimientos recientes</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Repuesto</th>
                    <th>Tipo</th>
                    <th class="text-end">Cantidad</th>
                    <th>Motivo</th>
                    <th>Orden asociada</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">Sin movimientos registrados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $movimiento): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$movimiento['fecha_mov'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string)$movimiento['repuesto_nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><span class="badge bg-<?= $movimiento['tipo'] === 'INGRESO' ? 'success' : ($movimiento['tipo'] === 'SALIDA' ? 'danger' : 'secondary'); ?>"><?= htmlspecialchars((string)$movimiento['tipo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                            <td class="text-end"><?= htmlspecialchars((string)$movimiento['cantidad'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string)($movimiento['motivo'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string)($movimiento['orden_codigo'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
