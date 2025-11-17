<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\InventarioMovimiento;
use App\Models\Repuesto;
use App\Models\RepuestoExistenciaModel;

class InventarioController extends Controller
{
    public function index(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $repuestos = (new Repuesto())->getActivosConStock();
        $movimientos = (new InventarioMovimiento())->getRecent(25);

        $formErrors = Session::get('form_errors', []);

        $this->view('inventario/index', [
            'repuestos' => $repuestos,
            'movimientos' => $movimientos,
            'errors' => $formErrors['inventario'] ?? [],
        ]);

        Session::remove('form_errors');
    }

    public function store(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $repuestoId = (int)($_POST['repuesto_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'INGRESO';
        $cantidad = (float)($_POST['cantidad'] ?? 0);
        $costo = (float)($_POST['costo_unitario'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');

        $errores = [];
        $repuestoModel = new Repuesto();
        $repuesto = $repuestoModel->findById($repuestoId);
        if ($repuesto === null) {
            $errores['repuesto_id'] = 'Selecciona un repuesto válido.';
        }

        if (!in_array($tipo, ['INGRESO', 'AJUSTE'], true)) {
            $errores['tipo'] = 'Tipo de movimiento no permitido.';
        }

        if ($tipo === 'INGRESO' && $cantidad <= 0) {
            $errores['cantidad'] = 'La cantidad debe ser mayor a 0 para un ingreso.';
        }

        if ($tipo === 'AJUSTE' && $cantidad === 0.0) {
            $errores['cantidad'] = 'La cantidad no puede ser 0 en un ajuste.';
        }

        $stockModel = new RepuestoExistenciaModel();
        $stockActual = $repuestoId > 0 ? $stockModel->getStock($repuestoId) : 0;

        if ($tipo === 'AJUSTE' && $cantidad < 0 && ($stockActual + $cantidad) < 0) {
            $errores['cantidad'] = 'El ajuste dejaría el stock en negativo.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['inventario' => $errores]);
            $this->redirect('/inventario');
        }

        if ($tipo === 'INGRESO') {
            $stockModel->increaseStock($repuestoId, $cantidad);
        } elseif ($cantidad > 0) {
            $stockModel->increaseStock($repuestoId, $cantidad);
        } else {
            $stockModel->decreaseStock($repuestoId, abs($cantidad));
        }

        $movimiento = new InventarioMovimiento();
        $movimiento->registrarMovimiento([
            'repuesto_id' => $repuestoId,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'costo_unitario' => $costo > 0 ? $costo : ($repuesto['precio_costo'] ?? null),
            'motivo' => $motivo !== '' ? $motivo : ($tipo === 'INGRESO' ? 'Ingreso de inventario manual' : 'Ajuste de stock'),
            'usuario_id' => Session::get('user_id'),
        ]);

        $this->flash('success', 'Movimiento de inventario registrado.');
        $this->redirect('/inventario');
    }
}

