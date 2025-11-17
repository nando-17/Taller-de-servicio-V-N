<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\EquipoModel;

class EquipoController extends Controller
{
    private EquipoModel $equipoModel;

    public function __construct()
    {
        $this->equipoModel = new EquipoModel();
    }

    public function getById(int $id): void
    {
        $equipo = $this->equipoModel->getById($id);
        header('Content-Type: application/json');
        echo json_encode($equipo);
    }
}
