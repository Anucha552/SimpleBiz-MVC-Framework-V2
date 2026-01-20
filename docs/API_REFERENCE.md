# API Reference Documentation (v1)

Complete API documentation for SimpleBiz MVC Framework V2

---

## Base Information

**Base URL:** `/api/v1`

**Response Format:** JSON

All API responses follow this standardized structure:

```json
{
  "success": true,
  "data": {...},
  "message": "Success message",
  "errors": []
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": ["Detailed error 1", "Detailed error 2"]
}
```

---

## Authentication

### Session-Based Authentication
Most endpoints require user authentication via session. User must be logged in via web interface.

### API Key Authentication
Sensitive operations (create orders, update status) require an additional API key.

**Methods to send API key:**
1. Header: `X-API-Key: <your-api-key>`
2. Query parameter: `?api_key=<your-api-key>`

---

## HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid input data |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server error occurred |

---

## Endpoints

### Products API (Public)

#### GET /api/v1/products
รายการสินค้าทั้งหมด

**Authentication:** None required

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Items per page (default: 20)
- `category` (optional): Filter by category ID
- `status` (optional): Filter by status (active, inactive, draft)

**Example Request:**
```bash
GET /api/v1/products?page=1&limit=10&category=5
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "slug": "product-name",
        "description": "Product description",
        "price": 299.00,
        "stock": 50,
        "category_id": 5,
        "image": "product-1.jpg",
        "status": "active",
        "created_at": "2026-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "total_items": 50,
      "per_page": 10
    }
  }
}
```

---

#### GET /api/v1/products/{id}
รายละเอียดสินค้าหนึ่งรายการ

**Authentication:** None required

**URL Parameters:**
- `id` (required): Product ID

**Example Request:**
```bash
GET /api/v1/products/1
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Product Name",
    "slug": "product-name",
    "description": "Detailed product description",
    "price": 299.00,
    "compare_price": 399.00,
    "stock": 50,
    "sku": "PRD-001",
    "category_id": 5,
    "category_name": "Electronics",
    "images": ["product-1.jpg", "product-2.jpg"],
    "status": "active",
    "views": 1234,
    "created_at": "2026-01-15 10:30:00",
    "updated_at": "2026-01-20 14:20:00"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

---

#### GET /api/v1/products/search
ค้นหาสินค้า

**Authentication:** None required

**Query Parameters:**
- `q` (required): Search query
- `category` (optional): Filter by category
- `min_price` (optional): Minimum price
- `max_price` (optional): Maximum price

**Example Request:**
```bash
GET /api/v1/products/search?q=laptop&min_price=10000&max_price=50000
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "query": "laptop",
    "results": [
      {
        "id": 10,
        "name": "Gaming Laptop",
        "price": 35000.00,
        "image": "laptop-1.jpg"
      }
    ],
    "total": 5
  }
}
```

---

### Cart API (Authenticated)

#### GET /api/v1/cart
ดูตะกร้าสินค้าของผู้ใช้

**Authentication:** Required (Session)

**Example Request:**
```bash
GET /api/v1/cart
Headers: Cookie: session_id=...
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "product_id": 10,
        "product_name": "Gaming Laptop",
        "price": 35000.00,
        "quantity": 1,
        "subtotal": 35000.00,
        "image": "laptop-1.jpg"
      }
    ],
    "summary": {
      "subtotal": 35000.00,
      "shipping": 100.00,
      "tax": 2457.00,
      "total": 37557.00
    },
    "item_count": 1
  }
}
```

---

#### POST /api/v1/cart/add
เพิ่มสินค้าลงตะกร้า

**Authentication:** Required (Session)

**Request Body:**
```json
{
  "product_id": 10,
  "quantity": 2
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Product added to cart",
  "data": {
    "cart_item_id": 15,
    "product_id": 10,
    "quantity": 2
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Insufficient stock",
  "errors": ["Only 1 item available in stock"]
}
```

---

#### PUT /api/v1/cart/update
อัพเดทจำนวนสินค้าในตะกร้า

**Authentication:** Required (Session)

**Request Body:**
```json
{
  "cart_item_id": 15,
  "quantity": 3
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Cart updated successfully",
  "data": {
    "cart_item_id": 15,
    "quantity": 3,
    "subtotal": 105000.00
  }
}
```

---

#### DELETE /api/v1/cart/remove/{product_id}
ลบสินค้าออกจากตะกร้า

**Authentication:** Required (Session)

**URL Parameters:**
- `product_id` (required): Product ID to remove

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Product removed from cart"
}
```

---

### Orders API (Authenticated + API Key)

#### GET /api/v1/orders
รายการคำสั่งซื้อของผู้ใช้

**Authentication:** Required (Session)

**Query Parameters:**
- `status` (optional): Filter by status (pending, processing, completed, cancelled)
- `page` (optional): Page number

**Example Request:**
```bash
GET /api/v1/orders?status=completed&page=1
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-20260120-001",
        "status": "completed",
        "total": 37557.00,
        "items_count": 2,
        "created_at": "2026-01-20 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3
    }
  }
}
```

---

#### GET /api/v1/orders/{id}
รายละเอียดคำสั่งซื้อ

**Authentication:** Required (Session)

**URL Parameters:**
- `id` (required): Order ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20260120-001",
    "status": "completed",
    "customer": {
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "0812345678"
    },
    "items": [
      {
        "product_id": 10,
        "product_name": "Gaming Laptop",
        "quantity": 1,
        "price": 35000.00,
        "subtotal": 35000.00
      }
    ],
    "summary": {
      "subtotal": 35000.00,
      "shipping": 100.00,
      "tax": 2457.00,
      "total": 37557.00
    },
    "shipping_address": {
      "address": "123 Main Street",
      "city": "Bangkok",
      "postal_code": "10110"
    },
    "created_at": "2026-01-20 10:30:00",
    "updated_at": "2026-01-20 15:45:00"
  }
}
```

---

#### POST /api/v1/orders/create
สร้างคำสั่งซื้อจากตะกร้า

**Authentication:** Required (Session + API Key)

**Headers:**
```
X-API-Key: your-api-key-here
```

**Request Body:**
```json
{
  "shipping_address": {
    "address": "123 Main Street",
    "city": "Bangkok",
    "province": "Bangkok",
    "postal_code": "10110"
  },
  "payment_method": "credit_card",
  "notes": "Please call before delivery"
}
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_id": 15,
    "order_number": "ORD-20260120-015",
    "total": 37557.00,
    "status": "pending"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "success": false,
  "message": "Cart is empty",
  "errors": ["Cannot create order from empty cart"]
}
```

---

#### PUT /api/v1/orders/{id}/status
อัพเดทสถานะคำสั่งซื้อ

**Authentication:** Required (Session + API Key)

**URL Parameters:**
- `id` (required): Order ID

**Request Body:**
```json
{
  "status": "processing"
}
```

**Available Status:**
- `pending` - รอการชำระเงิน
- `processing` - กำลังเตรียมจัดส่ง
- `shipped` - จัดส่งแล้ว
- `completed` - สำเร็จ
- `cancelled` - ยกเลิก

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Order status updated",
  "data": {
    "order_id": 15,
    "status": "processing"
  }
}
```

---

## Error Handling

### Common Errors

**401 Unauthorized:**
```json
{
  "success": false,
  "message": "Authentication required"
}
```

**403 Forbidden (Missing API Key):**
```json
{
  "success": false,
  "message": "API key required"
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "product_id": ["Product ID is required"],
    "quantity": ["Quantity must be at least 1"]
  }
}
```

---

## Rate Limiting

API requests may be rate limited to prevent abuse:
- 60 requests per minute per IP address
- 1000 requests per hour per authenticated user

**Rate Limit Headers:**
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642689600
```

---

## Code Examples

### JavaScript (Fetch API)

```javascript
// Get products
fetch('/api/v1/products')
  .then(response => response.json())
  .then(data => console.log(data));

// Add to cart (with session)
fetch('/api/v1/cart/add', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  credentials: 'include', // Include cookies
  body: JSON.stringify({
    product_id: 10,
    quantity: 2
  })
})
  .then(response => response.json())
  .then(data => console.log(data));

// Create order (with API key)
fetch('/api/v1/orders/create', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-API-Key': 'your-api-key'
  },
  credentials: 'include',
  body: JSON.stringify({
    shipping_address: {
      address: '123 Main Street',
      city: 'Bangkok',
      postal_code: '10110'
    }
  })
})
  .then(response => response.json())
  .then(data => console.log(data));
```

### PHP (cURL)

```php
// Get products
$ch = curl_init('http://yourdomain.com/api/v1/products');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

// Add to cart with session
$ch = curl_init('http://yourdomain.com/api/v1/cart/add');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'product_id' => 10,
    'quantity' => 2
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: session_id=...'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
```

---

## Testing

Use tools like:
- **Postman** - GUI for API testing
- **cURL** - Command line testing
- **Insomnia** - REST client

**Example cURL:**
```bash
# Get products
curl http://localhost:8000/api/v1/products

# Create order with API key
curl -X POST http://localhost:8000/api/v1/orders/create \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key" \
  -H "Cookie: session_id=your-session" \
  -d '{"shipping_address": {"address": "123 Main St"}}'
```

---

## See Also

- [routes/api.php](../routes/api.php) - API route definitions
- [app/Controllers/Api/V1/](../app/Controllers/Api/V1/) - API controllers
- [MIDDLEWARE_GUIDE.md](MIDDLEWARE_GUIDE.md) - Middleware documentation
