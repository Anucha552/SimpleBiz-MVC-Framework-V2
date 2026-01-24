<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;

/**
 * ApiController (API v1)
 *
 * Endpoints that are safe to expose publicly for monitoring.
 */
class ApiController extends Controller
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
     * GET /api/ping
     */
    public function ping(): Response
    {
        return Response::apiSuccess([
            'pong' => true,
            'timestamp' => date('c'),
        ], 'PONG');
    }
}
