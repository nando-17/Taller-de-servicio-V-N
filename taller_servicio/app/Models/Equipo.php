<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Equipo extends Model
{
    protected $table = 'equipos';

    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
