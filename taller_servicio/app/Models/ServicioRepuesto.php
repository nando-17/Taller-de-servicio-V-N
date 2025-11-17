<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ServicioRepuesto extends Model
{
    protected string $table = 'orden_repuesto_items';

    protected array $fillable = [
        'orden_id',
        'repuesto_id',
        'cantidad',
        'precio_unitario',
        'total_linea',
    ];
}
