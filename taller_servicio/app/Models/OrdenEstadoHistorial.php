<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class OrdenEstadoHistorial extends Model
{
    protected string $table = 'orden_estado_historial';

    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} (orden_id, estado_id, usuario_id, comentario) VALUES (:orden_id, :estado_id, :usuario_id, :comentario)";
        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':orden_id', $data['orden_id'], PDO::PARAM_INT);
        $stmt->bindValue(':estado_id', $data['estado_id'], PDO::PARAM_INT);
        if ($data['usuario_id'] !== null) {
            $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':usuario_id', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':comentario', $data['comentario']);

        return $stmt->execute();
    }

    public function getByOrder(int $ordenId): array
    {
        $sql = "
            SELECT h.*, eo.nombre AS estado_nombre, u.nombre AS usuario_nombre
            FROM orden_estado_historial h
            JOIN estados_orden eo ON h.estado_id = eo.id
            LEFT JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.orden_id = :orden
            ORDER BY h.fecha_cambio DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden' => $ordenId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
