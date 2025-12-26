<?php
/**
 * ตัวควบคุมการยืนยันตัวตน
 * 
 * จุดประสงค์: จัดการการลงทะเบียน, เข้าสู่ระบบ, ออกจากระบบ
 * ความปลอดภัย: การเข้ารหัสรหัสผ่าน, การจัดการเซสชัน
 * 
 * ความรับผิดชอบของตัวควบคุม:
 * - ตรวจสอบความถูกต้องของข้อมูลที่ส่งเข้ามา
 * - เรียกใช้โมเดล User สำหรับตรรกะทางธุรกิจ
 * - จัดการการตอบกลับ (เปลี่ยนเส้นทางหรือข้อความผิดพลาด)
 * 
 * ตรรกะทางธุรกิจอยู่ในโมเดล User!
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        // เริ่มเซสชันถ้ายังไม่ได้เริ่ม
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->userModel = new User();
    }

    /**
     * แสดงฟอร์มลงทะเบียน
     */
    public function showRegister(): void
    {
        $view = new View('auth/register');
        $view->layout('main')->show();
    }

    /**
     * จัดการการส่งฟอร์มลงทะเบียน
     */
    public function register(): void
    {
        // ตรวจสอบฟิลด์ที่จำเป็น
        $missing = $this->validateRequired(['username', 'email', 'password']);
        
        if (!empty($missing)) {
            echo "Missing fields: " . implode(', ', $missing);
            return;
        }

        // ทำความสะอาดข้อมูลนำเข้า
        $username = $this->sanitize($_POST['username']);
        $email = $this->sanitize($_POST['email']);
        $password = $_POST['password']; // ห้ามทำความสะอาดรหัสผ่าน!

        // เรียกใช้โมเดลเพื่อลงทะเบียนผู้ใช้
        $result = $this->userModel->register($username, $email, $password);

        if ($result['success']) {
            // เข้าสู่ระบบอัตโนมัติหลังจากลงทะเบียน
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $username;
            $this->redirect('/products');
        } else {
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/register'>Try again</a></p>";
        }
    }

    /**
     * แสดงฟอร์มเข้าสู่ระบบ
     */
    public function showLogin(): void
    {
        $view = new View('auth/login');
        $view->layout('main')->show();
    }

    /**
     * จัดการการส่งฟอร์มเข้าสู่ระบบ
     */
    public function login(): void
    {
        // ตรวจสอบฟิลด์ที่จำเป็น
        $missing = $this->validateRequired(['username', 'password']);
        
        if (!empty($missing)) {
            echo "Missing fields: " . implode(', ', $missing);
            return;
        }

        $username = $this->sanitize($_POST['username']);
        $password = $_POST['password'];

        // เรียกใช้โมเดลเพื่อยืนยันตัวตน
        $result = $this->userModel->login($username, $password);

        if ($result['success']) {
            $this->redirect('/products');
        } else {
            echo "<p>Error: {$result['message']}</p>";
            echo "<p><a href='/login'>Try again</a></p>";
        }
    }

    /**
     * จัดการการออกจากระบบ
     */
    public function logout(): void
    {
        $this->userModel->logout();
        $this->redirect('/');
    }
}
