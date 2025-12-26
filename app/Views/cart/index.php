<?php $this->section('title'); ?>
ตะกร้าสินค้า - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1 style="margin-bottom: 2rem; color: #333;">🛒 ตะกร้าสินค้า</h1>

<?php if (empty($cartItems)): ?>
    <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 5rem; margin-bottom: 1rem;">🛒</div>
        <h3 style="color: #666; margin-bottom: 1rem;">ตะกร้าสินค้าของคุณว่างเปล่า</h3>
        <p style="color: #999; margin-bottom: 2rem;">เริ่มช้อปปิ้งและเพิ่มสินค้าลงตะกร้า</p>
        <a href="/products" class="btn">เลือกซื้อสินค้า</a>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
        <!-- Cart Items -->
        <div>
            <?php foreach ($cartItems as $item): ?>
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; display: flex; gap: 1.5rem;">
                    <!-- Product Image -->
                    <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; flex-shrink: 0;">
                        <?php if (!empty($item['product']['image'])): ?>
                            <img src="<?= htmlspecialchars($item['product']['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['product']['name']) ?>"
                                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            📦
                        <?php endif; ?>
                    </div>
                    
                    <!-- Product Info -->
                    <div style="flex: 1;">
                        <h3 style="margin-bottom: 0.5rem;">
                            <a href="/products/<?= $item['product']['id'] ?>" 
                               style="color: #333; text-decoration: none;">
                                <?= htmlspecialchars($item['product']['name']) ?>
                            </a>
                        </h3>
                        
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                            ฿<?= number_format($item['product']['price'], 2) ?> / ชิ้น
                        </p>
                        
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <button onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] - 1 ?>)"
                                        style="padding: 0.25rem 0.75rem; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px;"
                                        <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>>
                                    −
                                </button>
                                
                                <input type="number" 
                                       id="qty-<?= $item['id'] ?>"
                                       value="<?= $item['quantity'] ?>"
                                       min="1"
                                       max="<?= $item['product']['stock'] ?>"
                                       onchange="updateQuantity(<?= $item['id'] ?>, this.value)"
                                       style="width: 60px; text-align: center; padding: 0.25rem; border: 1px solid #ddd; border-radius: 4px;">
                                
                                <button onclick="updateQuantity(<?= $item['id'] ?>, <?= $item['quantity'] + 1 ?>)"
                                        style="padding: 0.25rem 0.75rem; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px;"
                                        <?= $item['quantity'] >= $item['product']['stock'] ? 'disabled' : '' ?>>
                                    +
                                </button>
                            </div>
                            
                            <span style="font-weight: bold; color: #667eea; font-size: 1.1rem;">
                                ฿<?= number_format($item['product']['price'] * $item['quantity'], 2) ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Remove Button -->
                    <div>
                        <button onclick="removeFromCart(<?= $item['id'] ?>)"
                                class="btn btn-danger"
                                style="padding: 0.5rem 1rem;">
                            🗑️
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Order Summary -->
        <div>
            <div style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; position: sticky; top: 20px;">
                <h3 style="margin-bottom: 1rem; color: #333;">สรุปคำสั่งซื้อ</h3>
                
                <div style="border-bottom: 1px solid #ddd; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: #666;">ยอดรวมสินค้า:</span>
                        <span>฿<?= number_format($subtotal, 2) ?></span>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: #666;">ค่าจัดส่ง:</span>
                        <span>฿<?= number_format($shipping ?? 0, 2) ?></span>
                    </div>
                    
                    <?php if (isset($discount) && $discount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #28a745;">
                            <span>ส่วนลด:</span>
                            <span>-฿<?= number_format($discount, 2) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: bold;">
                    <span>ยอดรวมทั้งหมด:</span>
                    <span style="color: #667eea;">฿<?= number_format($total, 2) ?></span>
                </div>
                
                <a href="/checkout" class="btn" style="width: 100%; display: block; text-align: center; margin-bottom: 1rem;">
                    ดำเนินการชำระเงิน
                </a>
                
                <a href="/products" style="display: block; text-align: center; color: #667eea; text-decoration: none;">
                    ← ช้อปปิ้งต่อ
                </a>
                
                <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; font-size: 0.9rem;">
                    <strong>💳 วิธีการชำระเงิน</strong>
                    <ul style="margin: 0.5rem 0 0 1.5rem; color: #666;">
                        <li>โอนเงินผ่านธนาคาร</li>
                        <li>บัตรเครดิต/เดบิต</li>
                        <li>พร้อมเพย์</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
function updateQuantity(itemId, quantity) {
    if (quantity < 1) return;
    
    fetch('/cart/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการอัพเดทจำนวน');
    });
}

function removeFromCart(itemId) {
    if (!confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')) {
        return;
    }
    
    fetch('/cart/remove', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการลบสินค้า');
    });
}
</script>
<?php $this->endSection(); ?>
