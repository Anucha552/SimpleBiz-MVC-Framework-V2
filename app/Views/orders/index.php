<?php $this->section('title'); ?>
คำสั่งซื้อของฉัน - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1 style="margin-bottom: 2rem; color: #333;">📋 คำสั่งซื้อของฉัน</h1>

<?php if (empty($orders)): ?>
    <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 8px;">
        <div style="font-size: 5rem; margin-bottom: 1rem;">📋</div>
        <h3 style="color: #666; margin-bottom: 1rem;">ยังไม่มีคำสั่งซื้อ</h3>
        <p style="color: #999; margin-bottom: 2rem;">เมื่อคุณสั่งซื้อสินค้า คำสั่งซื้อจะปรากฏที่นี่</p>
        <a href="/products" class="btn">เริ่มช้อปปิ้ง</a>
    </div>
<?php else: ?>
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; overflow-x: auto;">
        <button class="btn" onclick="filterOrders('all')" style="white-space: nowrap;">
            ทั้งหมด
        </button>
        <button class="btn btn-secondary" onclick="filterOrders('pending')" style="white-space: nowrap;">
            รอดำเนินการ
        </button>
        <button class="btn btn-secondary" onclick="filterOrders('processing')" style="white-space: nowrap;">
            กำลังดำเนินการ
        </button>
        <button class="btn btn-secondary" onclick="filterOrders('shipped')" style="white-space: nowrap;">
            จัดส่งแล้ว
        </button>
        <button class="btn btn-secondary" onclick="filterOrders('completed')" style="white-space: nowrap;">
            สำเร็จ
        </button>
        <button class="btn btn-secondary" onclick="filterOrders('cancelled')" style="white-space: nowrap;">
            ยกเลิก
        </button>
    </div>
    
    <?php foreach ($orders as $order): ?>
        <div class="order-item" data-status="<?= $order['status'] ?>" 
             style="border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
            
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin-bottom: 0.5rem;">
                        คำสั่งซื้อ #<?= htmlspecialchars($order['order_number']) ?>
                    </h3>
                    <p style="color: #666; font-size: 0.9rem;">
                        วันที่สั่งซื้อ: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </p>
                </div>
                
                <div style="text-align: right;">
                    <span style="display: inline-block; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: bold; <?= getStatusStyle($order['status']) ?>">
                        <?= getStatusText($order['status']) ?>
                    </span>
                    <p style="font-size: 1.2rem; font-weight: bold; color: #667eea; margin-top: 0.5rem;">
                        ฿<?= number_format($order['total'], 2) ?>
                    </p>
                </div>
            </div>
            
            <div style="border-top: 1px solid #eee; padding-top: 1rem;">
                <?php if (!empty($order['items'])): ?>
                    <?php foreach (array_slice($order['items'], 0, 3) as $item): ?>
                        <div style="display: flex; gap: 1rem; margin-bottom: 0.5rem; align-items: center;">
                            <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                📦
                            </div>
                            <div style="flex: 1;">
                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                <span style="color: #666;"> × <?= $item['quantity'] ?></span>
                            </div>
                            <span style="color: #667eea; font-weight: bold;">
                                ฿<?= number_format($item['price'] * $item['quantity'], 2) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($order['items']) > 3): ?>
                        <p style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">
                            และอีก <?= count($order['items']) - 3 ?> รายการ...
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div style="margin-top: 1rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="/orders/<?= $order['id'] ?>" class="btn" style="font-size: 0.9rem;">
                    ดูรายละเอียด
                </a>
                
                <?php if ($order['status'] === 'pending'): ?>
                    <button onclick="cancelOrder(<?= $order['id'] ?>)" 
                            class="btn btn-danger" 
                            style="font-size: 0.9rem;">
                        ยกเลิกคำสั่งซื้อ
                    </button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
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
function filterOrders(status) {
    const items = document.querySelectorAll('.order-item');
    
    items.forEach(item => {
        if (status === 'all' || item.dataset.status === status) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Update button styles
    document.querySelectorAll('button[onclick^="filterOrders"]').forEach(btn => {
        btn.className = 'btn btn-secondary';
    });
    event.target.className = 'btn';
}

function cancelOrder(orderId) {
    if (!confirm('คุณต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?')) {
        return;
    }
    
    fetch(`/orders/${orderId}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ยกเลิกคำสั่งซื้อเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการยกเลิกคำสั่งซื้อ');
    });
}
</script>
<?php $this->endSection(); ?>

<?php
function getStatusStyle($status) {
    $styles = [
        'pending' => 'background: #ffc107; color: #000;',
        'processing' => 'background: #17a2b8; color: #fff;',
        'shipped' => 'background: #007bff; color: #fff;',
        'completed' => 'background: #28a745; color: #fff;',
        'cancelled' => 'background: #dc3545; color: #fff;'
    ];
    return $styles[$status] ?? 'background: #6c757d; color: #fff;';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'รอดำเนินการ',
        'processing' => 'กำลังดำเนินการ',
        'shipped' => 'จัดส่งแล้ว',
        'completed' => 'สำเร็จ',
        'cancelled' => 'ยกเลิก'
    ];
    return $texts[$status] ?? $status;
}
?>
