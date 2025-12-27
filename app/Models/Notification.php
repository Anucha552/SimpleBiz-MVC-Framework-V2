<?php
/**
 * โมเดลการแจ้งเตือน (Notification)
 * 
 * จุดประสงค์: จัดการการแจ้งเตือนภายในระบบ
 * 
 * กฎทางธุรกิจ:
 * - รองรับหลายช่องทาง (in-app, email, sms)
 * - ติดตามสถานะการอ่าน
 * - จัดประเภทตามความสำคัญ
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Notification
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * สร้างการแจ้งเตือน
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (
                    user_id, type, channel, title, message,
                    link, priority, metadata, created_at
                ) VALUES (
                    :user_id, :type, :channel, :title, :message,
                    :link, :priority, :metadata, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':type' => $data['type'] ?? 'info',
                ':channel' => $data['channel'] ?? 'in-app',
                ':title' => $data['title'],
                ':message' => $data['message'],
                ':link' => $data['link'] ?? null,
                ':priority' => $data['priority'] ?? 'normal',
                ':metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
            ]);
            
            $notificationId = $this->db->lastInsertId();
            
            $this->logger->info('Notification created', [
                'notification_id' => $notificationId,
                'user_id' => $data['user_id']
            ]);
            
            return [
                'success' => true,
                'message' => 'สร้างการแจ้งเตือนสำเร็จ',
                'notification_id' => $notificationId
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create notification', [
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
     * ดึงการแจ้งเตือนของผู้ใช้
     * 
     * @param int $userId
     * @param bool $unreadOnly
     * @param int $limit
     * @return array
     */
    public function getUserNotifications(int $userId, bool $unreadOnly = false, int $limit = 20): array
    {
        try {
            $sql = "
                SELECT * FROM notifications
                WHERE user_id = :user_id
            ";
            
            if ($unreadOnly) {
                $sql .= " AND read_at IS NULL";
            }
            
            $sql .= " ORDER BY priority DESC, created_at DESC LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // แปลง metadata จาก JSON
            foreach ($notifications as &$notification) {
                if ($notification['metadata']) {
                    $notification['metadata'] = json_decode($notification['metadata'], true);
                }
            }
            
            return $notifications;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * นับจำนวนการแจ้งเตือนที่ยังไม่อ่าน
     * 
     * @param int $userId
     * @return int
     */
    public function countUnread(int $userId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM notifications
                WHERE user_id = :user_id AND read_at IS NULL
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to count unread notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * ทำเครื่องหมายว่าอ่านแล้ว
     * 
     * @param int $notificationId
     * @return array
     */
    public function markAsRead(int $notificationId): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET read_at = NOW()
                WHERE id = :id AND read_at IS NULL
            ");
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'ทำเครื่องหมายว่าอ่านแล้ว'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว
     * 
     * @param int $userId
     * @return array
     */
    public function markAllAsRead(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET read_at = NOW()
                WHERE user_id = :user_id AND read_at IS NULL
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "ทำเครื่องหมาย $count รายการว่าอ่านแล้ว",
                'count' => $count
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to mark all as read', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบการแจ้งเตือน
     * 
     * @param int $notificationId
     * @param int|null $userId ถ้าระบุจะตรวจสอบเจ้าของด้วย
     * @return array
     */
    public function delete(int $notificationId, ?int $userId = null): array
    {
        try {
            $sql = "DELETE FROM notifications WHERE id = :id";
            
            if ($userId !== null) {
                $sql .= " AND user_id = :user_id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $notificationId, PDO::PARAM_INT);
            
            if ($userId !== null) {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบการแจ้งเตือน'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'ลบการแจ้งเตือนสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบการแจ้งเตือนที่อ่านแล้ว
     * 
     * @param int $userId
     * @return array
     */
    public function deleteRead(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE user_id = :user_id AND read_at IS NOT NULL
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $count = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "ลบ $count รายการ",
                'count' => $count
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete read notifications', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบการแจ้งเตือนเก่า
     * 
     * @param int $daysOld
     * @return int
     */
    public function deleteOld(int $daysOld = 30): int
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
                AND read_at IS NOT NULL
            ");
            $stmt->bindValue(':days', $daysOld, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete old notifications', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * ส่งการแจ้งเตือนแบบ broadcast (หลายคน)
     * 
     * @param array $userIds
     * @param array $data
     * @return array
     */
    public function broadcast(array $userIds, array $data): array
    {
        try {
            $this->db->beginTransaction();
            
            $created = 0;
            foreach ($userIds as $userId) {
                $data['user_id'] = $userId;
                $result = $this->create($data);
                
                if ($result['success']) {
                    $created++;
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => "ส่งการแจ้งเตือนสำเร็จ $created รายการ",
                'count' => $created
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to broadcast notifications', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * การแจ้งเตือนสำหรับคำสั่งซื้อใหม่
     * 
     * @param int $userId
     * @param int $orderId
     * @param string $orderNumber
     * @return array
     */
    public function notifyNewOrder(int $userId, int $orderId, string $orderNumber): array
    {
        return $this->create([
            'user_id' => $userId,
            'type' => 'order',
            'title' => 'คำสั่งซื้อใหม่',
            'message' => "คำสั่งซื้อ $orderNumber ของคุณได้รับการยืนยันแล้ว",
            'link' => "/orders/$orderId",
            'priority' => 'high',
            'metadata' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ]
        ]);
    }

    /**
     * การแจ้งเตือนการเปลี่ยนสถานะคำสั่งซื้อ
     * 
     * @param int $userId
     * @param int $orderId
     * @param string $status
     * @return array
     */
    public function notifyOrderStatusChange(int $userId, int $orderId, string $status): array
    {
        $statusMessages = [
            'processing' => 'กำลังดำเนินการ',
            'shipped' => 'จัดส่งแล้ว',
            'delivered' => 'ส่งสำเร็จ',
            'cancelled' => 'ยกเลิก'
        ];
        
        return $this->create([
            'user_id' => $userId,
            'type' => 'order',
            'title' => 'อัปเดตสถานะคำสั่งซื้อ',
            'message' => "คำสั่งซื้อของคุณ: " . ($statusMessages[$status] ?? $status),
            'link' => "/orders/$orderId",
            'priority' => 'normal',
            'metadata' => [
                'order_id' => $orderId,
                'status' => $status
            ]
        ]);
    }
}
