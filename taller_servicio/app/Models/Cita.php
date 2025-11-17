<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Cita extends Model
{
    protected string $table = 'citas';

    protected array $fillable = [
        'cliente_id',
        'equipo_id',
        'tecnico_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'notas',
        'created_by',
        'created_at',
        'updated_at',
    ];

    public function getUpcoming(): array
    {
        $sql = "
            SELECT ci.*,
                   c.nombre_razon AS cliente_nombre,
                   COALESCE(t.nombres, '')   AS tecnico_nombre,
                   COALESCE(t.apellidos, '') AS tecnico_apellido,
                   e.descripcion             AS equipo_descripcion
            FROM citas ci
            JOIN clientes c   ON ci.cliente_id = c.id
            LEFT JOIN tecnicos t ON ci.tecnico_id = t.id
            LEFT JOIN equipos e  ON ci.equipo_id = e.id
            WHERE ci.fecha_fin >= NOW()
            ORDER BY ci.fecha_inicio
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBetween(?string $start, ?string $end): array
    {
        $conditions = [];
        $params = [];

        if (!empty($start)) {
            $conditions[]     = 'ci.fecha_fin   >= :start';
            $params[':start'] = $start;
        }

        if (!empty($end)) {
            $conditions[]   = 'ci.fecha_inicio <= :end';
            $params[':end'] = $end;
        }

        $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $sql = "
            SELECT ci.*,
                   c.nombre_razon AS cliente_nombre,
                   COALESCE(t.nombres, '')   AS tecnico_nombre,
                   COALESCE(t.apellidos, '') AS tecnico_apellido,
                   e.descripcion             AS equipo_descripcion
            FROM citas ci
            JOIN clientes c   ON ci.cliente_id = c.id
            LEFT JOIN tecnicos t ON ci.tecnico_id = t.id
            LEFT JOIN equipos e  ON ci.equipo_id = e.id
            $where
            ORDER BY ci.fecha_inicio
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $sql = '
            INSERT INTO citas
                (cliente_id, equipo_id, tecnico_id, fecha_inicio, fecha_fin, estado, notas, created_by)
            VALUES
                (:cliente_id, :equipo_id, :tecnico_id, :fecha_inicio, :fecha_fin, :estado, :notas, :created_by)
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':cliente_id'   => $data['cliente_id'],
            ':equipo_id'    => $data['equipo_id'] ?? null,
            ':tecnico_id'   => $data['tecnico_id'] ?? null,
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin'    => $data['fecha_fin'],
            ':estado'       => $data['estado'] ?? 'PENDIENTE',
            ':notas'        => $data['notas'] ?? null,
            ':created_by'   => $data['created_by'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return true;
        }

        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $field => $value) {
            $fields[] = sprintf('%s = :%s', $field, $field);
            $params[sprintf(':%s', $field)] = $value;
        }

        // Mantén updated_at si tu tabla lo tiene
        if (!array_key_exists(':updated_at', $params)) {
            $fields[] = 'updated_at = NOW()';
        }

        $sql = sprintf('UPDATE citas SET %s WHERE id = :id', implode(', ', $fields));
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM citas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cita ?: null;
    }

    /**
     * Verifica si existe solape de horario para el técnico.
     * Regla estándar: (existente.inicio < nuevo.fin) AND (existente.fin > nuevo.inicio)
     * Excluye CANCELADA y permite ignorar un ID (para ediciones).
     */
    public function hasOverlap(int $tecnicoId, string $inicio, string $fin, ?int $ignoreId = null): bool
    {
        $sql = "
            SELECT 1
            FROM citas
            WHERE tecnico_id = :tecnico_id
              AND estado IN ('PENDIENTE','CONFIRMADA')
              AND fecha_inicio < :nuevo_fin
              AND fecha_fin    > :nuevo_inicio
        ";

        $params = [
            ':tecnico_id'   => $tecnicoId,
            ':nuevo_inicio' => $inicio,
            ':nuevo_fin'    => $fin,
        ];

        if ($ignoreId !== null) {
            $sql .= " AND id <> :ignore_id";
            $params[':ignore_id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool)$stmt->fetchColumn();
    }
}
