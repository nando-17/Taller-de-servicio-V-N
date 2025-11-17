<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ClienteModel extends Model
{
    public function __construct()
    {
        parent::__construct('clientes');
    }
}
