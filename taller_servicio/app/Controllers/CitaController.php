<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Cita;
use App\Models\ClienteModel;
use App\Models\EquipoModel;
use App\Models\TecnicoModel;

class CitaController extends Controller
{
    public function index(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $citaModel = new Cita();
        $clientes  = (new ClienteModel())->getAll();
        $equipos   = (new EquipoModel())->getAll();
        $tecnicos  = (new TecnicoModel())->getAllActive();

        $formErrors = Session::get('form_errors', []);

        $this->view('citas/index', [
            'citas'    => $citaModel->getUpcoming(),
            'clientes' => $clientes,
            'equipos'  => $equipos,
            'tecnicos' => $tecnicos,
            'errors'   => $formErrors['citas'] ?? [],
        ]);

        Session::remove('form_errors');
    }

    public function store(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $clienteId   = (int)($_POST['cliente_id'] ?? 0);
        $equipoId    = (int)($_POST['equipo_id'] ?? 0) ?: null;
        $tecnicoId   = (int)($_POST['tecnico_id'] ?? 0) ?: null;
        $fechaInicio = $_POST['fecha_inicio'] ?? '';
        $fechaFin    = $_POST['fecha_fin'] ?? '';
        $estado      = $_POST['estado'] ?? 'PENDIENTE';
        $notas       = trim($_POST['notas'] ?? '');

        // Normaliza formatos de fecha a 'Y-m-d H:i:s' (acepta 'YYYY-MM-DDTHH:MM')
        $fi = $fechaInicio ? date('Y-m-d H:i:s', strtotime((string)$fechaInicio)) : '';
        $ff = $fechaFin    ? date('Y-m-d H:i:s', strtotime((string)$fechaFin))    : '';

        $errores = [];
        if ($clienteId <= 0) {
            $errores['cliente_id'] = 'Selecciona un cliente.';
        }
        if ($fi === '' || $ff === '') {
            $errores['fecha_inicio'] = 'Debes indicar una fecha de inicio y fin.';
        } elseif (strtotime($ff) <= strtotime($fi)) {
            $errores['fecha_inicio'] = 'La fecha fin debe ser mayor a la de inicio.';
        }

        $citaModel = new Cita();

        // Valida solape únicamente si hay técnico y fechas válidas
        if ($tecnicoId && $fi && $ff && $citaModel->hasOverlap($tecnicoId, $fi, $ff)) {
            $errores['tecnico_id'] = 'El técnico tiene un compromiso en ese horario.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['citas' => $errores]);
            $this->redirect('/citas');
        }

        $citaModel->create([
            'cliente_id'   => $clienteId,
            'equipo_id'    => $equipoId,
            'tecnico_id'   => $tecnicoId,
            'fecha_inicio' => $fi,
            'fecha_fin'    => $ff,
            'estado'       => $estado,
            'notas'        => $notas,
            'created_by'   => Session::get('user_id'),
        ]);

        $this->flash('success', 'Cita registrada correctamente.');
        $this->redirect('/citas');
    }

    public function updateEstado(int $id): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $nuevoEstado = $_POST['estado'] ?? '';
        if (!in_array($nuevoEstado, ['PENDIENTE', 'CONFIRMADA', 'ATENDIDA', 'CANCELADA'], true)) {
            $this->flash('danger', 'Estado de cita no permitido.');
            $this->redirect('/citas');
        }

        $citaModel = new Cita();
        $cita = $citaModel->findById($id);
        if (!$cita) {
            $this->flash('danger', 'La cita indicada no existe.');
            $this->redirect('/citas');
        }

        $citaModel->update($id, ['estado' => $nuevoEstado]);

        $this->flash('success', 'Estado de la cita actualizado.');
        $this->redirect('/citas');
    }

    public function events(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $start = $_GET['start'] ?? null;
        $end   = $_GET['end'] ?? null;

        $startDate = $start ? date('Y-m-d H:i:s', strtotime((string)$start)) : null;
        $endDate   = $end   ? date('Y-m-d H:i:s', strtotime((string)$end))   : null;

        $citaModel = new Cita();
        $rows = $citaModel->getBetween($startDate, $endDate);

        $events = [];
        foreach ($rows as $row) {
            $inicio         = $row['fecha_inicio'] ?? null;
            $fin            = $row['fecha_fin'] ?? null;
            $inicioTs       = $inicio ? strtotime((string)$inicio) : false;
            $finTs          = $fin ? strtotime((string)$fin) : false;

            $events[] = [
                'id'    => (int)($row['id'] ?? 0),
                'title' => trim((string)($row['cliente_nombre'] ?? 'Cita')),
                'start' => $inicioTs ? date(DATE_ATOM, $inicioTs) : null,
                'end'   => $finTs ? date(DATE_ATOM, $finTs) : null,
                'extendedProps' => [
                    'cliente' => $row['cliente_nombre'] ?? '',
                    'equipo'  => $row['equipo_descripcion'] ?? '',
                    'tecnico' => trim(((string)($row['tecnico_nombre'] ?? '')) . ' ' . ((string)($row['tecnico_apellido'] ?? ''))),
                    'estado'  => $row['estado'] ?? '',
                    'notas'   => $row['notas'] ?? '',
                ],
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($events, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
