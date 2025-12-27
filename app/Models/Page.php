<?php
/**
 * โมเดลหน้าเนื้อหา (CMS)
 * 
 * จุดประสงค์: จัดการหน้าเนื้อหาแบบ CMS (Content Management System)
 * 
 * กฎทางธุรกิจ:
 * - slug ต้องไม่ซ้ำ (สำหรับ SEO-friendly URLs)
 * - รองรับ templates หลากหลาย
 * - จัดการ meta tags สำหรับ SEO
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Page
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงหน้าทั้งหมด
     * 
     * @param bool $publishedOnly
     * @return array
     */
    public function getAll(bool $publishedOnly = true): array
    {
        try {
            $sql = "SELECT * FROM pages";
            
            if ($publishedOnly) {
                $sql .= " WHERE status = 'published'";
            }
            
            $sql .= " ORDER BY sort_order ASC, title ASC";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch pages', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ดึงหน้าตาม ID
     * 
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM pages WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find page', ['id' => $id, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ดึงหน้าตาม slug
     * 
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM pages 
                WHERE slug = :slug AND status = 'published'
            ");
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // อัปเดต view count
                $this->incrementViews($result['id']);
            }
            
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find page by slug', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * สร้างหน้าใหม่
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            // ตรวจสอบ slug ซ้ำ
            if ($this->slugExists($data['slug'])) {
                return [
                    'success' => false,
                    'message' => 'Slug นี้ถูกใช้งานแล้ว'
                ];
            }

            $stmt = $this->db->prepare("
                INSERT INTO pages (
                    title, slug, content, excerpt, template,
                    meta_title, meta_description, meta_keywords,
                    featured_image, author_id, status, sort_order, created_at
                ) VALUES (
                    :title, :slug, :content, :excerpt, :template,
                    :meta_title, :meta_description, :meta_keywords,
                    :featured_image, :author_id, :status, :sort_order, NOW()
                )
            ");
            
            $stmt->execute([
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':content' => $data['content'],
                ':excerpt' => $data['excerpt'] ?? null,
                ':template' => $data['template'] ?? 'default',
                ':meta_title' => $data['meta_title'] ?? $data['title'],
                ':meta_description' => $data['meta_description'] ?? null,
                ':meta_keywords' => $data['meta_keywords'] ?? null,
                ':featured_image' => $data['featured_image'] ?? null,
                ':author_id' => $data['author_id'],
                ':status' => $data['status'] ?? 'draft',
                ':sort_order' => $data['sort_order'] ?? 0
            ]);
            
            $pageId = $this->db->lastInsertId();
            
            $this->logger->info('Page created', ['page_id' => $pageId]);
            
            return [
                'success' => true,
                'message' => 'สร้างหน้าสำเร็จ',
                'page_id' => $pageId
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create page', [
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
     * อัปเดตหน้า
     * 
     * @param int $id
     * @param array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
            // ตรวจสอบ slug ซ้ำ (ยกเว้นตัวเอง)
            if (isset($data['slug']) && $this->slugExists($data['slug'], $id)) {
                return [
                    'success' => false,
                    'message' => 'Slug นี้ถูกใช้งานแล้ว'
                ];
            }

            $fields = [];
            $params = [':id' => $id];
            
            $allowedFields = [
                'title', 'slug', 'content', 'excerpt', 'template',
                'meta_title', 'meta_description', 'meta_keywords',
                'featured_image', 'status', 'sort_order'
            ];
            
            foreach ($allowedFields as $field) {
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
            
            // ถ้าเผยแพร่ ให้อัปเดต published_at
            if (isset($data['status']) && $data['status'] === 'published') {
                $fields[] = "published_at = NOW()";
            }
            
            $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $this->logger->info('Page updated', ['page_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'อัปเดตหน้าสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update page', [
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
     * ลบหน้า
     * 
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM pages WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบหน้า'
                ];
            }
            
            $this->logger->info('Page deleted', ['page_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'ลบหน้าสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete page', [
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
     * ตรวจสอบว่า slug มีอยู่แล้วหรือไม่
     * 
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM pages WHERE slug = :slug";
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        
        if ($excludeId !== null) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * เพิ่มจำนวนการดู
     * 
     * @param int $id
     * @return bool
     */
    private function incrementViews(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE pages SET view_count = view_count + 1 WHERE id = :id
            ");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to increment page views', [
                'page_id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ค้นหาหน้า
     * 
     * @param string $keyword
     * @return array
     */
    public function search(string $keyword): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM pages
                WHERE status = 'published'
                AND (title LIKE :keyword OR content LIKE :keyword)
                ORDER BY title ASC
            ");
            $stmt->bindValue(':keyword', "%$keyword%", PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to search pages', [
                'keyword' => $keyword,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ดึงหน้าที่ได้รับความนิยม
     * 
     * @param int $limit
     * @return array
     */
    public function getPopular(int $limit = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM pages
                WHERE status = 'published'
                ORDER BY view_count DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch popular pages', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ดึงหน้าล่าสุด
     * 
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM pages
                WHERE status = 'published'
                ORDER BY published_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch recent pages', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
