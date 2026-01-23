<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use Modules\Auth\Repositories\UserRepository;

final class AuthApiController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function me(): Response
    {
        Session::start();

        $userId = Session::get('user_id');
        if (!is_int($userId) && !is_numeric($userId)) {
            return Response::apiError('Authentication required', [], 401);
        }

        $id = (int)$userId;

        try {
            $user = $this->users->findById($id);
        } catch (\Throwable $e) {
            return Response::apiError('Auth database not initialized', [], 500);
        }

        if (!$user) {
            return Response::apiError('User not found', [], 404);
        }

        unset($user['password'], $user['remember_token']);

        return Response::apiSuccess($user, 'OK', [
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? null,
        ]);
    }
}
