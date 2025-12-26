<?php $this->section('title'); ?>
<?= htmlspecialchars($product['name']) ?> - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="margin-bottom: 1rem;">
    <a href="/products" style="color: #667eea; text-decoration: none;">
        ← กลับไปหน้าสินค้า
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Product Image -->
    <div>
        <div style="width: 100%; height: 400px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 5rem;">
            <?php if (!empty($product['image'])): ?>
                <img src="<?= htmlspecialchars($product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
            <?php else: ?>
                📦
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Product Details -->
    <div>
        <h1 style="margin-bottom: 1rem; color: #333;">
            <?= htmlspecialchars($product['name']) ?>
        </h1>
        
        <div style="margin-bottom: 1rem;">
            <span style="font-size: 2rem; font-weight: bold; color: #667eea;">
                ฿<?= number_format($product['price'], 2) ?>
            </span>
        </div>
        
        <div style="margin-bottom: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <strong>สถานะสินค้า:</strong>
            <span style="color: <?= $product['stock'] > 0 ? '#28a745' : '#dc3545' ?>; font-weight: bold;">
                <?= $product['stock'] > 0 ? "มีสินค้าในสต็อก ({$product['stock']} ชิ้น)" : 'สินค้าหมด' ?>
            </span>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 0.5rem;">รายละเอียดสินค้า</h3>
            <p style="color: #666; line-height: 1.8;">
                <?= nl2br(htmlspecialchars($product['description'] ?? 'ไม่มีคำอธิบาย')) ?>
            </p>
        </div>
        
        <?php if ($product['stock'] > 0): ?>
            <div style="border: 1px solid #ddd; padding: 1.5rem; border-radius: 8px; background: #f8f9fa;">
                <form onsubmit="addToCart(event)">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="quantity">จำนวน:</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               min="1" 
                               max="<?= $product['stock'] ?>" 
                               value="1" 
                               style="width: 100px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <button type="submit" class="btn" style="width: 100%; font-size: 1.1rem;">
                        🛒 เพิ่มลงตะกร้า
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div style="padding: 1rem; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24; text-align: center;">
                <strong>สินค้าหมดชั่วคราว</strong>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 1.5rem; padding: 1rem; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; color: #0c5460;">
            <h4 style="margin-bottom: 0.5rem;">📌 ข้อมูลเพิ่มเติม</h4>
            <ul style="margin-left: 1.5rem; line-height: 1.8;">
                <li>รหัสสินค้า: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></li>
                <li>หมวดหมู่: <?= htmlspecialchars($product['category'] ?? 'ไม่ระบุ') ?></li>
                <li>วันที่เพิ่ม: <?= date('d/m/Y', strtotime($product['created_at'])) ?></li>
            </ul>
        </div>
    </div>
</div>

<!-- Related Products Section -->
<?php if (!empty($relatedProducts)): ?>
    <div style="margin-top: 3rem;">
        <h2 style="margin-bottom: 1.5rem; color: #333;">สินค้าที่เกี่ยวข้อง</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <?php foreach ($relatedProducts as $related): ?>
                <a href="/products/<?= $related['id'] ?>" 
                   style="text-decoration: none; color: inherit; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; transition: transform 0.3s;"
                   onmouseover="this.style.transform='translateY(-5px)'"
                   onmouseout="this.style.transform='translateY(0)'">
                    <div style="height: 150px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 3rem; margin-bottom: 0.5rem;">
                        📦
                    </div>
                    <h4 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($related['name']) ?></h4>
                    <span style="color: #667eea; font-weight: bold;">฿<?= number_format($related['price'], 2) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
function addToCart(event) {
    event.preventDefault();
    
    const quantity = document.getElementById('quantity').value;
    
    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: <?= $product['id'] ?>,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('เพิ่มสินค้าลงตะกร้าเรียบร้อย!');
            window.location.href = '/cart';
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า');
    });
}
</script>
<?php $this->endSection(); ?>
