<?php
/**
 * คลาสตัวควบคุมพื้นฐาน หรือ Controller Base จาก core
 * 
 * จุดประสงค์: คลาสแม่สำหรับตัวควบคุมทั้งหมด ให้ฟังก์ชันการทำงานทั่วไป
 * ปรัชญา: รักษาตัวควบคุมให้บาง - มอบหมายตรรกะทางธุรกิจให้โมเดล
 * Controller ควรใช้กับอะไร: เมื่อคุณสร้างตัวควบคุมใหม่สำหรับจัดการคำขอ HTTP
 * 
 * ความรับผิดชอบของตัวควบคุม:
 * - ตรวจสอบความถูกต้องของคำขอที่เข้ามา
 * - เรียกเมธอดของโมเดลสำหรับตรรกะทางธุรกิจ
 * - ส่งข้อมูลไปยังวิวหรือคืนค่าการตอบกลับ
 * - จัดการการตอบกลับ HTTP (การเปลี่ยนเส้นทาง, รหัสสถานะ)
 * 
 * ตัวควบคุมไม่ควร:
 * - มีตรรกะทางธุรกิจที่ซับซ้อน
 * - จัดการฐานข้อมูลโดยตรง
 * - ดำเนินการคำนวณหรือประมวลผลข้อมูล
 * 
 * ตรรกะทางธุรกิจทั้งหมดอยู่ในคลาสโมเดล!
 */

namespace App\Core;

use App\Helpers\UrlHelper;
use App\Helpers\FormHelper;
use App\Helpers\ArrayHelper;
use App\Core\View;
use App\Core\Response;
use App\Core\Request;
use App\Core\Auth;

class Controller
{

    /**
     * แสดงผลวิวพร้อมข้อมูล
     * จุดประสงค์: แสดงผลวิว HTML โดยส่งข้อมูลไปยังวิวโดยไม่ต้องคืนค่า Response
     * view() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลหน้าวิวพร้อมข้อมูลในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->view('home', ['name' => 'John']);
     * ```
     * 
     * @param string $view กำหนดชื่อวิวที่จะโหลด
     * @param array $data กำหนดข้อมูลที่จะส่งไปยังวิว
     * @return void ไม่คืนค่าอะไร
     */
    protected function view(string $view, array $data = []): void
    {
        // ใช้ View engine เพื่อรองรับ layouts/sections
        (new View($view, $data))->show();
    }

    /**
     * แสดงผลวิวพร้อมข้อมูลและคืนค่า Response พร้อมกำหนด Cache
     * จุดประสงค์: แสดงผลวิว HTML โดยส่งข้อมูลไปยังวิวและคืนค่า Response
     * responseView() ควรใช้กับอะไร: เมื่อคุณต้องการแสดงผลหน้าวิวพร้อมข้อมูลและคืนค่า Response ในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->responseView('home', ['name' => 'John'], 'main_layout', 200);
     * ```
     *
     * @param string $view กำหนดชื่อวิวที่จะโหลด
     * @param array $data กำหนดข้อมูลที่จะส่งไปยังวิว
     * @param string|null $layout กำหนดชื่อเลย์เอาต์ (ถ้ามี)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 200, 404
     * @param int|null $cacheSeconds กำหนดเวลาการแคชในหน่วยวินาที (ถ้ามี)
     * @return Response คืนค่า Response HTML
     */
    protected function responseView(string $view, array $data = [], ?string $layout = null, int $statusCode = 200, ?int $cacheSeconds = null): Response {
        $engine = new View($view, $data);

        if ($layout) {
            $engine->layout($layout);
        }

        if ($cacheSeconds !== null) {
            $engine->cache($cacheSeconds);
        }

        return Response::html($engine->render(), $statusCode);
    }

    /**
     * เปลี่ยนเส้นทางไปยัง URL อื่น
     * จุดประสงค์: สร้างการตอบกลับ HTTP redirect
     * redirect() ควรใช้กับอะไร: เมื่อคุณต้องการเปลี่ยนเส้นทางผู้ใช้ไปยัง URL อื่นจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->redirect('/home', 301);
     * ```
     * 
     * @param string $url กำหนด URL ที่จะเปลี่ยนเส้นทางไป
     * @param int $statusCode กำหนดรหัสสถานะ HTTP เช่น 302, 301
     * @return Response คืนค่า Response redirect
     */
    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    /**
     * สร้าง Response แบบ JSON (ไม่ exit) เพื่อใช้กับ Router ที่รองรับ return Response
     * จุดประสงค์: สร้างการตอบกลับ JSON สำหรับ API
     * responseJson() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างการตอบกลับ JSON จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->responseJson(true, ['id' => 1, 'name' => 'John'], 'User retrieved successfully', [], 200);
     * ```
     *
     * @param bool $success กำหนดสถานะความสำเร็จของการตอบกลับ
     * @param mixed $data กำหนดข้อมูลที่จะส่งกลับ (ถ้ามี)
     * @param string $message กำหนดข้อความเพิ่มเติม (ไม่บังคับ)
     * @param array $errors กำหนดอาร์เรย์ข้อผิดพลาด (ไม่บังคับ)
     * @param int $statusCode กำหนดรหัสสถานะ HTTP
     * @return Response คืนค่า Response JSON
     */
    protected function responseJson(bool $success, $data = null, string $message = '', array $errors = [], int $statusCode = 200): Response
    {
        if ($success) {
            return Response::apiSuccess($data, $message !== '' ? $message : 'Success', [], $statusCode);
        }

        return Response::apiError($message !== '' ? $message : 'Error', $errors, $statusCode);
    }


    /**
     * ดึง ID ของผู้ใช้ที่ยืนยันตัวตนปัจจุบัน
     * จุดประสงค์: รับ ID ของผู้ใช้ที่ยืนยันตัวตน
     * getUserId() ควรใช้กับอะไร: เมื่อคุณต้องการรับ ID ของผู้ใช้ที่ยืนยันตัวตนในระบบ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $userId = $this->getUserId();
     * ```
     * 
     * @return int|null คืนค่า ID ผู้ใช้หรือ null ถ้าไม่ได้ยืนยันตัวตน
     */
    protected function getUserId(): ?int
    {
        // Use Auth to obtain the current authenticated user id
        return Auth::id();
    }

    /**
     * ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * จุดประสงค์: ตรวจสอบสถานะการยืนยันตัวตนของผู้ใช้
     * isAuthenticated() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าผู้ใช้ได้ยืนยันตัวตนแล้วหรือไม่
     * ตัวอย่างการใช้งานโดยรวม:
     * ```php
     * // ตรวจสอบว่าผู้ใช้ยืนยันตัวตนหรือไม่
     * if ($this->isAuthenticated()) {
     *    // ผู้ใช้ยืนยันตัวตนแล้ว
     * } else {
     *    // ผู้ใช้ไม่ได้ยืนยันตัวตน
     * }
     * ```
     * 
     * @return bool คืนค่า true หากผู้ใช้ยืนยันตัวตน, false หากไม่ใช่
     */
    protected function isAuthenticated(): bool
    {
        // Delegate authentication check to Auth
        return Auth::check();
    }

    /**
     * ย่อการเรียก input จาก Request
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูล input จากคำขอได้อย่างสะดวก
     * input() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากคำขอ เช่น พารามิเตอร์หรือข้อมูลฟอร์ม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $username = $this->input('username');
     * ```
     * 
     * @param string|null $key กำหนดชื่อพารามิเตอร์ที่ต้องการดึง หรือ null เพื่อดึงทั้งหมด
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อพารามิเตอร์ไม่ถูกส่งมา
     * @return mixed คืนค่าพารามิเตอร์ที่ดึงมา หรือค่าดีฟอลต์ถ้าไม่ถูกส่งมา
     */
    protected function input(?string $key = null, $default = null)
    {
        return (new Request())->input($key, $default);
    }

    /**
     * ย่อการเรียก only จาก Request
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลคำขอเฉพาะบางพารามิเตอร์ได้อย่างสะดวก
     * only() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากคำขอเฉพาะพารามิเตอร์ที่ระบุ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $this->only(['username', 'email']);
     * ```
     * 
     * @param array $keys กำหนดอาร์เรย์ของชื่อพารามิเตอร์ที่ต้องการดึง
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ที่ดึงมา
     */
    protected function only(array $keys): array
    {
        return (new Request())->only($keys);
    }

    /**
     * ย่อการเรียก all จาก Request
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลคำขอทั้งหมดได้อย่างสะดวก
     * all() ควรใช้กับอะไร: เมื่อคุณต้องการดึงค่าจากคำขอทั้งหมด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $data = $this->all();
     * ```
     * 
     * @return array คืนค่าอาร์เรย์ของพารามิเตอร์ทั้งหมด
     */
    protected function all(): array
    {
        return (new Request())->all();
    }

    /**
     * Redirect กลับไปหน้าที่แล้ว (wrapper)
     * จุดประสงค์: ให้สามารถเปลี่ยนเส้นทางกลับไปยังหน้าที่แล้วได้อย่างสะดวก
     * back() ควรใช้กับอะไร: เมื่อคุณต้องการเปลี่ยนเส้นทางกลับไปยังหน้าที่แล้วจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->back();
     * ```
     * 
     * @param string|null $default กำหนด URL ดีฟอลต์เมื่อไม่สามารถย้อนกลับได้
     * @return Response คืนค่า Response redirect กลับไปยังหน้าที่แล้ว หรือ URL ดีฟอลต์ถ้าไม่สามารถย้อนกลับได้
     */
    protected function back(?string $default = null): Response
    {
        return UrlHelper::back($default);
    }

    /**
     * ตั้ง flash message (wrapper)
     * จุดประสงค์: ให้สามารถตั้งค่า flash message เพื่อใช้ในคำขอถัดไปได้อย่างสะดวก
     * flash() ควรใช้กับอะไร: เมื่อคุณต้องการตั้งค่า flash message เพื่อใช้ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->flash('success', 'Your profile has been updated successfully.');
     * ```
     * 
     * @param string $key กำหนดชื่อของ flash message
     * @param mixed $value กำหนดค่าของ flash message
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะตั้งค่า flash message ใน session เพื่อใช้ในคำขอถัดไป
     */
    protected function flash(string $key, $value): void
    {
        // Session::flash จะจัดการ start() เอง
        Session::flash($key, $value);
    }


    /**
     * ตรวจสอบสิทธิ์อย่างง่าย: ถ้าไม่ผ่าน คืน Response redirect ตามที่ระบุ
     * จุดประสงค์: ให้สามารถตรวจสอบสิทธิ์และเปลี่ยนเส้นทางผู้ใช้ได้อย่างสะดวก
     * authorize() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบสิทธิ์และเปลี่ยนเส้นทางผู้ใช้จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->authorize(Auth::check(), '/login');
     * ```
     * 
     * @param bool $allowed กำหนดสถานะการอนุญาต (true ถ้าอนุญาต, false ถ้าไม่อนุญาต)
     * @param string $redirect กำหนด URL ที่จะเปลี่ยนเส้นทางไปเมื่อไม่อนุญาต
     * @return Response|null คืนค่า Response redirect เมื่อไม่อนุญาต หรือ null เมื่ออนุญาต
     */
    protected function authorize(bool $allowed, string $redirect = '/login'): ?Response
    {
        if ($allowed) {
            return null;
        }

        return $this->redirect($redirect);
    }

    /**
     * คืนข้อมูลผู้ใช้แบบเต็ม (wrapper)
     * จุดประสงค์: ให้สามารถเข้าถึงข้อมูลผู้ใช้ที่ยืนยันตัวตนได้อย่างสะดวก
     * currentUser() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงข้อมูลผู้ใช้ที่ยืนยันตัวตนจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $user = $this->currentUser();
     * ```
     * 
     * @return User|null คืนค่าข้อมูลผู้ใช้ที่ยืนยันตัวตน หรือ null ถ้าไม่มีผู้ใช้ที่ยืนยันตัวตน
     */
    protected function currentUser()
    {
        return Auth::user();
    }

    /**
     * แฟลช input เพื่อใช้กับ old() ในคำขอถัดไป
     * จุดประสงค์: ให้สามารถแฟลชค่า input เพื่อใช้กับ old() ในคำขอถัดไปได้อย่างสะดวก
     * flashInput() ควรใช้กับอะไร: เมื่อคุณต้องการแฟลชค่า input เพื่อใช้กับ old() ในคำขอถัดไปจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->flashInput($this->all());
     * ```
     * 
     * @param array $input กำหนดอาร์เรย์ของค่า input ที่ต้องการแฟลช
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะแฟลชค่า input ใน session เพื่อใช้กับ old() ในคำขอถัดไป
     */
    protected function flashInput(array $input): void
    {
        Session::flashInput($input);
    }


    /**
     * สร้าง CSRF hidden field (wrapper)
     * จุดประสงค์: ให้สามารถสร้างฟิลด์ CSRF แบบซ่อนสำหรับฟอร์มได้อย่างสะดวก
     * csrfField() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างฟิลด์ CSRF แบบซ่อนสำหรับฟอร์มจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $csrfField = $this->csrfField();
     * ```
     * 
     * @return string คืนค่าฟิลด์ CSRF แบบซ่อนในรูปแบบ HTML
     */
    protected function csrfField(): string
    {
        return FormHelper::csrfField();
    }

    /**
     * สร้าง CSRF meta tag (wrapper)
     * จุดประสงค์: ให้สามารถสร้าง meta tag สำหรับ CSRF ได้อย่างสะดวก
     * csrfMeta() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง meta tag สำหรับ CSRF จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $csrfMeta = $this->csrfMeta();
     * ```
     * 
     * @return string คืนค่า meta tag สำหรับ CSRF ในรูปแบบ HTML
     */
    protected function csrfMeta(): string
    {
        return FormHelper::csrfMeta();
    }


    /**
     * สร้าง asset URL
     * จุดประสงค์: ให้สามารถสร้าง URL สำหรับไฟล์ asset ได้อย่างสะดวก
     * asset() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง URL สำหรับไฟล์ asset จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $assetUrl = $this->asset('css/app.css');
     * ```
     * 
     * @param string $path กำหนด path ของไฟล์ asset ที่ต้องการสร้าง URL
     * @return string คืนค่า URL สำหรับไฟล์ asset ที่สร้างขึ้น
     */
    protected function asset(string $path): string
    {
        return UrlHelper::asset($path);
    }

    /**
     * รับไฟล์อัปโหลดจากคำขอ
     * จุดประสงค์: ให้สามารถเข้าถึงไฟล์อัปโหลดจากคำขอได้อย่างสะดวก
     * file() ควรใช้กับอะไร: เมื่อคุณต้องการเข้าถึงไฟล์อัปโหลดจากคำขอในตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uploadedFile = $this->file('avatar');
     * ```
     * 
     * @param string|null $key กำหนดชื่อของไฟล์อัปโหลดที่ต้องการดึง หรือ null เพื่อดึงทั้งหมด
     * @return UploadedFile|array|null คืนค่า UploadedFile หรืออาร์เรย์ของ UploadedFile ที่ดึงมา หรือ null ถ้าไม่มีไฟล์อัปโหลดที่ตรงกับชื่อที่ระบุ
     */
    protected function file(?string $key = null)
    {
        return (new Request())->file($key);
    }

    /**
     * สร้าง URL โดยใช้ path/route-like string
     * จุดประสงค์: ให้สามารถสร้าง URL ได้อย่างสะดวกโดยใช้ UrlHelper
     * route() ควรใช้กับอะไร: เมื่อคุณต้องการสร้าง URL จากตัวควบคุมโดยใช้ path หรือ route-like string
     * ตัวอย่างการใช้งาน:
     * ```php
     * $routeUrl = $this->route('/users/{id}', ['id' => 1]);
     * ```
     * 
     * @param string $path กำหนด path หรือ route-like string ที่ต้องการสร้าง URL
     * @param array $params กำหนดอาร์เรย์ของพารามิเตอร์ที่จะเพิ่มลงใน URL
     * @return string คืนค่า URL ที่สร้างขึ้น
     */
    protected function route(string $path = '', array $params = []): string
    {
        return UrlHelper::to($path, $params);
    }

    /**
     * แบ่งหน้าอาร์เรย์โดยใช้ ArrayHelper (wrapper)
     * จุดประสงค์: ให้สามารถแบ่งหน้าอาร์เรย์ได้อย่างสะดวกโดยใช้ ArrayHelper
     * paginate() ควรใช้กับอะไร: เมื่อคุณต้องการแบ่งหน้าอาร์เรย์จากตัวควบคุมโดยใช้ ArrayHelper
     * ตัวอย่างการใช้งาน:
     * ```php
     * $paginatedItems = $this->paginate($items, $page, $perPage);
     * ```
     * 
     * @param array $items กำหนดอาร์เรย์ของไอเท็มที่ต้องการแบ่งหน้า
     * @param int $page กำหนดหมายเลขหน้าปัจจุบัน (เริ่มต้นที่ 1)
     * @param int $perPage กำหนดจำนวนไอเท็มต่อหน้า (เริ่มต้นที่ 15)
     * @return array คืนค่าอาร์เรย์ของไอเท็มที่ถูกแบ่งหน้า
     */
    protected function paginate(array $items, int $page = 1, int $perPage = 15): array
    {
        return ArrayHelper::paginate($items, $page, $perPage);
    }

    /**
     * ส่ง JSON 201 Created
     * จุดประสงค์: ให้สามารถส่งการตอบกลับ JSON ที่แสดงถึงการสร้างทรัพยากรใหม่ได้อย่างสะดวก
     * respondCreated() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบกลับ JSON ที่แสดงถึงการสร้างทรัพยากรใหม่จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->respondCreated(['id' => 1, 'name' => 'John'], 'User created successfully');
     * ```
     * 
     * @param mixed $data กำหนดข้อมูลที่จะส่งกลับ (ถ้ามี)
     * @param string $message กำหนดข้อความเพิ่มเติม (ไม่บังคับ)
     * @return Response คืนค่า Response ที่สร้างขึ้น
     */
    protected function respondCreated($data = null, string $message = 'Created'): Response
    {
        return $this->responseJson(true, $data, $message, [], 201);
    }

    /**
     * คืนค่า 204 No Content
     * จุดประสงค์: ให้สามารถส่งการตอบกลับที่แสดงถึงไม่มีเนื้อหาได้อย่างสะดวก
     * noContent() ควรใช้กับอะไร: เมื่อคุณต้องการส่งการตอบกลับที่แสดงถึงไม่มีเนื้อหาจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * return $this->noContent();
     * ```
     * 
     * @return Response คืนค่า Response ที่แสดงถึงไม่มีเนื้อหา (204 No Content)
     */
    protected function noContent(): Response
    {
        return Response::noContent();
    }


    /**
     * แชร์ข้อมูลให้ทุกวิว (wrapper)
     * จุดประสงค์: ให้สามารถแชร์ข้อมูลให้ทุกวิวได้อย่างสะดวก
     * share() ควรใช้กับอะไร: เมื่อคุณต้องการแชร์ข้อมูลให้ทุกวิวจากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $this->share('key', 'value');
     * ```
     * 
     * @param string|array $key กำหนดชื่อของข้อมูลที่ต้องการแชร์ หรืออาร์เรย์ของชื่อและค่าที่ต้องการแชร์
     * @param mixed|null $value กำหนดค่าของข้อมูลที่ต้องการแชร์ (ไม่จำเป็นถ้า $key เป็นอาร์เรย์)
     * @return void ไม่มีค่าที่ส่งกลับ แต่จะทำการแชร์ข้อมูลให้ทุกวิวผ่าน View::share
     */
    protected function share(string $key,mixed $value): void
    {
        View::share($key, $value);
    }

    /**
     * อ่านข้อมูลที่แชร์ไว้ (wrapper)
     * จุดประสงค์: ให้สามารถอ่านข้อมูลที่แชร์ไว้ได้อย่างสะดวก
     * shared() ควรใช้กับอะไร: เมื่อคุณต้องการอ่านข้อมูลที่แชร์ไว้จากตัวควบคุม
     * ตัวอย่างการใช้งาน:
     * ```php
     * $value = $this->shared('key', 'default');
     * ```
     * 
     * @param string|null $key กำหนดชื่อของข้อมูลที่ต้องการอ่าน หรือ null เพื่ออ่านทั้งหมด
     * @param mixed $default กำหนดค่าดีฟอลต์เมื่อข้อมูลที่ระบุไม่มีการแชร์ไว้
     * @return mixed คืนค่าของข้อมูลที่แชร์ไว้สำหรับชื่อที่ระบุ หรือค่าดีฟอลต์ถ้าไม่มีการแชร์ข้อมูลสำหรับชื่อที่ระบุ หรืออาร์เรย์ของข้อมูลทั้งหมดที่แชร์ไว้ถ้า $key เป็น null
     */
    protected function shared(?string $key = null, $default = null)
    {
        return View::shared($key, $default);
    }
} 