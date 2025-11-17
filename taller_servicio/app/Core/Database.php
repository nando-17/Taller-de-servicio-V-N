<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../config/config.php';
            $db = $config['database'];

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $db['driver'],
                $db['host'],
                $db['port'],
                $db['database'],
                $db['charset']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $db['username'],
                    $db['password'],
                    $db['options']
                );
            } catch (PDOException $e) {
                throw new PDOException('Error de conexión a la base de datos: ' . $e->getMessage(), (int) $e->getCode());
            }
        }

        return self::$connection;
    }
}
