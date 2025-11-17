<?php

declare(strict_types=1);

$ordenesPorEstado = $ordenesPorEstado ?? [];
$ventasPorMes = $ventasPorMes ?? [];
$productividad = $productividad ?? [];
$stockCritico = $stockCritico ?? [];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-chart-line me-2 text-primary"></i>Reportes operativos</h1>
        <p class="text-muted mb-0">Visualiza indicadores clave para la gestión del taller.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Órdenes por estado</h2>
                <?php if (empty($ordenesPorEstado)): ?>
                    <p class="text-muted">Sin datos disponibles.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($ordenesPorEstado as $estado): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($estado['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                <span class="badge bg-primary rounded-pill"><?= htmlspecialchars((string)$estado['total'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Ventas últimos 6 meses</h2>
                <?php if (empty($ventasPorMes)): ?>
                    <p class="text-muted">Sin facturación registrada.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <tbody>
                        <?php foreach ($ventasPorMes as $venta): ?>
                            <tr>
                                <td><?= htmlspecialchars($venta['periodo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                <td class="text-end">S/ <?= number_format((float)$venta['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Productividad por técnico</h2>
                <?php if (empty($productividad)): ?>
                    <p class="text-muted">No hay técnicos asignados.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($productividad as $fila): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($fila['tecnico'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                <span class="badge bg-success rounded-pill"><?= htmlspecialchars((string)$fila['total'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Stock crítico de repuestos</h2>
        <?php if (empty($stockCritico)): ?>
            <p class="text-muted">No hay repuestos con stock por debajo del mínimo.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Repuesto</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Mínimo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stockCritico as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td class="text-center"><span class="badge bg-danger"><?= htmlspecialchars((string)$item['stock'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                            <td class="text-center"><?= htmlspecialchars((string)$item['stock_minimo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
