<?php
/**
 * คลาส FileUpload สำหรับจัดการการอัปโหลดไฟล์
 * 
 * จุดประสงค์: จัดการการอัปโหลดไฟล์อย่างปลอดภัย
 * ฟีเจอร์: ตรวจสอบไฟล์, จัดการขนาด/ชนิดไฟล์, สร้างชื่อไฟล์ที่ปลอดภัย
 * FileUpload ควรใช้กับอะไร: ใช้เมื่อคุณต้องการให้อัปโหลดไฟล์จากฟอร์ม HTML ไปยังเซิร์ฟเวอร์
 * 
 * ฟีเจอร์หลัก:
 * - ตรวจสอบชนิดไฟล์
 * - ตรวจสอบขนาดไฟล์
 * - สร้างชื่อไฟล์ที่ไม่ซ้ำและปลอดภัย
 * - รองรับการอัปโหลดหลายไฟล์
 * - ย้ายไฟล์ไปยังโฟลเดอร์ที่กำหนด
 * 
 * ตัวอย่างการใช้งานโดยรวม:
 * ```php
 * $uploader = new FileUpload();
 * 
 * // ตั้งค่า
 * $uploader->setAllowedTypes(['jpg', 'png', 'pdf'])
 *          ->setMaxSize(5 * 1024 * 1024) // 5MB
 *          ->setUploadPath('uploads/documents');
 * 
 * // อัปโหลด
 * if ($uploader->upload('file_field')) {
 *     $filename = $uploader->getUploadedFileName();
 *     echo "อัปโหลดสำเร็จ: {$filename}";
 * } else {
 *     echo $uploader->getError();
 * }
 * ```
 */

namespace App\Core;

class FileUpload
{
    /**
     * ชนิดไฟล์ที่อนุญาต สำหรับนามสกุลไฟล์
     */
    private array $allowedTypes = [];

    /**
     * MIME types ที่อนุญาต สำหรับการตรวจสอบเพิ่มเติม
     */
    private array $allowedMimeTypes = [];

    /**
     * ขนาดไฟล์สูงสุด (bytes) สำหรับการอัปโหลด
     */
    private int $maxSize = 5242880; // 5MB

    /**
     * โฟลเดอร์สำหรับอัปโหลด สำหรับเก็บไฟล์ที่อัปโหลด
     */
    private string $uploadPath = 'uploads';

    /**
     * ชื่อไฟล์ที่อัปโหลด สำหรับดึงข้อมูลหลังอัปโหลดสำเร็จ
     */
    private ?string $uploadedFileName = null;

    /**
     * ข้อความแสดงข้อผิดพลาด สำหรับแจ้งเตือนเมื่อเกิดปัญหา
     */
    private ?string $error = null;

    /**
     * ข้อมูลไฟล์ที่อัปโหลด สำหรับดึงข้อมูลเพิ่มเติม
     */
    private ?array $fileData = null;

    /**
     * MIME types mapping สำหรับนามสกุลไฟล์
     */
    private array $mimeTypesMap = [
        // รูปภาพ
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        
        // เอกสาร
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        
        // Archives
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        '7z' => 'application/x-7z-compressed',
        
        // Text
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'xml' => 'application/xml',
    ];

    /**
     * ตั้งค่าชนิดไฟล์ที่อนุญาต
     * จุดประสงค์: กำหนดนามสกุลไฟล์ที่อนุญาตให้อัปโหลด
     * setAllowedTypes() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดชนิดไฟล์ที่อนุญาตให้อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uploader->setAllowedTypes(['jpg', 'png', 'pdf']);
     * ```
     * 
     * @param array $types กำหนดนามสกุลไฟล์ที่อนุญาต (เช่น ['jpg', 'png', 'pdf'])
     * @return self คืนค่าอ็อบเจ็กต์ FileUpload เพื่อรองรับการเรียกใช้แบบ method chaining
     */
    public function setAllowedTypes(array $types): self
    {
        $this->allowedTypes = array_map('strtolower', $types);
        
        // แปลงเป็น MIME types
        $this->allowedMimeTypes = [];
        foreach ($types as $type) {
            $type = strtolower($type);
            if (isset($this->mimeTypesMap[$type])) {
                $this->allowedMimeTypes[] = $this->mimeTypesMap[$type];
            }
        }

        return $this;
    }

    /**
     * ตั้งค่าขนาดไฟล์สูงสุด
     * จุดประสงค์: กำหนดขนาดไฟล์สูงสุดที่อนุญาตให้อัปโหลด
     * setMaxSize() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดขนาดไฟล์สูงสุดที่อนุญาตให้อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uploader->setMaxSize(10 * 1024 * 1024); // 10MB
     * ```
     * 
     * @param int $bytes กำหนดขนาดไฟล์สูงสุดที่อนุญาตให้อัปโหลด (bytes)
     * @return self คืนค่าอ็อบเจ็กต์ FileUpload เพื่อรองรับการเรียกใช้แบบ method chaining
     */
    public function setMaxSize(int $bytes): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * ตั้งค่าโฟลเดอร์อัปโหลด
     * จุดประสงค์: กำหนดโฟลเดอร์ที่ใช้เก็บไฟล์ที่อัปโหลด
     * setUploadPath() ควรใช้กับอะไร: เมื่อคุณต้องการกำหนดโฟลเดอร์สำหรับเก็บไฟล์ที่อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $uploader->setUploadPath('uploads/documents');
     * ```
     * 
     * @param string $path กำหนดโฟลเดอร์สำหรับเก็บไฟล์ที่อัปโหลด
     * @return self คืนค่าอ็อบเจ็กต์ FileUpload เพื่อรองรับการเรียกใช้แบบ method chaining
     */
    public function setUploadPath(string $path): self
    {
        $this->uploadPath = rtrim($path, '/');
        return $this;
    }

    /**
     * อัปโหลดไฟล์
     * จุดประสงค์: จัดการการอัปโหลดไฟล์จากฟิลด์ฟอร์ม
     * upload() ควรใช้กับอะไร: เมื่อคุณต้องการให้อัปโหลดไฟล์จากฟอร์ม HTML ไปยังเซิร์ฟเวอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($uploader->upload('file_field')) {
     *     echo "อัปโหลดสำเร็จ: " . $uploader->getUploadedFileName();
     * } else {
     *     echo $uploader->getError();
     * }
     * ```
     * 
     * @param string $fieldName กำหนดชื่อฟิลด์ในฟอร์มที่ใช้สำหรับอัปโหลดไฟล์
     * @param string|null $customName กำหนดชื่อไฟล์ที่ต้องการ (ถ้าไม่ระบุจะสร้างชื่อไฟล์อัตโนมัติ)
     * @return bool คืนค่า true ถ้าอัปโหลดสำเร็จ หรือ false ถ้าไม่สำเร็จ
     */
    public function upload(string $fieldName, ?string $customName = null): bool
    {
        $this->error = null;
        $this->uploadedFileName = null;

        // ตรวจสอบว่ามีไฟล์อัปโหลดหรือไม่
        if (!isset($_FILES[$fieldName])) {
            $this->error = 'ไม่พบไฟล์ที่ต้องการอัปโหลด';
            return false;
        }

        $file = $_FILES[$fieldName];

        // ตรวจสอบข้อผิดพลาดการอัปโหลด
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->error = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // บันทึกข้อมูลไฟล์
        $this->fileData = $file;

        // ตรวจสอบไฟล์
        if (!$this->validateFile($file)) {
            return false;
        }

        // สร้างชื่อไฟล์
        $fileName = $customName ?: $this->generateFileName($file['name']);

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!$this->createUploadDirectory()) {
            return false;
        }

        // ย้ายไฟล์
        $destination = $this->uploadPath . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $this->uploadedFileName = $fileName;
            return true;
        }

        $this->error = 'ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ปลายทางได้';
        return false;
    }

    /**
     * อัปโหลดหลายไฟล์
     * จุดประสงค์: จัดการการอัปโหลดหลายไฟล์จากฟิลด์ฟอร์ม
     * uploadMultiple() ควรใช้กับอะไร: เมื่อคุณต้องการให้อัปโหลดหลายไฟล์จากฟอร์ม HTML ไปยังเซิร์ฟเวอร์
     * ตัวอย่างการใช้งาน:
     * ```php
     * $result = $uploader->uploadMultiple('files_field');
     * if (!empty($result['success'])) {
     *     echo "อัปโหลดสำเร็จ: " . implode(', ', $result['success']);
     * }
     * ```
     * 
     * @param string $fieldName กำหนดชื่อฟิลด์ในฟอร์มที่ใช้สำหรับอัปโหลดไฟล์
     * @return array คืนค่าอาร์เรย์ที่มีรายการไฟล์ที่อัปโหลดสำเร็จและล้มเหลว
     */
    public function uploadMultiple(string $fieldName): array
    {
        $result = [
            'success' => [],
            'failed' => []
        ];

        if (!isset($_FILES[$fieldName])) {
            return $result;
        }

        $files = $this->normalizeFilesArray($_FILES[$fieldName]);

        foreach ($files as $file) {
            // สร้าง temporary $_FILES entry
            $tempFieldName = '_temp_upload_' . uniqid();
            $_FILES[$tempFieldName] = $file;

            if ($this->upload($tempFieldName)) {
                $result['success'][] = $this->getUploadedFileName();
            } else {
                $result['failed'][] = [
                    'file' => $file['name'],
                    'error' => $this->getError()
                ];
            }

            // ลบ temporary entry
            unset($_FILES[$tempFieldName]);
        }

        return $result;
    }

    /**
     * ตรวจสอบไฟล์
     * จุดประสงค์: ตรวจสอบความถูกต้องของไฟล์ที่อัปโหลด
     * validateFile() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบความถูกต้องของไฟล์ที่อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php   
     * if ($uploader->validateFile($file)) {
     *     echo "ไฟล์ถูกต้อง";
     * } else {
     *     echo $uploader->getError();
     * }
     * ```
     * 
     * @param array $file กำหนดข้อมูลไฟล์จาก $_FILES
     * @return bool คืนค่า true ถ้าไฟล์ถูกต้อง หรือ false ถ้าไม่ถูกต้อง
     */
    private function validateFile(array $file): bool
    {
        // ตรวจสอบว่าเป็นไฟล์ที่อัปโหลดจริง
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->error = 'ไฟล์ไม่ถูกต้อง';
            return false;
        }

        // ตรวจสอบขนาดไฟล์
        if ($file['size'] > $this->maxSize) {
            $maxSizeMB = round($this->maxSize / 1024 / 1024, 2);
            $this->error = "ไฟล์มีขนาดเกินที่กำหนด (สูงสุด {$maxSizeMB} MB)";
            return false;
        }

        // ตรวจสอบว่าไฟล์ว่างเปล่าหรือไม่
        if ($file['size'] === 0) {
            $this->error = 'ไฟล์ว่างเปล่า';
            return false;
        }

        // ตรวจสอบชนิดไฟล์
        if (!empty($this->allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $this->allowedTypes)) {
                $this->error = 'ชนิดไฟล์ไม่ได้รับอนุญาต (อนุญาตเฉพาะ: ' . implode(', ', $this->allowedTypes) . ')';
                return false;
            }
        }

        // ตรวจสอบ MIME type
        if (!empty($this->allowedMimeTypes)) {
            // ใช้แบบ OOP ของ finfo แทนการเรียก finfo_open/finfo_close
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            unset($finfo);

            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                $this->error = 'ชนิดไฟล์ไม่ถูกต้อง';
                return false;
            }
        }

        return true;
    }

    /**
     * สร้างชื่อไฟล์ที่ไม่ซ้ำและปลอดภัย
     * จุดประสงค์: สร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำและปลอดภัยสำหรับการอัปโหลด
     * generateFileName() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างชื่อไฟล์ใหม่ที่ไม่ซ้ำและปลอดภัยสำหรับการอัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $newFileName = $uploader->generateFileName($originalFileName);
     * ```
     * 
     * @param string $originalName กำหนดชื่อไฟล์ต้นฉบับ
     * @return string คืนค่าชื่อไฟล์ที่ถูกสร้างใหม่
     */
    private function generateFileName(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        // ทำความสะอาดชื่อไฟล์
        $basename = $this->sanitizeFileName($basename);

        // สร้างชื่อไฟล์ที่ไม่ซ้ำ
        $uniqueName = $basename . '_' . uniqid() . '.' . $extension;

        return $uniqueName;
    }

    /**
     * ทำความสะอาดชื่อไฟล์
     * จุดประสงค์: ทำความสะอาดชื่อไฟล์โดยการแทนที่อักขระพิเศษและจัดรูปแบบให้เหมาะสม
     * sanitizeFileName() ควรใช้กับอะไร: เมื่อคุณต้องการทำความสะอาดชื่อไฟล์ก่อนนำไปใช้งาน
     * ตัวอย่างการใช้งาน:
     * ```php
     * $cleanName = $uploader->sanitizeFileName($originalName);
     * ```
     * 
     * @param string $filename กำหนดชื่อไฟล์ต้นฉบับ
     * @return string คืนค่าชื่อไฟล์ที่ถูกทำความสะอาด
     */
    private function sanitizeFileName(string $filename): string
    {
        // แทนที่อักขระพิเศษด้วย underscore
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        
        // ลบ underscore ซ้ำติดกัน
        $filename = preg_replace('/_+/', '_', $filename);
        
        // ตัดความยาว
        $filename = substr($filename, 0, 50);
        
        return trim($filename, '_');
    }

    /**
     * สร้างโฟลเดอร์อัปโหลด
     * จุดประสงค์: สร้างโฟลเดอร์สำหรับเก็บไฟล์ที่อัปโหลดถ้ายังไม่มี
     * createUploadDirectory() ควรใช้กับอะไร: เมื่อคุณต้องการสร้างโฟลเดอร์สำหรับเก็บไฟล์ที่อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * if ($uploader->createUploadDirectory()) {
     *     echo "โฟลเดอร์พร้อมใช้งาน";
     * } else {
     *     echo $uploader->getError();
     * }
     * ```
     * 
     * @return bool คืนค่า true ถ้าโฟลเดอร์พร้อมใช้งาน หรือ false ถ้าไม่สำเร็จ
     */
    private function createUploadDirectory(): bool
    {
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                $this->error = 'ไม่สามารถสร้างโฟลเดอร์สำหรับอัปโหลดได้';
                return false;
            }
        }

        if (!is_writable($this->uploadPath)) {
            $this->error = 'ไม่สามารถเขียนไฟล์ในโฟลเดอร์ได้';
            return false;
        }

        return true;
    }

    /**
     * แปลง array ของไฟล์หลายไฟล์ให้เป็นรูปแบบปกติ
     * จุดประสงค์: แปลงโครงสร้างข้อมูลไฟล์หลายไฟล์จาก $_FILES ให้เป็นรูปแบบที่ง่ายต่อการประมวลผล
     * normalizeFilesArray() ควรใช้กับอะไร: เมื่อคุณต้องการแปลงข้อมูลไฟล์หลายไฟล์จาก $_FILES
     * ตัวอย่างการใช้งาน:
     * ```php
     * $normalizedFiles = $uploader->normalizeFilesArray($_FILES['input_name']);
     * ```
     * 
     * @param array $files กำหนดข้อมูลไฟล์จาก $_FILES
     * @return array คืนค่าอาร์เรย์ที่มีโครงสร้างไฟล์ที่ถูกแปลงแล้ว
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];

        if (!isset($files['name']) || !is_array($files['name'])) {
            return [$files];
        }

        $fileCount = count($files['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $normalized[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }

        return $normalized;
    }

    /**
     * รับข้อความแสดงข้อผิดพลาดจากรหัส error
     * จุดประสงค์: แปลงรหัสข้อผิดพลาดการอัปโหลดเป็นข้อความที่เข้าใจง่าย
     * getUploadErrorMessage() ควรใช้กับอะไร: เมื่อคุณต้องการแปลงรหัสข้อผิดพลาดการอัปโหลดเป็นข้อความที่เข้าใจง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $errorMessage = $uploader->getUploadErrorMessage(UPLOAD_ERR_INI_SIZE);
     * ```
     * 
     * @param int $errorCode กำหนดรหัสข้อผิดพลาดการอัปโหลด
     * @return string คืนค่าข้อความข้อผิดพลาดที่สอดคล้องกับรหัส
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'ไฟล์มีขนาดเกินที่กำหนดในการตั้งค่าเซิร์ฟเวอร์',
            UPLOAD_ERR_FORM_SIZE => 'ไฟล์มีขนาดเกินที่กำหนดในฟอร์ม',
            UPLOAD_ERR_PARTIAL => 'อัปโหลดไฟล์ไม่สมบูรณ์',
            UPLOAD_ERR_NO_FILE => 'ไม่มีไฟล์ที่ต้องการอัปโหลด',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่พบโฟลเดอร์ชั่วคราว',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ลงดิสก์',
            UPLOAD_ERR_EXTENSION => 'การอัปโหลดถูกหยุดโดย extension',
        ];

        return $errors[$errorCode] ?? 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ';
    }

    // ========== Getters ==========

    /**
     * รับชื่อไฟล์ที่อัปโหลด
     * จุดประสงค์: ดึงชื่อไฟล์ที่ถูกอัปโหลดสำเร็จ
     * getUploadedFileName() ควรใช้กับอะไร: เมื่อคุณต้องการดึงชื่อไฟล์ที่อัปโหลดสำเร็จ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $filename = $uploader->getUploadedFileName();
     * ```
     * 
     * @return string|null คืนค่าชื่อไฟล์ที่อัปโหลด หรือ null ถ้ายังไม่มีการอัปโหลด
     */
    public function getUploadedFileName(): ?string
    {
        return $this->uploadedFileName;
    }

    /**
     * รับ path เต็มของไฟล์ที่อัปโหลด
     * จุดประสงค์: ดึง path เต็มของไฟล์ที่ถูกอัปโหลดสำเร็จ
     * getUploadedFilePath() ควรใช้กับอะไร: เมื่อคุณต้องการดึง path เต็มของไฟล์ที่อัปโหลดสำเร็จ
     * ตัวอย่างการใช้งาน:
     * ```php
     * $filepath = $uploader->getUploadedFilePath();
     * ```
     * 
     * @return string|null คืนค่า path เต็มของไฟล์ที่อัปโหลด หรือ null ถ้ายังไม่มีการอัปโหลด
     */
    public function getUploadedFilePath(): ?string
    {
        if ($this->uploadedFileName) {
            return $this->uploadPath . '/' . $this->uploadedFileName;
        }

        return null;
    }

    /**
     * รับข้อความแสดงข้อผิดพลาด
     * จุดประสงค์: ดึงข้อความข้อผิดพลาดล่าสุดจากการอัปโหลด
     * getError() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อความข้อผิดพลาดล่าสุดจากการอัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $error = $uploader->getError();
     * ```
     * 
     * @return string|null คืนค่าข้อความข้อผิดพลาด หรือ null ถ้าไม่มีข้อผิดพลาด
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * รับข้อมูลไฟล์
     * จุดประสงค์: ดึงข้อมูลทั้งหมดของไฟล์ที่อัปโหลด
     * getFileData() ควรใช้กับอะไร: เมื่อคุณต้องการดึงข้อมูลทั้งหมดของไฟล์ที่อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $fileData = $uploader->getFileData();
     * ```
     * 
     * @return array|null คืนค่าอาร์เรย์ข้อมูลไฟล์ หรือ null ถ้ายังไม่มีการอัปโหลด
     */
    public function getFileData(): ?array
    {
        return $this->fileData;
    }

    /**
     * รับขนาดไฟล์ที่อัปโหลด
     * จุดประสงค์: ดึงขนาดไฟล์ที่อัปโหลดเป็น bytes
     * getFileSize() ควรใช้กับอะไร: เมื่อคุณต้องการดึงขนาดไฟล์ที่อัปโหลดเป็น bytes
     * ตัวอย่างการใช้งาน:
     * ```php
     * $fileSize = $uploader->getFileSize();
     * ```
     * 
     * @return int|null คืนค่าขนาดไฟล์ที่อัปโหลดเป็น bytes หรือ null ถ้ายังไม่มีการอัปโหลด
     */
    public function getFileSize(): ?int
    {
        return $this->fileData['size'] ?? null;
    }

    /**
     * รับชนิดไฟล์
     * จุดประสงค์: ดึงนามสกุลของไฟล์ที่อัปโหลด
     * getFileExtension() ควรใช้กับอะไร: เมื่อคุณต้องการดึงนามสกุลของไฟล์ที่อัปโหลด
     * ตัวอย่างการใช้งาน:
     * ```php
     * $extension = $uploader->getFileExtension();
     * ```
     * 
     * @return string|null คืนค่านามสกุลของไฟล์ที่อัปโหลด หรือ null ถ้ายังไม่มีการอัปโหลด
     */
    public function getFileExtension(): ?string
    {
        if ($this->uploadedFileName) {
            return strtolower(pathinfo($this->uploadedFileName, PATHINFO_EXTENSION));
        }

        return null;
    }

    // ========== Static Helper Methods ==========

    /**
     * ลบไฟล์
     * จุดประสงค์: ลบไฟล์จากระบบไฟล์
     * deleteFile() ควรใช้กับอะไร: เมื่อคุณต้องการลบไฟล์จากระบบไฟล์
     * ตัวอย่างการใช้งาน:
     * ```php
     * FileUpload::deleteFile('uploads/document.pdf');
     * ```
     * 
     * @param string $filePath กำหนด path ของไฟล์ที่ต้องการลบ
     * @return bool คืนค่า true ถ้าลบสำเร็จ หรือ false ถ้าไฟล์ไม่พบหรือไม่สามารถลบได้
     */
    public static function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * ตรวจสอบว่าเป็นรูปภาพหรือไม่
     * จุดประสงค์: ตรวจสอบว่าไฟล์ที่ระบุเป็นรูปภาพหรือไม่
     * isImage() ควรใช้กับอะไร: เมื่อคุณต้องการตรวจสอบว่าไฟล์ที่ระบุเป็นรูปภาพหรือไม่
     * ตัวอย่างการใช้งาน:
     * ```php
     * $isImage = FileUpload::isImage('uploads/photo.jpg');
     * ```
     * 
     * @param string $filePath กำหนด path ของไฟล์ที่ต้องการตรวจสอบ
     * @return bool คืนค่า true ถ้าเป็นรูปภาพ หรือ false ถ้าไม่ใช่รูปภาพ
     */
    public static function isImage(string $filePath): bool
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        // ใช้ OOP interface ของ finfo แทนการเรียกฟังก์ชันแบบ procedural
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);
        unset($finfo);

        return in_array($mimeType, $imageTypes);
    }

    /**
     * แปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
     * จุดประสงค์: แปลงขนาดไฟล์จาก bytes เป็นรูปแบบที่อ่านง่าย (KB, MB, GB)
     * formatFileSize() ควรใช้กับอะไร: เมื่อคุณต้องการแปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
     * ตัวอย่างการใช้งาน:
     * ```php
     * $readableSize = FileUpload::formatFileSize(1048576); // "1 MB"
     * ```
     * 
     * @param int $bytes กำหนดขนาดไฟล์ในหน่วย bytes
     * @return string คืนค่าขนาดไฟล์ในรูปแบบที่อ่านง่าย
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
