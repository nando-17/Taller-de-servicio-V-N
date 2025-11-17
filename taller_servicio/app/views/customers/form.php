<?php

declare(strict_types=1);

$title = $title ?? 'Clientes';
$errors = $errors ?? [];
$old = $old ?? [];
$customers = $customers ?? [];
$search = $search ?? '';
$editing = $editing ?? false;

ob_start();
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="fa-solid fa-users me-2 text-primary"></i>Clientes registrados</h2>
                        <p class="text-muted small mb-0">Consulta, edita o elimina clientes existentes.</p>
                    </div>
                    <form class="d-flex" action="/clientes" method="GET">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="search" class="form-control" id="buscar" name="buscar" value="<?= htmlspecialchars($search, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Documento o nombre">
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary ms-2"><i class="fa-solid fa-search"></i></button>
                    </form>
                </div>

                <div class="table-responsive flex-grow-1">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th scope="col">Cliente</th>
                            <th scope="col">Documento</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col" class="text-end">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($customers)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No se encontraron clientes para el criterio ingresado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($customers as $customer): ?>
                                <tr<?= ((int)($old['cliente_id'] ?? 0) === (int)$customer['id']) ? ' class="table-primary"' : ''; ?>>
                                    <td>
                                        <strong><?= htmlspecialchars($customer['nombre_razon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong><br>
                                        <small class="text-muted">Tipo: <?= htmlspecialchars($customer['tipo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($customer['documento'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($customer['telefono'] ?? '—', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <a href="/clientes?editar=<?= (int)$customer['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen-to-square"></i></a>
                                        <form action="/clientes/eliminar" method="POST" class="d-inline" data-confirm="¿Eliminar cliente?">
                                            <input type="hidden" name="cliente_id" value="<?= (int)$customer['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-3 mb-0">Mostrando hasta 100 registros recientes. Utiliza la búsqueda para filtrar por nombre o documento.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div>
                        <h2 class="h5 mb-1"><i class="fa-solid fa-user-pen me-2 text-primary"></i><?= $editing ? 'Actualizar cliente' : 'Registrar nuevo cliente'; ?></h2>
                        <p class="text-muted small mb-0">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
                    </div>
                    <?php if ($editing): ?>
                        <span class="badge bg-primary-subtle text-primary"><i class="fa-solid fa-rotate"></i> Modo edición</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errors['general'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form action="/clientes" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="cliente_id" value="<?= htmlspecialchars((string)($old['cliente_id'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de cliente<span class="text-danger"> *</span></label>
                            <select class="form-select <?= isset($errors['tipo']) ? 'is-invalid' : ''; ?>" id="tipo" name="tipo" required>
                                <option value="">Selecciona...</option>
                                <option value="NATURAL" <?= (($old['tipo'] ?? '') === 'NATURAL') ? 'selected' : ''; ?>>Persona Natural</option>
                                <option value="JURIDICA" <?= (($old['tipo'] ?? '') === 'JURIDICA') ? 'selected' : ''; ?>>Persona Jurídica</option>
                            </select>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['tipo'] ?? 'Indica el tipo de cliente.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="nombre_razon" class="form-label">Nombre/Razón social<span class="text-danger"> *</span></label>
                            <input type="text" class="form-control <?= isset($errors['nombre_razon']) ? 'is-invalid' : ''; ?>" id="nombre_razon" name="nombre_razon" value="<?= htmlspecialchars($old['nombre_razon'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required minlength="3">
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['nombre_razon'] ?? 'Ingresa el nombre del cliente.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="documento" class="form-label">Documento</label>
                            <input type="text" class="form-control <?= isset($errors['documento']) ? 'is-invalid' : ''; ?>" id="documento" name="documento" value="<?= htmlspecialchars($old['documento'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" minlength="6" maxlength="30" pattern="^[A-Za-z0-9-]+$">
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['documento'] ?? 'El documento debe tener al menos 6 caracteres.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="160">
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['email'] ?? 'Ingresa un correo válido.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : ''; ?>" id="telefono" name="telefono" value="<?= htmlspecialchars($old['telefono'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" pattern="^[0-9+\-\s]{6,20}$" placeholder="Ej. +51 987654321">
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['telefono'] ?? 'Ingresa un teléfono válido.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2" maxlength="200"><?= htmlspecialchars($old['direccion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="2" maxlength="255"><?= htmlspecialchars($old['observaciones'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="/clientes" class="btn btn-outline-secondary"><i class="fa-solid fa-eraser me-2"></i>Limpiar</a>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i><?= $editing ? 'Actualizar cliente' : 'Guardar cliente'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            event.preventDefault();
            const question = form.getAttribute('data-confirm') || '¿Confirmas la acción?';

            Swal.fire({
                icon: 'warning',
                title: question,
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
