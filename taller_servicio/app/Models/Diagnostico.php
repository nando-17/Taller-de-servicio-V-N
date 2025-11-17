<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Diagnostico extends Model
{
    protected string $table = 'diagnosticos';

    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (orden_id, tecnico_id, descripcion) VALUES (:orden_id, :tecnico_id, :descripcion)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':orden_id', $data['orden_id'], PDO::PARAM_INT);
        $stmt->bindValue(':tecnico_id', $data['tecnico_id'], PDO::PARAM_INT);
        $stmt->bindValue(':descripcion', $data['descripcion']);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }
}
