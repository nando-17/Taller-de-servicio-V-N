<?php

declare(strict_types=1);

$usuarios = $usuarios ?? [];
$errors = $errors ?? [];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="fa-solid fa-user-shield me-2 text-primary"></i>Usuarios y roles</h1>
        <p class="text-muted mb-0">Gestiona el acceso al sistema y los perfiles de trabajo.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Nuevo usuario</h2>
                <form action="/usuarios" method="POST" class="row gy-3">
                    <div class="col-12">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required>
                        <?php if (!empty($errors['nombre'] ?? '')): ?><div class="text-danger small"><?= htmlspecialchars($errors['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                        <?php if (!empty($errors['email'] ?? '')): ?><div class="text-danger small"><?= htmlspecialchars($errors['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="rol" class="form-label">Rol</label>
                        <select id="rol" name="rol" class="form-select" required>
                            <option value="OPERADOR">Operador</option>
                            <option value="TECNICO">Técnico</option>
                            <option value="ADMIN">Administrador</option>
                        </select>
                        <?php if (!empty($errors['rol'] ?? '')): ?><div class="text-danger small"><?= htmlspecialchars($errors['rol'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña inicial</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <?php if (!empty($errors['password'] ?? '')): ?><div class="text-danger small"><?= htmlspecialchars($errors['password'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus me-1"></i>Crear usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Usuarios registrados</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay usuarios registrados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($usuario['rol'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                                    <td><span class="badge bg-<?= $usuario['activo'] ? 'success' : 'secondary'; ?>"><?= $usuario['activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <form action="/usuarios/<?= (int)$usuario['id']; ?>/toggle" method="POST">
                                                <button type="submit" class="btn btn-outline-secondary"><?= $usuario['activo'] ? 'Desactivar' : 'Activar'; ?></button>
                                            </form>
                                            <form action="/usuarios/<?= (int)$usuario['id']; ?>/reset" method="POST">
                                                <button type="submit" class="btn btn-outline-primary">Resetear clave</button>
                                            </form>
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
    </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
