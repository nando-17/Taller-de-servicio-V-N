<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class RepuestoExistenciaModel extends Model
{
    public function __construct()
    {
        parent::__construct('repuesto_existencias');
    }

    public function getStock(int $repuestoId): int
    {
        $stmt = $this->db->prepare('SELECT cantidad FROM repuesto_existencias WHERE repuesto_id = :id');
        $stmt->execute(['id' => $repuestoId]);
        $stock = $stmt->fetchColumn();
        return $stock !== false ? (int) $stock : 0;
    }

    public function ensureRow(int $repuestoId): void
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO repuesto_existencias (repuesto_id, cantidad) VALUES (:id, 0)');
        $stmt->execute(['id' => $repuestoId]);
    }

    public function increaseStock(int $repuestoId, float $cantidad): bool
    {
        $this->ensureRow($repuestoId);
        $stmt = $this->db->prepare('UPDATE repuesto_existencias SET cantidad = cantidad + :cantidad WHERE repuesto_id = :id');
        return $stmt->execute(['cantidad' => $cantidad, 'id' => $repuestoId]);
    }

    public function decreaseStock(int $repuestoId, float $cantidad): bool
    {
        $stockActual = $this->getStock($repuestoId);
        if ($cantidad > $stockActual) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE repuesto_existencias SET cantidad = cantidad - :cantidad WHERE repuesto_id = :id');
        return $stmt->execute(['cantidad' => $cantidad, 'id' => $repuestoId]);
    }
}

