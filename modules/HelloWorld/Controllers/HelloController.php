<?php

namespace Modules\HelloWorld\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HelloController
{
    public function index(Request $request): Response
    {
        $id = $request->getRequestId();

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Hello Module</title></head><body>'
            . '<h1>Hello from modules/HelloWorld</h1>'
            . '<p>Request ID: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Try <a href="/api/hello">/api/hello</a></p>'
            . '</body></html>';

        return Response::html($html);
    }

    public function api(Request $request): Response
    {
        return Response::apiSuccess([
            'module' => 'HelloWorld',
            'request_id' => $request->getRequestId(),
        ], 'OK');
    }
}
