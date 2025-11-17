<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class EquipoModel extends Model
{
    public function __construct()
    {
        parent::__construct('equipos');
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                e.id,
                te.nombre AS tipo,
                COALESCE(m.nombre, 'Sin marca') AS marca,
                COALESCE(mo.nombre, 'Sin modelo') AS modelo,
                e.descripcion,
                e.accesorios_base
            FROM equipos e
            JOIN tipos_equipo te ON e.tipo_equipo_id = te.id
            LEFT JOIN marcas m ON e.marca_id = m.id
            LEFT JOIN modelos mo ON e.modelo_id = mo.id
            ORDER BY te.nombre, m.nombre, mo.nombre;
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "
            SELECT
                e.id,
                te.nombre AS tipo,
                COALESCE(m.nombre, 'Sin marca') AS marca,
                COALESCE(mo.nombre, 'Sin modelo') AS modelo,
                e.descripcion,
                e.accesorios_base
            FROM equipos e
            JOIN tipos_equipo te ON e.tipo_equipo_id = te.id
            LEFT JOIN marcas m ON e.marca_id = m.id
            LEFT JOIN modelos mo ON e.modelo_id = mo.id
            WHERE e.id = :id
            LIMIT 1;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $equipo = $stmt->fetch();

        return $equipo ?: null;
    }
}
