<?php

declare(strict_types=1);

$orden = $orden ?? [];
$errors = $errors ?? [];
$old = $old ?? [];

ob_start();
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-4">
            <i class="fa-solid fa-stethoscope me-2 text-primary"></i>
            Diagnóstico y Presupuesto - OS #<?= htmlspecialchars($orden['codigo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
        </h1>

        <div class="mb-3">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Equipo:</strong> <?= htmlspecialchars($orden['equipo_detalle'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Descripción base:</strong> <?= htmlspecialchars($orden['equipo_descripcion'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Accesorios base:</strong> <?= htmlspecialchars($orden['equipo_accesorios'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p><strong>Falla Reportada:</strong> <?= htmlspecialchars($orden['falla_reportada'] ?? 'N/A', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        </div>

        <form action="/ordenservicio/store-diagnosis/<?= (int)($orden['id'] ?? 0) ?>" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción del Diagnóstico<span class="text-danger"> *</span></label>
                <textarea class="form-control <?= isset($errors['descripcion']) ? 'is-invalid' : '' ?>" id="descripcion" name="descripcion" rows="5" required><?= htmlspecialchars($old['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                <?php if (isset($errors['descripcion'])) : ?>
                    <div class="invalid-feedback d-block">
                        <?= htmlspecialchars($errors['descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6 mb-3">
                <label for="costo_estimado" class="form-label">Costo Estimado del Presupuesto (S/.)</label>
                <input type="number" step="0.01" class="form-control <?= isset($errors['costo_estimado']) ? 'is-invalid' : '' ?>" id="costo_estimado" name="costo_estimado" value="<?= htmlspecialchars((string)($old['costo_estimado'] ?? '0.00'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" min="0">
                <?php if (isset($errors['costo_estimado'])) : ?>
                    <div class="invalid-feedback d-block">
                        <?= htmlspecialchars($errors['costo_estimado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                <a href="/ordenservicio" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-2"></i>Guardar Diagnóstico
                </button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
