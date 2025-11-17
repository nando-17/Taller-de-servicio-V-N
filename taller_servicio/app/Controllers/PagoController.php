<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Factura;
use App\Models\Pago;
use Mpdf\Mpdf;

class PagoController extends Controller
{
   public function downloadPdf(int $facturaId): void
{
    $this->authorize(['ADMIN', 'OPERADOR']);

    $facturaModel = new Factura();
    $factura = $facturaModel->findById($facturaId);
    if (!$factura) {
        $this->flash('danger', 'El comprobante no existe.');
        $this->redirect('/pagos');
    }

    $pagoModel = new Pago();
    $pagos = $pagoModel->getByFactura($facturaId);

    // ==== Helpers ====
    $e = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mon = static fn($n): string => number_format((float)$n, 2);

    // Totales
    $totalFactura   = (float)($factura['total'] ?? 0);
    $totalAplicado  = 0.0;
    $totalAnulado   = 0.0;
    foreach ($pagos as $p) {
        if (($p['estado'] ?? '') === 'ANULADO') {
            $totalAnulado += (float)($p['monto'] ?? 0);
        } else {
            $totalAplicado += (float)($p['monto'] ?? 0);
        }
    }
    $totalPendiente = max(0.0, $totalFactura - $totalAplicado);

    // Datos cabecera
    $comprobante  = trim(($factura['tipo'] ?? '') . ' ' . ($factura['serie'] ?? '') . '-' . ($factura['numero'] ?? ''));
    $cliente      = $factura['cliente_nombre'] ?? $factura['cliente'] ?? '—';
    $fechaEmision = isset($factura['fecha_emision']) && $factura['fecha_emision'] !== null
        ? date('d/m/Y H:i', strtotime($factura['fecha_emision']))
        : '—';
    $estadoComp   = $factura['estado'] ?? 'EMITIDA';

    // ==== mPDF ====
    $mpdf = new \Mpdf\Mpdf([
        'format'        => 'A4',
        'margin_left'   => 14,
        'margin_right'  => 14,
        'margin_top'    => 45, // deja espacio para header
        'margin_bottom' => 25, // deja espacio para footer
    ]);

    // Logo (opcional). Cambia la ruta si tienes un logo en /public/images/logo.png
    $logoPath = __DIR__ . '/../../public/images/logo.png';
    $logoHtml = is_file($logoPath)
        ? '<img src="' . $e($logoPath) . '" style="height:42px;">'
        : '<div style="font-weight:800;font-size:18px;letter-spacing:.5px;">Taller de Servicios</div>';

    // Header
    $header = '
    <table style="width:100%;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">
      <tr>
        <td style="width:60%;vertical-align:middle">'
          . $logoHtml .
        '</td>
        <td style="width:40%;text-align:right;font-size:12px;color:#374151;">
          <div style="font-size:13px;font-weight:700;color:#111827;">Recibo de Pago</div>
          <div>' . $e($comprobante) . '</div>
          <div>Fecha emisión: ' . $e($fechaEmision) . '</div>
        </td>
      </tr>
    </table>';

    // Footer
    $footer = '
    <div style="border-top:1px solid #e5e7eb;padding-top:6px;font-size:10px;color:#6b7280;">
      Generado por el sistema · {DATE d/m/Y H:i} · Página {PAGENO}/{nbpg}
    </div>';

    $mpdf->SetHTMLHeader($header);
    $mpdf->SetHTMLFooter($footer);

    // CSS
    $css = '
    <style>
      .muted{color:#6b7280;}
      .chip{
        display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;
      }
      .chip-ok{background:#e6ffed;color:#036b26;border:1px solid #b7f7c4;}
      .chip-warn{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;}
      .chip-bad{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}
      .kpi{
        border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;
      }
      .kpi .label{font-size:11px;color:#6b7280;margin-bottom:2px;}
      .kpi .value{font-size:16px;font-weight:800;color:#111827;}
      table.pay{
        width:100%;border-collapse:separate;border-spacing:0;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;
        font-size:12px;
      }
      table.pay thead th{
        background:#f9fafb;text-align:left;padding:10px;border-bottom:1px solid #e5e7eb;color:#374151;font-weight:700;
      }
      table.pay tbody td{
        padding:10px;border-top:1px solid #f3f4f6;color:#111827;
      }
      tr.strip:nth-child(even) td{background:#fbfbfd;}
      .right{text-align:right;}
    </style>';

    // Estado chip
    $chipClass = match (strtoupper($estadoComp)) {
        'PAGADA'   => 'chip chip-ok',
        'PENDIENTE'=> 'chip chip-warn',
        'ANULADA'  => 'chip chip-bad',
        default    => 'chip'
    };

    // Resumen
    $resumen = '
    <table style="width:100%;margin-top:8px;margin-bottom:14px;">
      <tr>
        <td style="width:60%;vertical-align:top;">
          <div style="font-size:13px;font-weight:700;margin-bottom:6px;color:#111827;">Datos del comprobante</div>
          <div class="muted">Cliente</div>
          <div style="margin-bottom:8px;">' . $e($cliente) . '</div>
          <div class="muted">Estado</div>
          <div><span class="' . $chipClass . '">' . $e($estadoComp) . '</span></div>
        </td>
        <td style="width:40%;vertical-align:top;">
          <table style="width:100%;border-spacing:8px 8px;">
            <tr>
              <td class="kpi">
                <div class="label">Total comprobante</div>
                <div class="value">S/ ' . $mon($totalFactura) . '</div>
              </td>
            </tr>
            <tr>
              <td class="kpi">
                <div class="label">Pagado (aplicado)</div>
                <div class="value">S/ ' . $mon($totalAplicado) . '</div>
              </td>
            </tr>
            <tr>
              <td class="kpi">
                <div class="label">Anulado</div>
                <div class="value">S/ ' . $mon($totalAnulado) . '</div>
              </td>
            </tr>
            <tr>
              <td class="kpi">
                <div class="label">Pendiente</div>
                <div class="value">S/ ' . $mon($totalPendiente) . '</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>';

    // Tabla de pagos
    $rows = '';
    $i = 0;
    foreach ($pagos as $pago) {
        $i++;
        $fecha = isset($pago['fecha_pago']) && $pago['fecha_pago'] !== null
            ? date('d/m/Y H:i', strtotime($pago['fecha_pago']))
            : '—';
        $estado = strtoupper($pago['estado'] ?? '—');
        $badge = match ($estado) {
            'APLICADO' => '<span class="chip chip-ok">APLICADO</span>',
            'ANULADO'  => '<span class="chip chip-bad">ANULADO</span>',
            default    => '<span class="chip">'.$e($estado).'</span>',
        };

        $rows .= '<tr class="strip">
            <td>'.$e($fecha).'</td>
            <td class="right">S/ '.$mon($pago['monto'] ?? 0).'</td>
            <td>'.$e($pago['metodo_pago'] ?? '—').'</td>
            <td>'.$e($pago['referencia'] ?? '—').'</td>
            <td>'.$badge.'</td>
        </tr>';
    }
    if ($rows === '') {
        $rows = '<tr><td colspan="5" class="right" style="padding:14px;color:#6b7280;">No hay pagos registrados.</td></tr>';
    }

    $tabla = '
    <table class="pay">
      <thead>
        <tr>
          <th>Fecha</th>
          <th class="right">Monto</th>
          <th>Método</th>
          <th>Referencia</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>'.$rows.'</tbody>
      <tfoot>
        <tr>
          <td style="background:#f9fafb;font-weight:700;">Totales</td>
          <td class="right" style="background:#f9fafb;font-weight:700;">S/ '.$mon($totalAplicado).' (aplicado)</td>
          <td colspan="2" style="background:#f9fafb;"></td>
          <td style="background:#f9fafb;">Pendiente: <strong>S/ '.$mon($totalPendiente).'</strong></td>
        </tr>
      </tfoot>
    </table>';

    // Cuerpo final
    $html = $css .
      '<div style="margin-top:6px;">
         <div style="font-size:18px;font-weight:800;color:#111827;margin:6px 0 2px;">'.$e($comprobante).'</div>
         <div class="muted">Generado el '.date('d/m/Y H:i').'</div>
       </div>'
      . $resumen
      . '<div style="font-size:13px;font-weight:700;margin:14px 0 8px;color:#111827;">Pagos</div>'
      . $tabla;

    // Watermark si la factura está anulada
    if (strtoupper($estadoComp) === 'ANULADA') {
        $mpdf->SetWatermarkText('ANULADA', 0.1);
        $mpdf->showWatermarkText = true;
    }

    $mpdf->WriteHTML($html);
    $mpdf->Output('recibo-'.$e($factura['serie'] ?? 'S').'-'.$e($factura['numero'] ?? '000000').'.pdf', 'D');
}

    public function index(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $facturaModel = new Factura();
        $pagoModel = new Pago();

        $facturas = array_map(function (array $factura) use ($pagoModel) {
            $factura['pagado'] = $pagoModel->sumAppliedByFactura((int) $factura['id']);
            $factura['pendiente'] = max(0, (float) $factura['total'] - $factura['pagado']);
            return $factura;
        }, $facturaModel->getAll());

        $formErrors = Session::get('form_errors', []);

        $this->view('pagos/index', [
            'facturas' => $facturas,
            'errors' => $formErrors['pagos'] ?? [],
        ]);

        Session::remove('form_errors');
    }

    public function store(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $facturaId = (int)($_POST['factura_id'] ?? 0);
        $monto = (float)($_POST['monto'] ?? 0);
        $metodo = $_POST['metodo_pago'] ?? 'EFECTIVO';
        $referencia = trim($_POST['referencia'] ?? '');

        $errores = [];
        $facturaModel = new Factura();
        $factura = $facturaModel->findById($facturaId);
        if (!$factura) {
            $errores['factura_id'] = 'Selecciona un comprobante válido.';
        }
        if ($monto <= 0) {
            $errores['monto'] = 'El monto debe ser mayor a 0.';
        }
        if (!in_array($metodo, ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'YAPE', 'PLIN', 'OTRO'], true)) {
            $errores['metodo_pago'] = 'Método de pago no permitido.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['pagos' => $errores]);
            $this->redirect('/pagos');
        }

        $pagoModel = new Pago();
        $pagoModel->create([
            'factura_id' => $facturaId,
            'monto' => $monto,
            'metodo_pago' => $metodo,
            'referencia' => $referencia !== '' ? $referencia : null,
            'usuario_id' => Session::get('user_id'),
        ]);

        $this->actualizarEstadoFactura($facturaModel, $pagoModel, $facturaId);

        $this->flash('success', 'Pago registrado correctamente.');
        $this->redirect('/pagos');
    }

    public function anular(int $pagoId): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $pagoModel = new Pago();
        $pago = $pagoModel->findById($pagoId);
        if (!$pago) {
            $this->flash('danger', 'El pago indicado no existe.');
            $this->redirect('/pagos');
        }

        $pagoModel->updateEstado($pagoId, 'ANULADO');
        $facturaModel = new Factura();
        $this->actualizarEstadoFactura($facturaModel, $pagoModel, (int) $pago['factura_id']);

        $this->flash('info', 'Pago anulado.');
        $this->redirect('/pagos');
    }

    private function actualizarEstadoFactura(Factura $facturaModel, Pago $pagoModel, int $facturaId): void
    {
        $factura = $facturaModel->findById($facturaId);
        if (!$factura) {
            return;
        }

        $pagado = $pagoModel->sumAppliedByFactura($facturaId);
        if ($pagado >= (float) $factura['total']) {
            $facturaModel->updateEstado($facturaId, 'PAGADA');
        } elseif ($pagado > 0) {
            $facturaModel->updateEstado($facturaId, 'PENDIENTE');
        } else {
            $facturaModel->updateEstado($facturaId, 'EMITIDA');
        }
    }
}

