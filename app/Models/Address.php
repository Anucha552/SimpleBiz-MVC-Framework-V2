<?php
/**
 * โมเดลที่อยู่
 * 
 * จุดประสงค์: จัดการที่อยู่สำหรับจัดส่ง
 * 
 * กฎทางธุรกิจ:
 * - ผู้ใช้มีได้หลายที่อยู่
 * - มีที่อยู่เริ่มต้น (default) 1 ที่อยู่
 * - รองรับที่อยู่จัดส่งและที่อยู่เรียกเก็บเงิน
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Address
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงที่อยู่ทั้งหมดของผู้ใช้
     * 
     * @param int $userId
     * @return array
     */
    public function getUserAddresses(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM addresses
                WHERE user_id = :user_id
                ORDER BY is_default DESC, created_at DESC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user addresses', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ดึงที่อยู่เริ่มต้น
     * 
     * @param int $userId
     * @return array|null
     */
    public function getDefaultAddress(int $userId): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM addresses
                WHERE user_id = :user_id AND is_default = 1
                LIMIT 1
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch default address', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ดึงที่อยู่ตาม ID
     * 
     * @param int $addressId
     * @param int|null $userId ถ้าระบุจะตรวจสอบเจ้าของด้วย
     * @return array|null
     */
    public function find(int $addressId, ?int $userId = null): ?array
    {
        try {
            $sql = "SELECT * FROM addresses WHERE id = :id";
            
            if ($userId !== null) {
                $sql .= " AND user_id = :user_id";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $addressId, PDO::PARAM_INT);
            
            if ($userId !== null) {
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find address', [
                'address_id' => $addressId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * สร้างที่อยู่ใหม่
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $this->db->beginTransaction();
            
            // ถ้าเป็นที่อยู่เริ่มต้น ให้ยกเลิกที่อยู่เริ่มต้นเดิม
            if (isset($data['is_default']) && $data['is_default']) {
                $this->removeDefaultFlag($data['user_id']);
            }

            $stmt = $this->db->prepare("
                INSERT INTO addresses (
                    user_id, label, full_name, phone,
                    address_line1, address_line2, 
                    district, city, province, postal_code, country,
                    is_default, type, created_at
                ) VALUES (
                    :user_id, :label, :full_name, :phone,
                    :address_line1, :address_line2,
                    :district, :city, :province, :postal_code, :country,
                    :is_default, :type, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':label' => $data['label'] ?? 'บ้าน',
                ':full_name' => $data['full_name'],
                ':phone' => $data['phone'],
                ':address_line1' => $data['address_line1'],
                ':address_line2' => $data['address_line2'] ?? null,
                ':district' => $data['district'] ?? null,
                ':city' => $data['city'],
                ':province' => $data['province'],
                ':postal_code' => $data['postal_code'],
                ':country' => $data['country'] ?? 'Thailand',
                ':is_default' => $data['is_default'] ?? 0,
                ':type' => $data['type'] ?? 'both' // shipping, billing, both
            ]);
            
            $addressId = $this->db->lastInsertId();
            
            $this->db->commit();
            
            $this->logger->info('Address created', [
                'address_id' => $addressId,
                'user_id' => $data['user_id']
            ]);
            
            return [
                'success' => true,
                'message' => 'เพิ่มที่อยู่สำเร็จ',
                'address_id' => $addressId
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to create address', [
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
     * อัปเดตที่อยู่
     * 
     * @param int $addressId
     * @param array $data
     * @param int $userId
     * @return array
     */
    public function update(int $addressId, array $data, int $userId): array
    {
        try {
            $this->db->beginTransaction();
            
            // ถ้าเป็นที่อยู่เริ่มต้น ให้ยกเลิกที่อยู่เริ่มต้นเดิม
            if (isset($data['is_default']) && $data['is_default']) {
                $this->removeDefaultFlag($userId);
            }

            $fields = [];
            $params = [':id' => $addressId, ':user_id' => $userId];
            
            $allowedFields = [
                'label', 'full_name', 'phone',
                'address_line1', 'address_line2',
                'district', 'city', 'province', 'postal_code', 'country',
                'is_default', 'type'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'ไม่มีข้อมูลที่ต้องอัปเดต'
                ];
            }
            
            $fields[] = "updated_at = NOW()";
            
            $sql = "UPDATE addresses SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'ไม่พบที่อยู่หรือคุณไม่มีสิทธิ์แก้ไข'
                ];
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'อัปเดตที่อยู่สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to update address', [
                'address_id' => $addressId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบที่อยู่
     * 
     * @param int $addressId
     * @param int $userId
     * @return array
     */
    public function delete(int $addressId, int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM addresses WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([':id' => $addressId, ':user_id' => $userId]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบที่อยู่หรือคุณไม่มีสิทธิ์ลบ'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'ลบที่อยู่สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete address', [
                'address_id' => $addressId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ตั้งเป็นที่อยู่เริ่มต้น
     * 
     * @param int $addressId
     * @param int $userId
     * @return array
     */
    public function setAsDefault(int $addressId, int $userId): array
    {
        try {
            $this->db->beginTransaction();
            
            // ยกเลิกที่อยู่เริ่มต้นเดิม
            $this->removeDefaultFlag($userId);
            
            // ตั้งที่อยู่ใหม่เป็นเริ่มต้น
            $stmt = $this->db->prepare("
                UPDATE addresses 
                SET is_default = 1
                WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([':id' => $addressId, ':user_id' => $userId]);
            
            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'ไม่พบที่อยู่'
                ];
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'ตั้งเป็นที่อยู่เริ่มต้นสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->db->rollBack();
            $this->logger->error('Failed to set default address', [
                'address_id' => $addressId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ยกเลิกสถานะ default ของที่อยู่ทั้งหมด
     * 
     * @param int $userId
     * @return bool
     */
    private function removeDefaultFlag(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE addresses SET is_default = 0 WHERE user_id = :user_id
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to remove default flag', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * นับจำนวนที่อยู่ของผู้ใช้
     * 
     * @param int $userId
     * @return int
     */
    public function countUserAddresses(int $userId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM addresses WHERE user_id = :user_id
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to count addresses', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * จัดรูปแบบที่อยู่เป็นข้อความ
     * 
     * @param array $address
     * @param bool $includePhone
     * @return string
     */
    public function formatAddress(array $address, bool $includePhone = false): string
    {
        $formatted = $address['full_name'] . "\n";
        
        if ($includePhone) {
            $formatted .= $address['phone'] . "\n";
        }
        
        $formatted .= $address['address_line1'];
        
        if (!empty($address['address_line2'])) {
            $formatted .= "\n" . $address['address_line2'];
        }
        
        $formatted .= "\n";
        
        if (!empty($address['district'])) {
            $formatted .= $address['district'] . " ";
        }
        
        $formatted .= $address['city'] . " " . $address['province'] . " " . $address['postal_code'];
        
        if ($address['country'] !== 'Thailand') {
            $formatted .= "\n" . $address['country'];
        }
        
        return $formatted;
    }
}
