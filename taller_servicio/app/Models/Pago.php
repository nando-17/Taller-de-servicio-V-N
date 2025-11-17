<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Pago extends Model
{
    protected string $table = 'pagos';

    protected array $fillable = [
        'factura_id',
        'fecha_pago',
        'monto',
        'metodo_pago',
        'referencia',
        'estado',
        'usuario_id',
    ];

    public function getByFactura(int $facturaId): array
    {
        $sql = "
            SELECT p.*, u.nombre AS usuario_nombre
            FROM pagos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.factura_id = :factura
            ORDER BY p.fecha_pago DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['factura' => $facturaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO pagos (factura_id, monto, metodo_pago, referencia, estado, usuario_id)
             VALUES (:factura_id, :monto, :metodo_pago, :referencia, :estado, :usuario_id)'
        );

        $stmt->execute([
            'factura_id' => $data['factura_id'],
            'monto' => $data['monto'],
            'metodo_pago' => $data['metodo_pago'],
            'referencia' => $data['referencia'] ?? null,
            'estado' => $data['estado'] ?? 'APLICADO',
            'usuario_id' => $data['usuario_id'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateEstado(int $pagoId, string $estado, ?string $referencia = null): bool
    {
        $stmt = $this->db->prepare('UPDATE pagos SET estado = :estado, referencia = COALESCE(:referencia, referencia) WHERE id = :id');
        return $stmt->execute(['estado' => $estado, 'referencia' => $referencia, 'id' => $pagoId]);
    }

    public function sumAppliedByFactura(int $facturaId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE factura_id = :factura AND estado = 'APLICADO'"
        );
        $stmt->execute(['factura' => $facturaId]);
        return (float) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pagos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $pago = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pago ?: null;
    }
}
