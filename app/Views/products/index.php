<?php $this->section('title'); ?>
สินค้าทั้งหมด - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 style="color: #333;">สินค้าทั้งหมด</h1>
    <div>
        <input type="text" 
               id="search" 
               placeholder="ค้นหาสินค้า..." 
               style="padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
    </div>
</div>

<?php if (empty($products)): ?>
    <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 4rem; margin-bottom: 1rem;">📦</div>
        <h3 style="color: #666;">ยังไม่มีสินค้าในระบบ</h3>
        <p style="color: #999;">กรุณาเพิ่มสินค้าใหม่</p>
    </div>
<?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
        <?php foreach ($products as $product): ?>
            <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s;"
                 onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)'"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                
                <!-- Product Image -->
                <div style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        📦
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div style="padding: 1rem;">
                    <h3 style="margin-bottom: 0.5rem; color: #333;">
                        <?= htmlspecialchars($product['name']) ?>
                    </h3>
                    
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem; height: 40px; overflow: hidden;">
                        <?= htmlspecialchars($product['description'] ?? 'ไม่มีคำอธิบาย') ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="font-size: 1.5rem; font-weight: bold; color: #667eea;">
                            ฿<?= number_format($product['price'], 2) ?>
                        </span>
                        <span style="color: <?= $product['stock'] > 0 ? '#28a745' : '#dc3545' ?>; font-size: 0.9rem;">
                            <?= $product['stock'] > 0 ? "คงเหลือ {$product['stock']}" : 'สินค้าหมด' ?>
                        </span>
                    </div>
                    
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="/products/<?= $product['id'] ?>" 
                           class="btn" 
                           style="flex: 1; text-align: center; font-size: 0.9rem;">
                            ดูรายละเอียด
                        </a>
                        
                        <?php if ($product['stock'] > 0): ?>
                            <button onclick="addToCart(<?= $product['id'] ?>)"
                                    class="btn"
                                    style="font-size: 0.9rem;">
                                🛒
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if (isset($pagination)): ?>
        <div style="margin-top: 2rem; text-align: center;">
            <?= $pagination ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
function addToCart(productId) {
    fetch('/cart/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('เพิ่มสินค้าลงตะกร้าเรียบร้อย!');
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการเพิ่มสินค้า');
    });
}

// Search functionality
document.getElementById('search')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const products = document.querySelectorAll('[style*="grid"] > div');
    
    products.forEach(product => {
        const productName = product.querySelector('h3').textContent.toLowerCase();
        const productDesc = product.querySelector('p').textContent.toLowerCase();
        
        if (productName.includes(searchTerm) || productDesc.includes(searchTerm)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
});
</script>
<?php $this->endSection(); ?>
