<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use PDO;
use PDOException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    public function showLoginForm(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/');
        }

        $formErrors = Session::get('form_errors', []);
        $oldInput = Session::get('old', []);

        $this->view('auth/login', [
            'title' => 'Iniciar sesión',
            'errors' => $formErrors['login'] ?? [],
            'old' => $oldInput['login'] ?? [],
        ]);

        Session::remove('form_errors');
        Session::remove('old');
    }

    public function authenticate(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un correo válido.';
        }

        if ($password === '') {
            $errors['password'] = 'Ingresa tu contraseña.';
        }

        $this->storeOldInput('login', ['email' => $email]);

        if ($this->isBlocked($email)) {
            $errors['general'] = 'Tu cuenta ha sido bloqueada temporalmente por múltiples intentos fallidos.';
        }

        if (!empty($errors)) {
            $this->storeErrors('login', $errors);
            $this->redirect('/login');
        }

        try {
            $db = Database::connection();
            $stmt = $db->prepare('SELECT id, nombre, password_hash, rol, activo FROM usuarios WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            $this->storeErrors('login', ['general' => 'No es posible conectarse al servicio de autenticación.']);
            $this->flash('danger', 'Error al validar tus credenciales.');
            $this->redirect('/login');
            return;
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->registerFailedAttempt($email, $user['id'] ?? null);
            $this->storeErrors('login', ['general' => 'Credenciales inválidas.']);
            $this->flash('danger', 'Correo o contraseña incorrectos.');
            $this->redirect('/login');
            return;
        }

        if ((int)$user['activo'] === 0) {
            $this->registerFailedAttempt($email, (int)$user['id']);
            $this->storeErrors('login', ['general' => 'Tu usuario está inactivo. Contacta al administrador.']);
            $this->flash('warning', 'Usuario inactivo.');
            $this->redirect('/login');
            return;
        }

        $this->clearAttempts($email);
        $normalizedRole = strtoupper(trim((string) $user['rol']));
        $this->startSessionForUser((int)$user['id'], $user['nombre'], $normalizedRole, $email);
        $this->logAudit((int)$user['id'], 'login_exitoso', 'success');
        $this->flash('success', 'Bienvenido de nuevo, ' . htmlspecialchars($user['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!');
        $this->redirect($this->resolveDashboard($normalizedRole));
    }

    public function logout(): void
    {
        if (Session::isLoggedIn()) {
            $userId = (int) Session::get('user_id', 0);
            $this->logAudit($userId, 'logout', 'success');
        }

        Session::destroy();

        Session::start();
        Session::set('flash', [
            'type' => 'info',
            'message' => 'Sesión finalizada correctamente.',
        ]);

        $this->redirect('/login');
    }

    private function resolveDashboard(string $role): string
    {
        return match (strtoupper($role)) {
            'ADMIN' => '/clientes',
            'OPERADOR' => '/clientes',
            'TECNICO' => '/equipos',
            default => '/',
        };
    }

    private function isBlocked(string $email): bool
    {
        $attemptsData = Session::get('login_attempts', []);
        $attempts = $attemptsData[$email]['count'] ?? 0;
        return $attempts >= self::MAX_LOGIN_ATTEMPTS;
    }

    private function registerFailedAttempt(string $email, ?int $userId): void
    {
        $loginAttempts = Session::get('login_attempts', []);
        $attempts = $loginAttempts[$email]['count'] ?? 0;
        $loginAttempts[$email] = [
            'count' => $attempts + 1,
            'last_attempt' => time(),
        ];
        Session::set('login_attempts', $loginAttempts);

        $this->logAudit($userId, 'login_fallido', 'error');
    }

    private function clearAttempts(string $email): void
    {
        $loginAttempts = Session::get('login_attempts', []);
        unset($loginAttempts[$email]);
        Session::set('login_attempts', $loginAttempts);
    }

    private function storeErrors(string $form, array $errors): void
    {
        Session::set('form_errors', [$form => $errors]);
    }

    private function storeOldInput(string $form, array $data): void
    {
        Session::set('old', [$form => $data]);
    }

    private function startSessionForUser(int $id, string $name, string $role, string $email): void
    {
        $normalizedRole = strtoupper(trim($role));

        Session::set('user_id', $id);
        Session::set('user_name', $name);
        Session::set('user_role', $normalizedRole);
        Session::set('user_email', $email);
        Session::set('session_meta', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function logAudit(?int $userId, string $action, string $status): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'INSERT INTO registros_auditoria (usuario_id, accion, estado, ip, user_agent, creado_en) ' .
                'VALUES (:usuario_id, :accion, :estado, :ip, :user_agent, NOW())'
            );
            $stmt->execute([
                'usuario_id' => $userId,
                'accion' => $action,
                'estado' => $status,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            ]);
        } catch (PDOException) {
            // Se omite el error de auditoría para no afectar la experiencia del usuario.
        }
    }
}
