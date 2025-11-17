<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use PDOException;

class EquipmentController extends Controller
{
    public function form(): void
    {
        $this->requireAuth(['ADMIN', 'OPERADOR']);

        $errors = $_SESSION['form_errors']['equipment'] ?? [];
        $old = $_SESSION['old']['equipment'] ?? null;
        $search = trim($_GET['buscar'] ?? '');
        $editId = isset($_GET['editar']) ? (int) $_GET['editar'] : null;

        $equipmentList = [];
        $equipmentToEdit = null;
        $customers = [];
        $types = [];
        $brands = [];
        $models = [];

        try {
            $db = Database::connection();

            $customers = $this->fetchCustomers($db);
            $types = $this->fetchEquipmentTypes($db);
            $brands = $this->fetchBrands($db);
            $models = $this->fetchModels($db);
            $equipmentList = $this->fetchEquipment($db, $search);

            if ($editId) {
                $equipmentToEdit = $this->findEquipmentById($db, $editId);

                if ($equipmentToEdit === null) {
                    $this->flash('warning', 'El equipo que intentas editar no existe.');
                    $this->redirect('/equipos');
                }
            }
        } catch (PDOException) {
            $this->flash('danger', 'No fue posible cargar la información de equipos.');
        }

        if ($old === null && $equipmentToEdit !== null) {
            $old = [
                'equipo_id' => (int) $equipmentToEdit['id'],
                'cliente_id' => (int) $equipmentToEdit['cliente_id'],
                'tipo_equipo_id' => (int) $equipmentToEdit['tipo_equipo_id'],
                'marca_id' => $equipmentToEdit['marca_id'] !== null ? (int) $equipmentToEdit['marca_id'] : null,
                'modelo_id' => $equipmentToEdit['modelo_id'] !== null ? (int) $equipmentToEdit['modelo_id'] : 0,
                'modelo_nombre' => '',
                'numero_serie' => (string) ($equipmentToEdit['numero_serie'] ?? ''),
                'imei' => (string) ($equipmentToEdit['imei'] ?? ''),
                'color' => (string) ($equipmentToEdit['color'] ?? ''),
                'descripcion' => (string) ($equipmentToEdit['descripcion'] ?? ''),
                'accesorios_base' => (string) ($equipmentToEdit['accesorios_base'] ?? ''),
            ];
        }

        $this->view('equipment/form', [
            'title' => 'Registro de Equipos',
            'errors' => $errors,
            'old' => $old ?? [],
            'equipment' => $equipmentList,
            'customers' => $customers,
            'types' => $types,
            'brands' => $brands,
            'models' => $models,
            'search' => $search,
            'editing' => $equipmentToEdit !== null,
        ]);

        unset($_SESSION['form_errors']['equipment'], $_SESSION['old']['equipment']);
    }

    public function save(): void
    {
        $this->requireAuth(['ADMIN', 'OPERADOR']);

        $equipoId = isset($_POST['equipo_id']) ? (int) $_POST['equipo_id'] : 0;
        $clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
        $tipoEquipoId = isset($_POST['tipo_equipo_id']) ? (int)$_POST['tipo_equipo_id'] : 0;
        $marcaId = isset($_POST['marca_id']) ? (int)$_POST['marca_id'] : null;
        $modeloId = isset($_POST['modelo_id']) ? (int)$_POST['modelo_id'] : null;
        $modeloNombre = trim($_POST['modelo_nombre'] ?? '');
        $numeroSerie = trim($_POST['numero_serie'] ?? '');
        $imei = trim($_POST['imei'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $accesoriosBase = trim($_POST['accesorios_base'] ?? '');

        $errors = $this->validateEquipment(
            $clienteId,
            $tipoEquipoId,
            $marcaId,
            $modeloId,
            $modeloNombre,
            $numeroSerie,
            $imei,
            $equipoId
        );

        $this->storeOldInput('equipment', [
            'equipo_id' => $equipoId,
            'cliente_id' => $clienteId,
            'tipo_equipo_id' => $tipoEquipoId,
            'marca_id' => $marcaId,
            'modelo_id' => $modeloId,
            'modelo_nombre' => $modeloNombre,
            'numero_serie' => $numeroSerie,
            'imei' => $imei,
            'color' => $color,
            'descripcion' => $descripcion,
            'accesorios_base' => $accesoriosBase,
        ]);

        if (!empty($errors)) {
            $this->storeErrors('equipment', $errors);
            $this->flash('danger', 'Corrige los datos del equipo.');
            $this->redirect('/equipos');
        }

        try {
            $db = Database::connection();
            $db->beginTransaction();

            if ($modeloId === 0) {
                $modeloId = null;
            }

            if ($modeloId === null && $modeloNombre !== '' && $marcaId) {
                $stmtModelo = $db->prepare('INSERT INTO modelos (marca_id, nombre) VALUES (:marca_id, :nombre)');
                $stmtModelo->execute([
                    'marca_id' => $marcaId,
                    'nombre' => $modeloNombre,
                ]);
                $modeloId = (int) $db->lastInsertId();
            }

            if ($equipoId > 0) {
                $existing = $this->findEquipmentById($db, $equipoId);

                if ($existing === null) {
                    $this->flash('warning', 'El equipo que intentas actualizar no existe.');
                    $this->redirect('/equipos');
                }

                $equipoStmt = $db->prepare(
                    'UPDATE equipos
                     SET cliente_id = :cliente_id,
                         tipo_equipo_id = :tipo_equipo_id,
                         marca_id = :marca_id,
                         modelo_id = :modelo_id,
                         numero_serie = :numero_serie,
                         imei = :imei,
                         color = :color,
                         descripcion = :descripcion,
                         accesorios_base = :accesorios_base
                     WHERE id = :id'
                );
                $equipoStmt->execute([
                    'cliente_id' => $clienteId,
                    'tipo_equipo_id' => $tipoEquipoId,
                    'marca_id' => $marcaId,
                    'modelo_id' => $modeloId,
                    'numero_serie' => $numeroSerie !== '' ? $numeroSerie : null,
                    'imei' => $imei !== '' ? $imei : null,
                    'color' => $color !== '' ? $color : null,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'accesorios_base' => $accesoriosBase !== '' ? $accesoriosBase : null,
                    'id' => $equipoId,
                ]);

                $message = 'Equipo actualizado correctamente.';
            } else {
                $equipoStmt = $db->prepare(
                    'INSERT INTO equipos (cliente_id, tipo_equipo_id, marca_id, modelo_id, numero_serie, imei, color, descripcion, accesorios_base)
                     VALUES (:cliente_id, :tipo_equipo_id, :marca_id, :modelo_id, :numero_serie, :imei, :color, :descripcion, :accesorios_base)'
                );
                $equipoStmt->execute([
                    'cliente_id' => $clienteId,
                    'tipo_equipo_id' => $tipoEquipoId,
                    'marca_id' => $marcaId,
                    'modelo_id' => $modeloId,
                    'numero_serie' => $numeroSerie !== '' ? $numeroSerie : null,
                    'imei' => $imei !== '' ? $imei : null,
                    'color' => $color !== '' ? $color : null,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'accesorios_base' => $accesoriosBase !== '' ? $accesoriosBase : null,
                ]);

                $message = 'Equipo registrado correctamente.';
            }

            $db->commit();
        } catch (PDOException) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }

            if ($numeroSerie !== '' && $this->isDuplicateSerie($numeroSerie, $equipoId > 0 ? $equipoId : null)) {
                $this->storeErrors('equipment', ['numero_serie' => 'El número de serie ya está registrado.']);
                $this->flash('warning', 'Número de serie duplicado.');
                $this->redirect('/equipos');
                return;
            }

            if ($imei !== '' && $this->isDuplicateImei($imei, $equipoId > 0 ? $equipoId : null)) {
                $this->storeErrors('equipment', ['imei' => 'El IMEI ya está registrado.']);
                $this->flash('warning', 'IMEI duplicado.');
                $this->redirect('/equipos');
                return;
            }

            $this->storeErrors('equipment', ['general' => 'No fue posible guardar el equipo.']);
            $this->flash('danger', 'Error al guardar el equipo.');
            $this->redirect('/equipos');
            return;
        }

        unset($_SESSION['old']['equipment']);
        $this->flash('success', $message);
        $this->redirect('/equipos');
    }

    public function delete(): void
    {
        $this->requireAuth(['ADMIN', 'OPERADOR']);

        $equipoId = isset($_POST['equipo_id']) ? (int) $_POST['equipo_id'] : 0;

        if ($equipoId <= 0) {
            $this->flash('warning', 'Selecciona un equipo válido para eliminar.');
            $this->redirect('/equipos');
        }

        try {
            $db = Database::connection();

            $equipment = $this->findEquipmentById($db, $equipoId);

            if ($equipment === null) {
                $this->flash('warning', 'El equipo indicado no existe.');
                $this->redirect('/equipos');
            }

            $stmt = $db->prepare('DELETE FROM equipos WHERE id = :id');
            $stmt->execute(['id' => $equipoId]);

            if ($stmt->rowCount() === 0) {
                $this->flash('warning', 'No se eliminó el equipo.');
                $this->redirect('/equipos');
            }
        } catch (PDOException) {
            $this->flash('danger', 'No fue posible eliminar el equipo.');
            $this->redirect('/equipos');
        }

        $this->flash('success', 'Equipo eliminado correctamente.');
        $this->redirect('/equipos');
    }

    private function validateEquipment(
        int $clienteId,
        int $tipoEquipoId,
        ?int $marcaId,
        ?int $modeloId,
        string $modeloNombre,
        string $numeroSerie,
        string $imei,
        int $equipoId = 0
    ): array {
        $errors = [];

        if ($clienteId <= 0) {
            $errors['cliente_id'] = 'Selecciona un cliente.';
        }

        if ($tipoEquipoId <= 0) {
            $errors['tipo_equipo_id'] = 'Selecciona el tipo de equipo.';
        }

        if ($marcaId === null || $marcaId <= 0) {
            $errors['marca_id'] = 'Selecciona la marca del equipo.';
        }

        if (($modeloId === null || $modeloId === 0) && $modeloNombre === '') {
            $errors['modelo_id'] = 'Selecciona un modelo o ingresa uno nuevo.';
        }

        if ($numeroSerie !== '' && strlen($numeroSerie) < 5) {
            $errors['numero_serie'] = 'El número de serie debe contener al menos 5 caracteres.';
        }

        if ($imei !== '' && !preg_match('/^[0-9]{14,16}$/', $imei)) {
            $errors['imei'] = 'El IMEI debe contener solo dígitos (14 a 16).';
        }

        if ($numeroSerie !== '' && $this->isDuplicateSerie($numeroSerie, $equipoId > 0 ? $equipoId : null)) {
            $errors['numero_serie'] = 'El número de serie ya está registrado en otro equipo.';
        }

        if ($imei !== '' && $this->isDuplicateImei($imei, $equipoId > 0 ? $equipoId : null)) {
            $errors['imei'] = 'El IMEI ya está registrado en otro equipo.';
        }

        return $errors;
    }

    private function isDuplicateSerie(string $numeroSerie, ?int $excludeId = null): bool
    {
        try {
            $db = Database::connection();
            if ($excludeId !== null) {
                $stmt = $db->prepare('SELECT COUNT(1) AS total FROM equipos WHERE numero_serie = :numero_serie AND id <> :id');
                $stmt->execute(['numero_serie' => $numeroSerie, 'id' => $excludeId]);
            } else {
                $stmt = $db->prepare('SELECT COUNT(1) AS total FROM equipos WHERE numero_serie = :numero_serie');
                $stmt->execute(['numero_serie' => $numeroSerie]);
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0) > 0;
        } catch (PDOException) {
            return false;
        }
    }

    private function isDuplicateImei(string $imei, ?int $excludeId = null): bool
    {
        try {
            $db = Database::connection();
            if ($excludeId !== null) {
                $stmt = $db->prepare('SELECT COUNT(1) AS total FROM equipos WHERE imei = :imei AND id <> :id');
                $stmt->execute(['imei' => $imei, 'id' => $excludeId]);
            } else {
                $stmt = $db->prepare('SELECT COUNT(1) AS total FROM equipos WHERE imei = :imei');
                $stmt->execute(['imei' => $imei]);
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0) > 0;
        } catch (PDOException) {
            return false;
        }
    }

    private function storeErrors(string $form, array $errors): void
    {
        $_SESSION['form_errors'][$form] = $errors;
    }

    private function storeOldInput(string $form, array $data): void
    {
        $_SESSION['old'][$form] = $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchCustomers(PDO $db): array
    {
        $stmt = $db->query(
            'SELECT id, nombre_razon, documento
             FROM clientes
             ORDER BY nombre_razon ASC
             LIMIT 200'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEquipmentTypes(PDO $db): array
    {
        $stmt = $db->query(
            'SELECT id, nombre
             FROM tipos_equipo
             ORDER BY nombre ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchBrands(PDO $db): array
    {
        $stmt = $db->query(
            'SELECT id, nombre
             FROM marcas
             ORDER BY nombre ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchModels(PDO $db): array
    {
        $stmt = $db->query(
            'SELECT id, marca_id, nombre
             FROM modelos
             ORDER BY nombre ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchEquipment(PDO $db, string $search): array
    {
        $baseSql =
            'SELECT e.id,
                    e.numero_serie,
                    e.imei,
                    e.color,
                    e.descripcion,
                    e.accesorios_base,
                    e.fecha_registro,
                    c.nombre_razon AS cliente_nombre,
                    c.documento AS cliente_documento,
                    te.nombre AS tipo_nombre,
                    m.nombre AS marca_nombre,
                    mo.nombre AS modelo_nombre
             FROM equipos e
             INNER JOIN clientes c ON c.id = e.cliente_id
             INNER JOIN tipos_equipo te ON te.id = e.tipo_equipo_id
             LEFT JOIN marcas m ON m.id = e.marca_id
             LEFT JOIN modelos mo ON mo.id = e.modelo_id';

        if ($search !== '') {
            $stmt = $db->prepare($baseSql .
                ' WHERE c.nombre_razon LIKE :term
                    OR c.documento LIKE :term
                    OR e.numero_serie LIKE :term
                    OR e.imei LIKE :term
               ORDER BY e.fecha_registro DESC
               LIMIT 100'
            );
            $stmt->execute(['term' => '%' . $search . '%']);
        } else {
            $stmt = $db->query($baseSql .
                ' ORDER BY e.fecha_registro DESC
                  LIMIT 100'
            );
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function findEquipmentById(PDO $db, int $equipoId): ?array
    {
        $stmt = $db->prepare(
            'SELECT id, cliente_id, tipo_equipo_id, marca_id, modelo_id, numero_serie, imei, color, descripcion, accesorios_base
             FROM equipos
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $equipoId]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $equipment === false ? null : $equipment;
    }
}
