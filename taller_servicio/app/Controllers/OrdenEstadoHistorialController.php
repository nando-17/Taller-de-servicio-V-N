<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OrdenEstadoHistorial;

class OrdenEstadoHistorialController extends Controller
{
    public function create(int $ordenId, int $estadoId, string $comentario = ''): void
    {
        $data = [
            'orden_id' => $ordenId,
            'estado_id' => $estadoId,
            'usuario_id' => $_SESSION['user']['id'] ?? null,
            'comentario' => $comentario,
        ];

        $ordenEstadoHistorial = new OrdenEstadoHistorial();
        $ordenEstadoHistorial->create($data);
    }
}
