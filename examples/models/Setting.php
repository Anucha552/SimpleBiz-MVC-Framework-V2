<?php
/**
 * โมเดลการตั้งค่าระบบ
 * 
 * จุดประสงค์: จัดเก็บการตั้งค่าแบบไดนามิกแบบ key-value
 * 
 * กฎทางธุรกิจ:
 * - รองรับหลายประเภทข้อมูล (string, integer, boolean, json)
 * - มี cache เพื่อประสิทธิภาพ
 * - อัปเดตได้โดยไม่ต้อง deploy code ใหม่
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Cache;
use PDO;

class Setting
{
    private PDO $db;
    private Logger $logger;
    private Cache $cache;
    private string $cachePrefix = 'setting:';
    private int $cacheTTL = 3600; // 1 ชั่วโมง

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
        $this->cache = new Cache();
    }

    /**
     * ดึงค่าการตั้งค่าทั้งหมด
     * 
     * @param string|null $group ชื่อกลุ่ม (ถ้าต้องการกรองเฉพาะกลุ่ม)
     * @return array
     */
    public function getAll(?string $group = null): array
    {
        try {
            $cacheKey = $this->cachePrefix . 'all' . ($group ? ":$group" : '');
            
            // ตรวจสอบ cache
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $sql = "SELECT * FROM settings";
            if ($group !== null) {
                $sql .= " WHERE group_name = :group";
            }
            $sql .= " ORDER BY group_name, sort_order ASC, key_name ASC";
            
            $stmt = $this->db->prepare($sql);
            if ($group !== null) {
                $stmt->bindValue(':group', $group, PDO::PARAM_STR);
            }
            $stmt->execute();
            
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // แปลงค่าตามประเภท
            foreach ($settings as &$setting) {
                $setting['value'] = $this->castValue($setting['value'], $setting['type']);
            }
            
            // เก็บใน cache
            $this->cache->set($cacheKey, $settings, $this->cacheTTL);
            
            return $settings;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch settings', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ดึงค่าการตั้งค่าแบบ key-value pairs
     * 
     * @param string|null $group
     * @return array
     */
    public function getAllAsKeyValue(?string $group = null): array
    {
        $settings = $this->getAll($group);
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['key_name']] = $setting['value'];
        }
        
        return $result;
    }

    /**
     * ดึงค่าการตั้งค่าตาม key
     * 
     * @param string $key
     * @param mixed $default ค่า default ถ้าไม่พบ
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        try {
            $cacheKey = $this->cachePrefix . $key;
            
            // ตรวจสอบ cache
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }

            $stmt = $this->db->prepare("
                SELECT value, type FROM settings WHERE key_name = :key
            ");
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return $default;
            }
            
            $value = $this->castValue($result['value'], $result['type']);
            
            // เก็บใน cache
            $this->cache->set($cacheKey, $value, $this->cacheTTL);
            
            return $value;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * ตั้งค่าใหม่หรืออัปเดต
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type (string, integer, boolean, json)
     * @param string|null $group
     * @param string|null $description
     * @return array
     */
    public function set(string $key, $value, string $type = 'string', ?string $group = null, ?string $description = null): array
    {
        try {
            // แปลงค่าสำหรับเก็บ
            $storedValue = $this->prepareValue($value, $type);
            
            // ตรวจสอบว่ามีอยู่แล้วหรือไม่
            $stmt = $this->db->prepare("SELECT id FROM settings WHERE key_name = :key");
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                // อัปเดต
                $stmt = $this->db->prepare("
                    UPDATE settings 
                    SET value = :value, type = :type, group_name = :group, 
                        description = :description, updated_at = NOW()
                    WHERE key_name = :key
                ");
            } else {
                // สร้างใหม่
                $stmt = $this->db->prepare("
                    INSERT INTO settings (key_name, value, type, group_name, description, created_at)
                    VALUES (:key, :value, :type, :group, :description, NOW())
                ");
            }
            
            $stmt->execute([
                ':key' => $key,
                ':value' => $storedValue,
                ':type' => $type,
                ':group' => $group ?? 'general',
                ':description' => $description
            ]);
            
            // ลบ cache
            $this->clearCache($key);
            
            $this->logger->info('Setting updated', ['key' => $key]);
            
            return [
                'success' => true,
                'message' => 'บันทึกการตั้งค่าสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to set setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบการตั้งค่า
     * 
     * @param string $key
     * @return array
     */
    public function delete(string $key): array
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM settings WHERE key_name = :key");
            $stmt->bindValue(':key', $key, PDO::PARAM_STR);
            $stmt->execute();
            
            // ลบ cache
            $this->clearCache($key);
            
            $this->logger->info('Setting deleted', ['key' => $key]);
            
            return [
                'success' => true,
                'message' => 'ลบการตั้งค่าสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete setting', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * อัปเดตหลายค่าพร้อมกัน
     * 
     * @param array $settings [key => value]
     * @param string $group
     * @return array
     */
    public function updateBatch(array $settings, string $group = 'general'): array
    {
        try {
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                // ตรวจหาประเภทอัตโนมัติ
                $type = $this->detectType($value);
                $this->set($key, $value, $type, $group);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'อัปเดตการตั้งค่าทั้งหมดสำเร็จ'
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to update batch settings', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * แปลงค่าตามประเภท (จาก DB)
     * 
     * @param string $value
     * @param string $type
     * @return mixed
     */
    private function castValue(string $value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * เตรียมค่าสำหรับเก็บ (ไป DB)
     * 
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private function prepareValue($value, string $type): string
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * ตรวจหาประเภทข้อมูลอัตโนมัติ
     * 
     * @param mixed $value
     * @return string
     */
    private function detectType($value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_array($value)) return 'json';
        return 'string';
    }

    /**
     * ลบ cache
     * 
     * @param string|null $key
     * @return void
     */
    private function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget($this->cachePrefix . $key);
        }
        
        // ลบ cache ทั้งหมด
        Cache::forget($this->cachePrefix . 'all');
    }
}
