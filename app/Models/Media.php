<?php
/**
 * โมเดลสื่อ/ไฟล์
 * 
 * จุดประสงค์: จัดการไฟล์ที่อัปโหลด (รูปภาพ, เอกสาร, วิดีโอ)
 * 
 * กฎทางธุรกิจ:
 * - เก็บ metadata ของไฟล์
 * - รองรับหลายประเภทไฟล์
 * - ตรวจสอบขนาดและประเภท
 * - สร้าง thumbnails อัตโนมัติสำหรับรูปภาพ
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Media
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงสื่อทั้งหมด
     * 
     * @param array $filters ['type' => 'image', 'user_id' => 1]
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM media WHERE 1=1";
            $params = [];
            
            if (isset($filters['type'])) {
                $sql .= " AND mime_type LIKE :type";
                $params[':type'] = $filters['type'] . '%';
            }
            
            if (isset($filters['user_id'])) {
                $sql .= " AND user_id = :user_id";
                $params[':user_id'] = $filters['user_id'];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch media', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ดึงสื่อตาม ID
     * 
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM media WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find media', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * บันทึกข้อมูลไฟล์ที่อัปโหลด
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO media (
                    user_id, filename, original_filename, mime_type, 
                    file_size, file_path, thumbnail_path, 
                    title, alt_text, description, created_at
                ) VALUES (
                    :user_id, :filename, :original_filename, :mime_type,
                    :file_size, :file_path, :thumbnail_path,
                    :title, :alt_text, :description, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'] ?? null,
                ':filename' => $data['filename'],
                ':original_filename' => $data['original_filename'],
                ':mime_type' => $data['mime_type'],
                ':file_size' => $data['file_size'],
                ':file_path' => $data['file_path'],
                ':thumbnail_path' => $data['thumbnail_path'] ?? null,
                ':title' => $data['title'] ?? $data['original_filename'],
                ':alt_text' => $data['alt_text'] ?? null,
                ':description' => $data['description'] ?? null
            ]);
            
            $mediaId = $this->db->lastInsertId();
            
            $this->logger->info('Media created', ['media_id' => $mediaId]);
            
            return [
                'success' => true,
                'message' => 'อัปโหลดไฟล์สำเร็จ',
                'media_id' => $mediaId,
                'media' => $this->find($mediaId)
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create media', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูลไฟล์'
            ];
        }
    }

    /**
     * อัปเดตข้อมูลสื่อ
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
            $fields = [];
            $params = [':id' => $id];
            
            foreach (['title', 'alt_text', 'description'] as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return [
                    'success' => false,
                    'message' => 'ไม่มีข้อมูลที่ต้องอัปเดต'
                ];
            }
            
            $fields[] = "updated_at = NOW()";
            
            $sql = "UPDATE media SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $this->logger->info('Media updated', ['media_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'อัปเดตข้อมูลสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update media', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบสื่อ (และไฟล์จริง)
     * 
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        try {
            // ดึงข้อมูลไฟล์ก่อน
            $media = $this->find($id);
            
            if (!$media) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบไฟล์'
                ];
            }
            
            // ลบจากฐานข้อมูล
            $stmt = $this->db->prepare("DELETE FROM media WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // ลบไฟล์จริง
            $this->deletePhysicalFile($media['file_path']);
            
            if ($media['thumbnail_path']) {
                $this->deletePhysicalFile($media['thumbnail_path']);
            }
            
            $this->logger->info('Media deleted', ['media_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'ลบไฟล์สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete media', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบไฟล์จากระบบ
     * 
     * @param string $path
     * @return bool
     */
    private function deletePhysicalFile(string $path): bool
    {
        $fullPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $path;
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }

    /**
     * นับจำนวนสื่อตามประเภท
     * 
     * @param string|null $mimeType
     * @return int
     */
    public function count(?string $mimeType = null): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM media";
            
            if ($mimeType) {
                $sql .= " WHERE mime_type LIKE :mime_type";
            }
            
            $stmt = $this->db->prepare($sql);
            
            if ($mimeType) {
                $stmt->bindValue(':mime_type', $mimeType . '%', PDO::PARAM_STR);
            }
            
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to count media', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * คำนวณพื้นที่ใช้งานทั้งหมด
     * 
     * @return int bytes
     */
    public function getTotalStorageUsed(): int
    {
        try {
            $stmt = $this->db->query("SELECT SUM(file_size) FROM media");
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get total storage', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * ดึงสื่อที่เกี่ยวข้องกับ entity
     * 
     * @param string $entityType (product, post, user, etc.)
     * @param int $entityId
     * @return array
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT m.* FROM media m
                INNER JOIN media_relations mr ON m.id = mr.media_id
                WHERE mr.entity_type = :entity_type AND mr.entity_id = :entity_id
                ORDER BY mr.sort_order ASC, m.created_at DESC
            ");
            $stmt->execute([
                ':entity_type' => $entityType,
                ':entity_id' => $entityId
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch media by entity', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * แนบสื่อกับ entity
     * 
     * @param int $mediaId
     * @param string $entityType
     * @param int $entityId
     * @param int $sortOrder
     * @return array
     */
    public function attachToEntity(int $mediaId, string $entityType, int $entityId, int $sortOrder = 0): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO media_relations (media_id, entity_type, entity_id, sort_order, created_at)
                VALUES (:media_id, :entity_type, :entity_id, :sort_order, NOW())
                ON DUPLICATE KEY UPDATE sort_order = :sort_order
            ");
            
            $stmt->execute([
                ':media_id' => $mediaId,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':sort_order' => $sortOrder
            ]);
            
            return [
                'success' => true,
                'message' => 'แนบไฟล์สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to attach media', [
                'media_id' => $mediaId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }
}
