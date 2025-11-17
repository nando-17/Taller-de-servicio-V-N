<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Usuario extends Model
{
    protected string $table = 'usuarios';

    protected array $fillable = [
        'nombre',
        'email',
        'password_hash',
        'rol',
        'activo',
        'created_at',
        'updated_at',
    ];

    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT id, nombre, email, rol, activo, created_at FROM usuarios ORDER BY nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash, rol, activo)
             VALUES (:nombre, :email, :password_hash, :rol, :activo)'
        );

        $stmt->execute([
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'rol' => strtoupper(trim((string) ($data['rol'] ?? 'OPERADOR'))),
            'activo' => $data['activo'] ?? 1,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (array_key_exists('rol', $data)) {
            $data['rol'] = strtoupper(trim((string) $data['rol']));
        }

        $fields = [];
        foreach ($data as $field => $value) {
            $fields[] = sprintf('%s = :%s', $field, $field);
        }

        $sql = sprintf('UPDATE usuarios SET %s WHERE id = :id', implode(', ', $fields));
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;

        return $stmt->execute($data);
    }
}
