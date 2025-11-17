<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Cliente extends Model
{
    protected $table = 'clientes';

    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
