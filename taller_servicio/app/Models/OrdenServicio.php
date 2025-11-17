<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class OrdenServicio extends Model
{
    protected string $table = 'ordenes_servicio';

    public function create(array $data): int
    {
        try {
            $this->db->beginTransaction();

            $correlative = $this->getNextYearlyCorrelative((int)date('Y'));
            $codigo = 'OS-' . date('Y') . '-' . str_pad((string)$correlative, 5, '0', STR_PAD_LEFT);

            $estadoId = $data['estado_id'] ?? 1;
            $usuarioId = $data['usuario_id'];

            $sql = "INSERT INTO {$this->table} (codigo, cliente_id, equipo_id, estado_id, prioridad_id, falla_reportada, accesorios_recibidos, garantia, ubicacion, created_by, updated_by) VALUES (:codigo, :cliente_id, :equipo_id, :estado_id, :prioridad_id, :falla_reportada, :accesorios_recibidos, :garantia, :ubicacion, :created_by, :updated_by)";
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':codigo', $codigo);
            $stmt->bindValue(':cliente_id', $data['cliente_id'], PDO::PARAM_INT);
            $stmt->bindValue(':equipo_id', $data['equipo_id'], PDO::PARAM_INT);
            $stmt->bindValue(':estado_id', $estadoId, PDO::PARAM_INT);
            $stmt->bindValue(':prioridad_id', $data['prioridad_id'], PDO::PARAM_INT);
            $stmt->bindValue(':falla_reportada', $data['falla_reportada']);
            if ($data['accesorios_recibidos'] !== null) {
                $stmt->bindValue(':accesorios_recibidos', $data['accesorios_recibidos']);
            } else {
                $stmt->bindValue(':accesorios_recibidos', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':garantia', $data['garantia'], PDO::PARAM_INT);
            if ($data['ubicacion'] !== null) {
                $stmt->bindValue(':ubicacion', $data['ubicacion']);
            } else {
                $stmt->bindValue(':ubicacion', null, PDO::PARAM_NULL);
            }

            $paramType = $usuarioId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL;
            $stmt->bindValue(':created_by', $usuarioId, $paramType);
            $stmt->bindValue(':updated_by', $usuarioId, $paramType);

            $stmt->execute();
            $lastId = (int)$this->db->lastInsertId();

            $this->db->commit();

            return $lastId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getNextYearlyCorrelative(int $year): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE YEAR(fecha_recepcion) = :year";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) + 1;
    }

    public function findById(int $id): ?array
    {
        // This query should join other tables to get complete information
        $sql = "SELECT os.*, c.nombre_razon as cliente_nombre, e.descripcion as equipo_descripcion
                FROM {$this->table} os
                JOIN clientes c ON os.cliente_id = c.id
                JOIN equipos e ON os.equipo_id = e.id
                WHERE os.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        foreach (array_keys($data) as $field) {
            $fields[] = "{$field} = :{$field}";
        }
        $fieldsStr = implode(', ', $fields);

        $sql = "UPDATE {$this->table} SET {$fieldsStr} WHERE id = :id";
        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(":{$key}", $value, $paramType);
        }
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function assignTechnician(int $orderId, int $technicianId, int $usuarioId): bool
    {
        try {
            $this->db->beginTransaction();

            $this->update($orderId, [
                'tecnico_asignado_id' => $technicianId,
                'updated_by' => $usuarioId,
            ]);

            $historial = new OrdenEstadoHistorial();
            $historial->create([
                'orden_id' => $orderId,
                'estado_id' => 2, // Assuming 2 = 'En diagnóstico'
                'usuario_id' => $usuarioId,
                'comentario' => 'Técnico asignado.'
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    public function getAll(): array
    {
        $sql = "SELECT os.id, os.codigo, c.nombre_razon AS cliente, e.descripcion AS equipo, os.falla_reportada, es.nombre AS estado
        FROM {$this->table} os
        JOIN clientes c ON os.cliente_id = c.id
        JOIN equipos e ON os.equipo_id = e.id
        JOIN estados_orden es ON os.estado_id = es.id
        ORDER BY os.fecha_recepcion DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $orderId, int $statusId, int $usuarioId): bool
    {
        return $this->update($orderId, [
            'estado_id' => $statusId,
            'updated_by' => $usuarioId,
        ]);
    }

    public function updateEstimatedCost(int $orderId, float $cost, int $usuarioId): bool
    {
        return $this->update($orderId, [
            'costo_estimado' => $cost,
            'updated_by' => $usuarioId,
        ]);
    }

    public function getStatusIdByCode(string $code): ?int
    {
        $sql = "SELECT id FROM estados_orden WHERE codigo = :codigo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['codigo' => $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    public function getInitialStatusId(): ?int
    {
        return $this->getStatusIdByCode('RECIBIDO');
    }

    public function getAllPriorities(): array
    {
        try {
            $stmt = $this->db->query('SELECT id, nombre FROM prioridades ORDER BY id');
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            return [];
        }
    }
}
