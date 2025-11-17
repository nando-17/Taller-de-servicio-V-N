<?php

declare(strict_types=1);

$title = $title ?? 'Panel Principal';
$user = $user ?? null;
$sessionMeta = $sessionMeta ?? [];
$loginAttempts = $loginAttempts ?? [];
$modules = $modules ?? [];
$quickActions = $quickActions ?? [];

$failedAttempts = 0;
if ($user && isset($user['email'])) {
    $failedAttempts = (int)($loginAttempts[$user['email']]['count'] ?? 0);
}

ob_start();
?>
<div class="row g-4 align-items-stretch">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 fw-bold mb-3"><i class="fa-solid fa-gauge-high text-primary me-2"></i>Bienvenido, <?= htmlspecialchars($user['name'] ?? 'Usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
                <p class="text-muted mb-4">Aquí verás únicamente los módulos habilitados para tu rol <strong><?= htmlspecialchars($user['role'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>. Accede rápido y sin distracciones a tus tareas diarias.</p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white border-0 h-100">
                            <div class="card-body">
                                <h2 class="h6 text-uppercase opacity-75">Sesión</h2>
                                <p class="mb-1"><strong>Último acceso:</strong> <?= htmlspecialchars($sessionMeta['last_login_at'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                                <p class="mb-1"><strong>IP:</strong> <?= htmlspecialchars($sessionMeta['ip'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                                <p class="mb-0"><strong>Intentos fallidos:</strong> <?= $failedAttempts; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-body-secondary h-100">
                            <div class="card-body">
                                <h2 class="h6 text-uppercase text-muted">Dispositivo</h2>
                                <p class="small mb-1">Navegador: <?= htmlspecialchars($sessionMeta['user_agent'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                                <p class="small mb-0">Correo en sesión: <?= htmlspecialchars($user['email'] ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($modules)): ?>
                    <hr class="my-4">
                    <h2 class="h5 mb-3">Accesos disponibles</h2>
                    <div class="row g-3">
                        <?php foreach ($modules as $module): ?>
                            <div class="col-md-6">
                                <a href="<?= htmlspecialchars($module['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="card border-0 shadow-sm h-100 text-decoration-none text-reset">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge bg-primary-subtle text-primary"><i class="fa-solid <?= htmlspecialchars($module['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></i></span>
                                            <h3 class="h6 mb-0"><?= htmlspecialchars($module['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h3>
                                        </div>
                                        <p class="text-muted small mb-0"><?= htmlspecialchars($module['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-4" role="alert">
                        Tu rol todavía no tiene módulos habilitados. Contacta al administrador para asignarte permisos.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 d-flex flex-column">
                <h2 class="h5 mb-3">Acciones rápidas</h2>
                <?php if (!empty($quickActions)): ?>
                    <p class="text-muted small">Selecciona una acción frecuente para empezar a trabajar de inmediato.</p>
                    <div class="d-grid gap-2 mb-4">
                        <?php foreach ($quickActions as $action): ?>
                            <a href="<?= htmlspecialchars($action['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="btn btn-outline-primary">
                                <i class="fa-solid <?= htmlspecialchars($action['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars($action['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small">No hay accesos rápidos configurados para tu rol.</p>
                <?php endif; ?>

                <div class="mt-auto">
                    <h3 class="h6 text-uppercase text-muted">Soporte</h3>
                    <p class="small text-muted mb-0">¿Necesitas habilitar un módulo adicional? Solicítalo al responsable de TI indicando tu rol y motivo.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

require __DIR__ . '/../layouts/main.php';
