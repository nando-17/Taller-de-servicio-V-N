<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Repuesto extends Model
{
    protected string $table = 'repuestos';

    protected array $fillable = [
        'codigo',
        'nombre',
        'marca_id',
        'modelo_id',
        'unidad',
        'precio_costo',
        'precio_venta',
        'stock_minimo',
        'activo',
    ];

    public function getActivosConStock(): array
    {
        $sql = "
            SELECT r.*, COALESCE(re.cantidad, 0) AS stock
            FROM repuestos r
            LEFT JOIN repuesto_existencias re ON r.id = re.repuesto_id
            WHERE r.activo = 1
            ORDER BY r.nombre
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTodosConStock(): array
    {
        $sql = "
            SELECT r.*, COALESCE(re.cantidad, 0) AS stock
            FROM repuestos r
            LEFT JOIN repuesto_existencias re ON r.id = re.repuesto_id
            ORDER BY r.activo DESC, r.nombre
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM repuestos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $repuesto = $stmt->fetch(PDO::FETCH_ASSOC);

        return $repuesto ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO repuestos (codigo, nombre, marca_id, modelo_id, unidad, precio_costo, precio_venta, stock_minimo, activo)
             VALUES (:codigo, :nombre, :marca_id, :modelo_id, :unidad, :precio_costo, :precio_venta, :stock_minimo, :activo)'
        );

        $stmt->execute([
            'codigo' => $data['codigo'] ?? null,
            'nombre' => $data['nombre'],
            'marca_id' => $data['marca_id'] ?? null,
            'modelo_id' => $data['modelo_id'] ?? null,
            'unidad' => $data['unidad'] ?? 'UND',
            'precio_costo' => $data['precio_costo'] ?? 0,
            'precio_venta' => $data['precio_venta'] ?? 0,
            'stock_minimo' => $data['stock_minimo'] ?? 0,
            'activo' => $data['activo'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $campos = [];
        foreach ($data as $campo => $valor) {
            $campos[] = sprintf('%s = :%s', $campo, $campo);
        }

        $sql = sprintf('UPDATE repuestos SET %s WHERE id = :id', implode(', ', $campos));
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;

        return $stmt->execute($data);
    }
}
