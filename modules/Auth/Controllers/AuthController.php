<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use Modules\Auth\Repositories\UserRepository;

final class AuthController extends Controller
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    public function showLogin(): Response
    {
        Session::start();
        return $this->responseView('auth/login', [], 'main');
    }

    public function login(): Response
    {
        Session::start();

        $data = [
            'login' => (string)($_POST['login'] ?? ''),
            'password' => (string)($_POST['password'] ?? ''),
        ];

        $validator = new Validator($data, [
            'login' => 'required|min:3|max:100',
            'password' => 'required|min:6|max:255',
        ]);

        if ($validator->fails()) {
            Session::flash('validation_errors', $validator->errors());
            Session::flashInput(['login' => $data['login']]);
            return Response::redirect('/login');
        }

        try {
            $user = str_contains($data['login'], '@')
                ? $this->users->findByEmail($data['login'])
                : $this->users->findByUsername($data['login']);
        } catch (\Throwable $e) {
            Session::flash('error', 'ยังไม่ได้ตั้งค่าฐานข้อมูลสำหรับ Auth (กรุณารัน migrations)');
            return Response::redirect('/login');
        }

        if (!$user || !isset($user['password']) || !password_verify($data['password'], (string)$user['password'])) {
            Session::flash('validation_errors', [
                'login' => ['ข้อมูลเข้าสู่ระบบไม่ถูกต้อง'],
            ]);
            Session::flashInput(['login' => $data['login']]);
            return Response::redirect('/login');
        }

        if (($user['status'] ?? 'active') !== 'active') {
            Session::flash('error', 'บัญชีนี้ไม่สามารถเข้าสู่ระบบได้');
            return Response::redirect('/login');
        }

        Session::regenerate(true);
        Session::set('user_id', (int)$user['id']);
        Session::set('user_username', (string)($user['username'] ?? ''));
        Session::set('user_email', (string)($user['email'] ?? ''));

        $intended = Session::get('auth.intended');
        if (is_string($intended) && $intended !== '') {
            Session::remove('auth.intended');
            return Response::redirect($intended);
        }

        Session::flash('success', 'เข้าสู่ระบบสำเร็จ');
        return Response::redirect('/');
    }

    public function showRegister(): Response
    {
        Session::start();
        return $this->responseView('auth/register', [], 'main');
    }

    public function register(): Response
    {
        Session::start();

        $data = [
            'username' => (string)($_POST['username'] ?? ''),
            'email' => (string)($_POST['email'] ?? ''),
            'password' => (string)($_POST['password'] ?? ''),
            'password_confirm' => (string)($_POST['password_confirm'] ?? ''),
        ];

        $validator = new Validator($data, [
            'username' => 'required|alphanumeric|min:3|max:50',
            'email' => 'required|email|max:100',
            'password' => 'required|min:6|max:255',
            'password_confirm' => 'required|match:password',
        ]);

        if ($validator->fails()) {
            Session::flash('validation_errors', $validator->errors());
            Session::flashInput([
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            return Response::redirect('/register');
        }

        try {
            if ($this->users->findByUsername($data['username'])) {
                Session::flash('validation_errors', ['username' => ['ชื่อผู้ใช้นี้ถูกใช้ไปแล้ว']]);
                Session::flashInput(['username' => $data['username'], 'email' => $data['email']]);
                return Response::redirect('/register');
            }

            if ($this->users->findByEmail($data['email'])) {
                Session::flash('validation_errors', ['email' => ['อีเมลนี้ถูกใช้ไปแล้ว']]);
                Session::flashInput(['username' => $data['username'], 'email' => $data['email']]);
                return Response::redirect('/register');
            }

            $userId = $this->users->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'status' => 'active',
            ]);
        } catch (\Throwable $e) {
            Session::flash('error', 'ยังไม่ได้ตั้งค่าฐานข้อมูลสำหรับ Auth (กรุณารัน migrations)');
            return Response::redirect('/register');
        }

        Session::regenerate(true);
        Session::set('user_id', $userId);
        Session::set('user_username', $data['username']);
        Session::set('user_email', $data['email']);

        Session::flash('success', 'สมัครสมาชิกสำเร็จ');
        return Response::redirect('/');
    }

    public function logout(): Response
    {
        Session::start();
        Session::remove('user_id');
        Session::remove('user_username');
        Session::remove('user_email');
        Session::remove('auth.intended');
        Session::regenerate(true);

        Session::flash('success', 'ออกจากระบบแล้ว');
        return Response::redirect('/');
    }
}
