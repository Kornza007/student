<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ถังขยะระบบ - กู้คืนหรือลบข้อมูลถาวร">
    <title>🗑️ ถังขยะระบบ | ระบบจัดการนักศึกษาฝึกงาน</title>
    <link rel="stylesheet" href="audit_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- i18n Multi-Language Scripts -->
    <script src="lang/th.js"></script>
    <script src="lang/en.js"></script>
    <script src="lang/i18n.js"></script>
</head>
<body class="audit-page" data-i18n-page-title="recycle_bin_page_title">

    <!-- TOP NAVIGATION -->
    <nav class="audit-topbar">
        <a href="index.html" class="audit-topbar-brand">
            <div class="brand-icon">🛡️</div>
            <div class="brand-text">
                <span data-i18n="app_title">ระบบตรวจสอบ</span>
                <small>Audit & Monitoring</small>
            </div>
        </a>

        <div class="audit-nav-tabs">
            <a href="admin_audit_logs.php" class="audit-nav-tab">
                <span class="tab-icon">📊</span>
                <span data-i18n="tab_audit_logs">ตรวจสอบข้อมูลระบบ</span>
            </a>
            <a href="admin_recycle_bin.php" class="audit-nav-tab active">
                <span class="tab-icon">🗑️</span>
                <span data-i18n="tab_recycle_bin">ถังขยะระบบ</span>
                <span class="tab-badge" id="recycleBinBadge" style="display:none">0</span>
            </a>
        </div>

        <div class="audit-topbar-actions">
            <!-- Language Switcher (Single Switch Toggle TH | EN) -->
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

        <!-- STATS CARDS -->
        <div class="audit-stats-grid" id="recycleStats">
            <div class="audit-stat-card">
                <div class="audit-stat-icon red"><i class="bi bi-trash3"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statTotalBin">0</h4>
                    <p>รายการทั้งหมดในถังขยะ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon blue"><i class="bi bi-mortarboard"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statStudentsBin">0</h4>
                    <p>นักศึกษาที่ลบ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon green"><i class="bi bi-person-badge"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statMentorsBin">0</h4>
                    <p>พี่เลี้ยงที่ลบ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon orange"><i class="bi bi-journal-text"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statLogsBin">0</h4>
                    <p>บันทึกงานที่ลบ</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon cyan"><i class="bi bi-clipboard-check"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statEvalsBin">0</h4>
                    <p>การประเมินที่ลบ</p>
                </div>
            </div>
        </div>

        <!-- TABLE TABS -->
        <div class="recycle-tabs" id="recycleTabs">
            <button class="recycle-tab active" data-table="students" onclick="switchTable('students')">
                <i class="bi bi-mortarboard"></i> นักศึกษา
                <span class="tab-count" id="tabCountStudents">0</span>
            </button>
            <button class="recycle-tab" data-table="mentors" onclick="switchTable('mentors')">
                <i class="bi bi-person-badge"></i> พี่เลี้ยง
                <span class="tab-count" id="tabCountMentors">0</span>
            </button>
            <button class="recycle-tab" data-table="internship_logs" onclick="switchTable('internship_logs')">
                <i class="bi bi-journal-text"></i> บันทึกการฝึกงาน
                <span class="tab-count" id="tabCountLogs">0</span>
            </button>
            <button class="recycle-tab" data-table="evaluations" onclick="switchTable('evaluations')">
                <i class="bi bi-clipboard-check"></i> การประเมินผล
                <span class="tab-count" id="tabCountEvals">0</span>
            </button>
        </div>

        <!-- BULK ACTION BAR -->
        <div class="recycle-bulk-bar" id="bulkBar">
            <div class="recycle-bulk-info">
                <i class="bi bi-check2-square"></i>
                <span>เลือกแล้ว <strong id="selectedCount">0</strong> รายการ</span>
            </div>
            <div class="recycle-bulk-actions">
                <button class="audit-btn audit-btn-success audit-btn-sm" onclick="bulkRestore()">
                    <i class="bi bi-arrow-counterclockwise"></i> กู้คืนที่เลือก
                </button>
                <button class="audit-btn audit-btn-danger audit-btn-sm" onclick="confirmBulkDelete()">
                    <i class="bi bi-trash3"></i> ลบถาวรที่เลือก
                </button>
            </div>
        </div>

        <!-- TABLE CARD -->
        <div class="audit-table-card">
            <div class="audit-table-header">
                <h3>
                    <i class="bi bi-recycle"></i>
                    <span id="tableTitle">ถังขยะ - นักศึกษา</span>
                    <span class="record-count" id="tableCount">0 รายการ</span>
                </h3>
                <div style="display:flex; gap:8px">
                    <button class="audit-btn audit-btn-danger audit-btn-sm" onclick="confirmEmptyBin()" id="emptyBinBtn">
                        <i class="bi bi-trash3-fill"></i> ลบทั้งหมด
                    </button>
                </div>
            </div>
            <div class="audit-table-wrap">
                <table class="audit-table" id="recycleTable">
                    <thead id="recycleTableHead">
                        <tr>
                            <th style="width:40px"><input type="checkbox" class="audit-checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                            <th>รหัส</th>
                            <th>ชื่อ</th>
                            <th>ลบเมื่อ</th>
                            <th>ลบโดย</th>
                            <th style="text-align:center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="recycleTableBody">
                        <tr>
                            <td colspan="6">
                                <div class="audit-loading">
                                    <div class="audit-spinner"></div>
                                    กำลังโหลดข้อมูล...
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
            <h3 id="confirmTitle">ยืนยันการลบถาวร</h3>
            <p id="confirmMessage">ข้อมูลที่ลบถาวรจะไม่สามารถกู้คืนได้อีก คุณแน่ใจหรือไม่?</p>
            <div class="confirm-modal-actions">
                <button class="audit-btn audit-btn-outline" onclick="closeConfirm()">ยกเลิก</button>
                <button class="audit-btn audit-btn-danger" id="confirmBtn" onclick="executeConfirm()">
                    <i class="bi bi-trash3"></i> ยืนยันลบ
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
            <h3>⚠️ ยืนยันครั้งสุดท้าย!</h3>
            <p id="doubleConfirmMessage">การกระทำนี้จะลบข้อมูลทั้งหมดในถังขยะอย่างถาวร ไม่สามารถกู้คืนได้<br><strong style="color:#ef4444">คุณแน่ใจจริงๆ หรือไม่?</strong></p>
            <div class="confirm-modal-actions">
                <button class="audit-btn audit-btn-outline" onclick="closeDoubleConfirm()">ยกเลิก</button>
                <button class="audit-btn audit-btn-danger" onclick="executeEmptyBin()">
                    <i class="bi bi-trash3-fill"></i> ยืนยัน ลบทั้งหมดถาวร
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
                window.location.href = 'login.html';
                return false;
            }
            currentUser = await res.json();
            if (!currentUser || currentUser.role !== 'admin') {
                alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
                window.location.href = 'index.html';
                return false;
            }
            return true;
        } catch {
            window.location.href = 'login.html';
            return false;
        }
    }

    // =========================================================
    // TOAST
    // =========================================================
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const icons = { success: '✅', error: '❌', info: 'ℹ️' };
        
        const toast = document.createElement('div');
        toast.className = `audit-toast ${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
            <span>${message}</span>
        `;
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(40px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }

    // =========================================================
    // STATE
    // =========================================================
    let currentTable = 'students';
    let selectedIds = new Set();
    let pendingAction = null;

    const tableTitles = {
        'students': 'ถังขยะ - นักศึกษา',
        'mentors': 'ถังขยะ - พี่เลี้ยง',
        'internship_logs': 'ถังขยะ - บันทึกการฝึกงาน',
        'evaluations': 'ถังขยะ - การประเมินผล'
    };

    // =========================================================
    // SWITCH TABLE TAB
    // =========================================================
    function switchTable(table) {
        currentTable = table;
        selectedIds.clear();
        updateBulkBar();

        // Update tab active state
        document.querySelectorAll('.recycle-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.table === table);
        });

        document.getElementById('tableTitle').textContent = tableTitles[table] || table;
        document.getElementById('selectAll').checked = false;
        
        loadRecycleBin();
    }

    // =========================================================
    // LOAD RECYCLE BIN
    // =========================================================
    async function loadRecycleBin() {
        const body = document.getElementById('recycleTableBody');
        const head = document.getElementById('recycleTableHead');
        body.innerHTML = `<tr><td colspan="8"><div class="audit-loading"><div class="audit-spinner"></div> กำลังโหลดข้อมูล...</div></td></tr>`;

        try {
            const res = await fetch(`audit_api.php?action=get_recycle_bin&table=${currentTable}`);
            const data = await res.json();

            if (!data.success) {
                body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon">⚠️</div><h4>${data.error}</h4></div></td></tr>`;
                return;
            }

            // Update counts
            updateCounts(data.counts);

            // Render table header based on table type
            head.innerHTML = getTableHeader(currentTable);

            const items = data.items;
            document.getElementById('tableCount').textContent = `${items.length} รายการ`;

            if (items.length === 0) {
                const colSpan = currentTable === 'students' ? 8 : 6;
                body.innerHTML = `<tr><td colspan="${colSpan}"><div class="audit-empty-state">
                    <div class="empty-icon">🎉</div>
                    <h4>ถังขยะว่าง</h4>
                    <p>ไม่มีข้อมูลที่ถูกลบ</p>
                </div></td></tr>`;
                return;
            }

            body.innerHTML = items.map(item => getTableRow(currentTable, item)).join('');

        } catch (err) {
            console.error('loadRecycleBin error:', err);
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon">❌</div><h4>เกิดข้อผิดพลาด</h4></div></td></tr>`;
        }
    }

    function getTableHeader(table) {
        const checkbox = `<th style="width:40px"><input type="checkbox" class="audit-checkbox" id="selectAll" onchange="toggleSelectAll()"></th>`;
        
        switch (table) {
            case 'students':
                return `<tr>${checkbox}<th>รหัสนักศึกษา</th><th>ชื่อ-นามสกุล</th><th>สาขา</th><th>พี่เลี้ยง</th><th>ลบเมื่อ</th><th>ลบโดย</th><th style="text-align:center">จัดการ</th></tr>`;
            case 'mentors':
                return `<tr>${checkbox}<th>ชื่อพี่เลี้ยง</th><th>อีเมล</th><th>แผนก</th><th>ลบเมื่อ</th><th>ลบโดย</th><th style="text-align:center">จัดการ</th></tr>`;
            case 'internship_logs':
                return `<tr>${checkbox}<th>นักศึกษา</th><th>วันที่</th><th>รายละเอียดงาน</th><th>สถานะ</th><th>ลบเมื่อ</th><th>ลบโดย</th><th style="text-align:center">จัดการ</th></tr>`;
            case 'evaluations':
                return `<tr>${checkbox}<th>นักศึกษา</th><th>คะแนนงาน</th><th>คะแนนเวลา</th><th>คะแนนพฤติกรรม</th><th>ลบเมื่อ</th><th>ลบโดย</th><th style="text-align:center">จัดการ</th></tr>`;
        }
    }

    function getTableRow(table, item) {
        const checkbox = `<td><input type="checkbox" class="audit-checkbox item-checkbox" value="${item.id}" onchange="toggleItem(${item.id})"></td>`;
        const deletedAt = formatDateTime(item.deleted_at);
        const deletedBy = escapeHtml(item.deleted_by_username || 'ระบบ');
        
        const actionBtns = `<td style="text-align:center; white-space:nowrap">
            <button class="audit-btn audit-btn-success audit-btn-sm" onclick="restoreItem(${item.id})" title="กู้คืน">
                <i class="bi bi-arrow-counterclockwise"></i> กู้คืน
            </button>
            <button class="audit-btn audit-btn-danger audit-btn-sm" onclick="confirmSingleDelete(${item.id})" title="ลบถาวร" style="margin-left:4px">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`;

        switch (table) {
            case 'students':
                return `<tr>
                    ${checkbox}
                    <td><strong>${escapeHtml(item.student_code || '-')}</strong></td>
                    <td>${escapeHtml(item.name || '-')}</td>
                    <td style="font-size:12.5px">${escapeHtml(item.major || '-')}</td>
                    <td style="font-size:12.5px">${escapeHtml(item.mentor_name || 'ไม่ระบุ')}</td>
                    <td style="font-size:12px; color:#6b7280">${deletedAt}</td>
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;
            
            case 'mentors':
                return `<tr>
                    ${checkbox}
                    <td><strong>${escapeHtml(item.name || '-')}</strong></td>
                    <td style="font-size:12.5px">${escapeHtml(item.email || '-')}</td>
                    <td style="font-size:12.5px">${escapeHtml(item.department || '-')}</td>
                    <td style="font-size:12px; color:#6b7280">${deletedAt}</td>
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;
            
            case 'internship_logs':
                return `<tr>
                    ${checkbox}
                    <td><strong>${escapeHtml(item.student_name || '-')}</strong> <small style="color:#9ca3af">(${escapeHtml(item.student_code || '')})</small></td>
                    <td style="font-size:12.5px">${item.log_date || '-'}</td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap" title="${escapeHtml(item.work_description || '')}">${escapeHtml(item.work_description || '-')}</td>
                    <td><span class="action-badge ${getStatusClass(item.status)}">${getStatusLabel(item.status)}</span></td>
                    <td style="font-size:12px; color:#6b7280">${deletedAt}</td>
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;
            
            case 'evaluations':
                return `<tr>
                    ${checkbox}
                    <td><strong>${escapeHtml(item.student_name || '-')}</strong> <small style="color:#9ca3af">(${escapeHtml(item.student_code || '')})</small></td>
                    <td style="text-align:center">${item.score_work ?? '-'}</td>
                    <td style="text-align:center">${item.score_time ?? '-'}</td>
                    <td style="text-align:center">${item.score_behavior ?? '-'}</td>
                    <td style="font-size:12px; color:#6b7280">${deletedAt}</td>
                    <td style="font-size:12.5px">${deletedBy}</td>
                    ${actionBtns}
                </tr>`;
        }
    }

    function getStatusClass(status) {
        const classes = { 'approved': 'create', 'pending': 'login', 'revision': 'update', 'rejected': 'delete' };
        return classes[status] || '';
    }

    function getStatusLabel(status) {
        const labels = { 'approved': '✅ อนุมัติ', 'pending': '⏳ รอ', 'revision': '🔄 แก้ไข', 'rejected': '❌ ปฏิเสธ' };
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
            if (checked) selectedIds.add(id);
            else selectedIds.delete(id);
        });
        updateBulkBar();
    }

    function toggleItem(id) {
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
        } else {
            selectedIds.add(id);
        }
        updateBulkBar();

        // Update select all checkbox
        const allCheckboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = allCheckboxes.length > 0 && [...allCheckboxes].every(cb => cb.checked);
        document.getElementById('selectAll').checked = allChecked;
    }

    function updateBulkBar() {
        const bar = document.getElementById('bulkBar');
        const count = selectedIds.size;
        
        if (count > 0) {
            bar.classList.add('show');
            document.getElementById('selectedCount').textContent = count;
        } else {
            bar.classList.remove('show');
        }
    }

    // =========================================================
    // RESTORE
    // =========================================================
    async function restoreItem(id) {
        try {
            const res = await fetch('audit_api.php?action=restore_item', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ table: currentTable, id: id })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast('กู้คืนข้อมูลเรียบร้อยแล้ว', 'success');
                selectedIds.delete(id);
                loadRecycleBin();
            } else {
                showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
            }
        } catch (err) {
            showToast('เกิดข้อผิดพลาดในการกู้คืน', 'error');
        }
    }

    async function bulkRestore() {
        if (selectedIds.size === 0) return;
        
        try {
            const res = await fetch('audit_api.php?action=bulk_restore', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ table: currentTable, ids: [...selectedIds] })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                selectedIds.clear();
                updateBulkBar();
                loadRecycleBin();
            } else {
                showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
            }
        } catch (err) {
            showToast('เกิดข้อผิดพลาดในการกู้คืน', 'error');
        }
    }

    // =========================================================
    // DELETE PERMANENTLY
    // =========================================================
    function confirmSingleDelete(id) {
        pendingAction = { type: 'single', id: id };
        document.getElementById('confirmTitle').textContent = 'ยืนยันการลบถาวร';
        document.getElementById('confirmMessage').innerHTML = 'ข้อมูลนี้จะถูกลบถาวรและ<strong style="color:#ef4444">ไม่สามารถกู้คืนได้อีก</strong><br>คุณยืนยันที่จะลบหรือไม่?';
        document.getElementById('confirmModal').classList.add('show');
    }

    function confirmBulkDelete() {
        if (selectedIds.size === 0) return;
        pendingAction = { type: 'bulk' };
        document.getElementById('confirmTitle').textContent = `ยืนยันการลบถาวร ${selectedIds.size} รายการ`;
        document.getElementById('confirmMessage').innerHTML = `ข้อมูลทั้ง <strong>${selectedIds.size}</strong> รายการที่เลือกจะถูกลบถาวร<br><strong style="color:#ef4444">ไม่สามารถกู้คืนได้อีก</strong> คุณยืนยันหรือไม่?`;
        document.getElementById('confirmModal').classList.add('show');
    }

    function confirmEmptyBin() {
        pendingAction = { type: 'empty' };
        document.getElementById('confirmTitle').textContent = '⚠️ ลบข้อมูลทั้งหมดในถังขยะ';
        document.getElementById('confirmMessage').innerHTML = `คุณต้องการลบข้อมูลทั้งหมดในถังขยะ (${tableTitles[currentTable] || 'ทั้งหมด'}) อย่างถาวรหรือไม่?<br><strong style="color:#ef4444">การกระทำนี้ไม่สามารถย้อนกลับได้</strong>`;
        document.getElementById('confirmModal').classList.add('show');
    }

    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('show');
        pendingAction = null;
    }

    function closeDoubleConfirm() {
        document.getElementById('doubleConfirmModal').classList.remove('show');
    }

    async function executeConfirm() {
        closeConfirm();

        if (!pendingAction) return;

        if (pendingAction.type === 'single') {
            await permanentDeleteItem(pendingAction.id);
        } else if (pendingAction.type === 'bulk') {
            await bulkPermanentDelete();
        } else if (pendingAction.type === 'empty') {
            // Show double confirm
            document.getElementById('doubleConfirmMessage').innerHTML = 
                `คุณกำลังจะลบข้อมูลทั้งหมดใน "${tableTitles[currentTable]}" อย่างถาวร<br><strong style="color:#ef4444">ไม่สามารถกู้คืนข้อมูลได้อีก</strong><br><br>คุณแน่ใจจริงๆ หรือไม่?`;
            document.getElementById('doubleConfirmModal').classList.add('show');
        }

        pendingAction = null;
    }

    async function permanentDeleteItem(id) {
        try {
            const res = await fetch('audit_api.php?action=permanent_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ table: currentTable, id: id })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast('ลบข้อมูลถาวรเรียบร้อยแล้ว', 'success');
                selectedIds.delete(id);
                loadRecycleBin();
            } else {
                showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
            }
        } catch (err) {
            showToast('เกิดข้อผิดพลาดในการลบ', 'error');
        }
    }

    async function bulkPermanentDelete() {
        if (selectedIds.size === 0) return;
        
        try {
            const res = await fetch('audit_api.php?action=bulk_permanent_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ table: currentTable, ids: [...selectedIds] })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                selectedIds.clear();
                updateBulkBar();
                loadRecycleBin();
            } else {
                showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
            }
        } catch (err) {
            showToast('เกิดข้อผิดพลาดในการลบ', 'error');
        }
    }

    async function executeEmptyBin() {
        closeDoubleConfirm();

        try {
            const res = await fetch('audit_api.php?action=empty_recycle_bin', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ table: currentTable })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                selectedIds.clear();
                updateBulkBar();
                loadRecycleBin();
            } else {
                showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
            }
        } catch (err) {
            showToast('เกิดข้อผิดพลาดในการล้างถังขยะ', 'error');
        }
    }

    // =========================================================
    // UPDATE COUNTS
    // =========================================================
    function updateCounts(counts) {
        if (!counts) return;
        
        document.getElementById('statTotalBin').textContent = counts.total || 0;
        document.getElementById('statStudentsBin').textContent = counts.students || 0;
        document.getElementById('statMentorsBin').textContent = counts.mentors || 0;
        document.getElementById('statLogsBin').textContent = counts.internship_logs || 0;
        document.getElementById('statEvalsBin').textContent = counts.evaluations || 0;

        document.getElementById('tabCountStudents').textContent = counts.students || 0;
        document.getElementById('tabCountMentors').textContent = counts.mentors || 0;
        document.getElementById('tabCountLogs').textContent = counts.internship_logs || 0;
        document.getElementById('tabCountEvals').textContent = counts.evaluations || 0;

        // Badge
        if (counts.total > 0) {
            const badge = document.getElementById('recycleBinBadge');
            badge.textContent = counts.total;
            badge.style.display = 'inline-block';
        }
    }

    // =========================================================
    // HELPERS
    // =========================================================
    function formatDateTime(dt) {
        if (!dt) return '-';
        const d = new Date(dt);
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // Close modals on Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeConfirm();
            closeDoubleConfirm();
        }
    });

    // Close modal on overlay click
    document.getElementById('confirmModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeConfirm();
    });
    document.getElementById('doubleConfirmModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeDoubleConfirm();
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
