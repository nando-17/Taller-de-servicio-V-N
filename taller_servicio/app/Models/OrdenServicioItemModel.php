<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use PDO;

class OrdenServicioItemModel extends Model
{
    public function __construct()
    {
        parent::__construct('orden_servicio_items');
    }

    public function getByOrder(int $orderId): array
    {
        $sql = "
            SELECT osi.id, osi.orden_id, osi.servicio_id, osi.descripcion, osi.cantidad, osi.precio_unitario, osi.total_linea,
                   sc.nombre AS servicio_nombre
            FROM orden_servicio_items osi
            JOIN servicios_catalogo sc ON osi.servicio_id = sc.id
            WHERE osi.orden_id = :orden
            ORDER BY osi.id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden' => $orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orden_servicio_items (orden_id, servicio_id, descripcion, cantidad, precio_unitario, total_linea)
             VALUES (:orden_id, :servicio_id, :descripcion, :cantidad, :precio_unitario, :total_linea)'
        );

        $cantidad = (float) ($data['cantidad'] ?? 0);
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        $stmt->execute([
            'orden_id' => (int) ($data['orden_id'] ?? 0),
            'servicio_id' => (int) ($data['servicio_id'] ?? 0),
            'descripcion' => $data['descripcion'] ?? null,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'total_linea' => $cantidad * $precioUnitario,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $itemId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orden_servicio_items WHERE id = :id');
        return $stmt->execute(['id' => $itemId]);
    }

    public function sumTotals(int $orderId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(total_linea), 0) FROM orden_servicio_items WHERE orden_id = :orden');
        $stmt->execute(['orden' => $orderId]);
        return (float) $stmt->fetchColumn();
    }

    public function getServiciosDisponibles(): array
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT id, nombre, precio_base FROM servicios_catalogo WHERE activo = 1 ORDER BY nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

