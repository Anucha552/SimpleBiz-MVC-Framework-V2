<?php $this->section('title'); ?>
คำสั่งซื้อ #<?= htmlspecialchars($order['order_number']) ?> - SimpleBiz
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div style="margin-bottom: 1rem;">
    <a href="/orders" style="color: #667eea; text-decoration: none;">
        ← กลับไปหน้าคำสั่งซื้อ
    </a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Order Details -->
    <div>
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 2rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2rem;">
                <div>
                    <h1 style="margin-bottom: 0.5rem;">คำสั่งซื้อ #<?= htmlspecialchars($order['order_number']) ?></h1>
                    <p style="color: #666;">วันที่สั่งซื้อ: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                </div>
                <span style="display: inline-block; padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; <?= getStatusStyle($order['status']) ?>">
                    <?= getStatusText($order['status']) ?>
                </span>
            </div>
            
            <!-- Order Items -->
            <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">รายการสินค้า</h3>
            
            <?php foreach ($order['items'] as $item): ?>
                <div style="display: flex; gap: 1rem; padding: 1rem; border-bottom: 1px solid #eee;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                        📦
                    </div>
                    
                    <div style="flex: 1;">
                        <h4 style="margin-bottom: 0.5rem;">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </h4>
                        <p style="color: #666; font-size: 0.9rem;">
                            ฿<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?>
                        </p>
                    </div>
                    
                    <div style="text-align: right;">
                        <p style="font-weight: bold; color: #667eea; font-size: 1.1rem;">
                            ฿<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Order Total -->
            <div style="margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>ยอดรวมสินค้า:</span>
                    <span>฿<?= number_format($order['subtotal'], 2) ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>ค่าจัดส่ง:</span>
                    <span>฿<?= number_format($order['shipping_cost'], 2) ?></span>
                </div>
                
                <?php if ($order['discount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: #28a745;">
                        <span>ส่วนลด:</span>
                        <span>-฿<?= number_format($order['discount'], 2) ?></span>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; padding-top: 1rem; border-top: 2px solid #ddd; font-size: 1.3rem; font-weight: bold;">
                    <span>ยอดรวมทั้งหมด:</span>
                    <span style="color: #667eea;">฿<?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Shipping Address -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 2rem;">
            <h3 style="margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                📍 ที่อยู่ในการจัดส่ง
            </h3>
            
            <div style="line-height: 1.8;">
                <p><strong><?= htmlspecialchars($order['shipping_name']) ?></strong></p>
                <p><?= htmlspecialchars($order['shipping_address']) ?></p>
                <p><?= htmlspecialchars($order['shipping_city']) ?> <?= htmlspecialchars($order['shipping_postcode']) ?></p>
                <p>เบอร์โทร: <?= htmlspecialchars($order['shipping_phone']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Order Info Sidebar -->
    <div>
        <!-- Order Timeline -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
            <h3 style="margin-bottom: 1rem;">สถานะการจัดส่ง</h3>
            
            <div style="position: relative; padding-left: 2rem;">
                <div style="position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: #ddd;"></div>
                
                <div style="position: relative; margin-bottom: 1.5rem;">
                    <div style="position: absolute; left: -2rem; width: 16px; height: 16px; background: #28a745; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #28a745;"></div>
                    <p style="font-weight: bold; margin-bottom: 0.25rem;">สั่งซื้อสำเร็จ</p>
                    <p style="font-size: 0.85rem; color: #666;">
                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </p>
                </div>
                
                <?php if ($order['status'] !== 'pending'): ?>
                    <div style="position: relative; margin-bottom: 1.5rem;">
                        <div style="position: absolute; left: -2rem; width: 16px; height: 16px; background: #28a745; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #28a745;"></div>
                        <p style="font-weight: bold; margin-bottom: 0.25rem;">กำลังดำเนินการ</p>
                        <p style="font-size: 0.85rem; color: #666;">
                            <?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if (in_array($order['status'], ['shipped', 'completed'])): ?>
                    <div style="position: relative; margin-bottom: 1.5rem;">
                        <div style="position: absolute; left: -2rem; width: 16px; height: 16px; background: #28a745; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #28a745;"></div>
                        <p style="font-weight: bold; margin-bottom: 0.25rem;">จัดส่งแล้ว</p>
                        <p style="font-size: 0.85rem; color: #666;">
                            <?= isset($order['shipped_at']) ? date('d/m/Y H:i', strtotime($order['shipped_at'])) : '-' ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if ($order['status'] === 'completed'): ?>
                    <div style="position: relative;">
                        <div style="position: absolute; left: -2rem; width: 16px; height: 16px; background: #28a745; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #28a745;"></div>
                        <p style="font-weight: bold; margin-bottom: 0.25rem;">ได้รับสินค้า</p>
                        <p style="font-size: 0.85rem; color: #666;">
                            <?= isset($order['completed_at']) ? date('d/m/Y H:i', strtotime($order['completed_at'])) : '-' ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions -->
        <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1.5rem;">
            <h3 style="margin-bottom: 1rem;">ดำเนินการ</h3>
            
            <?php if ($order['status'] === 'pending'): ?>
                <button onclick="cancelOrder(<?= $order['id'] ?>)" 
                        class="btn btn-danger" 
                        style="width: 100%; margin-bottom: 0.5rem;">
                    ยกเลิกคำสั่งซื้อ
                </button>
            <?php endif; ?>
            
            <?php if ($order['status'] === 'shipped'): ?>
                <button onclick="confirmReceived(<?= $order['id'] ?>)" 
                        class="btn" 
                        style="width: 100%; margin-bottom: 0.5rem;">
                    ยืนยันได้รับสินค้า
                </button>
            <?php endif; ?>
            
            <button onclick="window.print()" 
                    class="btn btn-secondary" 
                    style="width: 100%; margin-bottom: 0.5rem;">
                    🖨️ พิมพ์ใบเสร็จ
                </button>
            
            <a href="/contact" 
               class="btn btn-secondary" 
               style="width: 100%; display: block; text-align: center;">
                📞 ติดต่อฝ่ายสนับสนุน
            </a>
        </div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
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

function confirmReceived(orderId) {
    if (!confirm('ยืนยันว่าคุณได้รับสินค้าแล้วหรือไม่?')) {
        return;
    }
    
    fetch(`/orders/${orderId}/confirm-received`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('ยืนยันได้รับสินค้าเรียบร้อยแล้ว');
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาด');
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
