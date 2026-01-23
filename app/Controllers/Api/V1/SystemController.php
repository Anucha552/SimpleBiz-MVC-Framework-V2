<?php

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\Response;

/**
 * SystemController (API v1)
 *
 * Endpoints that are safe to expose publicly for monitoring.
 */
class SystemController extends Controller
{
    /**
     * GET /api/health
     */
    public function health(): Response
    {
        return Response::apiSuccess([
            'status' => 'ok',
            'timestamp' => date('c'),
        ], 'OK');
    }

    /**
     * GET /api/v1/ping
     */
    public function ping(): Response
    {
        return Response::apiSuccess([
            'pong' => true,
            'timestamp' => date('c'),
        ], 'PONG');
    }
}
