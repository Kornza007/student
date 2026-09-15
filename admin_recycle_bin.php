<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ถังขยะระบบ - กู้คืนหรือลบข้อมูลถาวร">
    <title>ถังขยะระบบ | ระบบจัดการนักศึกษาฝึกงาน</title>
    <link rel="stylesheet" href="audit_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- i18n Multi-Language Scripts -->
    <script src="lang/th.js"></script>
    <script src="lang/en.js"></script>
    <script src="lang/i18n.js"></script>
    <style>
        .expiry-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .expiry-urgent { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
        .expiry-warning { background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; }
        .expiry-safe { background: #f0fdf4; color: #16a34a; border: 1px solid #86efac; }
        .expiry-date-text {
            font-size: 11px;
            color: #6b7280;
            display: block;
            margin-top: 3px;
            white-space: nowrap;
        }
        .policy-banner {
            background: linear-gradient(135deg, #fff7ed, #fef3c7);
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #92400e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body class="audit-page" data-i18n-page-title="recycle_bin_page_title">

    <!-- TOP NAVIGATION -->
    <nav class="audit-topbar">
        <a href="index.html" class="audit-topbar-brand">
            <div class="brand-icon"><i class="bi bi-shield-check text-primary"></i></div>
            <div class="brand-text">
                <span data-i18n="app_title">ระบบตรวจสอบ</span>
                <small>Audit & Monitoring</small>
            </div>
        </a>

        <div class="audit-nav-tabs">
            <a href="admin_audit_logs.php" class="audit-nav-tab">
                <span class="tab-icon"><i class="bi bi-activity text-primary"></i></span>
                <span data-i18n="tab_audit_logs">ตรวจสอบข้อมูลระบบ</span>
            </a>
            <a href="admin_recycle_bin.php" class="audit-nav-tab active">
                <span class="tab-icon"><i class="bi bi-trash3 text-danger"></i></span>
                <span data-i18n="tab_recycle_bin">ถังขยะระบบ</span>
                <span class="tab-badge" id="recycleBinBadge" style="display:none">0</span>
            </a>
        </div>

        <div class="audit-topbar-actions">
            <!-- Language Switcher -->
            <div class="lang-switch-toggle audit-lang-switcher" onclick="toggleLanguage()" role="button" tabindex="0" title="สลับภาษา / Switch Language (TH | EN)" aria-label="Switch Language">
                <span class="lang-globe-icon"><i class="bi bi-globe2"></i></span>
                <span class="lang-opt th-opt active" onclick="event.stopPropagation(); setLanguage('th');" data-lang="th" title="ภาษาไทย">TH</span>
                <span class="lang-divider">|</span>
                <span class="lang-opt en-opt" onclick="event.stopPropagation(); setLanguage('en');" data-lang="en" title="English">EN</span>
            </div>
            <a href="index.html" class="audit-back-btn">
                <i class="bi bi-arrow-left"></i>
                <span data-i18n="btn_back_to_home">กลับหน้าหลัก</span>
            </a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="audit-container">

        <!-- 30-DAY POLICY BANNER -->
        <div class="policy-banner">
            <i class="bi bi-clock-history" style="font-size:16px;"></i>
            <span data-i18n="recycle_30day_policy">นโยบาย: ข้อมูลในถังขยะจะถูกลบถาวรอัตโนมัติหลังจาก 30 วัน</span>
        </div>

        <!-- STATS CARDS -->
        <div class="audit-stats-grid" id="recycleStats">
            <div class="audit-stat-card">
                <div class="audit-stat-icon red"><i class="bi bi-trash3"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statTotalBin">0</h4>
                    <p data-i18n="recycle_stat_total">รายการทั้งหมดในถังขยะ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon blue"><i class="bi bi-mortarboard"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statStudentsBin">0</h4>
                    <p data-i18n="recycle_stat_students">นักศึกษาที่ลบ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon green"><i class="bi bi-person-badge"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statMentorsBin">0</h4>
                    <p data-i18n="recycle_stat_mentors">พี่เลี้ยงที่ลบ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon orange"><i class="bi bi-journal-text"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statLogsBin">0</h4>
                    <p data-i18n="recycle_stat_logs">บันทึกงานที่ลบ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon cyan"><i class="bi bi-clipboard-check"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statEvalsBin">0</h4>
                    <p data-i18n="recycle_stat_evals">การประเมินที่ลบ</p>
                </div>
            </div>
        </div>

        <!-- TABLE TABS -->
        <div class="recycle-tabs" id="recycleTabs">
            <button class="recycle-tab active" data-table="students" onclick="switchTable('students')">
                <i class="bi bi-mortarboard"></i> <span data-i18n="recycle_tab_students">นักศึกษา</span>
                <span class="tab-count" id="tabCountStudents">0</span>
            </button>
            <button class="recycle-tab" data-table="mentors" onclick="switchTable('mentors')">
                <i class="bi bi-person-badge"></i> <span data-i18n="recycle_tab_mentors">พี่เลี้ยง</span>
                <span class="tab-count" id="tabCountMentors">0</span>
            </button>
            <button class="recycle-tab" data-table="internship_logs" onclick="switchTable('internship_logs')">
                <i class="bi bi-journal-text"></i> <span data-i18n="recycle_tab_logs">บันทึกการฝึกงาน</span>
                <span class="tab-count" id="tabCountLogs">0</span>
            </button>
            <button class="recycle-tab" data-table="evaluations" onclick="switchTable('evaluations')">
                <i class="bi bi-clipboard-check"></i> <span data-i18n="recycle_tab_evals">การประเมินผล</span>
                <span class="tab-count" id="tabCountEvals">0</span>
            </button>
        </div>

        <!-- BULK ACTION BAR -->
        <div class="recycle-bulk-bar" id="bulkBar">
            <div class="recycle-bulk-info">
                <i class="bi bi-check2-square"></i>
                <span><span data-i18n="recycle_selected_count">เลือกแล้ว</span> <strong id="selectedCount">0</strong> <span data-i18n="recycle_selected_unit">รายการ</span></span>
            </div>
            <div class="recycle-bulk-actions">
                <button class="audit-btn audit-btn-success audit-btn-sm" onclick="bulkRestore()">
                    <i class="bi bi-arrow-counterclockwise"></i> <span data-i18n="recycle_btn_restore_selected">กู้คืนที่เลือก</span>
                </button>
            </div>
        </div>

        <!-- TABLE CARD -->
        <div class="audit-table-card">
            <div class="audit-table-header">
                <h3>
                    <i class="bi bi-recycle text-danger"></i>
                    <span id="tableTitle">ถังขยะ - นักศึกษา</span>
                    <span class="record-count" id="tableCount">0 รายการ</span>
                </h3>
            </div>
            <div class="audit-table-wrap">
                <table class="audit-table" id="recycleTable">
                    <thead id="recycleTableHead">
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="audit-checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                            <th data-i18n="recycle_col_code">รหัส</th>
                            <th data-i18n="recycle_col_name">ชื่อ</th>
                            <th data-i18n="recycle_col_delete_date">วันที่ลบ</th>
                            <th data-i18n="recycle_col_expiry">วันหมดอายุ</th>
                            <th data-i18n="recycle_col_deleted_by">ลบโดย</th>
                            <th style="text-align:center" data-i18n="recycle_col_manage">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="recycleTableBody">
                        <tr>
                            <td colspan="7">
                                <div class="audit-loading">
                                    <div class="audit-spinner"></div>
                                    <span data-i18n="audit_loading">กำลังโหลดข้อมูล...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- CONFIRM DELETE MODAL -->
    <div class="confirm-modal-overlay" id="confirmModal">
        <div class="confirm-modal">
            <div class="confirm-modal-icon danger" id="confirmIcon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3 id="confirmTitle" data-i18n="recycle_confirm_delete_title">ยืนยันการลบถาวร</h3>
            <p id="confirmMessage">ข้อมูลที่ลบถาวรจะไม่สามารถกู้คืนได้อีก คุณแน่ใจหรือไม่?</p>
            <div class="confirm-modal-actions">
                <button class="audit-btn audit-btn-outline" onclick="closeConfirm()" data-i18n="recycle_btn_cancel">ยกเลิก</button>
                <button class="audit-btn audit-btn-danger" id="confirmBtn" onclick="executeConfirm()">
                    <i class="bi bi-trash3"></i> <span data-i18n="recycle_btn_confirm_delete">ยืนยันลบ</span>
                </button>
            </div>
        </div>
    </div>

    <!-- DOUBLE CONFIRM MODAL (for Empty All) -->
    <div class="confirm-modal-overlay" id="doubleConfirmModal">
        <div class="confirm-modal">
            <div class="confirm-modal-icon danger">
                <i class="bi bi-exclamation-octagon"></i>
            </div>
            <h3 data-i18n="recycle_double_confirm_title">ยืนยันครั้งสุดท้าย!</h3>
            <p id="doubleConfirmMessage">การกระทำนี้จะลบข้อมูลทั้งหมดในถังขยะอย่างถาวร ไม่สามารถกู้คืนได้<br><strong style="color:#ef4444">คุณแน่ใจจริงๆ หรือไม่?</strong></p>
            <div class="confirm-modal-actions">
                <button class="audit-btn audit-btn-outline" onclick="closeDoubleConfirm()" data-i18n="recycle_btn_cancel">ยกเลิก</button>
                <button class="audit-btn audit-btn-danger" onclick="executeEmptyBin()">
                    <i class="bi bi-trash3-fill"></i> <span data-i18n="recycle_btn_confirm_empty">ยืนยัน ลบทั้งหมดถาวร</span>
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="audit-toast-container" id="toastContainer"></div>

    <script>
    // =========================================================
    // AUTH CHECK
    // =========================================================
    let currentUser = null;

    async function checkAuth() {
        try {
            const res = await fetch('api.php?action=check_session');
            if (!res.ok) {
                showAuthRequiredState();
                setTimeout(() => { window.location.href = 'login.html'; }, 1500);
                return false;
            }
            currentUser = await res.json();
            if (!currentUser || currentUser.role !== 'admin') {
                showAuthForbiddenState();
                setTimeout(() => { window.location.href = 'index.html'; }, 2000);
                return false;
            }
            return true;
        } catch {
            showAuthRequiredState();
            setTimeout(() => { window.location.href = 'login.html'; }, 1500);
            return false;
        }
    }

    function showAuthRequiredState() {
        const body = document.getElementById('recycleTableBody');
        if (body) {
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon"><i class="bi bi-shield-lock text-warning" style="font-size:2.5rem;"></i></div><h4>กรุณาเข้าสู่ระบบ</h4><p>กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...</p></div></td></tr>`;
        }
    }

    function showAuthForbiddenState() {
        const body = document.getElementById('recycleTableBody');
        if (body) {
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon"><i class="bi bi-shield-x text-danger" style="font-size:2.5rem;"></i></div><h4>เฉพาะผู้ดูแลระบบ (Admin)</h4><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กำลังพากลับหน้าหลัก...</p></div></td></tr>`;
        }
    }

    // =========================================================
    // TOAST
    // =========================================================
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const iconClasses = { success: 'bi-check-circle-fill text-success', error: 'bi-x-circle-fill text-danger', info: 'bi-info-circle-fill text-info' };
        const toast = document.createElement('div');
        toast.className = `audit-toast ${type}`;
        toast.innerHTML = `<span class="toast-icon"><i class="bi ${iconClasses[type] || 'bi-info-circle-fill'}"></i></span><span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // =========================================================
    // THAI DATE HELPER
    // =========================================================
    const THAI_MONTHS_SHORT = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    const EN_MONTHS_SHORT   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function toLocalDate(dateStr, withTime = false) {
        if (!dateStr) return '-';
        const safeStr = String(dateStr).replace(/-/g, '/').replace('T', ' ');
        const d = new Date(safeStr);
        if (isNaN(d.getTime())) return '-';
        const lang = window.i18n ? window.i18n.getCurrentLanguage() : 'th';
        const pad = n => String(n).padStart(2, '0');

        if (lang === 'en') {
            const months = EN_MONTHS_SHORT;
            const day = d.getDate();
            const month = months[d.getMonth()];
            const year = d.getFullYear();
            return withTime
                ? `${day} ${month} ${year} ${pad(d.getHours())}:${pad(d.getMinutes())}`
                : `${day} ${month} ${year}`;
        } else {
            // ปฏิทินไทย (พ.ศ.)
            const months = THAI_MONTHS_SHORT;
            const day = d.getDate();
            const month = months[d.getMonth()];
            const year = d.getFullYear() + 543;
            return withTime
                ? `${day} ${month} ${year} ${pad(d.getHours())}:${pad(d.getMinutes())}`
                : `${day} ${month} ${year}`;
        }
    }

    /**
     * สร้าง HTML คอลัมน์วันที่ลบ + วันหมดอายุ
     */
    function renderExpiryCell(item) {
        const deletedDateStr = toLocalDate(item.deleted_at, false);
        const expiryDateStr  = item.expiry_date ? toLocalDate(item.expiry_date, false) : '-';
        const daysLeft       = item.days_left ?? 60;
        const urgency        = item.expiry_urgency || 'safe';

        const urgencyClass = { urgent: 'expiry-urgent', warning: 'expiry-warning', safe: 'expiry-safe' }[urgency] || 'expiry-safe';
        const urgencyIcon  = { urgent: '<i class="bi bi-exclamation-circle-fill text-danger me-1"></i>', warning: '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>', safe: '<i class="bi bi-check-circle-fill text-success me-1"></i>' }[urgency] || '<i class="bi bi-check-circle-fill text-success me-1"></i>';

        const daysLabel = window.i18n ? window.i18n.t('recycle_days_left_label','เหลืออีก') : 'เหลืออีก';
        const daysUnit  = window.i18n ? window.i18n.t('recycle_days_unit','วัน') : 'วัน';

        return `
            <td style="font-size:12px; color:#374151;">
                <span style="display:block; font-weight:600;">${deletedDateStr}</span>
            </td>
            <td style="font-size:11.5px;">
                <span class="expiry-badge ${urgencyClass}">${urgencyIcon} ${expiryDateStr}</span>
                <span class="expiry-date-text">${daysLabel} <strong>${daysLeft}</strong> ${daysUnit}</span>
            </td>
        `;
    }

    // =========================================================
    // STATE
    // =========================================================
    let currentTable = 'students';
    let selectedIds = new Set();
    let pendingAction = null;

    const tableTitleKeys = {
        'students':        'recycle_title_students',
        'mentors':         'recycle_title_mentors',
        'internship_logs': 'recycle_title_logs',
        'evaluations':     'recycle_title_evals'
    };
    const tableTitlesFallback = {
        'students': 'ถังขยะ - นักศึกษา',
        'mentors': 'ถังขยะ - พี่เลี้ยง',
        'internship_logs': 'ถังขยะ - บันทึกการฝึกงาน',
        'evaluations': 'ถังขยะ - การประเมินผล'
    };

    function getTableTitle(table) {
        const key = tableTitleKeys[table];
        return (key && window.i18n) ? window.i18n.t(key, tableTitlesFallback[table] || table) : (tableTitlesFallback[table] || table);
    }

    // =========================================================
    // SWITCH TABLE TAB
    // =========================================================
    function switchTable(table) {
        currentTable = table;
        selectedIds.clear();
        updateBulkBar();
        document.querySelectorAll('.recycle-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.table === table);
        });
        document.getElementById('tableTitle').textContent = getTableTitle(table);
        const sa = document.getElementById('selectAll');
        if (sa) sa.checked = false;
        loadRecycleBin();
    }

    // =========================================================
    // LOAD RECYCLE BIN
    // =========================================================
    async function loadRecycleBin() {
        const body = document.getElementById('recycleTableBody');
        const head = document.getElementById('recycleTableHead');
        const loadingText = window.i18n ? window.i18n.t('audit_loading','กำลังโหลดข้อมูล...') : 'กำลังโหลดข้อมูล...';
        body.innerHTML = `<tr><td colspan="8"><div class="audit-loading"><div class="audit-spinner"></div> ${loadingText}</div></td></tr>`;

        try {
            const res = await fetch(`audit_api.php?action=get_recycle_bin&table=${currentTable}`);
            const data = await res.json();

            if (!data.success) {
                body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon"><i class="bi bi-exclamation-triangle text-warning" style="font-size:2.5rem;"></i></div><h4>${data.error || 'เกิดข้อผิดพลาด'}</h4></div></td></tr>`;
                return;
            }

            updateCounts(data.counts);
            head.innerHTML = getTableHeader(currentTable);

            const items = data.items;
            const unitText = window.i18n ? window.i18n.t('audit_items_count','รายการ') : 'รายการ';
            document.getElementById('tableCount').textContent = `${items.length} ${unitText}`;

            if (items.length === 0) {
                const emptyTitle = window.i18n ? window.i18n.t('recycle_empty_bin','ถังขยะว่าง') : 'ถังขยะว่าง';
                const emptyDesc  = window.i18n ? window.i18n.t('recycle_empty_bin_desc','ไม่มีข้อมูลที่ถูกลบ') : 'ไม่มีข้อมูลที่ถูกลบ';
                const colSpan    = currentTable === 'students' ? 9 : 8;
                body.innerHTML = `<tr><td colspan="${colSpan}"><div class="audit-empty-state">
                    <div class="empty-icon"><i class="bi bi-check2-circle text-success" style="font-size:2.5rem;"></i></div>
                    <h4>${emptyTitle}</h4>
                    <p>${emptyDesc}</p>
                </div></td></tr>`;
                return;
            }

            body.innerHTML = items.map(item => getTableRow(currentTable, item)).join('');

        } catch (err) {
            console.error('loadRecycleBin error:', err);
            const errText = window.i18n ? window.i18n.t('audit_error','เกิดข้อผิดพลาด') : 'เกิดข้อผิดพลาด';
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon"><i class="bi bi-x-circle text-danger" style="font-size:2.5rem;"></i></div><h4>${errText}</h4></div></td></tr>`;
        }
    }

    function ti(key, fb) { return (window.i18n) ? window.i18n.t(key, fb) : fb; }

    function getTableHeader(table) {
        const checkbox     = `<th style="width:40px"><input type="checkbox" class="audit-checkbox" id="selectAll" onchange="toggleSelectAll()"></th>`;
        const colDeleteDate = `<th>${ti('recycle_col_delete_date','วันที่ลบ')}</th>`;
        const colExpiry     = `<th>${ti('recycle_col_expiry','วันหมดอายุ')}</th>`;
        const colDeletedBy  = `<th>${ti('recycle_col_deleted_by','ลบโดย')}</th>`;
        const colManage     = `<th style="text-align:center">${ti('recycle_col_manage','จัดการ')}</th>`;

        switch (table) {
            case 'students':
                return `<tr>${checkbox}<th>${ti('recycle_col_code','รหัสนักศึกษา')}</th><th>${ti('recycle_col_name','ชื่อ-นามสกุล')}</th><th>${ti('recycle_col_major','สาขา')}</th><th>${ti('recycle_col_mentor','พี่เลี้ยง')}</th>${colDeleteDate}${colExpiry}${colDeletedBy}${colManage}</tr>`;
            case 'mentors':
                return `<tr>${checkbox}<th>${ti('recycle_col_name','ชื่อพี่เลี้ยง')}</th><th>${ti('recycle_col_email','อีเมล')}</th><th>${ti('recycle_col_dept','แผนก')}</th>${colDeleteDate}${colExpiry}${colDeletedBy}${colManage}</tr>`;
            case 'internship_logs':
                return `<tr>${checkbox}<th>${ti('col_student','นักศึกษา')}</th><th>${ti('recycle_col_date','วันที่')}</th><th>${ti('recycle_col_work_desc','รายละเอียดงาน')}</th><th>${ti('recycle_col_status','สถานะ')}</th>${colDeleteDate}${colExpiry}${colDeletedBy}${colManage}</tr>`;
            case 'evaluations':
                return `<tr>${checkbox}<th>${ti('col_student','นักศึกษา')}</th><th>${ti('recycle_col_score_work','คะแนนงาน')}</th><th>${ti('recycle_col_score_time','คะแนนเวลา')}</th><th>${ti('recycle_col_score_behavior','คะแนนพฤติกรรม')}</th>${colDeleteDate}${colExpiry}${colDeletedBy}${colManage}</tr>`;
        }
    }

    function getTableRow(table, item) {
        const checkbox   = `<td><input type="checkbox" class="audit-checkbox item-checkbox" value="${item.id}" onchange="toggleItem(${item.id})"></td>`;
        const deletedBy  = escapeHtml(item.deleted_by_username || 'ระบบ');
        const expiryHtml = renderExpiryCell(item);
        const restoreLbl = ti('recycle_btn_restore','กู้คืน');

        const actionBtns = `<td style="text-align:center; white-space:nowrap">
            <button class="audit-btn audit-btn-success audit-btn-sm" onclick="restoreItem(${item.id})" title="${restoreLbl}">
                <i class="bi bi-arrow-counterclockwise"></i> ${restoreLbl}
            </button>
        </td>`;

        switch (table) {
            case 'students':
                return `<tr>${checkbox}
                    <td><strong>${escapeHtml(item.student_code||'-')}</strong></td>
                    <td>${escapeHtml(item.name||'-')}</td>
                    <td style="font-size:12.5px">${escapeHtml(item.major||'-')}</td>
                    <td style="font-size:12.5px">${escapeHtml(item.mentor_name||'ไม่ระบุ')}</td>
                    ${expiryHtml}
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;

            case 'mentors':
                return `<tr>${checkbox}
                    <td><strong>${escapeHtml(item.name||'-')}</strong></td>
                    <td style="font-size:12.5px">${escapeHtml(item.email||'-')}</td>
                    <td style="font-size:12.5px">${escapeHtml(item.department||'-')}</td>
                    ${expiryHtml}
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;

            case 'internship_logs':
                return `<tr>${checkbox}
                    <td><strong>${escapeHtml(item.student_name||'-')}</strong> <small style="color:#9ca3af">(${escapeHtml(item.student_code||'')})</small></td>
                    <td style="font-size:12.5px">${item.log_date||'-'}</td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escapeHtml(item.work_description||'')}">${escapeHtml(item.work_description||'-')}</td>
                    <td><span class="action-badge ${getStatusClass(item.status)}">${getStatusLabel(item.status)}</span></td>
                    ${expiryHtml}
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;

            case 'evaluations':
                return `<tr>${checkbox}
                    <td><strong>${escapeHtml(item.student_name||'-')}</strong> <small style="color:#9ca3af">(${escapeHtml(item.student_code||'')})</small></td>
                    <td style="text-align:center">${item.score_work??'-'}</td>
                    <td style="text-align:center">${item.score_time??'-'}</td>
                    <td style="text-align:center">${item.score_behavior??'-'}</td>
                    ${expiryHtml}
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;
        }
    }

    function getStatusClass(status) {
        return { approved:'create', pending:'login', revision:'update', rejected:'delete' }[status] || '';
    }

    function getStatusLabel(status) {
        const labels = {
            approved: ti('filter_status_approved', 'อนุมัติแล้ว'),
            pending: ti('filter_status_pending', 'รออนุมัติ'),
            revision: ti('filter_status_revision', 'ส่งกลับแก้ไข'),
            rejected: ti('filter_status_rejected', 'ปฏิเสธ')
        };
        return labels[status] || status || '-';
    }

    // =========================================================
    // SELECTION
    // =========================================================
    function toggleSelectAll() {
        const checked = document.getElementById('selectAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => {
            cb.checked = checked;
            const id = parseInt(cb.value);
            if (checked) selectedIds.add(id); else selectedIds.delete(id);
        });
        updateBulkBar();
    }

    function toggleItem(id) {
        selectedIds.has(id) ? selectedIds.delete(id) : selectedIds.add(id);
        updateBulkBar();
        const allCbs = document.querySelectorAll('.item-checkbox');
        document.getElementById('selectAll').checked = allCbs.length > 0 && [...allCbs].every(cb => cb.checked);
    }

    function updateBulkBar() {
        const bar = document.getElementById('bulkBar');
        const count = selectedIds.size;
        if (count > 0) { bar.classList.add('show'); document.getElementById('selectedCount').textContent = count; }
        else { bar.classList.remove('show'); }
    }

    // =========================================================
    // RESTORE
    // =========================================================
    async function restoreItem(id) {
        try {
            const res = await fetch('audit_api.php?action=restore_item', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ table: currentTable, id: id })
            });
            const data = await res.json();
            if (data.success) {
                showToast(ti('recycle_toast_restored','กู้คืนข้อมูลเรียบร้อยแล้ว'), 'success');
                selectedIds.delete(id);
                loadRecycleBin();
            } else {
                showToast(data.error || ti('recycle_toast_error','เกิดข้อผิดพลาด'), 'error');
            }
        } catch { showToast(ti('recycle_toast_error','เกิดข้อผิดพลาด'), 'error'); }
    }

    async function bulkRestore() {
        if (selectedIds.size === 0) return;
        try {
            const res = await fetch('audit_api.php?action=bulk_restore', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ table: currentTable, ids: [...selectedIds] })
            });
            const data = await res.json();
            if (data.success) { showToast(data.message,'success'); selectedIds.clear(); updateBulkBar(); loadRecycleBin(); }
            else { showToast(data.error || ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
        } catch { showToast(ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
    }

    // =========================================================
    // DELETE PERMANENTLY
    // =========================================================
    function confirmSingleDelete(id) {
        pendingAction = { type: 'single', id: id };
        document.getElementById('confirmTitle').textContent = ti('recycle_confirm_delete_title','ยืนยันการลบถาวร');
        document.getElementById('confirmMessage').innerHTML = `${ti('recycle_confirm_delete_msg','ข้อมูลนี้จะถูกลบถาวรและไม่สามารถกู้คืนได้อีก')}<br><strong style="color:#ef4444">คุณยืนยันที่จะลบหรือไม่?</strong>`;
        document.getElementById('confirmModal').classList.add('show');
    }

    function confirmBulkDelete() {
        if (selectedIds.size === 0) return;
        pendingAction = { type: 'bulk' };
        document.getElementById('confirmTitle').textContent = `${ti('recycle_confirm_delete_title','ยืนยันการลบถาวร')} ${selectedIds.size} ${ti('recycle_selected_unit','รายการ')}`;
        document.getElementById('confirmMessage').innerHTML = `ข้อมูลทั้ง <strong>${selectedIds.size}</strong> รายการจะถูกลบถาวร<br><strong style="color:#ef4444">ไม่สามารถกู้คืนได้อีก</strong>`;
        document.getElementById('confirmModal').classList.add('show');
    }

    function confirmEmptyBin() {
        pendingAction = { type: 'empty' };
        document.getElementById('confirmTitle').textContent = `${ti('recycle_btn_empty_bin','ลบทั้งหมด')}`;
        document.getElementById('confirmMessage').innerHTML = `คุณต้องการลบข้อมูลทั้งหมดใน "${getTableTitle(currentTable)}" อย่างถาวรหรือไม่?<br><strong style="color:#ef4444">การกระทำนี้ไม่สามารถย้อนกลับได้</strong>`;
        document.getElementById('confirmModal').classList.add('show');
    }

    function closeConfirm() { document.getElementById('confirmModal').classList.remove('show'); pendingAction = null; }
    function closeDoubleConfirm() { document.getElementById('doubleConfirmModal').classList.remove('show'); }

    async function executeConfirm() {
        closeConfirm();
        if (!pendingAction) return;
        if (pendingAction.type === 'single') await permanentDeleteItem(pendingAction.id);
        else if (pendingAction.type === 'bulk') await bulkPermanentDelete();
        else if (pendingAction.type === 'empty') {
            document.getElementById('doubleConfirmMessage').innerHTML = `คุณกำลังจะลบข้อมูลทั้งหมดใน "${getTableTitle(currentTable)}" อย่างถาวร<br><strong style="color:#ef4444">ไม่สามารถกู้คืนข้อมูลได้อีก</strong><br><br>คุณแน่ใจจริงๆ หรือไม่?`;
            document.getElementById('doubleConfirmModal').classList.add('show');
        }
        pendingAction = null;
    }

    async function permanentDeleteItem(id) {
        try {
            const res = await fetch('audit_api.php?action=permanent_delete', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ table: currentTable, id: id })
            });
            const data = await res.json();
            if (data.success) { showToast(ti('recycle_toast_deleted','ลบข้อมูลถาวรเรียบร้อยแล้ว'),'success'); selectedIds.delete(id); loadRecycleBin(); }
            else { showToast(data.error || ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
        } catch { showToast(ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
    }

    async function bulkPermanentDelete() {
        if (selectedIds.size === 0) return;
        try {
            const res = await fetch('audit_api.php?action=bulk_permanent_delete', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ table: currentTable, ids: [...selectedIds] })
            });
            const data = await res.json();
            if (data.success) { showToast(data.message,'success'); selectedIds.clear(); updateBulkBar(); loadRecycleBin(); }
            else { showToast(data.error || ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
        } catch { showToast(ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
    }

    async function executeEmptyBin() {
        closeDoubleConfirm();
        try {
            const res = await fetch('audit_api.php?action=empty_recycle_bin', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ table: currentTable })
            });
            const data = await res.json();
            if (data.success) { showToast(data.message,'success'); selectedIds.clear(); updateBulkBar(); loadRecycleBin(); }
            else { showToast(data.error || ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
        } catch { showToast(ti('recycle_toast_error','เกิดข้อผิดพลาด'),'error'); }
    }

    // =========================================================
    // UPDATE COUNTS
    // =========================================================
    function updateCounts(counts) {
        if (!counts) return;
        document.getElementById('statTotalBin').textContent    = counts.total || 0;
        document.getElementById('statStudentsBin').textContent = counts.students || 0;
        document.getElementById('statMentorsBin').textContent  = counts.mentors || 0;
        document.getElementById('statLogsBin').textContent     = counts.internship_logs || 0;
        document.getElementById('statEvalsBin').textContent    = counts.evaluations || 0;
        document.getElementById('tabCountStudents').textContent = counts.students || 0;
        document.getElementById('tabCountMentors').textContent  = counts.mentors || 0;
        document.getElementById('tabCountLogs').textContent     = counts.internship_logs || 0;
        document.getElementById('tabCountEvals').textContent    = counts.evaluations || 0;
        if (counts.total > 0) {
            const badge = document.getElementById('recycleBinBadge');
            badge.textContent = counts.total;
            badge.style.display = 'inline-block';
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeConfirm(); closeDoubleConfirm(); }
    });
    document.getElementById('confirmModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeConfirm(); });
    document.getElementById('doubleConfirmModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeDoubleConfirm(); });

    // Re-render when language changes
    window.addEventListener('languageChanged', () => {
        document.getElementById('tableTitle').textContent = getTableTitle(currentTable);
        loadRecycleBin();
    });

    // =========================================================
    // INIT
    // =========================================================
    (async function init() {
        const authed = await checkAuth();
        if (!authed) return;
        loadRecycleBin();
    })();
    </script>
</body>


</html>
