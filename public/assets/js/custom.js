/**
 * Custom JavaScript สำหรับ SimpleBiz MVC Framework
 * 
 * วิธีใช้: เพิ่มในหน้า view หรือ layout
 * <script src="/assets/js/custom.js"></script>
 */

// เมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    console.log('SimpleBiz MVC Framework - JavaScript Loaded!');
    
    // เพิ่ม animation เมื่อ scroll
    initScrollAnimations();
    
    // เพิ่ม smooth scroll สำหรับ anchor links
    initSmoothScroll();
});

/**
 * เพิ่ม fade-in animation เมื่อ scroll ถึง element
 */
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // สังเกตุ elements ที่มี class 'animate-on-scroll'
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Smooth scroll สำหรับ anchor links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Helper: แสดง loading spinner
 */
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="spinner mx-auto"></div>';
    }
}

/**
 * Helper: ซ่อน loading spinner
 */
function hideLoading(elementId, content = '') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = content;
    }
}

/**
 * Helper: แสดง toast notification
 */
function showToast(message, type = 'success') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // สร้าง container ถ้ายังไม่มี
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    // เพิ่ม toast
    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = container.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // ลบ element หลังจากซ่อน
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}
