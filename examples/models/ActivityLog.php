<?php
/**
 * โมเดลบันทึกกิจกรรม (Activity Log)
 * 
 * จุดประสงค์: บันทึกการกระทำของผู้ใช้เพื่อ audit trail และ security
 * 
 * กฎทางธุรกิจ:
 * - บันทึกทุกการกระทำสำคัญ (login, create, update, delete)
 * - เก็บ IP address และ user agent
 * - ใช้สำหรับ debugging และ security investigation
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class ActivityLog
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * บันทึกกิจกรรม
     * 
     * @param array $data
     * @return bool
     */
    public function log(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_logs (
                    user_id, action, entity_type, entity_id,
                    description, ip_address, user_agent,
                    metadata, created_at
                ) VALUES (
                    :user_id, :action, :entity_type, :entity_id,
                    :description, :ip_address, :user_agent,
                    :metadata, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'] ?? null,
                ':action' => $data['action'],
                ':entity_type' => $data['entity_type'] ?? null,
                ':entity_id' => $data['entity_id'] ?? null,
                ':description' => $data['description'] ?? null,
                ':ip_address' => $data['ip_address'] ?? $this->getClientIp(),
                ':user_agent' => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
                ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
            ]);
            
            return true;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to log activity', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * ดึงกิจกรรมทั้งหมด
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $sql = "
                SELECT al.*, u.username, u.email 
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1
            ";
            $params = [];
            
            // Filter by user
            if (isset($filters['user_id'])) {
                $sql .= " AND al.user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            // Filter by action
            if (isset($filters['action'])) {
                $sql .= " AND al.action = :action";
                $params[':action'] = $filters['action'];
            }
            
            // Filter by entity
            if (isset($filters['entity_type'])) {
                $sql .= " AND al.entity_type = :entity_type";
                $params[':entity_type'] = $filters['entity_type'];
            }
            
            if (isset($filters['entity_id'])) {
                $sql .= " AND al.entity_id = :entity_id";
                $params[':entity_id'] = $filters['entity_id'];
            }
            
            // Filter by date range
            if (isset($filters['date_from'])) {
                $sql .= " AND al.created_at >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to'])) {
                $sql .= " AND al.created_at <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            // Filter by IP
            if (isset($filters['ip_address'])) {
                $sql .= " AND al.ip_address = :ip_address";
                $params[':ip_address'] = $filters['ip_address'];
            }
            
            $sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // แปลง metadata จาก JSON
            foreach ($logs as &$log) {
                if ($log['metadata']) {
                    $log['metadata'] = json_decode($log['metadata'], true);
                }
            }
            
            return $logs;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch activity logs', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return [];
        }
    }

    /**
     * ดึงกิจกรรมของผู้ใช้
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getUserActivities(int $userId, int $limit = 20): array
    {
        return $this->getAll(['user_id' => $userId], $limit);
    }

    /**
     * ดึงกิจกรรมของ entity
     * 
     * @param string $entityType
     * @param int $entityId
     * @param int $limit
     * @return array
     */
    public function getEntityActivities(string $entityType, int $entityId, int $limit = 20): array
    {
        return $this->getAll([
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ], $limit);
    }

    /**
     * นับจำนวนกิจกรรม
     * 
     * @param array $filters
     * @return int
     */
    public function count(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM activity_logs WHERE 1=1";
            $params = [];
            
            if (isset($filters['user_id'])) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            if (isset($filters['action'])) {
                $sql .= " AND action = :action";
                $params[':action'] = $filters['action'];
            }
            
            if (isset($filters['entity_type'])) {
                $sql .= " AND entity_type = :entity_type";
                $params[':entity_type'] = $filters['entity_type'];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to count activities', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return 0;
        }
    }

    /**
     * ลบกิจกรรมเก่า
     * 
     * @param int $daysOld จำนวนวันที่เก่ากว่า
     * @return int จำนวนที่ลบ
     */
    public function deleteOld(int $daysOld = 90): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM activity_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->bindValue(':days', $daysOld, PDO::PARAM_INT);
            $stmt->execute();
            
            $deleted = $stmt->rowCount();
            
            $this->logger->info('Old activity logs deleted', [
                'days_old' => $daysOld,
                'deleted_count' => $deleted
            ]);
            
            return $deleted;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete old logs', [
                'error' => $e->getMessage(),
                'days_old' => $daysOld
            ]);
            return 0;
        }
    }

    /**
     * สถิติกิจกรรมตาม action
     * 
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getActionStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        try {
            $sql = "
                SELECT action, COUNT(*) as count
                FROM activity_logs
                WHERE 1=1
            ";
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND created_at <= :date_to";
                $params[':date_to'] = $dateTo;
            }
            
            $sql .= " GROUP BY action ORDER BY count DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get action stats', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ผู้ใช้ที่มีกิจกรรมมากที่สุด
     * 
     * @param int $limit
     * @param string|null $dateFrom
     * @return array
     */
    public function getMostActiveUsers(int $limit = 10, ?string $dateFrom = null): array
    {
        try {
            $sql = "
                SELECT u.id, u.username, u.email, COUNT(*) as activity_count
                FROM activity_logs al
                INNER JOIN users u ON al.user_id = u.id
                WHERE 1=1
            ";
            $params = [];
            
            if ($dateFrom) {
                $sql .= " AND al.created_at >= :date_from";
                $params[':date_from'] = $dateFrom;
            }
            
            $sql .= " GROUP BY u.id ORDER BY activity_count DESC LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get most active users', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ดึง IP address ของ client
     * 
     * @return string|null
     */
    private function getClientIp(): ?string
    {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // ถ้ามีหลาย IP (จาก proxy) ใช้ตัวแรก
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                
                return trim($ip);
            }
        }
        
        return null;
    }

    /**
     * บันทึกการ login
     * 
     * @param int $userId
     * @param bool $success
     * @return bool
     */
    public function logLogin(int $userId, bool $success = true): bool
    {
        return $this->log([
            'user_id' => $userId,
            'action' => $success ? 'login_success' : 'login_failed',
            'description' => $success ? 'เข้าสู่ระบบสำเร็จ' : 'เข้าสู่ระบบไม่สำเร็จ'
        ]);
    }

    /**
     * บันทึกการ logout
     * 
     * @param int $userId
     * @return bool
     */
    public function logLogout(int $userId): bool
    {
        return $this->log([
            'user_id' => $userId,
            'action' => 'logout',
            'description' => 'ออกจากระบบ'
        ]);
    }
}
