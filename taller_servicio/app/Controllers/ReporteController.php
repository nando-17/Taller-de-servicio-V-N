<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

class ReporteController extends Controller
{
    public function index(): void
    {
        $this->authorize(['ADMIN', 'OPERADOR']);

        $db = Database::connection();

        // 1) Órdenes por estado — incluir est.orden en SELECT y GROUP BY
        $ordenesPorEstado = $db->query(
            "SELECT 
                 est.id,
                 est.nombre AS estado,
                 est.orden,
                 COUNT(*) AS total
             FROM ordenes_servicio os
             JOIN estados_orden est ON os.estado_id = est.id
             GROUP BY est.id, est.nombre, est.orden
             ORDER BY est.orden ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // 2) Ventas por mes — agrupa por la MISMA expresión usada en el SELECT (seguro con ONLY_FULL_GROUP_BY)
        $ventasPorMes = $db->query(
            "SELECT 
                 DATE_FORMAT(fecha_emision, '%Y-%m') AS periodo,
                 SUM(total) AS total
             FROM facturas
             WHERE estado <> 'ANULADA'
             GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
             ORDER BY periodo DESC
             LIMIT 6"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // 3) Productividad por técnico — agrupa por ID (evita homónimos) y muestra alias
        $productividad = $db->query(
            "SELECT 
                 t.id AS tecnico_id,
                 TRIM(CONCAT(COALESCE(t.nombres, ''), ' ', COALESCE(t.apellidos, ''))) AS tecnico,
                 COUNT(*) AS total
             FROM ordenes_servicio os
             JOIN tecnicos t ON os.tecnico_asignado_id = t.id
             GROUP BY t.id, tecnico
             ORDER BY total DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // 4) Stock crítico — sin GROUP BY, OK con ONLY_FULL_GROUP_BY
        $stockCritico = $db->query(
            "SELECT 
                 r.id,
                 r.nombre,
                 COALESCE(re.cantidad, 0) AS stock,
                 r.stock_minimo
             FROM repuestos r
             LEFT JOIN repuesto_existencias re ON r.id = re.repuesto_id
             WHERE r.activo = 1 
               AND COALESCE(re.cantidad, 0) < r.stock_minimo
             ORDER BY r.nombre ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('reportes/index', [
            'ordenesPorEstado' => $ordenesPorEstado,
            'ventasPorMes'     => $ventasPorMes,
            'productividad'    => $productividad,
            'stockCritico'     => $stockCritico,
        ]);
    }
}
