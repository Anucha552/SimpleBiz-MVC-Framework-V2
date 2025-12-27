<?php
/**
 * โมเดลรีวิว/ความคิดเห็น
 * 
 * จุดประสงค์: จัดการรีวิวสินค้าและคะแนน
 * 
 * กฎทางธุรกิจ:
 * - แต่ละผู้ใช้รีวิวสินค้าได้ 1 ครั้ง
 * - ต้องซื้อสินค้าแล้วจึงรีวิวได้
 * - คะแนนอยู่ในช่วง 1-5
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class Review
{
    private PDO $db;
    private Logger $logger;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = new Logger();
    }

    /**
     * สร้างรีวิวใหม่
     * 
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        try {
            // ตรวจสอบว่ารีวิวแล้วหรือยัง
            if ($this->hasReviewed($data['user_id'], $data['product_id'])) {
                return [
                    'success' => false,
                    'message' => 'คุณรีวิวสินค้านี้แล้ว'
                ];
            }

            // ตรวจสอบว่าซื้อสินค้าแล้วหรือยัง (optional)
            if (isset($data['require_purchase']) && $data['require_purchase']) {
                if (!$this->hasPurchased($data['user_id'], $data['product_id'])) {
                    return [
                        'success' => false,
                        'message' => 'คุณต้องซื้อสินค้านี้ก่อนจึงจะรีวิวได้'
                    ];
                }
            }

            $stmt = $this->db->prepare("
                INSERT INTO reviews (
                    user_id, product_id, rating, title, comment,
                    status, created_at
                ) VALUES (
                    :user_id, :product_id, :rating, :title, :comment,
                    :status, NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':product_id' => $data['product_id'],
                ':rating' => $data['rating'],
                ':title' => $data['title'] ?? null,
                ':comment' => $data['comment'],
                ':status' => $data['status'] ?? 'pending' // pending, approved, rejected
            ]);
            
            $reviewId = $this->db->lastInsertId();
            
            // อัปเดตคะแนนเฉลี่ยของสินค้า
            $this->updateProductRating($data['product_id']);
            
            $this->logger->info('Review created', [
                'review_id' => $reviewId,
                'product_id' => $data['product_id']
            ]);
            
            return [
                'success' => true,
                'message' => 'ส่งรีวิวสำเร็จ',
                'review_id' => $reviewId
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to create review', [
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
     * ดึงรีวิวของสินค้า
     * 
     * @param int $productId
     * @param bool $approvedOnly
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProductReviews(int $productId, bool $approvedOnly = true, int $limit = 20, int $offset = 0): array
    {
        try {
            $sql = "
                SELECT r.*, u.username, u.email
                FROM reviews r
                INNER JOIN users u ON r.user_id = u.id
                WHERE r.product_id = :product_id
            ";
            
            if ($approvedOnly) {
                $sql .= " AND r.status = 'approved'";
            }
            
            $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch product reviews', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ดึงรีวิวของผู้ใช้
     * 
     * @param int $userId
     * @return array
     */
    public function getUserReviews(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, p.name as product_name
                FROM reviews r
                INNER JOIN products p ON r.product_id = p.id
                WHERE r.user_id = :user_id
                ORDER BY r.created_at DESC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error('Failed to fetch user reviews', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * อัปเดตรีวิว
     * 
     * @param int $reviewId
     * @param array $data
     * @param int $userId
     * @return array
     */
    public function update(int $reviewId, array $data, int $userId): array
    {
        try {
            $fields = [];
            $params = [':id' => $reviewId, ':user_id' => $userId];
            
            foreach (['rating', 'title', 'comment'] as $field) {
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
            
            $sql = "UPDATE reviews SET " . implode(', ', $fields) . " WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบรีวิวหรือคุณไม่มีสิทธิ์แก้ไข'
                ];
            }
            
            // อัปเดตคะแนนเฉลี่ย
            $review = $this->find($reviewId);
            if ($review) {
                $this->updateProductRating($review['product_id']);
            }
            
            return [
                'success' => true,
                'message' => 'อัปเดตรีวิวสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update review', [
                'review_id' => $reviewId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ลบรีวิว
     * 
     * @param int $reviewId
     * @param int $userId
     * @return array
     */
    public function delete(int $reviewId, int $userId): array
    {
        try {
            // ดึงข้อมูลก่อนลบ
            $review = $this->find($reviewId);
            
            if (!$review) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบรีวิว'
                ];
            }

            $stmt = $this->db->prepare("
                DELETE FROM reviews WHERE id = :id AND user_id = :user_id
            ");
            $stmt->execute([':id' => $reviewId, ':user_id' => $userId]);
            
            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบรีวิวนี้'
                ];
            }
            
            // อัปเดตคะแนนเฉลี่ย
            $this->updateProductRating($review['product_id']);
            
            return [
                'success' => true,
                'message' => 'ลบรีวิวสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to delete review', [
                'review_id' => $reviewId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * อนุมัติรีวิว (สำหรับ admin)
     * 
     * @param int $reviewId
     * @return array
     */
    public function approve(int $reviewId): array
    {
        return $this->updateStatus($reviewId, 'approved');
    }

    /**
     * ปฏิเสธรีวิว (สำหรับ admin)
     * 
     * @param int $reviewId
     * @return array
     */
    public function reject(int $reviewId): array
    {
        return $this->updateStatus($reviewId, 'rejected');
    }

    /**
     * อัปเดตสถานะรีวิว
     * 
     * @param int $reviewId
     * @param string $status
     * @return array
     */
    private function updateStatus(int $reviewId, string $status): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE reviews SET status = :status WHERE id = :id
            ");
            $stmt->execute([':status' => $status, ':id' => $reviewId]);
            
            // อัปเดตคะแนนเฉลี่ย
            $review = $this->find($reviewId);
            if ($review) {
                $this->updateProductRating($review['product_id']);
            }
            
            return [
                'success' => true,
                'message' => 'อัปเดตสถานะสำเร็จ'
            ];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update review status', [
                'review_id' => $reviewId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด'
            ];
        }
    }

    /**
     * ดึงรีวิวตาม ID
     * 
     * @param int $reviewId
     * @return array|null
     */
    public function find(int $reviewId): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM reviews WHERE id = :id");
            $stmt->bindValue(':id', $reviewId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            $this->logger->error('Failed to find review', [
                'review_id' => $reviewId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ตรวจสอบว่าผู้ใช้รีวิวสินค้านี้แล้วหรือยัง
     * 
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    private function hasReviewed(int $userId, int $productId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM reviews 
                WHERE user_id = :user_id AND product_id = :product_id
            ");
            $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * ตรวจสอบว่าผู้ใช้ซื้อสินค้านี้แล้วหรือยัง
     * 
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    private function hasPurchased(int $userId, int $productId): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = :user_id 
                AND oi.product_id = :product_id
                AND o.status IN ('completed', 'delivered')
            ");
            $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * อัปเดตคะแนนเฉลี่ยของสินค้า
     * 
     * @param int $productId
     * @return bool
     */
    private function updateProductRating(int $productId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE products SET
                    rating = (
                        SELECT AVG(rating) FROM reviews 
                        WHERE product_id = :product_id AND status = 'approved'
                    ),
                    review_count = (
                        SELECT COUNT(*) FROM reviews 
                        WHERE product_id = :product_id AND status = 'approved'
                    )
                WHERE id = :product_id
            ");
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->error('Failed to update product rating', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * สถิติรีวิว
     * 
     * @param int $productId
     * @return array
     */
    public function getStats(int $productId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews
                WHERE product_id = :product_id AND status = 'approved'
            ");
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            $this->logger->error('Failed to get review stats', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
