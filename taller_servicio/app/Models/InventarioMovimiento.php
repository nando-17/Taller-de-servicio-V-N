<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class InventarioMovimiento extends Model
{
    protected string $table = 'movimientos_inventario';

    protected array $fillable = [
        'repuesto_id',
        'tipo',
        'cantidad',
        'costo_unitario',
        'motivo',
        'orden_id',
        'fecha_mov',
        'usuario_id',
    ];

    public function registrarMovimiento(array $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO movimientos_inventario (repuesto_id, tipo, cantidad, costo_unitario, motivo, orden_id, usuario_id)
             VALUES (:repuesto_id, :tipo, :cantidad, :costo_unitario, :motivo, :orden_id, :usuario_id)'
        );

        return $stmt->execute([
            'repuesto_id' => (int) ($data['repuesto_id'] ?? 0),
            'tipo' => (string) ($data['tipo'] ?? ''),
            'cantidad' => (float) ($data['cantidad'] ?? 0),
            'costo_unitario' => $data['costo_unitario'] ?? null,
            'motivo' => $data['motivo'] ?? null,
            'orden_id' => $data['orden_id'] ?? null,
            'usuario_id' => $data['usuario_id'] ?? null,
        ]);
    }

    public function getRecent(int $limit = 20): array
    {
        $sql = "
            SELECT mi.*, r.nombre AS repuesto_nombre, os.codigo AS orden_codigo
            FROM movimientos_inventario mi
            JOIN repuestos r ON mi.repuesto_id = r.id
            LEFT JOIN ordenes_servicio os ON mi.orden_id = os.id
            ORDER BY mi.fecha_mov DESC
            LIMIT :limite
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
