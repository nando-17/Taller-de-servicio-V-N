<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Session;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../views/' . $view . '.php';
    }

    /**
     * Asegura que el usuario haya iniciado sesión y, opcionalmente,
     * valida que pertenezca a uno de los roles indicados.
     */
    protected function requireAuth(array $roles = []): void
    {
        $this->authorize($roles);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        Session::set('flash', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    protected function authorize(array $roles = []): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
        }

        if (empty($roles)) {
            return;
        }

        $normalizedRoles = array_map(
            static fn($role) => strtoupper(trim((string) $role)),
            $roles
        );

        $userRole = Session::getUserRole();
        if ($userRole === null || !in_array($userRole, $normalizedRoles, true)) {
            $this->redirect('/');
        }
    }
}
