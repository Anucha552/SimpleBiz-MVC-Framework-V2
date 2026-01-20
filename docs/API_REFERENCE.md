# API Reference (v1)

ฐาน URL: /api/v1

รูปแบบการตอบกลับมาตรฐาน:
```
{
  "success": true|false,
  "data": {...},
  "message": "...",
  "errors": [...]
}
```

## การยืนยันตัวตน
- บาง endpoint ต้องใช้ session login
- บาง endpoint ต้องใช้ API key

### ส่ง API key
- Header: X-API-Key: <your-key>
- หรือ Query: ?api_key=<your-key>

---

## Products (Public)
- GET /products
- GET /products/{id}
- GET /products/search

---

## Cart (ต้อง login)
- GET /cart
- POST /cart/add
- PUT /cart/update
- DELETE /cart/remove/{product_id}

---

## Orders (ต้อง login)
- GET /orders
- GET /orders/{id}

## Orders (ต้อง login + API key)
- POST /orders/create
- PUT /orders/{id}/status

---

## Status Codes (แนวทาง)
- 200 OK
- 201 Created
- 400 Bad Request
- 401 Unauthorized
- 403 Forbidden
- 404 Not Found
- 422 Validation Error
- 500 Server Error

---

ดูรายละเอียด logic เพิ่มเติมใน:
- routes/api.php
- app/Controllers/Api/V1
