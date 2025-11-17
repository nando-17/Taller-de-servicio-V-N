<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Repuesto;
use App\Models\RepuestoExistenciaModel;
use App\Models\Servicio;
use Mpdf\Mpdf;

class CatalogoController extends Controller
{
    public function downloadPdf(): void
    {
        $this->authorize(['ADMIN']);

        $servicioModel = new Servicio();
        $repuestoModel = new Repuesto();

        $servicios = $servicioModel->getTodos();
        $repuestos = $repuestoModel->getTodosConStock();

        $mpdf = new Mpdf();
        $mpdf->WriteHTML('<h1>Catálogo de Servicios y Repuestos</h1>');
        $mpdf->WriteHTML('<h2>Servicios</h2>');
        $html = '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Estado</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($servicios as $servicio) {
            $html .= '<tr>';
            $html .= '<td>' . $this->escape($servicio['nombre'] ?? '') . '</td>';
            $html .= '<td>' . $this->escape($servicio['descripcion'] ?? '') . '</td>';
            $html .= '<td>S/ ' . number_format((float)$servicio['precio_base'], 2) . '</td>';
            $html .= '<td>' . ($servicio['activo'] ? 'Activo' : 'Inactivo') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $mpdf->WriteHTML($html);

        $mpdf->WriteHTML('<h2>Repuestos</h2>');
        $html = '<table border="1" style="width:100%; border-collapse: collapse;">';
        $html .= '<thead><tr><th>Nombre</th><th>Código</th><th>Precio Costo</th><th>Precio Venta</th><th>Stock</th><th>Stock Mínimo</th><th>Estado</th></tr></thead>';
        $html .= '<tbody>';
        foreach ($repuestos as $repuesto) {
            $html .= '<tr>';
            $html .= '<td>' . $this->escape($repuesto['nombre'] ?? '') . '</td>';
            $html .= '<td>' . $this->escape($repuesto['codigo'] ?? '') . '</td>';
            $html .= '<td>S/ ' . number_format((float)$repuesto['precio_costo'], 2) . '</td>';
            $html .= '<td>S/ ' . number_format((float)$repuesto['precio_venta'], 2) . '</td>';
            $html .= '<td>' . $repuesto['stock'] . '</td>';
            $html .= '<td>' . $repuesto['stock_minimo'] . '</td>';
            $html .= '<td>' . ($repuesto['activo'] ? 'Activo' : 'Inactivo') . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $mpdf->WriteHTML($html);

        $mpdf->Output('catalogo.pdf', 'D');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function index(): void
    {
        $this->authorize(['ADMIN']);

        $servicioModel = new Servicio();
        $repuestoModel = new Repuesto();

        $formErrors = Session::get('form_errors', []);
        $oldInput = Session::get('old', []);

        $this->view('catalogos/index', [
            'servicios' => $servicioModel->getTodos(),
            'repuestos' => $repuestoModel->getTodosConStock(),
            'errors' => $formErrors['catalogos'] ?? [],
            'old' => $oldInput['catalogos'] ?? [],
        ]);

        Session::remove('form_errors');
        Session::remove('old');
    }

    public function storeServicio(): void
    {
        $this->authorize(['ADMIN']);

        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (float)($_POST['precio_base'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre del servicio es obligatorio.';
        }
        if ($precio < 0) {
            $errores['precio_base'] = 'El precio no puede ser negativo.';
        }

        $old = [
            'servicio' => [
                'nombre' => $nombre,
                'precio_base' => $precio,
                'descripcion' => $descripcion,
            ],
        ];

        if (!empty($errores)) {
            Session::set('form_errors', ['catalogos' => ['servicio' => $errores]]);
            Session::set('old', ['catalogos' => $old]);
            $this->redirect('/catalogos');
        }

        (new Servicio())->create([
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'precio_base' => $precio,
        ]);

        $this->flash('success', 'Servicio agregado al catálogo.');
        $this->redirect('/catalogos');
    }

    public function toggleServicio(int $id): void
    {
        $this->authorize(['ADMIN']);

        $servicioModel = new Servicio();
        $servicio = $servicioModel->findById($id);
        if (!$servicio) {
            $this->flash('danger', 'El servicio no existe.');
            $this->redirect('/catalogos');
        }

        $servicioModel->update($id, ['activo' => $servicio['activo'] ? 0 : 1]);
        $this->flash('info', 'Estado del servicio actualizado.');
        $this->redirect('/catalogos');
    }

    public function storeRepuesto(): void
    {
        $this->authorize(['ADMIN']);

        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $precioCosto = (float)($_POST['precio_costo'] ?? 0);
        $precioVenta = (float)($_POST['precio_venta'] ?? 0);
        $stockMinimo = (int)($_POST['stock_minimo'] ?? 0);

        $errores = [];
        if ($nombre === '') {
            $errores['nombre_repuesto'] = 'El nombre es obligatorio.';
        }
        if ($precioVenta < 0 || $precioCosto < 0) {
            $errores['precio'] = 'Los precios no pueden ser negativos.';
        }
        if ($stockMinimo < 0) {
            $errores['stock_minimo'] = 'El stock mínimo debe ser mayor o igual a 0.';
        }

        $old = [
            'repuesto' => [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'precio_costo' => $precioCosto,
                'precio_venta' => $precioVenta,
                'stock_minimo' => $stockMinimo,
            ],
        ];

        if (!empty($errores)) {
            Session::set('form_errors', ['catalogos' => ['repuesto' => $errores]]);
            Session::set('old', ['catalogos' => $old]);
            $this->redirect('/catalogos');
        }

        $repuestoModel = new Repuesto();
        $repuestoId = $repuestoModel->create([
            'nombre' => $nombre,
            'codigo' => $codigo !== '' ? $codigo : null,
            'precio_costo' => $precioCosto,
            'precio_venta' => $precioVenta,
            'stock_minimo' => $stockMinimo,
        ]);

        (new RepuestoExistenciaModel())->ensureRow($repuestoId);

        $this->flash('success', 'Repuesto agregado al catálogo.');
        $this->redirect('/catalogos');
    }

    public function toggleRepuesto(int $id): void
    {
        $this->authorize(['ADMIN']);

        $repuestoModel = new Repuesto();
        $repuesto = $repuestoModel->findById($id);
        if (!$repuesto) {
            $this->flash('danger', 'El repuesto no existe.');
            $this->redirect('/catalogos');
        }

        $repuestoModel->update($id, ['activo' => $repuesto['activo'] ? 0 : 1]);
        $this->flash('info', 'Estado del repuesto actualizado.');
        $this->redirect('/catalogos');
    }

    public function updateServicio(int $id): void
    {
        $this->authorize(['ADMIN']);

        $servicioModel = new Servicio();
        $servicio = $servicioModel->findById($id);
        if ($servicio === null) {
            $this->flash('danger', 'El servicio seleccionado no existe.');
            $this->redirect('/catalogos');
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (float)($_POST['precio_base'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if ($precio < 0) {
            $errores['precio_base'] = 'El precio no puede ser negativo.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['catalogos' => ['servicio_edit' => $errores]]);
            Session::set('old', ['catalogos' => ['servicio_edit' => [
                'id' => $id,
                'nombre' => $nombre,
                'precio_base' => $precio,
                'descripcion' => $descripcion,
            ]]]);
            $this->redirect('/catalogos#servicios');
        }

        $servicioModel->update($id, [
            'nombre' => $nombre,
            'precio_base' => $precio,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
        ]);

        $this->flash('success', 'Servicio actualizado correctamente.');
        $this->redirect('/catalogos#servicios');
    }

    public function updateRepuesto(int $id): void
    {
        $this->authorize(['ADMIN']);

        $repuestoModel = new Repuesto();
        $repuesto = $repuestoModel->findById($id);
        if ($repuesto === null) {
            $this->flash('danger', 'El repuesto seleccionado no existe.');
            $this->redirect('/catalogos');
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $codigo = trim($_POST['codigo'] ?? '');
        $precioCosto = (float)($_POST['precio_costo'] ?? 0);
        $precioVenta = (float)($_POST['precio_venta'] ?? 0);
        $stockMinimo = (int)($_POST['stock_minimo'] ?? 0);

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if ($precioCosto < 0) {
            $errores['precio_costo'] = 'El costo no puede ser negativo.';
        }
        if ($precioVenta < 0) {
            $errores['precio_venta'] = 'El precio de venta no puede ser negativo.';
        }
        if ($precioVenta < $precioCosto) {
            $errores['precio_venta'] = 'El precio de venta debe ser mayor o igual al costo.';
        }
        if ($stockMinimo < 0) {
            $errores['stock_minimo'] = 'El stock mínimo debe ser mayor o igual a 0.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['catalogos' => ['repuesto_edit' => $errores]]);
            Session::set('old', ['catalogos' => ['repuesto_edit' => [
                'id' => $id,
                'nombre' => $nombre,
                'codigo' => $codigo,
                'precio_costo' => $precioCosto,
                'precio_venta' => $precioVenta,
                'stock_minimo' => $stockMinimo,
            ]]]);
            $this->redirect('/catalogos#repuestos');
        }

        $repuestoModel->update($id, [
            'nombre' => $nombre,
            'codigo' => $codigo !== '' ? $codigo : null,
            'precio_costo' => $precioCosto,
            'precio_venta' => $precioVenta,
            'stock_minimo' => $stockMinimo,
        ]);

        $this->flash('success', 'Repuesto actualizado correctamente.');
        $this->redirect('/catalogos#repuestos');
    }
}

