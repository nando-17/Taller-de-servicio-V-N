<?php

declare(strict_types=1);

$ordenes = $ordenes ?? [];
$statusCatalog = $statusCatalog ?? [];
$statusSummary = $statusSummary ?? [];
$statusFilter = $statusFilter ?? '';
$searchTerm = $searchTerm ?? '';
$userRole = $userRole ?? App\Core\Session::getUserRole();

$totalOrdenes = count($ordenes);

ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-list-check me-2 text-primary"></i>Órdenes de servicio</h1>
        <p class="text-muted mb-0">Controla cada etapa desde la recepción hasta la entrega.</p>
    </div>
    <div class="d-flex gap-2">
        <form class="d-flex align-items-center gap-2" method="GET" action="/ordenservicio">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="search" name="buscar" class="form-control" placeholder="Código, cliente o equipo" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
            </div>
            <select name="estado" class="form-select form-select-sm">
                <option value="">Todos los estados</option>
                <?php foreach ($statusCatalog as $estado): ?>
                    <?php $codigo = strtoupper((string)($estado['codigo'] ?? '')); ?>
                    <option value="<?= htmlspecialchars($codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" <?= $statusFilter === $codigo ? 'selected' : ''; ?>><?= htmlspecialchars($estado['nombre'] ?? $codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-primary btn-sm">Filtrar</button>
        </form>
        <?php if (in_array($userRole, ['ADMIN', 'OPERADOR'], true)): ?>
            <a href="/ordenservicio/crear" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i>Nueva orden</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-muted text-uppercase small mb-1">Total listadas</p>
                <h2 class="h4 mb-0"><?= $totalOrdenes; ?></h2>
                <?php if ($searchTerm !== '' || $statusFilter !== ''): ?>
                    <p class="small text-muted mb-0">Aplicando filtros actuales.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php foreach ($statusSummary as $resumen): ?>
        <div class="col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <p class="text-muted text-uppercase small mb-1"><?= htmlspecialchars($resumen['nombre'] ?? $resumen['codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                    <h2 class="h4 mb-0"><?= (int)($resumen['total'] ?? 0); ?></h2>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Equipo</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Recepción</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No se encontraron órdenes que coincidan con el filtro aplicado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordenes as $orden): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($orden['codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($orden['cliente'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td>
                                <?= htmlspecialchars($orden['equipo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                <?php if (!empty($orden['equipo_descripcion'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($orden['equipo_descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= htmlspecialchars($orden['prioridad_nombre'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($orden['estado'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                            <td><?= htmlspecialchars(isset($orden['fecha_recepcion']) ? date('d/m/Y H:i', strtotime((string)$orden['fecha_recepcion'])) : '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/ordenservicio/ver/<?= (int)($orden['id'] ?? 0); ?>" class="btn btn-outline-primary" title="Gestionar"><i class="fa-solid fa-up-right-from-square"></i></a>
                                    <?php if (in_array($userRole, ['ADMIN', 'OPERADOR'], true)): ?>
                                        <a href="/ordenservicio/assign/<?= (int)($orden['id'] ?? 0); ?>" class="btn btn-outline-secondary" title="Asignar técnico"><i class="fa-solid fa-user-gear"></i></a>
                                    <?php endif; ?>
                                    <?php if (in_array($userRole, ['ADMIN', 'TECNICO'], true)): ?>
                                        <a href="/ordenservicio/diagnose/<?= (int)($orden['id'] ?? 0); ?>" class="btn btn-outline-warning" title="Registrar diagnóstico"><i class="fa-solid fa-stethoscope"></i></a>
                                    <?php endif; ?>
                                    <a href="/ordenservicio/pdf/<?= (int)($orden['id'] ?? 0); ?>" class="btn btn-outline-success" title="Descargar PDF"><i class="fa-solid fa-file-arrow-down"></i></a>
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

<?php
$content = ob_get_clean();

require __DIR__ . '/../layouts/main.php';
