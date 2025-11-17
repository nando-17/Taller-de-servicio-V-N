<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Factura extends Model
{
    protected string $table = 'facturas';

    protected array $fillable = [
        'orden_id',
        'cliente_id',
        'tipo',
        'serie',
        'numero',
        'moneda',
        'fecha_emision',
        'subtotal',
        'impuesto',
        'total',
        'estado',
        'observaciones',
    ];

    public function getAll(): array
    {
        $sql = "
            SELECT f.*, os.codigo AS orden_codigo, c.nombre_razon AS cliente_nombre
            FROM facturas f
            JOIN ordenes_servicio os ON f.orden_id = os.id
            JOIN clientes c ON f.cliente_id = c.id
            ORDER BY f.fecha_emision DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT f.*, os.codigo AS orden_codigo, c.nombre_razon AS cliente_nombre
            FROM facturas f
            JOIN ordenes_servicio os ON f.orden_id = os.id
            JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        return $factura ?: null;
    }

    public function findByOrdenId(int $ordenId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM facturas WHERE orden_id = :orden LIMIT 1');
        $stmt->execute(['orden' => $ordenId]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);

        return $factura ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO facturas (orden_id, cliente_id, tipo, serie, numero, moneda, subtotal, impuesto, total, estado, observaciones)
             VALUES (:orden_id, :cliente_id, :tipo, :serie, :numero, :moneda, :subtotal, :impuesto, :total, :estado, :observaciones)'
        );

        $stmt->execute([
            'orden_id' => $data['orden_id'],
            'cliente_id' => $data['cliente_id'],
            'tipo' => $data['tipo'] ?? 'RECIBO',
            'serie' => $data['serie'] ?? null,
            'numero' => $data['numero'] ?? null,
            'moneda' => $data['moneda'] ?? 'PEN',
            'subtotal' => $data['subtotal'] ?? 0,
            'impuesto' => $data['impuesto'] ?? 0,
            'total' => $data['total'] ?? 0,
            'estado' => $data['estado'] ?? 'EMITIDA',
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateEstado(int $id, string $estado): bool
    {
        $stmt = $this->db->prepare('UPDATE facturas SET estado = :estado WHERE id = :id');
        return $stmt->execute(['estado' => $estado, 'id' => $id]);
    }
}
