<?php

declare(strict_types=1);

$title = $title ?? 'Equipos';
$errors = $errors ?? [];
$old = $old ?? [];
$equipment = $equipment ?? [];
$customers = $customers ?? [];
$types = $types ?? [];
$brands = $brands ?? [];
$models = $models ?? [];
$search = $search ?? '';
$editing = $editing ?? false;

ob_start();
?>
<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="fa-solid fa-mobile-screen-button me-2 text-primary"></i><?= $editing ? 'Actualizar equipo' : 'Registrar equipo'; ?></h2>
                        <p class="text-muted small mb-0">Completa la información y vincula el equipo al cliente correspondiente.</p>
                    </div>
                    <?php if ($editing): ?>
                        <span class="badge bg-primary-subtle text-primary"><i class="fa-solid fa-rotate"></i> Modo edición</span>
                    <?php else: ?>
                        <span class="badge bg-dark-subtle text-dark"><i class="fa-solid fa-database me-1"></i>Catálogo en vivo</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errors['general'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form action="/equipos" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="equipo_id" value="<?= htmlspecialchars((string)($old['equipo_id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="cliente_id" class="form-label">Cliente<span class="text-danger"> *</span></label>
                            <select class="form-select <?= isset($errors['cliente_id']) ? 'is-invalid' : ''; ?>" id="cliente_id" name="cliente_id" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= (int) $customer['id']; ?>" <?= ((int)($old['cliente_id'] ?? 0) === (int) $customer['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($customer['nombre_razon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?><?= $customer['documento'] ? ' - ' . htmlspecialchars((string)$customer['documento'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['cliente_id'] ?? 'Selecciona el cliente asociado.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo_equipo_id" class="form-label">Tipo de equipo<span class="text-danger"> *</span></label>
                            <select class="form-select <?= isset($errors['tipo_equipo_id']) ? 'is-invalid' : ''; ?>" id="tipo_equipo_id" name="tipo_equipo_id" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= (int) $type['id']; ?>" <?= ((int)($old['tipo_equipo_id'] ?? 0) === (int) $type['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($type['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['tipo_equipo_id'] ?? 'Elige el tipo de equipo.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="marca_id" class="form-label">Marca<span class="text-danger"> *</span></label>
                            <select class="form-select <?= isset($errors['marca_id']) ? 'is-invalid' : ''; ?>" id="marca_id" name="marca_id" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= (int) $brand['id']; ?>" <?= ((int)($old['marca_id'] ?? 0) === (int) $brand['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($brand['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['marca_id'] ?? 'Selecciona la marca.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modelo_id" class="form-label">Modelo registrado</label>
                            <select class="form-select <?= isset($errors['modelo_id']) ? 'is-invalid' : ''; ?>" id="modelo_id" name="modelo_id">
                                <option value="0" data-marca="">Selecciona...</option>
                                <?php foreach ($models as $model): ?>
                                    <option value="<?= (int) $model['id']; ?>" data-marca="<?= (int) $model['marca_id']; ?>" <?= ((int)($old['modelo_id'] ?? 0) === (int) $model['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($model['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['modelo_id'] ?? 'Selecciona un modelo o ingresa uno nuevo.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                            <div class="form-text">Si no encuentras el modelo, ingrésalo en el campo siguiente.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modelo_nombre" class="form-label">Modelo nuevo (si no aparece)</label>
                            <input type="text" class="form-control" id="modelo_nombre" name="modelo_nombre" value="<?= htmlspecialchars($old['modelo_nombre'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="120" placeholder="Ej. Equipo personalizado">
                        </div>
                        <div class="col-md-6">
                            <label for="numero_serie" class="form-label">Número de serie</label>
                            <input type="text" class="form-control <?= isset($errors['numero_serie']) ? 'is-invalid' : ''; ?>" id="numero_serie" name="numero_serie" value="<?= htmlspecialchars($old['numero_serie'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" minlength="5" maxlength="120">
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['numero_serie'] ?? 'Ingresa una serie válida o déjalo en blanco.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="imei" class="form-label">IMEI</label>
                            <input type="text" class="form-control <?= isset($errors['imei']) ? 'is-invalid' : ''; ?>" id="imei" name="imei" value="<?= htmlspecialchars($old['imei'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="16" pattern="^[0-9]{14,16}$">
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['imei'] ?? 'El IMEI debe tener entre 14 y 16 dígitos.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="color" name="color" value="<?= htmlspecialchars($old['color'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="60">
                        </div>
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="255"><?= htmlspecialchars($old['descripcion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label for="accesorios_base" class="form-label">Accesorios base</label>
                            <input type="text" class="form-control" id="accesorios_base" name="accesorios_base" value="<?= htmlspecialchars($old['accesorios_base'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="255" placeholder="Cargador, funda, etc.">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="/equipos" class="btn btn-outline-secondary"><i class="fa-solid fa-eraser me-2"></i>Limpiar</a>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i><?= $editing ? 'Actualizar equipo' : 'Guardar equipo'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="fa-solid fa-list me-2 text-primary"></i>Equipos registrados</h2>
                        <p class="text-muted small mb-0">Listado de equipos vinculados a clientes. Puedes editar o eliminar desde aquí.</p>
                    </div>
                    <form class="d-flex" action="/equipos" method="GET">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="search" class="form-control" name="buscar" value="<?= htmlspecialchars($search, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Cliente, serie o IMEI">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary ms-2"><i class="fa-solid fa-search"></i></button>
                    </form>
                </div>

                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">Cliente</th>
                            <th scope="col">Equipo</th>
                            <th scope="col">Serie</th>
                            <th scope="col">IMEI</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($equipment)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Aún no hay equipos registrados o no coinciden con la búsqueda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipment as $item): ?>
                                <tr<?= ((int)($old['equipo_id'] ?? 0) === (int)$item['id']) ? ' class="table-primary"' : ''; ?>>
                                    <td>
                                        <strong><?= htmlspecialchars($item['cliente_nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong><br>
                                        <small class="text-muted">Documento: <?= htmlspecialchars($item['cliente_documento'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($item['tipo_nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?><br>
                                        <small class="text-muted">Marca: <?= htmlspecialchars($item['marca_nombre'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?><?= $item['modelo_nombre'] ? ' · Modelo: ' . htmlspecialchars($item['modelo_nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : ''; ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($item['numero_serie'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($item['imei'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <a href="/equipos?editar=<?= (int) $item['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <form action="/equipos/eliminar" method="POST" class="d-inline" data-confirm="¿Eliminar equipo?">
                                            <input type="hidden" name="equipo_id" value="<?= (int) $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="text-muted small mt-3 mb-0">Mostrando hasta 100 equipos ordenados por fecha de registro. Usa la búsqueda para localizar un equipo por cliente, serie o IMEI.</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
    const brandSelect = document.getElementById('marca_id');
    const modelSelect = document.getElementById('modelo_id');

    const filterModels = () => {
        if (!brandSelect || !modelSelect) {
            return;
        }

        const selectedBrand = brandSelect.value;
        let hasVisibleOption = false;

        modelSelect.querySelectorAll('option').forEach(option => {
            const optionBrand = option.getAttribute('data-marca');
            if (!optionBrand) {
                option.hidden = false;
                return;
            }

            const matches = selectedBrand !== '' && optionBrand === selectedBrand;
            option.hidden = !matches;

            if (matches) {
                hasVisibleOption = true;
            }

            if (!matches && option.selected) {
                option.selected = false;
            }
        });

        modelSelect.disabled = selectedBrand === '' || !hasVisibleOption;
    };

    if (brandSelect) {
        brandSelect.addEventListener('change', filterModels);
        filterModels();
    }

    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            const message = form.getAttribute('data-confirm') || '¿Confirmas la acción?';

            Swal.fire({
                icon: 'warning',
                title: message,
                text: 'Esta operación no se puede deshacer.',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            }).then(result => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
<?php
$scripts = ob_get_clean();

require __DIR__ . '/../layouts/main.php';
