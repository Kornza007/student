/**
 * University Admission Portfolio - Logic & Accessibility Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    // =========================================================
    // 1. ACCESSIBILITY FONT SIZE CONTROLLER (สเกล 1 - 100)
    // =========================================================
    const slider = document.getElementById('fontSizeSlider');
    const displayBadge = document.getElementById('fontSizeDisplay');
    const btnSmall = document.getElementById('btnFontSmall');
    const btnDefault = document.getElementById('btnFontDefault');
    const btnLarge = document.getElementById('btnFontLarge');

    // ฟังก์ชันแปลงค่า 1 - 100 เป็นค่าพิกเซล (13px - 28px)
    // สเกล 50 คือขนาดมาตรฐาน 17px สบายตาสูงสุด
    function calculateFontSize(val) {
        val = parseInt(val, 10);
        if (isNaN(val)) val = 50;
        if (val < 1) val = 1;
        if (val > 100) val = 100;

        let px;
        if (val <= 50) {
            // ช่วง 1 - 50 แปลงเป็น 13px - 17px
            px = 13 + ((val - 1) / 49) * 4;
        } else {
            // ช่วง 51 - 100 แปลงเป็น 17px - 28px (ขยายใหญ่พิเศษสำหรับผู้สูงอายุ)
            px = 17 + ((val - 50) / 50) * 11;
        }
        return Math.round(px * 10) / 10;
    }

    function applyFontSize(val, updateInput = true) {
        val = parseInt(val, 10);
        if (isNaN(val)) val = 50;

        const px = calculateFontSize(val);
        document.documentElement.style.setProperty('--base-font-size', `${px}px`);

        if (updateInput && slider) {
            slider.value = val;
        }

        // ข้อความกำกับระดับ
        let label = 'ปกติ';
        if (val < 35) label = 'กะทัดรัด';
        else if (val > 70) label = 'ใหญ่พิเศษ';
        else if (val > 55) label = 'ใหญ่';

        if (displayBadge) {
            displayBadge.textContent = `${val} / 100 (${label})`;
        }

        // อัปเดตสถานะปุ่มด่วน
        if (btnSmall) btnSmall.classList.toggle('active', val <= 25);
        if (btnDefault) btnDefault.classList.toggle('active', val >= 45 && val <= 55);
        if (btnLarge) btnLarge.classList.toggle('active', val >= 80);

        try {
            localStorage.setItem('admission_font_size', val);
        } catch (e) {
            // ignore localStorage error
        }
    }

    // Event Listener สำหรับ Slider
    if (slider) {
        slider.addEventListener('input', (e) => {
            applyFontSize(e.target.value, false);
        });
    }

    // ปุ่มลัด
    if (btnSmall) {
        btnSmall.addEventListener('click', () => applyFontSize(20));
    }
    if (btnDefault) {
        btnDefault.addEventListener('click', () => applyFontSize(50));
    }
    if (btnLarge) {
        btnLarge.addEventListener('click', () => applyFontSize(85));
    }

    // โหลดค่าเดิมที่เคยตั้งไว้ (หรือค่ามาตรฐาน 50)
    let savedFontSize = 50;
    try {
        const stored = localStorage.getItem('admission_font_size');
        if (stored !== null) savedFontSize = parseInt(stored, 10);
    } catch (e) {
        savedFontSize = 50;
    }
    applyFontSize(savedFontSize);

    // =========================================================
    // 2. MOBILE NAVIGATION MENU
    // =========================================================
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('open');
            const icon = mobileToggle.querySelector('i');
            if (icon) {
                if (navLinks.classList.contains('open')) {
                    icon.className = 'bi bi-x-lg';
                } else {
                    icon.className = 'bi bi-list';
                }
            }
        });

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('open');
                const icon = mobileToggle.querySelector('i');
                if (icon) icon.className = 'bi bi-list';
            });
        });
    }

    // =========================================================
    // 3. SCROLL SPY (ขีดเส้นใต้ตามหัวข้อปัจจุบัน)
    // =========================================================
    const sections = document.querySelectorAll('section[id]');
    const navItems = document.querySelectorAll('.nav-link');

    window.addEventListener('scroll', () => {
        let current = '';
        const scrollPos = window.scrollY + 120;

        sections.forEach(sec => {
            const top = sec.offsetTop;
            const h = sec.offsetHeight;
            if (scrollPos >= top && scrollPos < top + h) {
                current = sec.getAttribute('id');
            }
        });

        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === `#${current}`) {
                item.classList.add('active');
            }
        });
    });

    // =========================================================
    // 4. COPY CONTACT TO CLIPBOARD & TOAST
    // =========================================================
    window.copyText = function(text, label = 'ข้อมูล') {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            showToast(`คัดลอก${label}เรียบร้อยแล้ว: ${text}`);
        }).catch(() => {
            const input = document.createElement('input');
            input.value = text;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showToast(`คัดลอก${label}เรียบร้อยแล้ว: ${text}`);
        });
    };

    function showToast(msg) {
        const toast = document.getElementById('portfolioToast');
        const toastMsg = document.getElementById('toastMsg');
        if (!toast || !toastMsg) return;

        toastMsg.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3200);
    }
});
