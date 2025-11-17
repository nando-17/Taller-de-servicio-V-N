<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use PDO;

class OrdenRepuestoItemModel extends Model
{
    public function __construct()
    {
        parent::__construct('orden_repuesto_items');
    }

    public function getByOrder(int $orderId): array
    {
        $sql = "
            SELECT ori.id, ori.orden_id, ori.repuesto_id, ori.cantidad, ori.precio_unitario, ori.total_linea,
                   r.nombre AS repuesto_nombre, COALESCE(re.cantidad, 0) AS stock_actual
            FROM orden_repuesto_items ori
            JOIN repuestos r ON ori.repuesto_id = r.id
            LEFT JOIN repuesto_existencias re ON ori.repuesto_id = re.repuesto_id
            WHERE ori.orden_id = :orden
            ORDER BY ori.id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden' => $orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO orden_repuesto_items (orden_id, repuesto_id, cantidad, precio_unitario, total_linea)
             VALUES (:orden_id, :repuesto_id, :cantidad, :precio_unitario, :total_linea)'
        );

        $cantidad = (float) ($data['cantidad'] ?? 0);
        $precioUnitario = (float) ($data['precio_unitario'] ?? 0);

        $stmt->execute([
            'orden_id' => (int) ($data['orden_id'] ?? 0),
            'repuesto_id' => (int) ($data['repuesto_id'] ?? 0),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'total_linea' => $cantidad * $precioUnitario,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $itemId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orden_repuesto_items WHERE id = :id');
        return $stmt->execute(['id' => $itemId]);
    }

    public function sumTotals(int $orderId): float
    {
        $stmt = $this->db->prepare('SELECT COALESCE(SUM(total_linea), 0) FROM orden_repuesto_items WHERE orden_id = :orden');
        $stmt->execute(['orden' => $orderId]);
        return (float) $stmt->fetchColumn();
    }

    public function getRepuestosDisponibles(): array
    {
        $db = Database::connection();
        $sql = "
            SELECT r.id, r.nombre, r.precio_venta, COALESCE(re.cantidad, 0) AS stock
            FROM repuestos r
            LEFT JOIN repuesto_existencias re ON r.id = re.repuesto_id
            WHERE r.activo = 1
            ORDER BY r.nombre
        ";

        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

