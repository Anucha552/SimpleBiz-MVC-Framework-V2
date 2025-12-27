<?php
/**
 * โมเดล API Key
 * 
 * จุดประสงค์: จัดการ API keys สำหรับ authentication แบบ API
 * 
 * กฎทางธุรกิจ:
 * - แต่ละ key มีขอบเขตการใช้งาน (scopes)
 * - ตรวจสอบอายุและจำนวนการใช้งาน
 * - บันทึก usage statistics
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class ApiKey
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * สร้าง API key ใหม่
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            // สร้าง key แบบสุ่ม
            $apiKey = $this->generateKey();
            
            $stmt = $this->db->prepare("
                INSERT INTO api_keys (
                    user_id, name, key_hash, scopes, 
                    expires_at, rate_limit, status, created_at
                ) VALUES (
                    :user_id, :name, :key_hash, :scopes,
                    :expires_at, :rate_limit, :status, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':name' => $data['name'],
                ':key_hash' => hash('sha256', $apiKey),
                ':scopes' => isset($data['scopes']) ? json_encode($data['scopes']) : json_encode([]),
                ':expires_at' => $data['expires_at'] ?? null,
                ':rate_limit' => $data['rate_limit'] ?? 60, // 60 requests/minute
                ':status' => 'active'
            ]);
            
            $keyId = $this->db->lastInsertId();
            
            $this->logger->info('API key created', [
                'key_id' => $keyId,
                'user_id' => $data['user_id']
            ]);
            
            return [
                'success' => true,
                'message' => 'สร้าง API key สำเร็จ',
                'key_id' => $keyId,
                'api_key' => $apiKey // แสดงครั้งเดียว
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create API key', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ตรวจสอบ API key
     * 
     * @param string $apiKey
     * @return array|null
     */
    public function validate(string $apiKey): ?array
    {
        try {
            $keyHash = hash('sha256', $apiKey);
            
            $stmt = $this->db->prepare("
                SELECT * FROM api_keys
                WHERE key_hash = :key_hash 
                AND status = 'active'
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->bindValue(':key_hash', $keyHash, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return null;
            }
            
            // แปลง scopes จาก JSON
            $result['scopes'] = json_decode($result['scopes'], true);
            
            // อัปเดตการใช้งาน
            $this->updateUsage($result['id']);
            
            return $result;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to validate API key', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ตรวจสอบว่ามี scope หรือไม่
     * 
     * @param array $keyData
     * @param string $requiredScope
     * @return bool
     */
    public function hasScope(array $keyData, string $requiredScope): bool
    {
        $scopes = $keyData['scopes'] ?? [];
        
        // ถ้ามี * แสดงว่ามีสิทธิ์ทั้งหมด
        if (in_array('*', $scopes)) {
            return true;
        }
        
        return in_array($requiredScope, $scopes);
    }

    /**
     * ดึง API keys ทั้งหมดของผู้ใช้
     * 
     * @param int $userId
     * @return array
     */
    public function getUserKeys(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, user_id, name, scopes, expires_at, 
                       rate_limit, status, usage_count, last_used_at, created_at
                FROM api_keys
                WHERE user_id = :user_id
                ORDER BY created_at DESC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // แปลง scopes
            foreach ($keys as &$key) {
                $key['scopes'] = json_decode($key['scopes'], true);
            }
            
            return $keys;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user API keys', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * อัปเดตการใช้งาน
     * 
     * @param int $keyId
     * @return bool
     */
    private function updateUsage(int $keyId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE api_keys 
                SET usage_count = usage_count + 1,
                    last_used_at = NOW()
                WHERE id = :id
            ");
            $stmt->bindValue(':id', $keyId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update API key usage', [
                'key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ยกเลิก/ปิดการใช้งาน API key
     * 
     * @param int $keyId
     * @param int $userId
     * @return array
     */
    public function revoke(int $keyId, int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE api_keys 
                SET status = 'revoked'
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                ':id' => $keyId,
                ':user_id' => $userId
            ]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ API key'
                ];
            }
            
            $this->logger->info('API key revoked', [
                'key_id' => $keyId,
                'user_id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'ยกเลิก API key สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to revoke API key', [
                'key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบ API key
     * 
     * @param int $keyId
     * @param int $userId
     * @return array
     */
    public function delete(int $keyId, int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM api_keys 
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([
                ':id' => $keyId,
                ':user_id' => $userId
            ]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบ API key'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'ลบ API key สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete API key', [
                'key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ตรวจสอบ rate limit
     * 
     * @param int $keyId
     * @param int $rateLimit requests per minute
     * @return bool
     */
    public function checkRateLimit(int $keyId, int $rateLimit): bool
    {
        try {
            // นับจำนวน request ในช่วง 1 นาทีที่ผ่านมา
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM api_key_requests
                WHERE api_key_id = :key_id 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ");
            $stmt->bindValue(':key_id', $keyId, PDO::PARAM_INT);
            $stmt->execute();
            
            $count = (int) $stmt->fetchColumn();
            
            return $count < $rateLimit;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to check rate limit', [
                'key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * บันทึก request
     * 
     * @param int $keyId
     * @param string $endpoint
     * @param string $method
     * @param string|null $ipAddress
     * @return bool
     */
    public function logRequest(int $keyId, string $endpoint, string $method, ?string $ipAddress = null): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO api_key_requests (api_key_id, endpoint, method, ip_address, created_at)
                VALUES (:key_id, :endpoint, :method, :ip_address, NOW())
            ");
            
            return $stmt->execute([
                ':key_id' => $keyId,
                ':endpoint' => $endpoint,
                ':method' => $method,
                ':ip_address' => $ipAddress
            ]);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to log API request', [
                'key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * สร้าง API key แบบสุ่ม
     * 
     * @return string
     */
    private function generateKey(): string
    {
        // Format: sbz_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
        $prefix = 'sbz_live_';
        $randomBytes = random_bytes(32);
        $key = $prefix . bin2hex($randomBytes);
        
        return $key;
    }

    /**
     * ลบ API keys ที่หมดอายุ
     * 
     * @return int
     */
    public function deleteExpired(): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM api_keys 
                WHERE expires_at IS NOT NULL 
                AND expires_at < NOW()
                AND status != 'revoked'
            ");
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete expired API keys', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * สถิติการใช้งาน API key
     * 
     * @param int $keyId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getUsageStats(int $keyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            $sql = "
                SELECT 
                    DATE(created_at) as date,
                    endpoint,
                    method,
                    COUNT(*) as request_count
                FROM api_key_requests
                WHERE api_key_id = :key_id
            ";
            $params = [':key_id' => $keyId];
            
            if ($dateFrom) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $sql .= " GROUP BY date, endpoint, method ORDER BY date DESC, request_count DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get API key usage stats', [
                'key_id' => $keyId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
