<?php

declare(strict_types=1);

$citas = $citas ?? [];
$clientes = $clientes ?? [];
$equipos = $equipos ?? [];
$tecnicos = $tecnicos ?? [];
$errors = $errors ?? [];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>Agenda de citas</h1>
        <p class="text-muted mb-0">Programa y actualiza las citas de recepción, diagnóstico y entrega.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Registrar nueva cita</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger py-2">
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="/citas" method="POST" class="row gy-3">
                    <div class="col-12">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select id="cliente_id" name="cliente_id" class="form-select" required>
                            <option value="">Selecciona un cliente...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= (int)$cliente['id']; ?>"><?= htmlspecialchars($cliente['nombre_razon'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="equipo_id" class="form-label">Equipo (opcional)</label>
                        <select id="equipo_id" name="equipo_id" class="form-select">
                            <option value="">Selecciona un equipo...</option>
                            <?php foreach ($equipos as $equipo): ?>
                                <option value="<?= (int)$equipo['id']; ?>"><?= htmlspecialchars(($equipo['tipo'] ?? '') . ' - ' . ($equipo['marca'] ?? '') . ' ' . ($equipo['modelo'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="tecnico_id" class="form-label">Técnico (opcional)</label>
                        <select id="tecnico_id" name="tecnico_id" class="form-select">
                            <option value="">Selecciona un técnico...</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?= (int)$tecnico['id']; ?>"><?= htmlspecialchars(($tecnico['nombres'] ?? '') . ' ' . ($tecnico['apellidos'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_inicio" class="form-label">Fecha inicio</label>
                        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_fin" class="form-label">Fecha fin</label>
                        <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label for="estado" class="form-label">Estado</label>
                        <select id="estado" name="estado" class="form-select" required>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="CONFIRMADA">Confirmada</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea id="notas" name="notas" rows="3" class="form-control" placeholder="Detalles adicionales"></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-calendar-plus me-1"></i>Guardar cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">Calendario de agenda</h2>
                    <div class="text-muted small"><i class="fa-solid fa-circle me-1 text-warning"></i>Pendiente <i class="fa-solid fa-circle ms-3 me-1 text-success"></i>Confirmada <i class="fa-solid fa-circle ms-3 me-1 text-primary"></i>Atendida <i class="fa-solid fa-circle ms-3 me-1 text-danger"></i>Cancelada</div>
                </div>
                <div id="calendarAgenda"></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Citas programadas</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>Horario</th>
                    <th>Técnico</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No hay citas programadas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas as $cita): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string)$cita['cliente_nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                <?php if (!empty($cita['equipo_descripcion'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars((string)$cita['equipo_descripcion'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$cita['fecha_inicio'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                <br>
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$cita['fecha_fin'])), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </td>
                            <td><?= htmlspecialchars(trim(($cita['tecnico_nombre'] ?? '') . ' ' . ($cita['tecnico_apellido'] ?? '')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                            <td><span class="badge bg-<?= $cita['estado'] === 'CONFIRMADA' ? 'success' : ($cita['estado'] === 'CANCELADA' ? 'danger' : ($cita['estado'] === 'ATENDIDA' ? 'primary' : 'warning')); ?>"><?= htmlspecialchars((string)$cita['estado'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                            <td class="text-end">
                                <form action="/citas/<?= (int)$cita['id']; ?>/estado" method="POST" class="d-flex gap-2 justify-content-end">
                                    <select name="estado" class="form-select form-select-sm" required>
                                        <option value="PENDIENTE" <?= $cita['estado'] === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="CONFIRMADA" <?= $cita['estado'] === 'CONFIRMADA' ? 'selected' : ''; ?>>Confirmada</option>
                                        <option value="ATENDIDA" <?= $cita['estado'] === 'ATENDIDA' ? 'selected' : ''; ?>>Atendida</option>
                                        <option value="CANCELADA" <?= $cita['estado'] === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Actualizar</button>
                                </form>
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
$styles = <<<'HTML'
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
    #calendarAgenda {
        min-height: 520px;
    }
</style>
HTML;

$scripts = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const calendarEl = document.getElementById('calendarAgenda');
        if (!calendarEl || !window.FullCalendar) {
            return;
        }

        const escapeHtml = (unsafe = '') => (
            unsafe
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;')
        );

        const estadoColores = {
            'PENDIENTE': '#ffc107',
            'CONFIRMADA': '#198754',
            'ATENDIDA': '#0d6efd',
            'CANCELADA': '#dc3545'
        };

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es',
            initialView: 'timeGridWeek',
            height: 'auto',
            firstDay: 1,
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: {
                url: '/citas/eventos',
                failure: () => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el calendario de citas.'
                    });
                }
            },
            eventClick: info => {
                info.jsEvent.preventDefault();
                const props = info.event.extendedProps || {};
                const cliente = escapeHtml(props.cliente || 'Cita sin cliente');
                const tecnico = props.tecnico ? `<div><strong>Técnico:</strong> ${escapeHtml(props.tecnico)}</div>` : '';
                const equipo = props.equipo ? `<div><strong>Equipo:</strong> ${escapeHtml(props.equipo)}</div>` : '';
                const notas = props.notas ? `<div class="mt-2"><strong>Notas:</strong><br>${escapeHtml(props.notas)}</div>` : '';
                const inicio = info.event.start ? info.event.start.toLocaleString('es-PE') : '';
                const fin = info.event.end ? info.event.end.toLocaleString('es-PE') : '';
                Swal.fire({
                    icon: 'info',
                    title: cliente,
                    html: `<div><strong>Estado:</strong> ${escapeHtml(props.estado || '')}</div>` +
                          tecnico +
                          equipo +
                          (inicio ? `<div><strong>Inicio:</strong> ${escapeHtml(inicio)}</div>` : '') +
                          (fin ? `<div><strong>Fin:</strong> ${escapeHtml(fin)}</div>` : '') +
                          notas
                });
            },
            eventDidMount: info => {
                const estado = info.event.extendedProps?.estado || '';
                const color = estadoColores[estado] || '#0d6efd';
                info.el.style.setProperty('background-color', color, 'important');
                info.el.style.setProperty('border-color', color, 'important');
                info.el.style.setProperty('color', '#fff', 'important');
            }
        });

        calendar.render();
    });
</script>
HTML;

$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
