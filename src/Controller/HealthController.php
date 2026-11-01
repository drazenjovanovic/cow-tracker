<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    public function __construct(private readonly Database $db) {}

    public function check(Request $request): Response
    {
        $dbUp = $this->db->ping();

        return Response::json([
            'ok'   => $dbUp,
            'db'   => $dbUp ? 'up' : 'down',
            'time' => date('Y-m-d H:i:s'),
        ], $dbUp ? 200 : 503);
    }
}
