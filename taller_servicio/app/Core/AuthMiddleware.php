<?php

declare(strict_types=1);

namespace App\Core;

class AuthMiddleware
{
    public function handle(array $roles = []): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            return;
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
            // In a real application, you might redirect to a '403 Forbidden' page.
            // For simplicity, we redirect to the home page.
            $this->redirect('/');
        }
    }

    private function redirect(string $url): void
    {
        header("Location: {$url}");
        exit();
    }
}
