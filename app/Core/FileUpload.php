<?php
/**
 * คลาส FileUpload
 * 
 * จุดประสงค์: จัดการการอัปโหลดไฟล์อย่างปลอดภัย
 * ฟีเจอร์: ตรวจสอบไฟล์, จัดการขนาด/ชนิดไฟล์, สร้างชื่อไฟล์ที่ปลอดภัย
 * 
 * ฟีเจอร์หลัก:
 * - ตรวจสอบชนิดไฟล์
 * - ตรวจสอบขนาดไฟล์
 * - สร้างชื่อไฟล์ที่ไม่ซ้ำและปลอดภัย
 * - รองรับการอัปโหลดหลายไฟล์
 * - ย้ายไฟล์ไปยังโฟลเดอร์ที่กำหนด
 * 
 * ตัวอย่างการใช้งาน:
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
     * ชนิดไฟล์ที่อนุญาต
     */
    private array $allowedTypes = [];

    /**
     * MIME types ที่อนุญาต
     */
    private array $allowedMimeTypes = [];

    /**
     * ขนาดไฟล์สูงสุด (bytes)
     */
    private int $maxSize = 5242880; // 5MB

    /**
     * โฟลเดอร์สำหรับอัปโหลด
     */
    private string $uploadPath = 'uploads';

    /**
     * ชื่อไฟล์ที่อัปโหลด
     */
    private ?string $uploadedFileName = null;

    /**
     * ข้อความแสดงข้อผิดพลาด
     */
    private ?string $error = null;

    /**
     * ข้อมูลไฟล์ที่อัปโหลด
     */
    private ?array $fileData = null;

    /**
     * MIME types mapping
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
     * 
     * @param array $types
     * @return self
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
     * 
     * @param int $bytes
     * @return self
     */
    public function setMaxSize(int $bytes): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * ตั้งค่าโฟลเดอร์อัปโหลด
     * 
     * @param string $path
     * @return self
     */
    public function setUploadPath(string $path): self
    {
        $this->uploadPath = rtrim($path, '/');
        return $this;
    }

    /**
     * อัปโหลดไฟล์
     * 
     * @param string $fieldName ชื่อฟิลด์ในฟอร์ม
     * @param string|null $customName ชื่อไฟล์แบบกำหนดเอง (ไม่บังคับ)
     * @return bool
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
     * 
     * @param string $fieldName
     * @return array ['success' => [...], 'failed' => [...]]
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
     * 
     * @param array $file
     * @return bool
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
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                $this->error = 'ชนิดไฟล์ไม่ถูกต้อง';
                return false;
            }
        }

        return true;
    }

    /**
     * สร้างชื่อไฟล์ที่ไม่ซ้ำและปลอดภัย
     * 
     * @param string $originalName
     * @return string
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
     * 
     * @param string $filename
     * @return string
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
     * 
     * @return bool
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
     * 
     * @param array $files
     * @return array
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
     * 
     * @param int $errorCode
     * @return string
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
     * 
     * @return string|null
     */
    public function getUploadedFileName(): ?string
    {
        return $this->uploadedFileName;
    }

    /**
     * รับ path เต็มของไฟล์ที่อัปโหลด
     * 
     * @return string|null
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
     * 
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * รับข้อมูลไฟล์
     * 
     * @return array|null
     */
    public function getFileData(): ?array
    {
        return $this->fileData;
    }

    /**
     * รับขนาดไฟล์ที่อัปโหลด
     * 
     * @return int|null
     */
    public function getFileSize(): ?int
    {
        return $this->fileData['size'] ?? null;
    }

    /**
     * รับชนิดไฟล์
     * 
     * @return string|null
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
     * 
     * @param string $filePath
     * @return bool
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
     * 
     * @param string $filePath
     * @return bool
     */
    public static function isImage(string $filePath): bool
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return in_array($mimeType, $imageTypes);
    }

    /**
     * แปลงขนาดไฟล์เป็นรูปแบบที่อ่านง่าย
     * 
     * @param int $bytes
     * @return string
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
