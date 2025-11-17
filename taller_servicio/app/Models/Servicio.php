<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Servicio extends Model
{
    protected string $table = 'servicios_catalogo';

    protected array $fillable = [
        'nombre',
        'descripcion',
        'precio_base',
        'activo',
    ];

    public function getActivos(): array
    {
        $stmt = $this->db->query('SELECT * FROM servicios_catalogo WHERE activo = 1 ORDER BY nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTodos(): array
    {
        $stmt = $this->db->query('SELECT * FROM servicios_catalogo ORDER BY activo DESC, nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM servicios_catalogo WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

        return $servicio ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO servicios_catalogo (nombre, descripcion, precio_base, activo) VALUES (:nombre, :descripcion, :precio_base, :activo)'
        );

        $stmt->execute([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio_base' => $data['precio_base'] ?? 0,
            'activo' => $data['activo'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        foreach ($data as $field => $value) {
            $fields[] = sprintf('%s = :%s', $field, $field);
        }

        $sql = sprintf('UPDATE servicios_catalogo SET %s WHERE id = :id', implode(', ', $fields));
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;

        return $stmt->execute($data);
    }
}
