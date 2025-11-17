<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    public function index(): void
    {
        $this->authorize(['ADMIN']);

        $usuarioModel = new Usuario();
        $formErrors = Session::get('form_errors', []);

        $this->view('usuarios/index', [
            'usuarios' => $usuarioModel->getAll(),
            'errors' => $formErrors['usuarios'] ?? [],
        ]);

        Session::remove('form_errors');
    }

    public function store(): void
    {
        $this->authorize(['ADMIN']);

        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $rol = strtoupper(trim((string)($_POST['rol'] ?? 'OPERADOR')));
        $password = $_POST['password'] ?? '';

        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Ingresa un correo electrónico válido.';
        }
        if (!in_array($rol, ['ADMIN', 'OPERADOR', 'TECNICO'], true)) {
            $errores['rol'] = 'Rol inválido.';
        }
        if (strlen($password) < 6) {
            $errores['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        $usuarioModel = new Usuario();
        if ($email !== '' && $usuarioModel->findByEmail($email)) {
            $errores['email'] = 'Ya existe un usuario con ese correo.';
        }

        if (!empty($errores)) {
            Session::set('form_errors', ['usuarios' => $errores]);
            $this->redirect('/usuarios');
        }

        $usuarioModel->create([
            'nombre' => $nombre,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => $rol,
        ]);

        $this->flash('success', 'Usuario creado correctamente.');
        $this->redirect('/usuarios');
    }

    public function toggle(int $id): void
    {
        $this->authorize(['ADMIN']);

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findById($id);
        if (!$usuario) {
            $this->flash('danger', 'El usuario no existe.');
            $this->redirect('/usuarios');
        }

        $usuarioModel->update($id, ['activo' => $usuario['activo'] ? 0 : 1]);
        $this->flash('info', 'Estado del usuario actualizado.');
        $this->redirect('/usuarios');
    }

    public function reset(int $id): void
    {
        $this->authorize(['ADMIN']);

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findById($id);
        if (!$usuario) {
            $this->flash('danger', 'El usuario no existe.');
            $this->redirect('/usuarios');
        }

        $nuevoPassword = bin2hex(random_bytes(4));
        $usuarioModel->update($id, ['password_hash' => password_hash($nuevoPassword, PASSWORD_DEFAULT)]);

        $this->flash('info', 'Contraseña reiniciada. Nueva clave temporal: ' . $nuevoPassword);
        $this->redirect('/usuarios');
    }
}

