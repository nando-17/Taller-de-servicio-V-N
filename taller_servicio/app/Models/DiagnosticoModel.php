<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DiagnosticoModel extends Model
{
    public function __construct()
    {
        parent::__construct('diagnosticos');
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO diagnosticos (orden_id, tecnico_id, descripcion) VALUES (:orden_id, :tecnico_id, :descripcion)'
        );

        $stmt->execute([
            'orden_id' => (int) ($data['orden_id'] ?? 0),
            'tecnico_id' => $data['tecnico_id'] !== null ? (int) $data['tecnico_id'] : null,
            'descripcion' => (string) ($data['descripcion'] ?? ''),
        ]);

        return (int) $this->db->lastInsertId();
    }
}
