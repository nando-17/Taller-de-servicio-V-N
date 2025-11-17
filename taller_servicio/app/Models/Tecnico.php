<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Tecnico extends Model
{
    protected string $table = 'tecnicos';

    protected array $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'email',
        'especialidad',
        'activo',
        'created_at',
        'updated_at',
    ];
}
