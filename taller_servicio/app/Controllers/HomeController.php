<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

class HomeController extends Controller
{

    public function index(): void
    {
        $this->authorize();

        $user = [
            'name' => Session::get('user_name'),
            'role' => Session::getUserRole(),
            'email' => Session::get('user_email'),
        ];

        $role = $user['role'] ?? '';

        $this->view('home/index', [
            'title' => 'Panel Principal del Taller',
            'user' => $user,
            'sessionMeta' => Session::get('session_meta', []),
            'loginAttempts' => Session::get('login_attempts', []),
            'modules' => $this->getModulesForRole($role),
            'quickActions' => $this->getQuickActionsForRole($role),
        ]);
    }

    /**
     * Devuelve los módulos visibles por rol.
     */
    private function getModulesForRole(?string $role): array
    {
        $normalizedRole = strtoupper((string) $role);

        $catalog = [
            'ADMIN' => [
                [
                    'label' => 'Clientes',
                    'description' => 'Registra y consulta la información de tus clientes con historial de servicio.',
                    'icon' => 'fa-users',
                    'href' => '/clientes',
                ],
                [
                    'label' => 'Equipos',
                    'description' => 'Asocia equipos y dispositivos a cada cliente para dar seguimiento técnico.',
                    'icon' => 'fa-mobile-screen-button',
                    'href' => '/equipos',
                ],
                [
                    'label' => 'Órdenes de servicio',
                    'description' => 'Controla el ciclo de vida de cada orden, desde la recepción hasta la entrega.',
                    'icon' => 'fa-list-check',
                    'href' => '/ordenservicio',
                ],
                [
                    'label' => 'Agenda',
                    'description' => 'Programa citas de recepción, diagnóstico y entrega sin choques de horario.',
                    'icon' => 'fa-calendar-days',
                    'href' => '/citas',
                ],
                [
                    'label' => 'Inventario',
                    'description' => 'Gestiona existencias, ajustes y mínimos de los repuestos del taller.',
                    'icon' => 'fa-warehouse',
                    'href' => '/inventario',
                ],
                [
                    'label' => 'Facturación',
                    'description' => 'Emite comprobantes y haz seguimiento de los pagos vinculados a órdenes.',
                    'icon' => 'fa-file-invoice-dollar',
                    'href' => '/facturacion',
                ],
                [
                    'label' => 'Pagos',
                    'description' => 'Registra cobros, anula operaciones y descarga recibos en PDF.',
                    'icon' => 'fa-money-check-dollar',
                    'href' => '/pagos',
                ],
                [
                    'label' => 'Reportes',
                    'description' => 'Analiza métricas clave de productividad, ingresos y tiempos de entrega.',
                    'icon' => 'fa-chart-line',
                    'href' => '/reportes',
                ],
                [
                    'label' => 'Catálogos',
                    'description' => 'Mantén actualizados los servicios y repuestos autorizados por el negocio.',
                    'icon' => 'fa-tags',
                    'href' => '/catalogos',
                ],
                [
                    'label' => 'Usuarios',
                    'description' => 'Administra accesos, restablece contraseñas y define responsabilidades.',
                    'icon' => 'fa-user-gear',
                    'href' => '/usuarios',
                ],
            ],
            'OPERADOR' => [
                [
                    'label' => 'Clientes',
                    'description' => 'Registra y consulta la información de tus clientes con historial de servicio.',
                    'icon' => 'fa-users',
                    'href' => '/clientes',
                ],
                [
                    'label' => 'Equipos',
                    'description' => 'Asocia equipos y dispositivos a cada cliente para dar seguimiento técnico.',
                    'icon' => 'fa-mobile-screen-button',
                    'href' => '/equipos',
                ],
                [
                    'label' => 'Órdenes de servicio',
                    'description' => 'Controla el ciclo de vida de cada orden, desde la recepción hasta la entrega.',
                    'icon' => 'fa-list-check',
                    'href' => '/ordenservicio',
                ],
                [
                    'label' => 'Agenda',
                    'description' => 'Programa citas de recepción, diagnóstico y entrega sin choques de horario.',
                    'icon' => 'fa-calendar-days',
                    'href' => '/citas',
                ],
                [
                    'label' => 'Inventario',
                    'description' => 'Gestiona existencias, ajustes y mínimos de los repuestos del taller.',
                    'icon' => 'fa-warehouse',
                    'href' => '/inventario',
                ],
                [
                    'label' => 'Facturación',
                    'description' => 'Emite comprobantes y haz seguimiento de los pagos vinculados a órdenes.',
                    'icon' => 'fa-file-invoice-dollar',
                    'href' => '/facturacion',
                ],
                [
                    'label' => 'Pagos',
                    'description' => 'Registra cobros, anula operaciones y descarga recibos en PDF.',
                    'icon' => 'fa-money-check-dollar',
                    'href' => '/pagos',
                ],
                [
                    'label' => 'Reportes',
                    'description' => 'Analiza métricas clave de productividad, ingresos y tiempos de entrega.',
                    'icon' => 'fa-chart-line',
                    'href' => '/reportes',
                ],
            ],
            'TECNICO' => [
                [
                    'label' => 'Órdenes asignadas',
                    'description' => 'Consulta tus órdenes pendientes, registra diagnósticos y actualiza avances.',
                    'icon' => 'fa-clipboard-list',
                    'href' => '/ordenservicio',
                ],
                [
                    'label' => 'Diagnóstico',
                    'description' => 'Documenta hallazgos técnicos y costos estimados para aprobación del cliente.',
                    'icon' => 'fa-stethoscope',
                    'href' => '/ordenservicio',
                ],
                [
                    'label' => 'Historial de servicios',
                    'description' => 'Revisa intervenciones previas para acelerar nuevas reparaciones.',
                    'icon' => 'fa-clock-rotate-left',
                    'href' => '/ordenservicio',
                ],
            ],
        ];

        return $catalog[$normalizedRole] ?? [];
    }

    private function getQuickActionsForRole(?string $role): array
    {
        $normalizedRole = strtoupper((string) $role);

        $actions = [
            'ADMIN' => [
                [
                    'label' => 'Registrar cliente',
                    'href' => '/clientes',
                    'icon' => 'fa-users',
                ],
                [
                    'label' => 'Crear orden',
                    'href' => '/ordenservicio/crear',
                    'icon' => 'fa-list-check',
                ],
                [
                    'label' => 'Emitir comprobante',
                    'href' => '/facturacion',
                    'icon' => 'fa-file-invoice-dollar',
                ],
            ],
            'OPERADOR' => [
                [
                    'label' => 'Registrar cliente',
                    'href' => '/clientes',
                    'icon' => 'fa-users',
                ],
                [
                    'label' => 'Crear orden',
                    'href' => '/ordenservicio/crear',
                    'icon' => 'fa-list-check',
                ],
                [
                    'label' => 'Gestionar inventario',
                    'href' => '/inventario',
                    'icon' => 'fa-warehouse',
                ],
            ],
            'TECNICO' => [
                [
                    'label' => 'Ver órdenes asignadas',
                    'href' => '/ordenservicio',
                    'icon' => 'fa-clipboard-check',
                ],
                [
                    'label' => 'Registrar diagnóstico',
                    'href' => '/ordenservicio',
                    'icon' => 'fa-stethoscope',
                ],
            ],
        ];

        return $actions[$normalizedRole] ?? [];
    }
}
