<?php
/**
 * โมเดลหมวดหมู่
 * 
 * จุดประสงค์: จัดการหมวดหมู่สินค้า/เนื้อหา แบบ hierarchical (ซ้อนกันได้)
 * 
 * กฎทางธุรกิจ:
 * - slug ต้องไม่ซ้ำ (สำหรับ SEO-friendly URLs)
 * - รองรับ nested categories (parent-child relationship)
 * - สามารถเปิด/ปิดการใช้งานได้
 * - เรียงตามลำดับที่กำหนด
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Category
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * ดึงหมวดหมู่ทั้งหมดที่ใช้งานได้
     * 
     * @param int|null $parentId ดึงเฉพาะ child ของ parent นี้ (null = รูททั้งหมด)
     * @return array
     */
    public function getAll(?int $parentId = null): array
    {
        try {
            $sql = "SELECT * FROM categories WHERE status = 'active'";
            
            if ($parentId === null) {
                $sql .= " AND parent_id IS NULL";
            } else {
                $sql .= " AND parent_id = :parent_id";
            }
            
            $sql .= " ORDER BY sort_order ASC, name ASC";
            
            $stmt = $this->db->prepare($sql);
            
            if ($parentId !== null) {
                $stmt->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch categories', [
                'error' => $e->getMessage(),
                'parent_id' => $parentId
            ]);
            return [];
        }
    }

    /**
     * ดึงหมวดหมู่แบบ tree structure
     * 
     * @return array
     */
    public function getTree(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM categories 
                WHERE status = 'active'
                ORDER BY parent_id, sort_order ASC, name ASC
            ");
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->buildTree($categories);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch category tree', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * สร้าง tree structure จาก flat array
     * 
     * @param array $categories
     * @param int|null $parentId
     * @return array
     */
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $branch = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        
        return $branch;
    }

    /**
     * ดึงหมวดหมู่ตาม ID
     * 
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM categories WHERE id = :id
            ");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find category', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ดึงหมวดหมู่ตาม slug
     * 
     * @param string $slug
     * @return array|null
     */
    public function findBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM categories WHERE slug = :slug AND status = 'active'
            ");
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find category by slug', [
                'slug' => $slug,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * สร้างหมวดหมู่ใหม่
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
                INSERT INTO categories (name, slug, description, parent_id, sort_order, status, created_at)
                VALUES (:name, :slug, :description, :parent_id, :sort_order, :status, NOW())
            ");
            
            $stmt->execute([
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':parent_id' => $data['parent_id'] ?? null,
                ':sort_order' => $data['sort_order'] ?? 0,
                ':status' => $data['status'] ?? 'active'
            ]);
            
            $categoryId = $this->db->lastInsertId();
            
            $this->logger->info('Category created', ['category_id' => $categoryId]);
            
            return [
                'success' => true,
                'message' => 'สร้างหมวดหมู่สำเร็จ',
                'category_id' => $categoryId
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create category', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างหมวดหมู่'
            ];
        }
    }

    /**
     * อัปเดตหมวดหมู่
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
            
            foreach (['name', 'slug', 'description', 'parent_id', 'sort_order', 'status'] as $field) {
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
            
            $sql = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $this->logger->info('Category updated', ['category_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'อัปเดตหมวดหมู่สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update category', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตหมวดหมู่'
            ];
        }
    }

    /**
     * ลบหมวดหมู่ (soft delete)
     * 
     * @param int $id
     * @return array
     */
    public function delete(int $id): array
    {
        try {
            // ตรวจสอบว่ามี child categories หรือไม่
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่สามารถลบหมวดหมู่ที่มีหมวดหมู่ย่อยได้'
                ];
            }

            $stmt = $this->db->prepare("
                UPDATE categories 
                SET status = 'inactive', deleted_at = NOW()
                WHERE id = :id
            ");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->logger->info('Category deleted', ['category_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'ลบหมวดหมู่สำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete category', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบหมวดหมู่'
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
        $sql = "SELECT COUNT(*) FROM categories WHERE slug = :slug";
        
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
     * นับจำนวนสินค้าในหมวดหมู่
     * 
     * @param int $categoryId
     * @return int
     */
    public function countProducts(int $categoryId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM products 
                WHERE category_id = :category_id AND status = 'active'
            ");
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to count products', [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
