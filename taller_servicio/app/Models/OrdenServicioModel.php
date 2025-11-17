<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use App\Core\Session;
use PDO;

class OrdenServicioModel extends Model
{
    public function __construct()
    {
        parent::__construct('ordenes_servicio');
    }

    public function getAll(?string $statusCode = null, ?string $term = null): array
    {
        $db = Database::connection();
        $sql = "
            SELECT
                os.id,
                os.codigo,
                os.fecha_recepcion,
                c.nombre_razon AS cliente,
                CONCAT(
                    te.nombre,
                    ' - ',
                    COALESCE(m.nombre, 'Sin marca'),
                    ' - ',
                    COALESCE(mo.nombre, 'Sin modelo')
                ) AS equipo,
                e.descripcion AS equipo_descripcion,
                os.falla_reportada,
                est.nombre AS estado,
                est.codigo AS estado_codigo,
                pr.nombre AS prioridad_nombre
            FROM ordenes_servicio os
            JOIN clientes c ON os.cliente_id = c.id
            JOIN equipos e ON os.equipo_id = e.id
            JOIN tipos_equipo te ON e.tipo_equipo_id = te.id
            LEFT JOIN marcas m ON e.marca_id = m.id
            LEFT JOIN modelos mo ON e.modelo_id = mo.id
            JOIN estados_orden est ON os.estado_id = est.id
            JOIN prioridades pr ON os.prioridad_id = pr.id
        ";

        $conditions = [];
        $params = [];

        if ($statusCode !== null && $statusCode !== '') {
            $conditions[] = 'est.codigo = :status';
            $params['status'] = strtoupper($statusCode);
        }

        if ($term !== null && $term !== '') {
            $conditions[] = '(
                os.codigo LIKE :term OR
                c.nombre_razon LIKE :term OR
                e.descripcion LIKE :term
            )';
            $params['term'] = '%' . $term . '%';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY os.fecha_recepcion DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id)
    {
        $db = Database::connection();
        $sql = "
            SELECT
                os.*,
                c.nombre_razon AS cliente_nombre,
                CONCAT(
                    te.nombre,
                    ' - ',
                    COALESCE(m.nombre, 'Sin marca'),
                    ' - ',
                    COALESCE(mo.nombre, 'Sin modelo')
                ) AS equipo_detalle,
                e.descripcion AS equipo_descripcion,
                e.accesorios_base AS equipo_accesorios,
                est.nombre AS estado_nombre,
                TRIM(CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, ''))) AS tecnico_nombre
            FROM ordenes_servicio os
            JOIN clientes c ON os.cliente_id = c.id
            JOIN equipos e ON os.equipo_id = e.id
            JOIN tipos_equipo te ON e.tipo_equipo_id = te.id
            LEFT JOIN marcas m ON e.marca_id = m.id
            LEFT JOIN modelos mo ON e.modelo_id = mo.id
            JOIN estados_orden est ON os.estado_id = est.id
            LEFT JOIN tecnicos t ON os.tecnico_asignado_id = t.id
            WHERE os.id = :id
            LIMIT 1;
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function create(array $data): int
    {
        $db = Database::connection();
        $codigo = 'OS-' . date('Y') . '-' . str_pad((string)$this->getNextId(), 5, '0', STR_PAD_LEFT);
        $data['codigo'] = $codigo;
        $stmt = $db->prepare('INSERT INTO ordenes_servicio (cliente_id, equipo_id, estado_id, prioridad_id, falla_reportada, accesorios_recibidos, garantia, ubicacion, created_by, codigo)
                            VALUES (:cliente_id, :equipo_id, :estado_id, :prioridad_id, :falla_reportada, :accesorios_recibidos, :garantia, :ubicacion, :usuario_id, :codigo)');
        $stmt->execute($data);
        return (int)$db->lastInsertId();
    }

    public function assignTechnician(int $orderId, int $technicianId, int $userId): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE ordenes_servicio SET tecnico_asignado_id = :tecnico_id, updated_by = :user_id WHERE id = :order_id');
        return $stmt->execute(['tecnico_id' => $technicianId, 'user_id' => $userId, 'order_id' => $orderId]);
    }

    public function updateStatus(int $orderId, int $statusId, int $userId): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE ordenes_servicio SET estado_id = :status_id, updated_by = :user_id WHERE id = :order_id');
        return $stmt->execute(['status_id' => $statusId, 'user_id' => $userId, 'order_id' => $orderId]);
    }

    public function updateEstimatedCost(int $orderId, float $cost, int $userId): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE ordenes_servicio SET costo_estimado = :cost, updated_by = :user_id WHERE id = :order_id');
        return $stmt->execute(['cost' => $cost, 'user_id' => $userId, 'order_id' => $orderId]);
    }

    public function getInitialStatusId(): ?int
    {
        $db = Database::connection();
        $stmt = $db->query("SELECT id FROM estados_orden WHERE codigo = 'RECIBIDO' LIMIT 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    public function getStatusIdByCode(string $code): ?int
    {
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM estados_orden WHERE codigo = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['id'] : null;
    }

    public function getStatusCodeById(int $statusId): ?string
    {
        $stmt = $this->db->prepare('SELECT codigo FROM estados_orden WHERE id = :id');
        $stmt->execute(['id' => $statusId]);
        $codigo = $stmt->fetchColumn();

        return $codigo !== false ? (string) $codigo : null;
    }

    public function getStatusCatalog(): array
    {
        $stmt = $this->db->query('SELECT id, codigo, nombre FROM estados_orden ORDER BY orden');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getValidTransitions(string $currentCode): array
    {
        $transitions = [
            'RECIBIDO' => ['DIAGNOSTICO', 'ANULADO'],
            'DIAGNOSTICO' => ['REPARACION', 'ESPERA_REPUESTO', 'ANULADO'],
            'REPARACION' => ['LISTO', 'ESPERA_REPUESTO', 'ANULADO'],
            'ESPERA_REPUESTO' => ['DIAGNOSTICO', 'REPARACION', 'ANULADO'],
            'LISTO' => ['ENTREGADO', 'ANULADO'],
            'ENTREGADO' => [],
            'ANULADO' => [],
        ];

        return $transitions[$currentCode] ?? [];
    }

    public function getDetailForManagement(int $orderId): ?array
    {
        $sql = "
            SELECT os.*, c.nombre_razon AS cliente_nombre, c.telefono AS cliente_telefono, c.email AS cliente_email,
                   e.descripcion AS equipo_descripcion, est.codigo AS estado_codigo, est.nombre AS estado_nombre,
                   COALESCE(t.nombres, '') AS tecnico_nombre, COALESCE(t.apellidos, '') AS tecnico_apellido
            FROM ordenes_servicio os
            JOIN clientes c ON os.cliente_id = c.id
            JOIN equipos e ON os.equipo_id = e.id
            JOIN estados_orden est ON os.estado_id = est.id
            LEFT JOIN tecnicos t ON os.tecnico_asignado_id = t.id
            WHERE os.id = :orden
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['orden' => $orderId]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$detalle) {
            return null;
        }

        $detalle['tecnico_completo'] = trim(($detalle['tecnico_nombre'] ?? '') . ' ' . ($detalle['tecnico_apellido'] ?? ''));

        return $detalle;
    }

    public function actualizarCampos(int $orderId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        foreach ($data as $field => $value) {
            $fields[] = sprintf('%s = :%s', $field, $field);
        }

        $sql = sprintf('UPDATE ordenes_servicio SET %s, updated_by = :user WHERE id = :id', implode(', ', $fields));
        $stmt = $this->db->prepare($sql);

        foreach ($data as $field => $value) {
            $stmt->bindValue(':' . $field, $value);
        }

        $userId = Session::get('user_id');
        if ($userId !== null) {
            $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':user', null, PDO::PARAM_NULL);
        }
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function getAllPriorities(): array
    {
        $db = Database::connection();
        $stmt = $db->query('SELECT * FROM prioridades ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getNextId(): int
    {
        $db = Database::connection();
        $stmt = $db->query("SELECT MAX(id) + 1 as next_id FROM ordenes_servicio");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['next_id'] ? (int)$result['next_id'] : 1;
    }

    public function getPendientesDeFacturacion(): array
    {
        $sql = "
            SELECT os.id, os.codigo, c.nombre_razon AS cliente, est.nombre AS estado
            FROM ordenes_servicio os
            JOIN clientes c ON os.cliente_id = c.id
            JOIN estados_orden est ON os.estado_id = est.id
            LEFT JOIN facturas f ON f.orden_id = os.id
            WHERE f.id IS NULL
            ORDER BY os.fecha_recepcion DESC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
