<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ClienteModel;
use App\Models\DetalleFactura;
use App\Models\DiagnosticoModel;
use App\Models\EquipoModel;
use App\Models\Factura;
use App\Models\InventarioMovimiento;
use App\Models\OrdenEstadoHistorial;
use App\Models\OrdenRepuestoItemModel;
use App\Models\OrdenServicioItemModel;
use App\Models\OrdenServicioModel;
use App\Models\Pago;
use App\Models\Repuesto;
use App\Models\RepuestoExistenciaModel;
use App\Models\Servicio;
use App\Models\TecnicoModel;
use Mpdf\Mpdf;

class OrdenServicioController extends Controller
{
    private OrdenServicioModel $ordenServicioModel;

    public function __construct()
    {
        $this->ordenServicioModel = new OrdenServicioModel();
    }

    public function index(): void
    {
        $this->requireAuth();
        $statusFilter = strtoupper(trim((string)($_GET['estado'] ?? '')));
        $search = trim((string)($_GET['buscar'] ?? ''));

        $ordenes = $this->ordenServicioModel->getAll(
            $statusFilter !== '' ? $statusFilter : null,
            $search !== '' ? $search : null
        );

        $statusCatalog = $this->ordenServicioModel->getStatusCatalog();
        $summary = [];
        foreach ($statusCatalog as $estado) {
            $codigo = (string)($estado['codigo'] ?? '');
            $summary[$codigo] = [
                'codigo' => $codigo,
                'nombre' => (string)($estado['nombre'] ?? ''),
                'total' => 0,
            ];
        }

        foreach ($ordenes as $orden) {
            $codigo = (string)($orden['estado_codigo'] ?? '');
            if (isset($summary[$codigo])) {
                $summary[$codigo]['total']++;
            }
        }

        $this->view('ordenservicio/index', [
            'ordenes' => $ordenes,
            'statusCatalog' => $statusCatalog,
            'statusSummary' => $summary,
            'statusFilter' => $statusFilter,
            'searchTerm' => $search,
            'userRole' => Session::getUserRole(),
        ]);
    }

    public function create(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->getAll();

        $equipoModel = new EquipoModel();
        $equipos = $equipoModel->getAll();

        $prioridades = $this->ordenServicioModel->getAllPriorities();

        $formErrors = Session::get('form_errors', []);
        $oldInput = Session::get('old', []);

        $this->view('ordenservicio/crear', [
            'clientes' => $clientes,
            'equipos' => $equipos,
            'prioridades' => $prioridades,
            'errors' => $formErrors['orden_servicio'] ?? [],
            'old' => $oldInput['orden_servicio'] ?? [],
        ]);

        Session::remove('form_errors');
        Session::remove('old');
    }

    public function store(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $data = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'equipo_id' => (int)($_POST['equipo_id'] ?? 0),
            'prioridad_id' => (int)($_POST['prioridad_id'] ?? 0),
            'falla_reportada' => trim($_POST['falla_reportada'] ?? ''),
            'accesorios_recibidos' => trim($_POST['accesorios_recibidos'] ?? '') ?: null,
            'garantia' => isset($_POST['garantia']) ? 1 : 0,
            'ubicacion' => trim($_POST['ubicacion'] ?? '') ?: null,
            'usuario_id' => Session::get('user_id'),
        ];

        $errors = $this->validateServiceOrder($data);

        $estadoInicialId = $this->ordenServicioModel->getInitialStatusId();
        if ($estadoInicialId === null) {
            $errors['general'] = 'No se encontró un estado inicial para la orden de servicio.';
        }
        $data['estado_id'] = $estadoInicialId;

        if (!empty($errors)) {
            Session::set('form_errors', ['orden_servicio' => $errors]);
            Session::set('old', ['orden_servicio' => $data]);
            $this->redirect('/ordenservicio/crear');
        }

        $orderId = $this->ordenServicioModel->create($data);

        if ($estadoInicialId !== null) {
            $this->recordHistory(
                $orderId,
                $estadoInicialId,
                'Orden creada en estado inicial.'
            );
        }

        $this->flash('success', 'Orden de servicio registrada correctamente.');
        $this->redirect('/ordenservicio');
    }

    public function assign(int $orderId): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $orden = $this->ordenServicioModel->findById($orderId);
        if (!$orden) {
            $this->flash('danger', 'Orden de servicio no encontrada.');
            $this->redirect('/ordenservicio');
        }

        $tecnicoModel = new TecnicoModel();
        $tecnicos = $tecnicoModel->getAllActive(true);

        $formErrors = Session::get('form_errors', []);

        $this->view('ordenservicio/asignar', [
            'orden' => $orden,
            'tecnicos' => $tecnicos,
            'errors' => $formErrors['assign_technician'] ?? [],
        ]);
        Session::remove('form_errors');
    }

    public function updateAssignment(int $orderId): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $orden = $this->ordenServicioModel->findById($orderId);
        if (!$orden) {
            $this->flash('danger', 'La orden de servicio no existe.');
            $this->redirect('/ordenservicio');
        }

        $tecnicoId = (int)($_POST['tecnico_id'] ?? 0);

        if ($tecnicoId <= 0) {
            Session::set('form_errors', ['assign_technician' => ['tecnico_id' => 'Debe seleccionar un técnico.']]);
            $this->redirect("/ordenservicio/assign/{$orderId}");
        }

        $tecnicoModel = new TecnicoModel();
        $tecnico = $tecnicoModel->findActiveById($tecnicoId, true);
        if ($tecnico === null) {
            Session::set(
                'form_errors',
                ['assign_technician' => ['tecnico_id' => 'El técnico seleccionado no está disponible.']]
            );
            $this->redirect("/ordenservicio/assign/{$orderId}");
        }

        $asignado = $this->ordenServicioModel->assignTechnician($orderId, $tecnicoId, Session::get('user_id'));

        if (!$asignado) {
            $this->flash('danger', 'No se pudo asignar el técnico. Intenta nuevamente.');
            $this->redirect("/ordenservicio/assign/{$orderId}");
        }

        $comentario = trim(sprintf(
            'Técnico asignado: %s %s',
            $tecnico['nombres'] ?? '',
            $tecnico['apellidos'] ?? ''
        ));

        if ($comentario === 'Técnico asignado:') {
            $comentario = 'Técnico asignado.';
        }

        $this->recordHistory(
            $orderId,
            (int) ($orden['estado_id'] ?? 0),
            $comentario
        );

        $this->flash('success', 'Técnico asignado correctamente.');
        $this->redirect("/ordenservicio/ver/{$orderId}");
    }

    public function show(int $orderId): void
    {
        $this->requireAuth();

        $orden = $this->ordenServicioModel->getDetailForManagement($orderId);
        if (!$orden) {
            $this->flash('danger', 'La orden solicitada no existe.');
            $this->redirect('/ordenservicio');
        }

        $tecnicosDisponibles = (new TecnicoModel())->getAllActive(true);
        $historial = (new OrdenEstadoHistorial())->getByOrder($orderId);

        $servicioItemModel = new OrdenServicioItemModel();
        $servicioItems = $servicioItemModel->getByOrder($orderId);
        $totalServicios = array_sum(array_map(static fn(array $item): float => (float)($item['total_linea'] ?? 0), $servicioItems));

        $repuestoItemModel = new OrdenRepuestoItemModel();
        $repuestoItems = $repuestoItemModel->getByOrder($orderId);
        $totalRepuestos = array_sum(array_map(static fn(array $item): float => (float)($item['total_linea'] ?? 0), $repuestoItems));

        $catalogoServicios = (new Servicio())->getActivos();
        $catalogoRepuestos = $repuestoItemModel->getRepuestosDisponibles();

        $estadosCatalogo = $this->ordenServicioModel->getStatusCatalog();
        $transicionesValidas = $this->ordenServicioModel->getValidTransitions((string)$orden['estado_codigo']);
        $estadosDisponibles = array_values(array_filter($estadosCatalogo, static function (array $estado) use ($transicionesValidas) {
            return in_array($estado['codigo'], $transicionesValidas, true);
        }));

        $facturaModel = new Factura();
        $factura = $facturaModel->findByOrdenId($orderId);
        $pagos = [];
        $montoPagado = 0.0;
        if ($factura) {
            $pagoModel = new Pago();
            $pagos = $pagoModel->getByFactura((int)$factura['id']);
            $montoPagado = $pagoModel->sumAppliedByFactura((int)$factura['id']);
        }

        $errors = Session::get('form_errors') ?? [];

        $this->view('ordenservicio/ver', [
            'orden' => $orden,
            'tecnicosDisponibles' => $tecnicosDisponibles,
            'historial' => $historial,
            'servicioItems' => $servicioItems,
            'repuestoItems' => $repuestoItems,
            'catalogoServicios' => $catalogoServicios,
            'catalogoRepuestos' => $catalogoRepuestos,
            'estadosDisponibles' => $estadosDisponibles,
            'factura' => $factura,
            'pagos' => $pagos,
            'montoPagado' => $montoPagado,
            'totales' => [
                'servicios' => $totalServicios,
                'repuestos' => $totalRepuestos,
                'general' => $totalServicios + $totalRepuestos,
            ],
            'errors' => [
                'status' => $errors['change_status'] ?? [],
                'service_item' => $errors['service_item'] ?? [],
                'repuesto_item' => $errors['repuesto_item'] ?? [],
                'deliver' => $errors['deliver'] ?? [],
            ],
        ]);

        Session::remove('form_errors');
    }

    public function changeStatus(int $orderId): void
    {
        $this->authorize(['ADMIN', 'OPERADOR', 'TECNICO']);

        $orden = $this->ordenServicioModel->getDetailForManagement($orderId);
        if (!$orden) {
            $this->flash('danger', 'La orden de servicio no existe.');
            $this->redirect('/ordenservicio');
        }

        $estadoId = (int)($_POST['estado_id'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');

        $errores = [];
        if ($estadoId <= 0) {
            $errores['estado_id'] = 'Selecciona un estado válido.';
        } else {
            $codigoObjetivo = $this->ordenServicioModel->getStatusCodeById($estadoId);
            if ($codigoObjetivo === null) {
                $errores['estado_id'] = 'Estado no encontrado.';
            } else {
                $validos = $this->ordenServicioModel->getValidTransitions((string)$orden['estado_codigo']);
                if (!in_array($codigoObjetivo, $validos, true)) {
                    $errores['estado_id'] = 'La transición solicitada no está permitida.';
                }
            }
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['change_status' => $errores]);
            $this->redirect("/ordenservicio/ver/{$orderId}");
        }

        $this->ordenServicioModel->updateStatus($orderId, $estadoId, (int) Session::get('user_id'));
        $this->recordHistory($orderId, $estadoId, $comentario !== '' ? $comentario : 'Estado actualizado.');

        $this->flash('success', 'Estado de la orden actualizado.');
        $this->redirect("/ordenservicio/ver/{$orderId}");
    }

    public function storeServiceItem(int $orderId): void
    {
        $this->authorize(['ADMIN', 'TECNICO']);

        $servicioId = (int)($_POST['servicio_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $precio = (float)($_POST['precio_unitario'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');

        $errores = [];
        if ($servicioId <= 0) {
            $errores['servicio_id'] = 'Selecciona un servicio válido.';
        }
        if ($cantidad <= 0) {
            $errores['cantidad'] = 'La cantidad debe ser mayor a 0.';
        }
        if ($precio < 0) {
            $errores['precio_unitario'] = 'El precio debe ser mayor o igual a 0.';
        }

        $servicioModel = new Servicio();
        $servicio = $servicioModel->findById($servicioId);
        if ($servicio === null) {
            $errores['servicio_id'] = 'El servicio seleccionado no existe.';
        } elseif ($precio <= 0) {
            $precio = (float)($servicio['precio_base'] ?? 0);
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['service_item' => $errores]);
            $this->redirect("/ordenservicio/ver/{$orderId}");
        }

        $itemModel = new OrdenServicioItemModel();
        $itemModel->create([
            'orden_id' => $orderId,
            'servicio_id' => $servicioId,
            'descripcion' => $descripcion !== '' ? $descripcion : ($servicio['nombre'] ?? null),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
        ]);

        $this->flash('success', 'Servicio agregado a la orden.');
        $this->redirect("/ordenservicio/ver/{$orderId}");
    }

    public function deleteServiceItem(int $orderId, int $itemId): void
    {
        $this->authorize(['ADMIN', 'TECNICO']);

        $itemModel = new OrdenServicioItemModel();
        $itemModel->delete($itemId);

        $this->flash('info', 'Servicio eliminado de la orden.');
        $this->redirect("/ordenservicio/ver/{$orderId}");
    }

    public function storeRepuestoItem(int $orderId): void
    {
        $this->authorize(['ADMIN', 'TECNICO']);

        $repuestoId = (int)($_POST['repuesto_id'] ?? 0);
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $precio = (float)($_POST['precio_unitario'] ?? 0);

        $errores = [];
        if ($repuestoId <= 0) {
            $errores['repuesto_id'] = 'Selecciona un repuesto.';
        }
        if ($cantidad <= 0) {
            $errores['cantidad'] = 'La cantidad debe ser mayor a 0.';
        }
        if ($precio < 0) {
            $errores['precio_unitario'] = 'El precio no puede ser negativo.';
        }

        $repuestoModel = new Repuesto();
        $repuesto = $repuestoModel->findById($repuestoId);
        if ($repuesto === null) {
            $errores['repuesto_id'] = 'El repuesto no existe.';
        } elseif ($precio <= 0) {
            $precio = (float)($repuesto['precio_venta'] ?? 0);
        }

        $stockModel = new RepuestoExistenciaModel();
        $stockDisponible = $stockModel->getStock($repuestoId);
        if ($cantidad > $stockDisponible) {
            $errores['cantidad'] = 'Stock insuficiente para la salida solicitada.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['repuesto_item' => $errores]);
            $this->redirect("/ordenservicio/ver/{$orderId}");
        }

        $itemModel = new OrdenRepuestoItemModel();
        $itemId = $itemModel->create([
            'orden_id' => $orderId,
            'repuesto_id' => $repuestoId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
        ]);

        $stockModel->decreaseStock($repuestoId, $cantidad);

        $movimiento = new InventarioMovimiento();
        $movimiento->registrarMovimiento([
            'repuesto_id' => $repuestoId,
            'tipo' => 'SALIDA',
            'cantidad' => $cantidad,
            'costo_unitario' => $repuesto['precio_costo'] ?? null,
            'motivo' => 'Consumo orden servicio #' . $orderId,
            'orden_id' => $orderId,
            'usuario_id' => Session::get('user_id'),
        ]);

        $this->flash('success', 'Repuesto agregado y stock actualizado.');
        $this->redirect("/ordenservicio/ver/{$orderId}#repuestos");
    }

    public function deleteRepuestoItem(int $orderId, int $itemId): void
    {
        $this->authorize(['ADMIN']);

        $itemModel = new OrdenRepuestoItemModel();
        $items = $itemModel->getByOrder($orderId);
        $item = null;
        foreach ($items as $registro) {
            if ((int)$registro['id'] === $itemId) {
                $item = $registro;
                break;
            }
        }

        if ($item !== null) {
            $stockModel = new RepuestoExistenciaModel();
            $stockModel->increaseStock((int)$item['repuesto_id'], (float)$item['cantidad']);

            $movimiento = new InventarioMovimiento();
            $movimiento->registrarMovimiento([
                'repuesto_id' => (int)$item['repuesto_id'],
                'tipo' => 'AJUSTE',
                'cantidad' => (float)$item['cantidad'],
                'motivo' => 'Devolución de repuesto OS #' . $orderId,
                'orden_id' => $orderId,
                'usuario_id' => Session::get('user_id'),
            ]);
        }

        $itemModel->delete($itemId);

        $this->flash('info', 'Repuesto removido y stock ajustado.');
        $this->redirect("/ordenservicio/ver/{$orderId}#repuestos");
    }

    public function deliver(int $orderId): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $orden = $this->ordenServicioModel->getDetailForManagement($orderId);
        if (!$orden) {
            $this->flash('danger', 'La orden no existe.');
            $this->redirect('/ordenservicio');
        }

        $facturaModel = new Factura();
        $factura = $facturaModel->findByOrdenId($orderId);
        $errores = [];

        if ($factura && in_array($factura['estado'], ['EMITIDA', 'PENDIENTE'], true)) {
            $errores['factura'] = 'La factura debe estar pagada o anulada antes de entregar.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['deliver' => $errores]);
            $this->redirect("/ordenservicio/ver/{$orderId}");
        }

        $estadoEntregado = $this->ordenServicioModel->getStatusIdByCode('ENTREGADO');
        if ($estadoEntregado !== null) {
            $this->ordenServicioModel->updateStatus($orderId, $estadoEntregado, (int) Session::get('user_id'));
            $this->ordenServicioModel->actualizarCampos($orderId, ['fecha_entrega_real' => date('Y-m-d H:i:s')]);
            $this->recordHistory($orderId, $estadoEntregado, 'Equipo entregado al cliente.');
        }

        $this->flash('success', 'Orden marcada como entregada.');
        $this->redirect("/ordenservicio/ver/{$orderId}");
    }

    public function diagnose(int $orderId): void
    {
        $this->authorize(['ADMIN', 'TECNICO']);

        $orden = $this->ordenServicioModel->findById($orderId);
        if (!$orden) {
            $this->flash('danger', 'Orden de servicio no encontrada.');
            $this->redirect('/ordenservicio');
        }

        $formErrors = Session::get('form_errors', []);
        $oldInput = Session::get('old', []);

        $this->view('ordenservicio/diagnosticar', [
            'orden' => $orden,
            'errors' => $formErrors['diagnose'] ?? [],
            'old' => $oldInput['diagnose'] ?? [],
        ]);
        Session::remove('form_errors');
        Session::remove('old');
    }

    public function storeDiagnosis(int $orderId): void
    {
        $this->authorize(['ADMIN', 'TECNICO']);

        $orden = $this->ordenServicioModel->findById($orderId);
        if (!$orden) {
            $this->flash('danger', 'Orden de servicio no encontrada.');
            $this->redirect('/ordenservicio');
        }

        $data = [
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'costo_estimado' => (float)($_POST['costo_estimado'] ?? 0.0),
        ];

        $errors = [];
        if (empty($data['descripcion'])) {
            $errors['descripcion'] = 'La descripción del diagnóstico es requerida.';
        }
        if ($data['costo_estimado'] < 0) {
            $errors['costo_estimado'] = 'El costo estimado no puede ser negativo.';
        }

        if (!empty($errors)) {
            Session::set('form_errors', ['diagnose' => $errors]);
            Session::set('old', ['diagnose' => $data]);
            $this->redirect("/ordenservicio/diagnose/{$orderId}");
        }

        $diagnosticoModel = new DiagnosticoModel();
        $diagnosticoModel->create([
            'orden_id' => $orderId,
            'tecnico_id' => $orden['tecnico_asignado_id'] ?? null,
            'descripcion' => $data['descripcion'],
        ]);

        $statusId = $this->ordenServicioModel->getStatusIdByCode('DIAGNOSTICO');
        if ($statusId) {
            $this->ordenServicioModel->updateStatus($orderId, $statusId, Session::get('user_id'));
        }

        $this->ordenServicioModel->updateEstimatedCost($orderId, $data['costo_estimado'], Session::get('user_id'));

        $this->recordHistory(
            $orderId,
            (int) ($statusId ?: ($orden['estado_id'] ?? 0)),
            'Diagnóstico registrado. Orden en diagnóstico. Costo estimado: S/ ' . number_format($data['costo_estimado'], 2)
        );

        $this->flash('success', 'Diagnóstico registrado correctamente.');
        $this->redirect("/ordenservicio/ver/{$orderId}");
    }

    private function validateServiceOrder(array $data): array
    {
        $errors = [];
        if ($data['cliente_id'] <= 0) {
            $errors['cliente_id'] = 'El cliente es requerido.';
        }
        if ($data['equipo_id'] <= 0) {
            $errors['equipo_id'] = 'El equipo es requerido.';
        }
        if ($data['prioridad_id'] <= 0) {
            $errors['prioridad_id'] = 'Selecciona la prioridad.';
        }
        if ($data['falla_reportada'] === '') {
            $errors['falla_reportada'] = 'La falla reportada es requerida.';
        }
        return $errors;
    }

    private function recordHistory(int $orderId, int $stateId, string $comment = ''): void
    {
        if ($orderId <= 0 || $stateId <= 0) {
            return;
        }

        $historial = new OrdenEstadoHistorial();
        $historial->create([
            'orden_id' => $orderId,
            'estado_id' => $stateId,
            'usuario_id' => Session::get('user_id'),
            'comentario' => $comment,
        ]);
    }

    public function downloadPdf(int $orderId): void
    {
        $this->requireAuth();

        $orden = $this->ordenServicioModel->getDetailForManagement($orderId);
        if (!$orden) {
            $this->flash('danger', 'La orden solicitada no existe.');
            $this->redirect('/ordenservicio');
        }

        $servicioItemModel = new OrdenServicioItemModel();
        $servicioItems = $servicioItemModel->getByOrder($orderId);

        $repuestoItemModel = new OrdenRepuestoItemModel();
        $repuestoItems = $repuestoItemModel->getByOrder($orderId);

        $mpdf = new Mpdf();
        $mpdf->WriteHTML('<h1>Orden de Servicio ' . htmlspecialchars($orden['codigo']) . '</h1>');

        $mpdf->WriteHTML('<h2>Información General</h2>');
        $html = '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<tr><th>Cliente</th><td>' . htmlspecialchars($orden['cliente_nombre']) . '</td></tr>';
        $html .= '<tr><th>Equipo</th><td>' . htmlspecialchars($orden['equipo_descripcion']) . '</td></tr>';
        $html .= '<tr><th>Falla Reportada</th><td>' . htmlspecialchars($orden['falla_reportada']) . '</td></tr>';
        $html .= '<tr><th>Técnico Asignado</th><td>' . htmlspecialchars($orden['tecnico_completo'] ?: 'Sin asignar') . '</td></tr>';
        $html .= '<tr><th>Estado</th><td>' . htmlspecialchars($orden['estado_nombre']) . '</td></tr>';
        $html .= '</table>';
        $mpdf->WriteHTML($html);

        $mpdf->WriteHTML('<h2>Servicios</h2>');
        $html = '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th>Servicio</th><th>Cantidad</th><th>Precio Unitario</th><th>Total</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($servicioItems as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['servicio_nombre']) . '</td>';
            $html .= '<td>' . $item['cantidad'] . '</td>';
            $html .= '<td>S/ ' . number_format((float)$item['precio_unitario'], 2) . '</td>';
            $html .= '<td>S/ ' . number_format((float)$item['total_linea'], 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $mpdf->WriteHTML($html);

        $mpdf->WriteHTML('<h2>Repuestos</h2>');
        $html = '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th>Repuesto</th><th>Cantidad</th><th>Precio Unitario</th><th>Total</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($repuestoItems as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['repuesto_nombre']) . '</td>';
            $html .= '<td>' . $item['cantidad'] . '</td>';
            $html .= '<td>S/ ' . number_format((float)$item['precio_unitario'], 2) . '</td>';
            $html .= '<td>S/ ' . number_format((float)$item['total_linea'], 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $mpdf->WriteHTML($html);

        $mpdf->Output('orden_servicio_' . $orden['codigo'] . '.pdf', 'D');
    }
}
