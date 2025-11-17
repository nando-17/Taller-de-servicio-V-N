<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CatalogoController;
use App\Controllers\CitaController;
use App\Controllers\ErrorController;
use App\Controllers\CustomerController;
use App\Controllers\EquipmentController;
use App\Controllers\EquipoController;
use App\Controllers\FacturacionController;
use App\Controllers\HomeController;
use App\Controllers\InventarioController;
use App\Controllers\OrdenServicioController;
use App\Controllers\PagoController;
use App\Controllers\ReporteController;
use App\Controllers\UsuarioController;
use App\Core\AuthMiddleware;
use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\Session;

require_once __DIR__ . '/../app/bootstrap.php';

$appEnv = strtolower((string)($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? ''));
$debugValue = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
$isDebug = false;

if ($debugValue !== false && $debugValue !== null) {
    $isDebug = filter_var((string)$debugValue, FILTER_VALIDATE_BOOLEAN);
} elseif ($appEnv !== '') {
    $isDebug = in_array($appEnv, ['development', 'local', 'dev'], true);
}

(new ErrorHandler(__DIR__ . '/../storage/logs', $isDebug))->register();

if (PHP_SAPI === 'cli-server') {
    $requestedPath   = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $publicDirectory = realpath(__DIR__);
    $fullPath        = $publicDirectory !== false ? realpath($publicDirectory . $requestedPath) : false;

    if (
        $requestedPath !== '/' &&
        $fullPath !== false &&
        $publicDirectory !== false &&
        str_starts_with($fullPath, $publicDirectory) &&
        is_file($fullPath)
    ) {
        return false;
    }
}

Session::start();

$router = new Router();
$router->setNotFoundHandler([ErrorController::class, 'notFound']);

/**
 * Público
 */
$router->get('/', [HomeController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLoginForm']);
$router->post('/login', [AuthController::class, 'authenticate']);

/**
 * Privado
 */
$router->group(['middleware' => [[AuthMiddleware::class, 'handle']]], function (Router $router) {

    // Auth
    $router->get('/logout', [AuthController::class, 'logout']);

    // Clientes
    $router->get('/clientes', [CustomerController::class, 'form']);
    $router->post('/clientes', [CustomerController::class, 'save']);
    $router->post('/clientes/eliminar', [CustomerController::class, 'delete']);

    // Equipos (ABM)
    $router->get('/equipos', [EquipmentController::class, 'form']);
    $router->post('/equipos', [EquipmentController::class, 'save']);
    $router->post('/equipos/eliminar', [EquipmentController::class, 'delete']);

    // Órdenes de servicio (TODOS alineados con $orderId y $itemId)
    $router->get('/ordenservicio/crear', [OrdenServicioController::class, 'create']);
    $router->post('/ordenservicio/store', [OrdenServicioController::class, 'store']);
    $router->get('/ordenservicio', [OrdenServicioController::class, 'index']);
    $router->get('/ordenservicio/ver/{id}', [OrdenServicioController::class, 'show']);
    $router->get('/ordenservicio/pdf/{id}', [OrdenServicioController::class, 'downloadPdf']);
    $router->get('/ordenservicio/assign/{id}', [OrdenServicioController::class, 'assign']);
    $router->post('/ordenservicio/update-assignment/{id}', [OrdenServicioController::class, 'updateAssignment']);
    $router->post('/ordenservicio/estado/{id}', [OrdenServicioController::class, 'changeStatus']);
    $router->post('/ordenservicio/{id}/servicios', [OrdenServicioController::class, 'storeServiceItem']);
    $router->post('/ordenservicio/{orderId}/servicios/{itemId}/eliminar', [OrdenServicioController::class, 'deleteServiceItem']);

    $router->post('/ordenservicio/{orderId}/repuestos', [OrdenServicioController::class, 'storeRepuestoItem']);
    $router->post('/ordenservicio/{orderId}/repuestos/{itemId}/eliminar', [OrdenServicioController::class, 'deleteRepuestoItem']);

    $router->post('/ordenservicio/{orderId}/entregar', [OrdenServicioController::class, 'deliver']);

    $router->get('/ordenservicio/diagnose/{orderId}', [OrdenServicioController::class, 'diagnose']);
    $router->post('/ordenservicio/store-diagnosis/{orderId}', [OrdenServicioController::class, 'storeDiagnosis']);

    // Inventario
    $router->get('/inventario', [InventarioController::class, 'index']);
    $router->post('/inventario', [InventarioController::class, 'store']);

    // Citas
    $router->get('/citas', [CitaController::class, 'index']);
    $router->post('/citas', [CitaController::class, 'store']);
    $router->get('/citas/eventos', [CitaController::class, 'events']);
    $router->post('/citas/{id}/estado', [CitaController::class, 'updateEstado']); // se asume firma con $id

    // Facturación / Pagos
    $router->get('/facturacion', [FacturacionController::class, 'index']);
    $router->post('/facturacion', [FacturacionController::class, 'store']);

    $router->get('/pagos', [PagoController::class, 'index']);
    $router->get('/pagos/pdf/{id}', [PagoController::class, 'downloadPdf']);
    $router->post('/pagos', [PagoController::class, 'store']);
    $router->post('/pagos/{id}/anular', [PagoController::class, 'anular']); // se asume firma con $id

    // Reportes
    $router->get('/reportes', [ReporteController::class, 'index']);

    // Catálogos
    $router->get('/catalogos', [CatalogoController::class, 'index']);
    $router->get('/catalogos/pdf', [CatalogoController::class, 'downloadPdf']);
    $router->post('/catalogos/servicios', [CatalogoController::class, 'storeServicio']);
    $router->post('/catalogos/servicios/{id}/toggle', [CatalogoController::class, 'toggleServicio']);
    $router->post('/catalogos/servicios/{id}/actualizar', [CatalogoController::class, 'updateServicio']);
    $router->post('/catalogos/repuestos', [CatalogoController::class, 'storeRepuesto']);
    $router->post('/catalogos/repuestos/{id}/toggle', [CatalogoController::class, 'toggleRepuesto']);
    $router->post('/catalogos/repuestos/{id}/actualizar', [CatalogoController::class, 'updateRepuesto']);

    // Usuarios
    $router->get('/usuarios', [UsuarioController::class, 'index']);
    $router->post('/usuarios', [UsuarioController::class, 'store']);
    $router->post('/usuarios/{id}/toggle', [UsuarioController::class, 'toggle']);
    $router->post('/usuarios/{id}/reset', [UsuarioController::class, 'reset']);

    // API Equipos
    $router->get('/equipo/getById/{id}', [EquipoController::class, 'getById']); // se asume firma con $id
});

$router->dispatch($_SERVER['REQUEST_URI'] ?? '/', $_SERVER['REQUEST_METHOD'] ?? 'GET');
