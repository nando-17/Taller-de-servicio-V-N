<?php

declare(strict_types=1);

$errors = $errors ?? [];
$old = $old ?? [];

ob_start();
?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h1 class="h4 mb-4"><i class="fa-solid fa-file-circle-plus me-2 text-primary"></i>Nueva Orden de Servicio</h1>
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($errors['general'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
            </div>
        <?php endif; ?>
        <form action="/ordenservicio/store" method="POST" class="row g-3 needs-validation" novalidate>
            <div class="col-md-6">
                <label for="cliente_id" class="form-label">Cliente<span class="text-danger"> *</span></label>
                <select class="form-select <?= isset($errors['cliente_id']) ? 'is-invalid' : ''; ?>" id="cliente_id" name="cliente_id" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= (int)$cliente['id']; ?>" <?= ((int)($old['cliente_id'] ?? 0) === (int)$cliente['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($cliente['nombre_razon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['cliente_id'] ?? 'Selecciona un cliente.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="equipo_id" class="form-label">Equipo<span class="text-danger"> *</span></label>
                <select class="form-select <?= isset($errors['equipo_id']) ? 'is-invalid' : ''; ?>" id="equipo_id" name="equipo_id" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($equipos as $equipo): ?>
                        <option value="<?= (int)$equipo['id']; ?>" <?= ((int)($old['equipo_id'] ?? 0) === (int)$equipo['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($equipo['tipo'] . ' - ' . $equipo['marca'] . ' - ' . $equipo['modelo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['equipo_id'] ?? 'Selecciona un equipo.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
            </div>
            <div class="col-md-6">
                <div id="equipo-info" class="alert alert-secondary h-100 d-flex flex-column justify-content-center mb-0 <?= isset($old['equipo_id']) ? '' : 'd-none'; ?>">
                    <p class="mb-1"><strong>Equipo seleccionado:</strong> <span id="equipo-detalle">-</span></p>
                    <p class="mb-1"><strong>Descripción base:</strong> <span id="equipo-descripcion">-</span></p>
                    <p class="mb-0"><strong>Accesorios base:</strong> <span id="equipo-accesorios">-</span></p>
                </div>
            </div>
            <div class="col-md-6">
                <label for="prioridad_id" class="form-label">Prioridad<span class="text-danger"> *</span></label>
                <select class="form-select <?= isset($errors['prioridad_id']) ? 'is-invalid' : ''; ?>" id="prioridad_id" name="prioridad_id" required>
                    <option value="">Selecciona...</option>
                    <?php foreach ($prioridades as $prioridad): ?>
                        <option value="<?= (int)$prioridad['id']; ?>" <?= ((int)($old['prioridad_id'] ?? 0) === (int)$prioridad['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($prioridad['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['prioridad_id'] ?? 'Selecciona la prioridad.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="ubicacion" class="form-label">Ubicación interna</label>
                <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?= htmlspecialchars($old['ubicacion'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="100" placeholder="Ej. Estante A-3">
            </div>
            <div class="col-12">
                <label for="falla_reportada" class="form-label">Falla reportada<span class="text-danger"> *</span></label>
                <textarea class="form-control <?= isset($errors['falla_reportada']) ? 'is-invalid' : ''; ?>" id="falla_reportada" name="falla_reportada" rows="3" required><?= htmlspecialchars($old['falla_reportada'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['falla_reportada'] ?? 'Describe la falla reportada por el cliente.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
            </div>
            <div class="col-md-6">
                <label for="accesorios_recibidos" class="form-label">Accesorios recibidos</label>
                <input type="text" class="form-control" id="accesorios_recibidos" name="accesorios_recibidos" value="<?= htmlspecialchars($old['accesorios_recibidos'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" maxlength="255" placeholder="Cargador, funda, etc.">
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" value="1" id="garantia" name="garantia" <?= (($old['garantia'] ?? 0) == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="garantia">
                        Equipo en garantía
                    </label>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                <a href="/" class="btn btn-outline-secondary"><i class="fa-solid fa-xmark me-2"></i>Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar orden</button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const equipoSelect = document.getElementById('equipo_id');
        const fallaReportadaTextarea = document.getElementById('falla_reportada');
        const accesoriosRecibidosInput = document.getElementById('accesorios_recibidos');
        const equipoInfo = document.getElementById('equipo-info');
        const equipoDetalle = document.getElementById('equipo-detalle');
        const equipoDescripcion = document.getElementById('equipo-descripcion');
        const equipoAccesorios = document.getElementById('equipo-accesorios');

        const setEquipoInfo = (detalle, descripcion, accesorios) => {
            if (!equipoInfo) {
                return;
            }

            if (detalle || descripcion || accesorios) {
                equipoInfo.classList.remove('d-none');
            } else {
                equipoInfo.classList.add('d-none');
            }

            equipoDetalle.textContent = detalle || '-';
            equipoDescripcion.textContent = descripcion || '-';
            equipoAccesorios.textContent = accesorios || '-';
        };

        const limpiarCamposEquipo = () => {
            setEquipoInfo('', '', '');

            if (fallaReportadaTextarea) {
                if (fallaReportadaTextarea.dataset.autocompleted === 'true' || fallaReportadaTextarea.value.trim() === '') {
                    fallaReportadaTextarea.value = '';
                }
                delete fallaReportadaTextarea.dataset.autocompleted;
            }

            if (accesoriosRecibidosInput) {
                if (accesoriosRecibidosInput.dataset.autocompleted === 'true' || accesoriosRecibidosInput.value.trim() === '') {
                    accesoriosRecibidosInput.value = '';
                }
                delete accesoriosRecibidosInput.dataset.autocompleted;
            }
        };

        const cargarEquipo = (equipoId) => {
            fetch(`/equipo/getById/${equipoId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data) {
                        limpiarCamposEquipo();
                        return;
                    }

                    const detalle = data.tipo && data.marca && data.modelo
                        ? `${data.tipo} - ${data.marca} - ${data.modelo}`
                        : '';

                    setEquipoInfo(detalle, data.descripcion ?? '', data.accesorios_base ?? '');

                    if (data.descripcion && fallaReportadaTextarea && fallaReportadaTextarea.value.trim() === '') {
                        fallaReportadaTextarea.value = data.descripcion;
                        fallaReportadaTextarea.dataset.autocompleted = 'true';
                    }

                    if (data.accesorios_base && accesoriosRecibidosInput && accesoriosRecibidosInput.value.trim() === '') {
                        accesoriosRecibidosInput.value = data.accesorios_base;
                        accesoriosRecibidosInput.dataset.autocompleted = 'true';
                    }
                })
                .catch(() => {
                    limpiarCamposEquipo();
                });
        };

        if (fallaReportadaTextarea) {
            fallaReportadaTextarea.addEventListener('input', () => {
                if (fallaReportadaTextarea.value.trim() !== '') {
                    delete fallaReportadaTextarea.dataset.autocompleted;
                }
            });
        }

        if (accesoriosRecibidosInput) {
            accesoriosRecibidosInput.addEventListener('input', () => {
                if (accesoriosRecibidosInput.value.trim() !== '') {
                    delete accesoriosRecibidosInput.dataset.autocompleted;
                }
            });
        }

        if (equipoSelect.value) {
            cargarEquipo(equipoSelect.value);
        }

        equipoSelect.addEventListener('change', function () {
            const equipoId = this.value;

            if (equipoId) {
                cargarEquipo(equipoId);
            } else {
                limpiarCamposEquipo();
            }
        });
    });
</script>

<?php
require __DIR__ . '/../layouts/main.php';
