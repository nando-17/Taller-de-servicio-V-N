<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class DetalleFactura extends Model
{
    protected string $table = 'factura_items';

    protected array $fillable = [
        'factura_id',
        'tipo_item',
        'referencia_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'total_linea',
    ];

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO factura_items (factura_id, tipo_item, referencia_id, descripcion, cantidad, precio_unitario, total_linea)
             VALUES (:factura_id, :tipo_item, :referencia_id, :descripcion, :cantidad, :precio_unitario, :total_linea)'
        );

        $stmt->execute([
            'factura_id' => $data['factura_id'],
            'tipo_item' => $data['tipo_item'],
            'referencia_id' => $data['referencia_id'] ?? null,
            'descripcion' => $data['descripcion'],
            'cantidad' => $data['cantidad'],
            'precio_unitario' => $data['precio_unitario'],
            'total_linea' => $data['total_linea'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getByFactura(int $facturaId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM factura_items WHERE factura_id = :factura');
        $stmt->execute(['factura' => $facturaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
