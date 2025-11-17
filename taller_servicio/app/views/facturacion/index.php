<?php

declare(strict_types=1);

$facturas = $facturas ?? [];
$ordenesDisponibles = $ordenesDisponibles ?? [];
$errors = $errors ?? [];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-file-invoice me-2 text-primary"></i>Facturación</h1>
        <p class="text-muted mb-0">Emite comprobantes para las órdenes de servicio y revisa su estado.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Emitir comprobante</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="/facturacion" method="POST" class="row gy-3">
                    <div class="col-12">
                        <label for="orden_id" class="form-label">Orden de servicio</label>
                        <select id="orden_id" name="orden_id" class="form-select" required>
                            <option value="">Selecciona una orden...</option>
                            <?php foreach ($ordenesDisponibles as $orden): ?>
                                <option value="<?= (int)$orden['id']; ?>"><?= htmlspecialchars($orden['codigo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> · <?= htmlspecialchars($orden['cliente'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> (<?= htmlspecialchars($orden['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select id="tipo" name="tipo" class="form-select" required>
                            <option value="FACTURA">Factura</option>
                            <option value="BOLETA">Boleta</option>
                            <option value="RECIBO">Recibo</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="serie" class="form-label">Serie</label>
                        <input type="text" id="serie" name="serie" class="form-control" placeholder="F001">
                    </div>
                    <div class="col-md-3">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" id="numero" name="numero" class="form-control" placeholder="000123">
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-file-circle-plus me-1"></i>Emitir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Comprobantes emitidos</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Orden</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($facturas)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay comprobantes emitidos.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($facturas as $factura): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($factura['tipo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                        <div class="small text-muted"><?= htmlspecialchars(trim(($factura['serie'] ?? '') . '-' . ($factura['numero'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($factura['cliente_nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td>S/ <?= number_format((float)$factura['total'], 2); ?></td>
                                    <td><span class="badge bg-<?= $factura['estado'] === 'PAGADA' ? 'success' : ($factura['estado'] === 'ANULADA' ? 'secondary' : 'warning'); ?>"><?= htmlspecialchars($factura['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                                    <td><?= htmlspecialchars($factura['orden_codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
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
