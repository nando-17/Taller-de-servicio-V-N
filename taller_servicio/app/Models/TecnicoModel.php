<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use PDO;

class TecnicoModel extends Model
{
    public function __construct()
    {
        parent::__construct('tecnicos');
    }

    public function getAllActive(bool $onlyAvailable = false): array
    {
        $db = Database::connection();

        if ($onlyAvailable) {
            $sql = "
                SELECT t.*
                FROM tecnicos t
                WHERE t.activo = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM ordenes_servicio os
                      JOIN estados_orden eo ON os.estado_id = eo.id
                      WHERE os.tecnico_asignado_id = t.id
                        AND eo.codigo NOT IN ('LISTO', 'ENTREGADO', 'ANULADO')
                  )
                ORDER BY t.nombres, t.apellidos
            ";
            $stmt = $db->query($sql);
        } else {
            $stmt = $db->query('SELECT * FROM tecnicos WHERE activo = 1 ORDER BY nombres, apellidos');
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findActiveById(int $id, bool $onlyAvailable = false): ?array
    {
        $db = Database::connection();

        if ($onlyAvailable) {
            $sql = "
                SELECT t.*
                FROM tecnicos t
                WHERE t.id = :id
                  AND t.activo = 1
                  AND NOT EXISTS (
                      SELECT 1
                      FROM ordenes_servicio os
                      JOIN estados_orden eo ON os.estado_id = eo.id
                      WHERE os.tecnico_asignado_id = t.id
                        AND eo.codigo NOT IN ('LISTO', 'ENTREGADO', 'ANULADO')
                  )
                LIMIT 1
            ";
            $stmt = $db->prepare($sql);
        } else {
            $stmt = $db->prepare('SELECT * FROM tecnicos WHERE id = :id AND activo = 1 LIMIT 1');
        }

        $stmt->execute(['id' => $id]);
        $tecnico = $stmt->fetch(PDO::FETCH_ASSOC);

        return $tecnico ?: null;
    }
}
