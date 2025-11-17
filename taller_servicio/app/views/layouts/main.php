<?php
declare(strict_types=1);

use App\Core\Session;

$userRole = Session::getUserRole();
$userName = Session::get('user_name');
$isLoggedIn = Session::isLoggedIn();
$flash = Session::get('flash');
Session::remove('flash');

$menuItems = [
    [
        'href' => '/',
        'icon' => 'fa-solid fa-gauge-high',
        'label' => 'Dashboard',
        'roles' => ['ADMIN', 'OPERADOR', 'TECNICO'],
    ],
    [
        'href' => '/clientes',
        'icon' => 'fa-solid fa-users',
        'label' => 'Clientes',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/equipos',
        'icon' => 'fa-solid fa-mobile-screen-button',
        'label' => 'Equipos',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/citas',
        'icon' => 'fa-solid fa-calendar-days',
        'label' => 'Agenda',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/ordenservicio',
        'icon' => 'fa-solid fa-list-check',
        'label' => 'Órdenes de Servicio',
        'roles' => ['ADMIN', 'OPERADOR', 'TECNICO'],
    ],
    [
        'href' => '/inventario',
        'icon' => 'fa-solid fa-warehouse',
        'label' => 'Inventario',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/facturacion',
        'icon' => 'fa-solid fa-file-invoice-dollar',
        'label' => 'Facturación',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/pagos',
        'icon' => 'fa-solid fa-money-check-dollar',
        'label' => 'Pagos',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/reportes',
        'icon' => 'fa-solid fa-chart-line',
        'label' => 'Reportes',
        'roles' => ['ADMIN', 'OPERADOR'],
    ],
    [
        'href' => '/catalogos',
        'icon' => 'fa-solid fa-tags',
        'label' => 'Catálogos',
        'roles' => ['ADMIN'],
    ],
    [
        'href' => '/usuarios',
        'icon' => 'fa-solid fa-user-gear',
        'label' => 'Usuarios',
        'roles' => ['ADMIN'],
    ],
];

/** Helpers simples para URL de assets (opcional) */
if (!function_exists('asset')) {
    function asset(string $path): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost'; // incluye puerto si corresponde
        $base   = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'); // ej: "" o "/repuestos"
        $base   = $base === '/' ? '' : $base;
        return $scheme . '://' . $host . $base . '/' . ltrim($path, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= htmlspecialchars($title ?? 'Sistema de Taller', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>

    <!-- Fuente -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap (sin SRI para evitar bloqueos por hash) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Font Awesome (sin SRI) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <?= $styles ?? '' ?>

    <!-- Tu CSS opcional -->
    <!-- <link rel="stylesheet" href="<?= asset('css/app.css') ?>"> -->

    <style>
        :root{
            --bg-app:#f5f6fa;
        }
        html,body{height:100%}
        body{
            font-family:'Nunito', system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol", sans-serif;
            background:var(--bg-app);
        }
        .navbar-brand{font-weight:700;letter-spacing:.02rem}
        .container-narrow{max-width:1100px}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container container-narrow">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <span>Taller de servicio VN</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div id="mainNav" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if ($isLoggedIn): ?>
                    <?php foreach ($menuItems as $item): ?>
                        <?php
                        $allowedRoles = $item['roles'] ?? [];
                        $canSee = $userRole !== null && (empty($allowedRoles) || in_array($userRole, $allowedRoles, true));
                        ?>
                        <?php if ($canSee): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= htmlspecialchars($item['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                    <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?> me-1"></i><?= htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user me-1"></i>
                            <?= htmlspecialchars($userName ?? 'Usuario', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-item-text text-muted small">
                                <strong>Rol:</strong>
                                <?= htmlspecialchars($userRole ?? 'N/D', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Cerrar sesión</a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login"><i class="fa-solid fa-right-to-bracket me-1"></i>Ingresar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container container-narrow py-4">
    <?= $content ?? '' ?>
</main>

<!-- Bootstrap bundle (sin SRI) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 (sin SRI) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
(() => {
    // Validación Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    for (const form of forms) {
        form.addEventListener('submit', ev => {
            if (!form.checkValidity()) {
                ev.preventDefault();
                ev.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    }
})();
<?php if ($flash): ?>
// Notificación flash
Swal.fire({
    icon: <?= json_encode((string)($flash['type'] ?? 'info')) ?>,
    title: 'Notificación',
    text: <?= json_encode((string)($flash['message'] ?? '')) ?>,
    confirmButtonColor: '#0d6efd'
});
<?php endif; ?>
</script>

<?= $scripts ?? '' ?>
</body>
</html>
