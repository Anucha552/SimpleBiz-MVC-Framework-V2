<?php $this->layout('layouts/main'); ?>

<?php $this->start('title'); ?>
ตัวอย่างการใช้งาน CSS, JS และรูปภาพ
<?php $this->end(); ?>

<?php $this->start('head'); ?>
<!-- เพิ่ม CSS ที่ต้องการใน section 'head' -->
<link rel="stylesheet" href="/assets/css/custom.css">
<?php $this->end(); ?>

<?php $this->start('styles'); ?>
<!-- หรือเขียน inline CSS ใน section 'styles' -->
<style>
    .demo-section {
        margin: 3rem 0;
        padding: 2rem;
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .code-example {
        background: #282c34;
        color: #abb2bf;
        padding: 1.5rem;
        border-radius: 5px;
        overflow-x: auto;
        margin: 1rem 0;
    }
    
    .code-example code {
        color: #98c379;
        font-family: 'Courier New', monospace;
    }
    
    .demo-image {
        max-width: 100%;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
</style>
<?php $this->end(); ?>

<div class="animate-on-scroll">
    <h1><i class="bi bi-palette"></i> ตัวอย่างการใช้งาน Assets</h1>
    <p class="lead">เรียนรู้วิธีการใช้ CSS, JavaScript และรูปภาพใน Views</p>
</div>

<!-- ตัวอย่าง CSS -->
<div class="demo-section animate-on-scroll">
    <h2><i class="bi bi-filetype-css"></i> 1. การใช้งาน CSS</h2>
    
    <h4>วิธีที่ 1: ใช้ไฟล์ CSS ภายนอก</h4>
    <p>เพิ่มในส่วน <code>&lt;head&gt;</code> ของ view:</p>
    <div class="code-example">
        <code>&lt;?php $this-&gt;start('head'); ?&gt;<br>
&lt;link rel="stylesheet" href="/assets/css/custom.css"&gt;<br>
&lt;?php $this-&gt;end(); ?&gt;</code>
    </div>
    
    <h4>วิธีที่ 2: เขียน Inline CSS</h4>
    <p>เพิ่มในส่วน <code>&lt;styles&gt;</code>:</p>
    <div class="code-example">
        <code>&lt;?php $this-&gt;start('styles'); ?&gt;<br>
&lt;style&gt;<br>
    .my-class {<br>
        color: #667eea;<br>
        font-weight: bold;<br>
    }<br>
&lt;/style&gt;<br>
&lt;?php $this-&gt;end(); ?&gt;</code>
    </div>
    
    <h4>ตัวอย่างการใช้งาน:</h4>
    <button class="btn btn-gradient">
        <i class="bi bi-rocket"></i> ปุ่มแบบ Gradient
    </button>
    <div class="card card-hover mt-3" style="max-width: 400px;">
        <div class="card-body">
            <h5 class="card-title">Card ที่มี Hover Effect</h5>
            <p class="card-text">ลองเลื่อนเมาส์มาที่การ์ดนี้</p>
        </div>
    </div>
</div>

<!-- ตัวอย่าง JavaScript -->
<div class="demo-section animate-on-scroll">
    <h2><i class="bi bi-filetype-js"></i> 2. การใช้งาน JavaScript</h2>
    
    <h4>วิธีที่ 1: ใช้ไฟล์ JS ภายนอก</h4>
    <p>เพิ่มก่อนปิด <code>&lt;/body&gt;</code> ใน layout หรือใน view:</p>
    <div class="code-example">
        <code>&lt;script src="/assets/js/custom.js"&gt;&lt;/script&gt;</code>
    </div>
    
    <h4>วิธีที่ 2: เขียน Inline JavaScript</h4>
    <div class="code-example">
        <code>&lt;script&gt;<br>
    document.addEventListener('DOMContentLoaded', function() {<br>
        console.log('Hello from JavaScript!');<br>
    });<br>
&lt;/script&gt;</code>
    </div>
    
    <h4>ตัวอย่างการใช้งาน:</h4>
    <button class="btn btn-primary" onclick="showToast('สวัสดีจาก JavaScript!', 'success')">
        <i class="bi bi-bell"></i> แสดง Toast Notification
    </button>
    
    <button class="btn btn-info ms-2" onclick="testLoadingSpinner()">
        <i class="bi bi-hourglass-split"></i> ทดสอบ Loading Spinner
    </button>
    
    <div id="loading-demo" class="mt-3 p-3 bg-white rounded" style="min-height: 100px;">
        คลิกปุ่มด้านบนเพื่อทดสอบ
    </div>
</div>

<!-- ตัวอย่างรูปภาพ -->
<div class="demo-section animate-on-scroll">
    <h2><i class="bi bi-image"></i> 3. การใช้งานรูปภาพ</h2>
    
    <h4>วิธีใช้งาน:</h4>
    <div class="code-example">
        <code>&lt;!-- ภาพทั่วไป --&gt;<br>
&lt;img src="/assets/images/logo.png" alt="Logo"&gt;<br><br>
&lt;!-- ภาพพร้อม responsive class --&gt;<br>
&lt;img src="/assets/images/banner.jpg" class="img-fluid" alt="Banner"&gt;</code>
    </div>
    
    <h4>โครงสร้างโฟลเดอร์แนะนำ:</h4>
    <div class="code-example">
        <code>public/<br>
  assets/<br>
    images/<br>
      logo.png          - โลโก้<br>
      favicon.ico       - Favicon<br>
      banners/          - รูปแบนเนอร์<br>
      products/         - รูปสินค้า<br>
      avatars/          - รูปโปรไฟล์<br>
      icons/            - ไอคอนต่างๆ</code>
    </div>
    
    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle"></i> <strong>หมายเหตุ:</strong> 
        Path ทั้งหมดเริ่มต้นจาก <code>/assets/</code> เนื่องจาก public folder เป็น document root
    </div>
</div>

<!-- สรุป -->
<div class="demo-section animate-on-scroll">
    <h2><i class="bi bi-bookmark-check"></i> สรุป</h2>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-filetype-css text-primary"></i> CSS</h5>
                    <ul class="list-unstyled">
                        <li>✅ ใช้ <code>/assets/css/</code></li>
                        <li>✅ เพิ่มใน section 'head'</li>
                        <li>✅ หรือใน section 'styles'</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-filetype-js text-warning"></i> JavaScript</h5>
                    <ul class="list-unstyled">
                        <li>✅ ใช้ <code>/assets/js/</code></li>
                        <li>✅ เพิ่มก่อนปิด body</li>
                        <li>✅ หรือเขียน inline script</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-image text-success"></i> รูปภาพ</h5>
                    <ul class="list-unstyled">
                        <li>✅ ใช้ <code>/assets/images/</code></li>
                        <li>✅ เพิ่มด้วย <code>&lt;img&gt;</code></li>
                        <li>✅ หรือใน CSS background</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <a href="/" class="btn btn-primary btn-lg">
        <i class="bi bi-arrow-left"></i> กลับหน้าแรก
    </a>
</div>

<!-- เพิ่ม JavaScript ท้ายสุด -->
<script src="/assets/js/custom.js"></script>
<script>
// ฟังก์ชันทดสอบ loading spinner
function testLoadingSpinner() {
    const demoDiv = document.getElementById('loading-demo');
    
    // แสดง loading
    showLoading('loading-demo');
    
    // จำลองการโหลดข้อมูล
    setTimeout(() => {
        hideLoading('loading-demo', '<div class="alert alert-success mb-0"><i class="bi bi-check-circle"></i> โหลดข้อมูลเสร็จสิ้น!</div>');
    }, 2000);
}

// เพิ่ม animation เมื่อ scroll
document.addEventListener('DOMContentLoaded', function() {
    console.log('Assets Demo Page - Ready!');
});
</script>
