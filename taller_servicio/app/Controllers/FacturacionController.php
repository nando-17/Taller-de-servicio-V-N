<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\DetalleFactura;
use App\Models\Factura;
use App\Models\OrdenRepuestoItemModel;
use App\Models\OrdenServicioItemModel;
use App\Models\OrdenServicioModel;

class FacturacionController extends Controller
{
    public function index(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $facturaModel = new Factura();
        $ordenModel = new OrdenServicioModel();

        $formErrors = Session::get('form_errors', []);

        $this->view('facturacion/index', [
            'facturas' => $facturaModel->getAll(),
            'ordenesDisponibles' => $ordenModel->getPendientesDeFacturacion(),
            'errors' => $formErrors['facturacion'] ?? [],
        ]);

        Session::remove('form_errors');
    }

    public function store(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $ordenId = (int)($_POST['orden_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'RECIBO';
        $serie = trim($_POST['serie'] ?? '');
        $numero = trim($_POST['numero'] ?? '');

        $errores = [];
        if ($ordenId <= 0) {
            $errores['orden_id'] = 'Selecciona una orden válida.';
        }
        if (!in_array($tipo, ['FACTURA', 'BOLETA', 'RECIBO'], true)) {
            $errores['tipo'] = 'Tipo de comprobante no permitido.';
        }

        $ordenModel = new OrdenServicioModel();
        $orden = $ordenModel->getDetailForManagement($ordenId);
        if (!$orden) {
            $errores['orden_id'] = 'La orden seleccionada no existe.';
        }

        $facturaModel = new Factura();
        if ($orden && $facturaModel->findByOrdenId($ordenId)) {
            $errores['orden_id'] = 'La orden ya cuenta con un comprobante emitido.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['facturacion' => $errores]);
            $this->redirect('/facturacion');
        }

        $servicioItems = (new OrdenServicioItemModel())->getByOrder($ordenId);
        $repuestoItems = (new OrdenRepuestoItemModel())->getByOrder($ordenId);

        $subtotal = 0.0;
        foreach ($servicioItems as $item) {
            $subtotal += (float)($item['total_linea'] ?? 0);
        }
        foreach ($repuestoItems as $item) {
            $subtotal += (float)($item['total_linea'] ?? 0);
        }

        $impuesto = round($subtotal * 0.18, 2);
        $total = $subtotal + $impuesto;

        $facturaId = $facturaModel->create([
            'orden_id' => $ordenId,
            'cliente_id' => $orden['cliente_id'] ?? $orden['clienteId'] ?? 0,
            'tipo' => $tipo,
            'serie' => $serie !== '' ? $serie : null,
            'numero' => $numero !== '' ? $numero : null,
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $total,
            'estado' => 'EMITIDA',
        ]);

        $detalleModel = new DetalleFactura();
        foreach ($servicioItems as $item) {
            $detalleModel->create([
                'factura_id' => $facturaId,
                'tipo_item' => 'SERVICIO',
                'referencia_id' => $item['servicio_id'] ?? null,
                'descripcion' => $item['servicio_nombre'] ?? 'Servicio',
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'total_linea' => $item['total_linea'],
            ]);
        }

        foreach ($repuestoItems as $item) {
            $detalleModel->create([
                'factura_id' => $facturaId,
                'tipo_item' => 'REPUESTO',
                'referencia_id' => $item['repuesto_id'] ?? null,
                'descripcion' => $item['repuesto_nombre'] ?? 'Repuesto',
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'total_linea' => $item['total_linea'],
            ]);
        }

        $this->flash('success', 'Comprobante emitido correctamente.');
        $this->redirect('/facturacion');
    }
}

