<?php

declare(strict_types=1);

$title = $title ?? 'Página no encontrada';

ob_start();
?>
<div class="text-center py-5">
    <div class="display-1 fw-bold text-primary">404</div>
    <h1 class="h3 fw-semibold mb-3">Página no encontrada</h1>
    <p class="text-muted mb-4">
        La ruta solicitada no existe o fue movida. Verifica la dirección o vuelve al inicio del sistema.
    </p>
    <a class="btn btn-outline-primary" href="/">
        <i class="fa-solid fa-house me-2"></i>Ir al panel principal
    </a>
</div>

<?php
$content = ob_get_clean();

require __DIR__ . '/../layouts/main.php';

