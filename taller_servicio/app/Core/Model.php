<?php

namespace App\Core;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct(?string $table = null)
    {
        $this->db = Database::connection();
        if ($table !== null) {
            $this->table = $table;
        }
    }

    public function getAll(): array
    {
        if ($this->table === '') {
            return [];
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table}");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
