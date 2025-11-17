<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDO;
use PDOException;

class CustomerController extends Controller
{
    public function form(): void
    {
        $this->requireAuth(['ADMIN', 'OPERADOR']);

        $errors = $_SESSION['form_errors']['customer'] ?? [];
        $old = $_SESSION['old']['customer'] ?? null;
        $search = trim($_GET['buscar'] ?? '');
        $editId = isset($_GET['editar']) ? (int)$_GET['editar'] : null;

        $customers = [];
        $customerToEdit = null;

        try {
            $db = Database::connection();

            $customers = $this->fetchCustomers($db, $search);

            if ($editId) {
                $customerToEdit = $this->findCustomerById($db, $editId);

                if ($customerToEdit === null) {
                    $this->flash('warning', 'El cliente que intentas editar no existe.');
                    $this->redirect('/clientes');
                }
            }
        } catch (PDOException) {
            $this->flash('danger', 'No fue posible cargar los clientes desde la base de datos.');
        }

        if ($old === null && $customerToEdit !== null) {
            $old = [
                'cliente_id' => (int) $customerToEdit['id'],
                'tipo' => (string) $customerToEdit['tipo'],
                'nombre_razon' => (string) $customerToEdit['nombre_razon'],
                'documento' => (string) ($customerToEdit['documento'] ?? ''),
                'email' => (string) ($customerToEdit['email'] ?? ''),
                'telefono' => (string) ($customerToEdit['telefono'] ?? ''),
                'direccion' => (string) ($customerToEdit['direccion'] ?? ''),
                'observaciones' => (string) ($customerToEdit['observaciones'] ?? ''),
            ];
        }

        $this->view('customers/form', [
            'title' => 'Gestión de Clientes',
            'errors' => $errors,
            'old' => $old ?? [],
            'customers' => $customers,
            'search' => $search,
            'editing' => $customerToEdit !== null,
        ]);

        unset($_SESSION['form_errors']['customer'], $_SESSION['old']['customer']);
    }

    public function save(): void
    {
        $this->requireAuth(['ADMIN', 'OPERADOR']);

        $customerId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : null;
        $tipo = strtoupper(trim($_POST['tipo'] ?? ''));
        $nombreRazon = trim($_POST['nombre_razon'] ?? '');
        $documento = trim($_POST['documento'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        $errors = $this->validateCustomer($tipo, $nombreRazon, $documento, $email, $telefono);

        $this->storeOldInput('customer', [
            'cliente_id' => $customerId,
            'tipo' => $tipo,
            'nombre_razon' => $nombreRazon,
            'documento' => $documento,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'observaciones' => $observaciones,
        ]);

        if (!empty($errors)) {
            $this->storeErrors('customer', $errors);
            $this->flash('danger', 'Revise la información del cliente.');
            $this->redirect('/clientes');
        }

        try {
            $db = Database::connection();

            if ($documento !== '') {
                $duplicateStmt = $db->prepare('SELECT id FROM clientes WHERE documento = :documento AND id <> :id LIMIT 1');
                $duplicateStmt->execute([
                    'documento' => $documento,
                    'id' => $customerId ?? 0,
                ]);

                if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->storeErrors('customer', ['documento' => 'El documento ya está registrado en otro cliente.']);
                    $this->flash('warning', 'Documento duplicado.');
                    $this->redirect('/clientes');
                    return;
                }
            }

            if ($customerId) {
                $existing = $this->findCustomerById($db, $customerId);

                if ($existing === null) {
                    $this->flash('warning', 'El cliente que intentas actualizar no existe.');
                    $this->redirect('/clientes');
                }

                $stmt = $db->prepare(
                    'UPDATE clientes SET tipo = :tipo, nombre_razon = :nombre_razon, documento = :documento, email = :email, telefono = :telefono, direccion = :direccion, observaciones = :observaciones WHERE id = :id'
                );
                $stmt->execute([
                    'tipo' => $tipo,
                    'nombre_razon' => $nombreRazon,
                    'documento' => $documento !== '' ? $documento : null,
                    'email' => $email !== '' ? $email : null,
                    'telefono' => $telefono !== '' ? $telefono : null,
                    'direccion' => $direccion !== '' ? $direccion : null,
                    'observaciones' => $observaciones !== '' ? $observaciones : null,
                    'id' => $customerId,
                ]);
                $message = 'Cliente actualizado correctamente.';
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO clientes (tipo, nombre_razon, documento, email, telefono, direccion, observaciones) VALUES (:tipo, :nombre_razon, :documento, :email, :telefono, :direccion, :observaciones)'
                );
                $stmt->execute([
                    'tipo' => $tipo,
                    'nombre_razon' => $nombreRazon,
                    'documento' => $documento !== '' ? $documento : null,
                    'email' => $email !== '' ? $email : null,
                    'telefono' => $telefono !== '' ? $telefono : null,
                    'direccion' => $direccion !== '' ? $direccion : null,
                    'observaciones' => $observaciones !== '' ? $observaciones : null,
                ]);
                $message = 'Cliente registrado correctamente.';
            }
        } catch (PDOException $exception) {
            $this->storeErrors('customer', ['general' => 'No fue posible guardar la información del cliente.']);
            $this->flash('danger', 'Error al guardar el cliente.');
            $this->redirect('/clientes');
            return;
        }

        unset($_SESSION['old']['customer']);
        $this->flash('success', $message);
        $this->redirect('/clientes');
    }

    public function delete(): void
    {
        $this->requireAuth(['ADMIN', 'OPERADOR']);

        $customerId = isset($_POST['cliente_id']) ? (int) $_POST['cliente_id'] : 0;

        if ($customerId <= 0) {
            $this->flash('warning', 'Selecciona un cliente válido para eliminar.');
            $this->redirect('/clientes');
        }

        try {
            $db = Database::connection();

            $customer = $this->findCustomerById($db, $customerId);

            if ($customer === null) {
                $this->flash('warning', 'El cliente indicado no existe.');
                $this->redirect('/clientes');
            }

            $usageStmt = $db->prepare('SELECT COUNT(1) AS total FROM equipos WHERE cliente_id = :id');
            $usageStmt->execute(['id' => $customerId]);
            $usage = $usageStmt->fetch(PDO::FETCH_ASSOC);

            if ((int) ($usage['total'] ?? 0) > 0) {
                $this->flash('warning', 'No puedes eliminar el cliente porque tiene equipos asociados.');
                $this->redirect('/clientes');
            }

            $deleteStmt = $db->prepare('DELETE FROM clientes WHERE id = :id');
            $deleteStmt->execute(['id' => $customerId]);

            if ($deleteStmt->rowCount() === 0) {
                $this->flash('warning', 'No se eliminó el cliente.');
                $this->redirect('/clientes');
            }
        } catch (PDOException) {
            $this->flash('danger', 'No fue posible eliminar el cliente.');
            $this->redirect('/clientes');
        }

        $this->flash('success', 'Cliente eliminado correctamente.');
        $this->redirect('/clientes');
    }

    private function validateCustomer(string $tipo, string $nombreRazon, string $documento, string $email, string $telefono): array
    {
        $errors = [];

        if (!in_array($tipo, ['NATURAL', 'JURIDICA'], true)) {
            $errors['tipo'] = 'Selecciona el tipo de cliente.';
        }

        if ($nombreRazon === '' || mb_strlen($nombreRazon) < 3) {
            $errors['nombre_razon'] = 'El nombre o razón social es obligatorio.';
        }

        if ($documento !== '' && strlen($documento) < 6) {
            $errors['documento'] = 'El documento debe tener al menos 6 caracteres.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo electrónico válido.';
        }

        if ($telefono !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $telefono)) {
            $errors['telefono'] = 'Ingresa un teléfono válido.';
        }

        return $errors;
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
    private function fetchCustomers(PDO $db, string $search): array
    {
        if ($search !== '') {
            $stmt = $db->prepare(
                'SELECT id, tipo, nombre_razon, documento, email, telefono, direccion, observaciones, created_at
                 FROM clientes
                 WHERE documento LIKE :term OR nombre_razon LIKE :term
                 ORDER BY nombre_razon ASC
                 LIMIT 100'
            );
            $stmt->execute(['term' => '%' . $search . '%']);
        } else {
            $stmt = $db->query(
                'SELECT id, tipo, nombre_razon, documento, email, telefono, direccion, observaciones, created_at
                 FROM clientes
                 ORDER BY created_at DESC
                 LIMIT 100'
            );
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function findCustomerById(PDO $db, int $customerId): ?array
    {
        $stmt = $db->prepare(
            'SELECT id, tipo, nombre_razon, documento, email, telefono, direccion, observaciones
             FROM clientes
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $customer === false ? null : $customer;
    }
}
