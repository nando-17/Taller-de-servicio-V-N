<?php

declare(strict_types=1);

$orden = $orden ?? [];
$tecnicos = $tecnicos ?? [];
$errors = $errors ?? [];

ob_start();
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-4">
            <i class="fa-solid fa-user-plus me-2 text-primary"></i>
            Asignar Técnico a OS #<?= htmlspecialchars($orden['codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </h1>

        <div class="mb-3">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Equipo:</strong> <?= htmlspecialchars($orden['equipo_detalle'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Descripción base:</strong> <?= htmlspecialchars($orden['equipo_descripcion'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Accesorios base:</strong> <?= htmlspecialchars($orden['equipo_accesorios'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Falla Reportada:</strong> <?= htmlspecialchars($orden['falla_reportada'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        </div>

        <form action="/ordenservicio/update-assignment/<?= (int)($orden['id'] ?? 0) ?>" method="POST" class="needs-validation" novalidate>
            <div class="col-md-6">
                <label for="tecnico_id" class="form-label">Técnico disponible<span class="text-danger"> *</span></label>
                <select class="form-select <?= isset($errors['tecnico_id']) ? 'is-invalid' : '' ?>" id="tecnico_id" name="tecnico_id" required>
                    <option value="">Selecciona un técnico...</option>
                    <?php foreach ($tecnicos as $tecnico) : ?>
                        <option value="<?= (int)$tecnico['id'] ?>">
                            <?= htmlspecialchars($tecnico['nombres'] . ' ' . $tecnico['apellidos'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['tecnico_id'])) : ?>
                    <div class="invalid-feedback d-block">
                        <?= htmlspecialchars($errors['tecnico_id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                <a href="/ordenservicio" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-user-check me-2"></i>Asignar Técnico
                </button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
