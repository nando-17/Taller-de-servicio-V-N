<?php

declare(strict_types=1);

$facturas = $facturas ?? [];
$errors = $errors ?? [];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-cash-register me-2 text-primary"></i>Pagos</h1>
        <p class="text-muted mb-0">Registra y concilia los pagos de los comprobantes emitidos.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Registrar pago</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="/pagos" method="POST" class="row gy-3">
                    <div class="col-12">
                        <label for="factura_id" class="form-label">Comprobante</label>
                        <select id="factura_id" name="factura_id" class="form-select" required>
                            <option value="">Selecciona un comprobante...</option>
                            <?php foreach ($facturas as $factura): ?>
                                <option value="<?= (int)$factura['id']; ?>">
                                    <?= htmlspecialchars($factura['tipo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> <?= htmlspecialchars(trim(($factura['serie'] ?? '') . '-' . ($factura['numero'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> · Pendiente: S/ <?= number_format((float)$factura['pendiente'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="monto" class="form-label">Monto (S/)</label>
                        <input type="number" step="0.01" min="0.01" id="monto" name="monto" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="metodo_pago" class="form-label">Método</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-select" required>
                            <option value="EFECTIVO">Efectivo</option>
                            <option value="TARJETA">Tarjeta</option>
                            <option value="TRANSFERENCIA">Transferencia</option>
                            <option value="YAPE">Yape</option>
                            <option value="PLIN">Plin</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="referencia" class="form-label">Referencia</label>
                        <input type="text" id="referencia" name="referencia" class="form-control" placeholder="Número de operación o nota (opcional)">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar pago</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Estado de comprobantes</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Comprobante</th>
                            <th>Total</th>
                            <th>Pagado</th>
                            <th>Pendiente</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($facturas)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay comprobantes disponibles.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facturas as $factura): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($factura['tipo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars(trim(($factura['serie'] ?? '') . '-' . ($factura['numero'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                    </td>
                                    <td>S/ <?= number_format((float)$factura['total'], 2); ?></td>
                                    <td>S/ <?= number_format((float)$factura['pagado'], 2); ?></td>
                                    <td>S/ <?= number_format((float)$factura['pendiente'], 2); ?></td>
                                    <td><span class="badge bg-<?= $factura['estado'] === 'PAGADA' ? 'success' : ($factura['estado'] === 'ANULADA' ? 'secondary' : 'warning'); ?>"><?= htmlspecialchars($factura['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                                     <td class="text-end">
                                        <a href="/pagos/pdf/<?= (int)$factura['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download"></i></a>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
