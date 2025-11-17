<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Proveedor extends Model
{
    protected string $table = 'proveedores';

    protected array $fillable = [
        'razon_social',
        'nombre_contacto',
        'correo_electronico',
        'telefono',
        'direccion',
        'ciudad',
        'condiciones_pago'
    ];
}
