<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบตรวจสอบประวัติการใช้งาน - Admin Dashboard">
    <title>📊 ตรวจสอบข้อมูลระบบ | ระบบจัดการนักศึกษาฝึกงาน</title>
    <link rel="stylesheet" href="audit_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- i18n Multi-Language Scripts -->
    <script src="lang/th.js"></script>
    <script src="lang/en.js"></script>
    <script src="lang/i18n.js"></script>
</head>
<body class="audit-page" data-i18n-page-title="audit_page_title">

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
            <a href="admin_audit_logs.php" class="audit-nav-tab active">
                <span class="tab-icon">📊</span>
                <span data-i18n="tab_audit_logs">ตรวจสอบข้อมูลระบบ</span>
            </a>
            <a href="admin_recycle_bin.php" class="audit-nav-tab" id="recycleBinTab">
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
        <div class="audit-stats-grid" id="statsGrid">
            <div class="audit-stat-card">
                <div class="audit-stat-icon blue"><i class="bi bi-activity"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statTotalToday">-</h4>
                    <p data-i18n="audit_stat_today_label">กิจกรรมวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon green"><i class="bi bi-plus-circle"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statCreates">-</h4>
                    <p data-i18n="audit_stat_creates_label">เพิ่มข้อมูลวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon orange"><i class="bi bi-pencil-square"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statUpdates">-</h4>
                    <p data-i18n="audit_stat_updates_label">แก้ไขข้อมูลวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon red"><i class="bi bi-trash3"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statDeletes">-</h4>
                    <p data-i18n="audit_stat_deletes_label">ลบข้อมูลวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon cyan"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statLogins">-</h4>
                    <p data-i18n="audit_stat_logins_label">เข้าสู่ระบบวันนี้</p>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="audit-filter-bar">
            <h3><i class="bi bi-funnel"></i> <span data-i18n="audit_filter_title">ตัวกรองข้อมูล</span></h3>
            <div class="audit-filter-row">
                <div class="audit-filter-group">
                    <label data-i18n="audit_filter_date_from">วันที่เริ่มต้น</label>
                    <input type="date" id="filterDateFrom">
                </div>
                <div class="audit-filter-group">
                    <label data-i18n="audit_filter_date_to">วันที่สิ้นสุด</label>
                    <input type="date" id="filterDateTo">
                </div>
                <div class="audit-filter-group">
                    <label data-i18n="audit_filter_action_type">ประเภทการกระทำ</label>
                    <select id="filterAction">
                        <option value="" data-i18n-opt="audit_filter_all">ทั้งหมด</option>
                        <option value="CREATE" data-i18n-opt="audit_action_create">➕ เพิ่มข้อมูล</option>
                        <option value="UPDATE" data-i18n-opt="audit_action_update">✏️ แก้ไขข้อมูล</option>
                        <option value="DELETE" data-i18n-opt="audit_action_delete">🗑️ ลบข้อมูล</option>
                        <option value="LOGIN" data-i18n-opt="audit_action_login">🔑 เข้าสู่ระบบ</option>
                        <option value="LOGOUT" data-i18n-opt="audit_action_logout">🚪 ออกจากระบบ</option>
                        <option value="RESTORE" data-i18n-opt="audit_action_restore">♻️ กู้คืนข้อมูล</option>
                        <option value="PERMANENT_DELETE" data-i18n-opt="audit_action_perm_delete">💀 ลบถาวร</option>
                    </select>
                </div>
                <div class="audit-filter-group">
                    <label data-i18n="audit_filter_table">ตาราง</label>
                    <select id="filterTable">
                        <option value="" data-i18n-opt="audit_filter_all">ทั้งหมด</option>
                        <option value="students" data-i18n-opt="audit_table_students">นักศึกษา</option>
                        <option value="mentors" data-i18n-opt="audit_table_mentors">พี่เลี้ยง</option>
                        <option value="internship_logs" data-i18n-opt="audit_table_logs">บันทึกการฝึกงาน</option>
                        <option value="evaluations" data-i18n-opt="audit_table_evaluations">การประเมินผล</option>
                        <option value="users" data-i18n-opt="audit_table_users">บัญชีผู้ใช้</option>
                    </select>
                </div>
                <div class="audit-filter-group">
                    <label data-i18n="audit_filter_user">ผู้ใช้</label>
                    <select id="filterUser">
                        <option value="" data-i18n-opt="audit_filter_all">ทั้งหมด</option>
                    </select>
                </div>
                <div class="audit-filter-group">
                    <label data-i18n="audit_filter_search">ค้นหา</label>
                    <input type="text" id="filterSearch" data-i18n-placeholder="audit_filter_search_placeholder" placeholder="ค้นหาคำอธิบาย...">
                </div>
                <div class="audit-filter-actions">
                    <button class="audit-btn audit-btn-primary" onclick="loadAuditLogs(1)">
                        <i class="bi bi-search"></i> <span data-i18n="audit_btn_search">ค้นหา</span>
                    </button>
                    <button class="audit-btn audit-btn-outline" onclick="clearFilters()">
                        <i class="bi bi-x-lg"></i> <span data-i18n="audit_btn_clear">ล้าง</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- AUDIT LOGS TABLE -->
        <div class="audit-table-card">
            <div class="audit-table-header">
                <h3>
                    <i class="bi bi-clock-history"></i>
                    <span data-i18n="audit_table_title">ประวัติการใช้งานระบบ</span>
                    <span class="record-count" id="totalRecords">0 รายการ</span>
                </h3>
            </div>
            <div class="audit-table-wrap">
                <table class="audit-table" id="auditTable">
                    <thead>
                        <tr>
                            <th data-i18n="audit_col_time">เวลา</th>
                            <th data-i18n="audit_col_user">ผู้ใช้</th>
                            <th data-i18n="audit_col_role">Role</th>
                            <th data-i18n="audit_col_action">การกระทำ</th>
                            <th data-i18n="audit_col_table">ตาราง</th>
                            <th data-i18n="audit_col_desc">คำอธิบาย</th>
                            <th data-i18n="audit_col_ip">IP</th>
                            <th style="text-align:center" data-i18n="audit_col_detail">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr>
                            <td colspan="8">
                                <div class="audit-loading">
                                    <div class="audit-spinner"></div>
                                    <span data-i18n="audit_loading">กำลังโหลดข้อมูล...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="audit-pagination" id="paginationBar">
                <div id="paginationInfo">-</div>
                <div class="audit-pagination-controls" id="paginationControls"></div>
            </div>
        </div>

    </main>

    <!-- DETAIL MODAL -->
    <div class="audit-modal-overlay" id="detailModal">
        <div class="audit-modal">
            <div class="audit-modal-header">
                <h3 id="detailModalTitle"><i class="bi bi-info-circle"></i> <span data-i18n="audit_detail_modal_title">รายละเอียดการดำเนินการ</span></h3>
                <button class="audit-modal-close" onclick="closeDetailModal()">✕</button>
            </div>
            <div class="audit-modal-body" id="detailModalBody">
                <div class="audit-loading">
                    <div class="audit-spinner"></div>
                    <span data-i18n="audit_loading">กำลังโหลดข้อมูล...</span>
                </div>
            </div>
            <div class="audit-modal-footer">
                <button class="audit-btn audit-btn-outline" onclick="closeDetailModal()" data-i18n="audit_modal_close">ปิด</button>
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
        } catch (e) {
            showAuthRequiredState();
            setTimeout(() => { window.location.href = 'login.html'; }, 1500);
            return false;
        }
    }

    function showAuthRequiredState() {
        const body = document.getElementById('auditTableBody');
        if (body) {
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon"><i class="bi bi-shield-lock text-warning" style="font-size:2.5rem;"></i></div><h4>กรุณาเข้าสู่ระบบ</h4><p>กำลังนำคุณไปยังหน้าเข้าสู่ระบบ...</p></div></td></tr>`;
        }
    }

    function showAuthForbiddenState() {
        const body = document.getElementById('auditTableBody');
        if (body) {
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon"><i class="bi bi-shield-x text-danger" style="font-size:2.5rem;"></i></div><h4>เฉพาะผู้ดูแลระบบ (Admin)</h4><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กำลังพากลับหน้าหลัก...</p></div></td></tr>`;
        }
    }

    // =========================================================
    // TOAST NOTIFICATION
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
    // LOAD STATS
    // =========================================================
    async function loadStats() {
        try {
            const res = await fetch('audit_api.php?action=get_audit_stats');
            const data = await res.json();
            if (data.success) {
                document.getElementById('statTotalToday').textContent = data.today?.total_today || '0';
                document.getElementById('statCreates').textContent = data.today?.creates || '0';
                document.getElementById('statUpdates').textContent = data.today?.updates || '0';
                document.getElementById('statDeletes').textContent = data.today?.deletes || '0';
                document.getElementById('statLogins').textContent = data.today?.logins || '0';

                // Recycle bin badge
                if (data.recycle_bin && data.recycle_bin.total > 0) {
                    const badge = document.getElementById('recycleBinBadge');
                    badge.textContent = data.recycle_bin.total;
                    badge.style.display = 'inline-block';
                }
            }
        } catch (err) {
            console.error('loadStats error:', err);
        }
    }

    // =========================================================
    // LOAD AUDIT LOGS
    // =========================================================
    let currentPage = 1;

    async function loadAuditLogs(page = 1) {
        currentPage = page;
        const body = document.getElementById('auditTableBody');
        body.innerHTML = `<tr><td colspan="8"><div class="audit-loading"><div class="audit-spinner"></div> กำลังโหลดข้อมูล...</div></td></tr>`;

        const params = new URLSearchParams({
            action: 'get_audit_logs',
            page: page
        });

        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        const actionType = document.getElementById('filterAction').value;
        const tableName = document.getElementById('filterTable').value;
        const userId = document.getElementById('filterUser').value;
        const search = document.getElementById('filterSearch').value;

        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        if (actionType) params.set('action_type', actionType);
        if (tableName) params.set('table_name', tableName);
        if (userId) params.set('user_id', userId);
        if (search) params.set('search', search);

        try {
            const res = await fetch(`audit_api.php?${params}`);
            const data = await res.json();

            if (!data.success) {
                body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon">⚠️</div><h4>${data.error || 'เกิดข้อผิดพลาด'}</h4></div></td></tr>`;
                return;
            }

            // Populate user filter dropdown (first load only)
            if (page === 1 && data.distinct_users) {
                const userSelect = document.getElementById('filterUser');
                if (userSelect.options.length <= 1) {
                    data.distinct_users.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.user_id;
                        opt.textContent = `${u.real_name || u.username} (${u.user_role})`;
                        userSelect.appendChild(opt);
                    });
                }
            }

            if (data.logs.length === 0) {
                body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state">
                    <div class="empty-icon"><i class="bi bi-clipboard-x text-secondary" style="font-size:2.5rem;"></i></div>
                    <h4>${t('audit_no_data','ไม่พบข้อมูล')}</h4>
                    <p>${t('audit_no_data_desc','ยังไม่มีประวัติการใช้งานที่ตรงกับเงื่อนไข')}</p>
                </div></td></tr>`;
                document.getElementById('totalRecords').textContent = `0 ${t('audit_items_count','รายการ')}`;
                document.getElementById('paginationControls').innerHTML = '';
                document.getElementById('paginationInfo').textContent = '-';
                return;
            }

            body.innerHTML = data.logs.map(log => `
                <tr>
                    <td style="white-space:nowrap; font-size:12.5px; color:#6b7280">
                        ${formatDateTime(log.created_at)}
                    </td>
                    <td>
                        <strong style="font-size:13px; color:#1e293b;">${escapeHtml(log.real_name || log.display_name || log.username || '-')}</strong>
                        ${(log.real_name && log.username && log.real_name !== log.username) ? `<br><small style="color:#94a3b8; font-size:11px;">${escapeHtml(log.username)}</small>` : ''}
                    </td>
                    <td>
                        <span class="role-badge ${log.user_role || ''}">${log.user_role || '-'}</span>
                    </td>
                    <td>
                        <span class="action-badge ${(log.action_type || '').toLowerCase()}">${getActionIcon(log.action_type)} ${getActionLabel(log.action_type, log.action_display)}</span>
                    </td>
                    <td style="font-size:12.5px">${getTableLabel(log.table_name, log.table_display)}</td>
                    <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(log.description || '')}">
                        ${escapeHtml(log.description || '-')}
                    </td>
                    <td style="font-size:12px; color:#9ca3af">${log.ip_address || '-'}</td>
                    <td style="text-align:center">
                        <button class="audit-detail-btn" onclick="openDetail(${log.log_id})">
                            <i class="bi bi-eye"></i> ${t('audit_btn_view_detail','ดู')}
                        </button>
                    </td>
                </tr>
            `).join('');

            // Pagination
            const pg = data.pagination;
            const unitText = t('audit_items_count','รายการ');
            document.getElementById('totalRecords').textContent = `${pg.total_records} ${unitText}`;
            const showingText = t('audit_page_showing','แสดง');
            const ofText = t('audit_page_of','จาก');
            document.getElementById('paginationInfo').textContent = 
                `${showingText} ${(pg.current_page - 1) * pg.per_page + 1} - ${Math.min(pg.current_page * pg.per_page, pg.total_records)} ${ofText} ${pg.total_records} ${unitText}`;
            
            renderPagination(pg);

        } catch (err) {
            console.error('loadAuditLogs error:', err);
            body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state"><div class="empty-icon">❌</div><h4>เกิดข้อผิดพลาดในการโหลดข้อมูล</h4></div></td></tr>`;
        }
    }

    function renderPagination(pg) {
        const container = document.getElementById('paginationControls');
        let html = '';
        
        html += `<button class="audit-page-btn" onclick="loadAuditLogs(${pg.current_page - 1})" ${pg.current_page <= 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>`;
        
        const maxVisible = 5;
        let start = Math.max(1, pg.current_page - Math.floor(maxVisible / 2));
        let end = Math.min(pg.total_pages, start + maxVisible - 1);
        if (end - start < maxVisible - 1) start = Math.max(1, end - maxVisible + 1);

        if (start > 1) {
            html += `<button class="audit-page-btn" onclick="loadAuditLogs(1)">1</button>`;
            if (start > 2) html += `<span style="padding:0 4px;color:#9ca3af">...</span>`;
        }

        for (let i = start; i <= end; i++) {
            html += `<button class="audit-page-btn ${i === pg.current_page ? 'active' : ''}" onclick="loadAuditLogs(${i})">${i}</button>`;
        }

        if (end < pg.total_pages) {
            if (end < pg.total_pages - 1) html += `<span style="padding:0 4px;color:#9ca3af">...</span>`;
            html += `<button class="audit-page-btn" onclick="loadAuditLogs(${pg.total_pages})">${pg.total_pages}</button>`;
        }

        html += `<button class="audit-page-btn" onclick="loadAuditLogs(${pg.current_page + 1})" ${pg.current_page >= pg.total_pages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>`;
        
        container.innerHTML = html;
    }

    // =========================================================
    // DETAIL MODAL
    // =========================================================
    async function openDetail(logId) {
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('detailModalBody');
        modal.classList.add('show');
        body.innerHTML = `<div class="audit-loading"><div class="audit-spinner"></div> กำลังโหลดข้อมูล...</div>`;

        try {
            const res = await fetch(`audit_api.php?action=get_audit_detail&log_id=${logId}`);
            const data = await res.json();

            if (!data.success) {
                body.innerHTML = `<div class="audit-empty-state"><div class="empty-icon">⚠️</div><h4>${data.error}</h4></div>`;
                return;
            }

            const log = data.log;
            let html = '';

            // ข้อมูลพื้นฐาน
            html += `
                <div class="audit-info-grid">
                    <div class="audit-info-item">
                        <span class="info-label">${t('audit_label_time','เวลา:')}</span>
                        <span class="info-value">${formatDateTime(log.created_at)}</span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">${t('audit_label_actor','ผู้ดำเนินการ:')}</span>
                        <span class="info-value"><strong>${escapeHtml(log.username || '-')}</strong> <span class="role-badge ${log.user_role}">${log.user_role}</span></span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">${t('audit_label_action','การกระทำ:')}</span>
                        <span class="info-value"><span class="action-badge ${(log.action_type||'').toLowerCase()}">${getActionIcon(log.action_type)} ${getActionLabel(log.action_type, log.action_display)}</span></span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">${t('audit_label_table','ตาราง:')}</span>
                        <span class="info-value">${getTableLabel(log.table_name, log.table_display)}</span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">${t('audit_label_record_id','Record ID:')}</span>
                        <span class="info-value">${log.record_id || '-'}</span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">${t('audit_label_ip','IP Address:')}</span>
                        <span class="info-value">${log.ip_address || '-'}</span>
                    </div>
                </div>
            `;

            if (log.description) {
                html += `<div style="margin-bottom:12px; padding:10px 14px; background:#f8fafc; border-radius:10px; font-size:13.5px; border:1px solid #e5e7eb;">
                    <strong>${t('audit_label_description','📝 คำอธิบาย:')}</strong> ${escapeHtml(log.description)}
                </div>`;
            }

            // แสดงข้อมูลตาม action type
            if (log.action_type === 'UPDATE' && log.old_values && log.new_values) {
                html += renderUpdateDiff(log.old_values, log.new_values);
            } else if (log.action_type === 'CREATE' && log.new_values) {
                html += `<div class="audit-section-title">${t('audit_section_create_data','➕ ข้อมูลที่เพิ่ม')}</div>`;
                html += renderDataView(log.new_values);
            } else if ((log.action_type === 'DELETE' || log.action_type === 'PERMANENT_DELETE') && log.old_values) {
                html += `<div class="audit-section-title">${t('audit_section_delete_data','🗑️ ข้อมูลที่ลบ')}</div>`;
                html += renderDataView(log.old_values);
            }

            // ประวัติการแก้ไขก่อนหน้าของ record นี้ (กรณี DELETE)
            if (data.record_history && data.record_history.length > 1) {
                html += `<div class="audit-section-title" style="margin-top:20px">${t('audit_section_history','📜 ประวัติการดำเนินการทั้งหมดของ Record นี้')}</div>`;
                html += `<div class="audit-timeline">`;
                
                data.record_history.forEach((h, idx) => {
                    const isCurrent = h.log_id === log.log_id;
                    html += `
                        <div class="audit-timeline-item ${isCurrent ? 'current' : ''}">
                            <div class="audit-timeline-meta">
                                ${formatDateTime(h.created_at)} — <strong>${escapeHtml(h.username || '-')}</strong>
                                <span class="action-badge ${(h.action_type||'').toLowerCase()}" style="font-size:10.5px; padding:2px 6px; margin-left:4px">${getActionIcon(h.action_type)} ${getActionLabel(h.action_type, h.action_display)}</span>
                                ${isCurrent ? ` <strong style="color:#ef4444">${t('audit_history_current','(← กำลังดู)')}</strong>` : ''}
                            </div>
                            <div class="audit-timeline-content">
                                ${h.description || '-'}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }

            body.innerHTML = html;

        } catch (err) {
            console.error('openDetail error:', err);
            body.innerHTML = `<div class="audit-empty-state"><div class="empty-icon">❌</div><h4>${t('audit_error','เกิดข้อผิดพลาด')}</h4></div>`;
        }
    }

    function renderUpdateDiff(oldValues, newValues) {
        const allKeys = [...new Set([...Object.keys(oldValues || {}), ...Object.keys(newValues || {})])];
        const filteredKeys = allKeys.filter(k => !k.toLowerCase().includes('password'));
        
        let html = `<div class="audit-section-title">${t('audit_section_update_diff','✏️ เปรียบเทียบก่อน-หลังแก้ไข')}</div>`;
        html += `<div class="audit-diff-container">`;
        
        // Old panel
        html += `<div class="audit-diff-panel old">
            <div class="audit-diff-panel-header"><i class="bi bi-arrow-left-circle"></i> ${t('audit_section_before','ก่อนแก้ไข')}</div>
            <div class="audit-diff-content">`;
        
        filteredKeys.forEach(key => {
            const oldVal = oldValues?.[key] ?? '-';
            const newVal = newValues?.[key] ?? '-';
            const changed = String(oldVal) !== String(newVal);
            html += `<div class="audit-diff-row">
                <div class="audit-diff-key">${escapeHtml(getFieldLabel(key))}</div>
                <div class="audit-diff-value ${changed ? 'changed' : ''}">${escapeHtml(String(oldVal))}</div>
            </div>`;
        });
        
        html += `</div></div>`;
        
        // New panel
        html += `<div class="audit-diff-panel new">
            <div class="audit-diff-panel-header"><i class="bi bi-arrow-right-circle"></i> ${t('audit_section_after','หลังแก้ไข')}</div>
            <div class="audit-diff-content">`;
        
        filteredKeys.forEach(key => {
            const oldVal = oldValues?.[key] ?? '-';
            const newVal = newValues?.[key] ?? '-';
            const changed = String(oldVal) !== String(newVal);
            html += `<div class="audit-diff-row">
                <div class="audit-diff-key">${escapeHtml(getFieldLabel(key))}</div>
                <div class="audit-diff-value ${changed ? 'changed' : ''}">${escapeHtml(String(newVal))}</div>
            </div>`;
        });
        
        html += `</div></div></div>`;
        return html;
    }

    function renderDataView(data) {
        if (!data || typeof data !== 'object') return '<p style="color:#9ca3af">ไม่มีข้อมูล</p>';
        
        const keys = Object.keys(data).filter(k => !k.toLowerCase().includes('password'));
        
        let html = `<div class="audit-data-view">`;
        keys.forEach(key => {
            html += `<div class="audit-data-row">
                <div class="audit-data-key">${escapeHtml(getFieldLabel(key))}</div>
                <div class="audit-data-value">${escapeHtml(String(data[key] ?? '-'))}</div>
            </div>`;
        });
        html += `</div>`;
        return html;
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('show');
    }

    // =========================================================
    // HELPERS
    // =========================================================
    function getFieldLabel(field) {
        const labels = {
            'id': 'ID',
            'student_code': 'รหัสนักศึกษา',
            'name': 'ชื่อ-นามสกุล',
            'email': 'อีเมล',
            'department': 'แผนก',
            'major': 'สาขาวิชา',
            'university': 'มหาวิทยาลัย',
            'faculty': 'คณะ',
            'phone': 'เบอร์โทร',
            'mentor_id': 'พี่เลี้ยง (ID)',
            'student_id': 'นักศึกษา (ID)',
            'log_date': 'วันที่บันทึก',
            'work_description': 'รายละเอียดงาน',
            'status': 'สถานะ',
            'mentor_comment': 'ความเห็นพี่เลี้ยง',
            'score_work': 'คะแนนงาน',
            'score_time': 'คะแนนเวลา',
            'score_behavior': 'คะแนนพฤติกรรม',
            'final_feedback': 'ข้อเสนอแนะ',
            'start_date': 'วันที่เริ่มฝึก',
            'duration_days': 'จำนวนวัน',
            'username': 'ชื่อผู้ใช้',
            'role': 'Role',
            'ref_id': 'Ref ID',
            'created_at': 'สร้างเมื่อ',
            'updated_at': 'แก้ไขล่าสุด',
            'is_deleted': 'สถานะลบ',
            'deleted_at': 'ลบเมื่อ',
            'deleted_by': 'ลบโดย',
            'evaluated_at': 'ประเมินเมื่อ'
        };
        return labels[field] || field;
    }

    function getActionIcon(action) {
        const icons = {
            'CREATE': '<i class="bi bi-plus-circle text-success me-1"></i>',
            'UPDATE': '<i class="bi bi-pencil-square text-warning me-1"></i>',
            'DELETE': '<i class="bi bi-trash3 text-danger me-1"></i>',
            'LOGIN': '<i class="bi bi-box-arrow-in-right text-primary me-1"></i>',
            'LOGOUT': '<i class="bi bi-box-arrow-right text-secondary me-1"></i>',
            'RESTORE': '<i class="bi bi-arrow-counterclockwise text-info me-1"></i>',
            'PERMANENT_DELETE': '<i class="bi bi-x-circle text-danger me-1"></i>'
        };
        return icons[action] || '<i class="bi bi-file-text me-1"></i>';
    }

    /**
     * แปลชื่อ Action ตามภาษาที่เลือก
     */
    function getActionLabel(actionType, fallback) {
        const keyMap = {
            'CREATE': 'audit_action_create',
            'UPDATE': 'audit_action_update',
            'DELETE': 'audit_action_delete',
            'LOGIN': 'audit_action_login',
            'LOGOUT': 'audit_action_logout',
            'RESTORE': 'audit_action_restore',
            'PERMANENT_DELETE': 'audit_action_perm_delete'
        };
        const key = keyMap[actionType];
        return (key && window.i18n) ? window.i18n.t(key, fallback || actionType) : (fallback || actionType || '-');
    }

    /**
     * แปลชื่อตารางตามภาษาที่เลือก
     */
    function getTableLabel(tableName, fallback) {
        const keyMap = {
            'students': 'audit_table_students',
            'mentors': 'audit_table_mentors',
            'internship_logs': 'audit_table_logs',
            'evaluations': 'audit_table_evaluations',
            'users': 'audit_table_users'
        };
        const key = keyMap[tableName];
        return (key && window.i18n) ? window.i18n.t(key, fallback || tableName) : (fallback || tableName || '-');
    }

    function formatDateTime(dt) {
        if (!dt) return '-';
        // Cross-browser safe Date parsing
        const safeDt = String(dt).replace(/-/g, '/').replace('T', ' ');
        const d = new Date(safeDt);
        if (isNaN(d.getTime())) return dt;
        
        const pad = n => String(n).padStart(2, '0');
        const lang = window.i18n ? window.i18n.getCurrentLanguage() : 'th';
        const year = (lang === 'th') ? (d.getFullYear() + 543) : d.getFullYear();
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${year} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function clearFilters() {
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.getElementById('filterAction').value = '';
        document.getElementById('filterTable').value = '';
        document.getElementById('filterUser').value = '';
        document.getElementById('filterSearch').value = '';
        loadAuditLogs(1);
    }

    // Close modal on Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDetailModal();
    });

    // Close modal on overlay click
    document.getElementById('detailModal').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeDetailModal();
    });

    // Re-render table when language changes
    window.addEventListener('languageChanged', () => {
        loadAuditLogs(currentPage || 1);
    });

    // =========================================================
    // INIT
    // =========================================================
    (async function init() {
        const authed = await checkAuth();
        if (!authed) return;
        
        loadStats();
        loadAuditLogs(1);
    })();
    </script>
</body>


</html>
