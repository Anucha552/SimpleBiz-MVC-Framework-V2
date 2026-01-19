# API Reference (v1)

คู่มืออ้างอิง API ฉบับสมบูรณ์สำหรับ SimpleBiz MVC Framework V2

## ข้อมูลพื้นฐาน

**Base URL:** `/api/v1`

**รูปแบบการตอบกลับมาตรฐาน:**
```json
{
  "success": true|false,
  "data": {...},
  "message": "ข้อความอธิบาย",
  "errors": []
}
```

## การยืนยันตัวตน

### Session Authentication
- ใช้สำหรับ Web Application
- ต้อง login ผ่าน `/auth/login` ก่อน
- Session จะถูกเก็บใน Cookie

### API Key Authentication
- ใช้สำหรับ External API calls
- ส่งผ่าน Header: `X-API-Key: your-api-key`
- หรือ Query Parameter: `?api_key=your-api-key`

---

## 📦 Products API

### GET /api/v1/products
ดึงรายการสินค้าทั้งหมด (Public)

**Query Parameters:**
- `page` (optional): หน้าที่ต้องการ (default: 1)
- `limit` (optional): จำนวนต่อหน้า (default: 10)
- `category_id` (optional): กรองตามหมวดหมู่

**ตัวอย่าง Request:**
```bash
GET /api/v1/products?page=1&limit=10&category_id=3
```

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "MacBook Pro 14",
        "slug": "macbook-pro-14",
        "description": "Apple MacBook Pro 14 inch M3 Pro",
        "price": "75990.00",
        "compare_price": "79990.00",
        "stock": 15,
        "category_id": 3,
        "status": "active",
        "created_at": "2026-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 48,
      "items_per_page": 10
    }
  },
  "message": "Products retrieved successfully"
}
```

---

### GET /api/v1/products/{id}
ดึงข้อมูลสินค้าตาม ID (Public)

**URL Parameters:**
- `id`: Product ID

**ตัวอย่าง Request:**
```bash
GET /api/v1/products/1
```

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "MacBook Pro 14",
    "slug": "macbook-pro-14",
    "description": "Apple MacBook Pro 14 inch M3 Pro",
    "price": "75990.00",
    "compare_price": "79990.00",
    "stock": 15,
    "category_id": 3,
    "category_name": "Computers",
    "images": [
      "/assets/images/products/macbook-1.jpg"
    ],
    "status": "active",
    "created_at": "2026-01-15 10:30:00"
  },
  "message": "Product found"
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "data": null,
  "message": "Product not found",
  "errors": []
}
```

---

### GET /api/v1/products/search
ค้นหาสินค้า (Public)

**Query Parameters:**
- `q`: คำค้นหา (required)
- `page` (optional): หน้าที่ต้องการ
- `limit` (optional): จำนวนต่อหน้า

**ตัวอย่าง Request:**
```bash
GET /api/v1/products/search?q=macbook&page=1
```

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "products": [...],
    "search_term": "macbook",
    "total_found": 3
  },
  "message": "Search completed"
}
```

---

## 🛒 Cart API

### GET /api/v1/cart
ดึงข้อมูลตะกร้าสินค้า (ต้อง Login)

**ตัวอย่าง Request:**
```bash
GET /api/v1/cart
Cookie: PHPSESSID=...
```

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "MacBook Pro 14",
        "price": "75990.00",
        "quantity": 1,
        "subtotal": "75990.00"
      }
    ],
    "summary": {
      "subtotal": "75990.00",
      "tax": "5319.30",
      "total": "81309.30",
      "item_count": 1
    }
  },
  "message": "Cart retrieved"
}
```

---

### POST /api/v1/cart/add
เพิ่มสินค้าลงตะกร้า (ต้อง Login)

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

**ตัวอย่าง Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "cart_item_id": 15,
    "product_id": 1,
    "quantity": 2,
    "price": "75990.00",
    "subtotal": "151980.00"
  },
  "message": "Product added to cart"
}
```

**Error Response (400 Bad Request - สินค้าหมด):**
```json
{
  "success": false,
  "data": null,
  "message": "Insufficient stock",
  "errors": [
    "Available stock: 1, Requested: 2"
  ]
}
```

---

### PUT /api/v1/cart/update
อัพเดทจำนวนสินค้าในตะกร้า (ต้อง Login)

**Request Body:**
```json
{
  "cart_item_id": 15,
  "quantity": 3
}
```

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "cart_item_id": 15,
    "new_quantity": 3,
    "new_subtotal": "227970.00"
  },
  "message": "Cart updated"
}
```

---

### DELETE /api/v1/cart/remove/{product_id}
ลบสินค้าออกจากตะกร้า (ต้อง Login)

**ตัวอย่าง Request:**
```bash
DELETE /api/v1/cart/remove/1
Cookie: PHPSESSID=...
```

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": null,
  "message": "Item removed from cart"
}
```

---

## 📋 Orders API

### GET /api/v1/orders
ดึงรายการคำสั่งซื้อ (ต้อง Login)

**Query Parameters:**
- `page` (optional): หน้าที่ต้องการ
- `status` (optional): กรองตามสถานะ (pending, processing, completed, cancelled)

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-2026-0001",
        "total_amount": "81309.30",
        "status": "completed",
        "created_at": "2026-01-18 14:30:00",
        "items_count": 1
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_items": 1
    }
  },
  "message": "Orders retrieved"
}
```

---

### GET /api/v1/orders/{id}
ดึงข้อมูลคำสั่งซื้อแบบละเอียด (ต้อง Login)

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-2026-0001",
    "user_id": 5,
    "status": "completed",
    "subtotal": "75990.00",
    "tax": "5319.30",
    "total_amount": "81309.30",
    "shipping_address": "123 ถนนสุขุมวิท กรุงเทพฯ",
    "payment_method": "credit_card",
    "items": [
      {
        "product_id": 1,
        "product_name": "MacBook Pro 14",
        "quantity": 1,
        "price": "75990.00",
        "subtotal": "75990.00"
      }
    ],
    "created_at": "2026-01-18 14:30:00",
    "updated_at": "2026-01-18 15:00:00"
  },
  "message": "Order details retrieved"
}
```

---

### POST /api/v1/orders/create
สร้างคำสั่งซื้อจากตะกร้า (ต้อง Login + API Key)

**Headers:**
```
X-API-Key: your-api-key
Cookie: PHPSESSID=...
```

**Request Body:**
```json
{
  "shipping_address": "123 ถนนสุขุมวิท กรุงเทพฯ 10110",
  "payment_method": "credit_card",
  "notes": "ส่งเร็วด้วยครับ"
}
```

**ตัวอย่าง Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "order_id": 2,
    "order_number": "ORD-2026-0002",
    "total_amount": "81309.30",
    "status": "pending",
    "created_at": "2026-01-19 10:15:00"
  },
  "message": "Order created successfully"
}
```

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": [
    "Shipping address is required",
    "Payment method is invalid"
  ]
}
```

---

### PUT /api/v1/orders/{id}/status
อัพเดทสถานะคำสั่งซื้อ (ต้อง Login + API Key + Admin Role)

**Headers:**
```
X-API-Key: your-api-key
Cookie: PHPSESSID=...
```

**Request Body:**
```json
{
  "status": "processing"
}
```

**Valid Status Values:**
- `pending` - รอดำเนินการ
- `processing` - กำลังจัดเตรียม
- `completed` - เสร็จสิ้น
- `cancelled` - ยกเลิก

**ตัวอย่าง Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "order_id": 1,
    "old_status": "pending",
    "new_status": "processing",
    "updated_at": "2026-01-19 10:20:00"
  },
  "message": "Order status updated"
}
```

---

## 🔐 HTTP Status Codes

| Code | ความหมาย | เมื่อใช้ |
|------|----------|---------|
| 200 | OK | Request สำเร็จ (GET, PUT, DELETE) |
| 201 | Created | สร้างข้อมูลสำเร็จ (POST) |
| 400 | Bad Request | Request ผิดรูปแบบ / ข้อมูลไม่ถูกต้อง |
| 401 | Unauthorized | ไม่ได้ login หรือ session หมดอายุ |
| 403 | Forbidden | ไม่มีสิทธิ์เข้าถึง (ขาด role หรือ API key) |
| 404 | Not Found | ไม่พบข้อมูลที่ร้องขอ |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | เกิน rate limit |
| 500 | Internal Server Error | เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์ |

---

## 📝 Error Response Examples

### 401 Unauthorized
```json
{
  "success": false,
  "data": null,
  "message": "Authentication required",
  "errors": ["Please login to continue"]
}
```

### 403 Forbidden (Missing API Key)
```json
{
  "success": false,
  "data": null,
  "message": "API key required",
  "errors": ["This endpoint requires a valid API key"]
}
```

### 422 Validation Error
```json
{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": [
    "Product ID is required",
    "Quantity must be at least 1",
    "Quantity exceeds available stock"
  ]
}
```

### 429 Rate Limit
```json
{
  "success": false,
  "data": null,
  "message": "Rate limit exceeded",
  "errors": ["Too many requests. Please try again in 60 seconds"]
}
```

---

## 🔧 การทดสอบ API

### ใช้ cURL
```bash
# GET Products
curl -X GET "http://localhost:8000/api/v1/products"

# POST Add to Cart (with session)
curl -X POST "http://localhost:8000/api/v1/cart/add" \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{"product_id": 1, "quantity": 2}'

# POST Create Order (with API key)
curl -X POST "http://localhost:8000/api/v1/orders/create" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{"shipping_address": "123 Main St", "payment_method": "credit_card"}'
```

### ใช้ Postman
1. Import collection จาก `/docs/postman/SimpleBiz-API.postman_collection.json`
2. ตั้งค่า Environment variables:
   - `base_url`: http://localhost:8000
   - `api_key`: your-api-key
3. ใช้ Pre-request Script เพื่อจัดการ session cookies

---

## 📚 เอกสารเพิ่มเติม

- [Middleware Guide](MIDDLEWARE_GUIDE.md) - การใช้งาน API Key และ Authentication
- [Security Hardening](SECURITY_HARDENING.md) - การรักษาความปลอดภัย API
- [Models Guide](MODELS_GUIDE.md) - โครงสร้างข้อมูลแต่ละ Model
