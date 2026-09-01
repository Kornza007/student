<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบตรวจสอบประวัติการใช้งาน - Admin Dashboard">
    <title>📊 ตรวจสอบข้อมูลระบบ | ระบบจัดการนักศึกษาฝึกงาน</title>
    <link rel="stylesheet" href="audit_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="audit-page">

    <!-- TOP NAVIGATION -->
    <nav class="audit-topbar">
        <a href="index.html" class="audit-topbar-brand">
            <div class="brand-icon">🛡️</div>
            <div class="brand-text">
                ระบบตรวจสอบ
                <small>Audit & Monitoring</small>
            </div>
        </a>

        <div class="audit-nav-tabs">
            <a href="admin_audit_logs.php" class="audit-nav-tab active">
                <span class="tab-icon">📊</span>
                ตรวจสอบข้อมูลระบบ
            </a>
            <a href="admin_recycle_bin.php" class="audit-nav-tab" id="recycleBinTab">
                <span class="tab-icon">🗑️</span>
                ถังขยะระบบ
                <span class="tab-badge" id="recycleBinBadge" style="display:none">0</span>
            </a>
        </div>

        <div class="audit-topbar-actions">
            <a href="index.html" class="audit-back-btn">
                <i class="bi bi-arrow-left"></i>
                กลับหน้าหลัก
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
                    <p>กิจกรรมวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon green"><i class="bi bi-plus-circle"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statCreates">-</h4>
                    <p>เพิ่มข้อมูลวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon orange"><i class="bi bi-pencil-square"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statUpdates">-</h4>
                    <p>แก้ไขข้อมูลวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon red"><i class="bi bi-trash3"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statDeletes">-</h4>
                    <p>ลบข้อมูลวันนี้</p>
                </div>
            </div>
            <div class="audit-stat-card">
                <div class="audit-stat-icon cyan"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="audit-stat-info">
                    <h4 id="statLogins">-</h4>
                    <p>เข้าสู่ระบบวันนี้</p>
                </div>
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="audit-filter-bar">
            <h3><i class="bi bi-funnel"></i> ตัวกรองข้อมูล</h3>
            <div class="audit-filter-row">
                <div class="audit-filter-group">
                    <label>วันที่เริ่มต้น</label>
                    <input type="date" id="filterDateFrom">
                </div>
                <div class="audit-filter-group">
                    <label>วันที่สิ้นสุด</label>
                    <input type="date" id="filterDateTo">
                </div>
                <div class="audit-filter-group">
                    <label>ประเภทการกระทำ</label>
                    <select id="filterAction">
                        <option value="">ทั้งหมด</option>
                        <option value="CREATE">➕ เพิ่มข้อมูล</option>
                        <option value="UPDATE">✏️ แก้ไขข้อมูล</option>
                        <option value="DELETE">🗑️ ลบข้อมูล</option>
                        <option value="LOGIN">🔑 เข้าสู่ระบบ</option>
                        <option value="LOGOUT">🚪 ออกจากระบบ</option>
                        <option value="RESTORE">♻️ กู้คืนข้อมูล</option>
                        <option value="PERMANENT_DELETE">💀 ลบถาวร</option>
                    </select>
                </div>
                <div class="audit-filter-group">
                    <label>ตาราง</label>
                    <select id="filterTable">
                        <option value="">ทั้งหมด</option>
                        <option value="students">นักศึกษา</option>
                        <option value="mentors">พี่เลี้ยง</option>
                        <option value="internship_logs">บันทึกการฝึกงาน</option>
                        <option value="evaluations">การประเมินผล</option>
                        <option value="users">บัญชีผู้ใช้</option>
                    </select>
                </div>
                <div class="audit-filter-group">
                    <label>ผู้ใช้</label>
                    <select id="filterUser">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>
                <div class="audit-filter-group">
                    <label>ค้นหา</label>
                    <input type="text" id="filterSearch" placeholder="ค้นหาคำอธิบาย...">
                </div>
                <div class="audit-filter-actions">
                    <button class="audit-btn audit-btn-primary" onclick="loadAuditLogs(1)">
                        <i class="bi bi-search"></i> ค้นหา
                    </button>
                    <button class="audit-btn audit-btn-outline" onclick="clearFilters()">
                        <i class="bi bi-x-lg"></i> ล้าง
                    </button>
                </div>
            </div>
        </div>

        <!-- AUDIT LOGS TABLE -->
        <div class="audit-table-card">
            <div class="audit-table-header">
                <h3>
                    <i class="bi bi-clock-history"></i>
                    ประวัติการใช้งานระบบ
                    <span class="record-count" id="totalRecords">0 รายการ</span>
                </h3>
            </div>
            <div class="audit-table-wrap">
                <table class="audit-table" id="auditTable">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>ผู้ใช้</th>
                            <th>Role</th>
                            <th>การกระทำ</th>
                            <th>ตาราง</th>
                            <th>คำอธิบาย</th>
                            <th>IP</th>
                            <th style="text-align:center">รายละเอียด</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <tr>
                            <td colspan="8">
                                <div class="audit-loading">
                                    <div class="audit-spinner"></div>
                                    กำลังโหลดข้อมูล...
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
                <h3 id="detailModalTitle"><i class="bi bi-info-circle"></i> รายละเอียดการดำเนินการ</h3>
                <button class="audit-modal-close" onclick="closeDetailModal()">✕</button>
            </div>
            <div class="audit-modal-body" id="detailModalBody">
                <div class="audit-loading">
                    <div class="audit-spinner"></div>
                    กำลังโหลดข้อมูล...
                </div>
            </div>
            <div class="audit-modal-footer">
                <button class="audit-btn audit-btn-outline" onclick="closeDetailModal()">ปิด</button>
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
                        opt.textContent = `${u.username} (${u.user_role})`;
                        userSelect.appendChild(opt);
                    });
                }
            }

            if (data.logs.length === 0) {
                body.innerHTML = `<tr><td colspan="8"><div class="audit-empty-state">
                    <div class="empty-icon">📋</div>
                    <h4>ไม่พบข้อมูล</h4>
                    <p>ยังไม่มีประวัติการใช้งานที่ตรงกับเงื่อนไข</p>
                </div></td></tr>`;
                document.getElementById('totalRecords').textContent = '0 รายการ';
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
                        <strong style="font-size:13px">${escapeHtml(log.username || '-')}</strong>
                    </td>
                    <td>
                        <span class="role-badge ${log.user_role || ''}">${log.user_role || '-'}</span>
                    </td>
                    <td>
                        <span class="action-badge ${(log.action_type || '').toLowerCase()}">${getActionIcon(log.action_type)} ${log.action_display || log.action_type}</span>
                    </td>
                    <td style="font-size:12.5px">${log.table_display || '-'}</td>
                    <td style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${escapeHtml(log.description || '')}">
                        ${escapeHtml(log.description || '-')}
                    </td>
                    <td style="font-size:12px; color:#9ca3af">${log.ip_address || '-'}</td>
                    <td style="text-align:center">
                        <button class="audit-detail-btn" onclick="openDetail(${log.log_id})">
                            <i class="bi bi-eye"></i> ดู
                        </button>
                    </td>
                </tr>
            `).join('');

            // Pagination
            const pg = data.pagination;
            document.getElementById('totalRecords').textContent = `${pg.total_records} รายการ`;
            document.getElementById('paginationInfo').textContent = 
                `แสดง ${(pg.current_page - 1) * pg.per_page + 1} - ${Math.min(pg.current_page * pg.per_page, pg.total_records)} จาก ${pg.total_records}`;
            
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
                        <span class="info-label">เวลา:</span>
                        <span class="info-value">${formatDateTime(log.created_at)}</span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">ผู้ดำเนินการ:</span>
                        <span class="info-value"><strong>${escapeHtml(log.username || '-')}</strong> <span class="role-badge ${log.user_role}">${log.user_role}</span></span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">การกระทำ:</span>
                        <span class="info-value"><span class="action-badge ${(log.action_type||'').toLowerCase()}">${getActionIcon(log.action_type)} ${log.action_display}</span></span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">ตาราง:</span>
                        <span class="info-value">${log.table_display || '-'}</span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">Record ID:</span>
                        <span class="info-value">${log.record_id || '-'}</span>
                    </div>
                    <div class="audit-info-item">
                        <span class="info-label">IP Address:</span>
                        <span class="info-value">${log.ip_address || '-'}</span>
                    </div>
                </div>
            `;

            if (log.description) {
                html += `<div style="margin-bottom:12px; padding:10px 14px; background:#f8fafc; border-radius:10px; font-size:13.5px; border:1px solid #e5e7eb;">
                    <strong>📝 คำอธิบาย:</strong> ${escapeHtml(log.description)}
                </div>`;
            }

            // แสดงข้อมูลตาม action type
            if (log.action_type === 'UPDATE' && log.old_values && log.new_values) {
                html += renderUpdateDiff(log.old_values, log.new_values);
            } else if (log.action_type === 'CREATE' && log.new_values) {
                html += `<div class="audit-section-title">➕ ข้อมูลที่เพิ่ม</div>`;
                html += renderDataView(log.new_values);
            } else if ((log.action_type === 'DELETE' || log.action_type === 'PERMANENT_DELETE') && log.old_values) {
                html += `<div class="audit-section-title">🗑️ ข้อมูลที่ลบ</div>`;
                html += renderDataView(log.old_values);
            }

            // ประวัติการแก้ไขก่อนหน้าของ record นี้ (กรณี DELETE)
            if (data.record_history && data.record_history.length > 1) {
                html += `<div class="audit-section-title" style="margin-top:20px">📜 ประวัติการดำเนินการทั้งหมดของ Record นี้</div>`;
                html += `<div class="audit-timeline">`;
                
                data.record_history.forEach((h, idx) => {
                    const isCurrent = h.log_id === log.log_id;
                    html += `
                        <div class="audit-timeline-item ${isCurrent ? 'current' : ''}">
                            <div class="audit-timeline-meta">
                                ${formatDateTime(h.created_at)} — <strong>${escapeHtml(h.username || '-')}</strong>
                                <span class="action-badge ${(h.action_type||'').toLowerCase()}" style="font-size:10.5px; padding:2px 6px; margin-left:4px">${getActionIcon(h.action_type)} ${h.action_display}</span>
                                ${isCurrent ? ' <strong style="color:#ef4444">(← กำลังดู)</strong>' : ''}
                            </div>
                            <div class="audit-timeline-content">
                                ${h.description || 'ไม่มีคำอธิบาย'}
                            </div>
                        </div>
                    `;
                });
                
                html += `</div>`;
            }

            body.innerHTML = html;

        } catch (err) {
            console.error('openDetail error:', err);
            body.innerHTML = `<div class="audit-empty-state"><div class="empty-icon">❌</div><h4>เกิดข้อผิดพลาด</h4></div>`;
        }
    }

    function renderUpdateDiff(oldValues, newValues) {
        // หาฟิลด์ที่เปลี่ยนแปลง
        const allKeys = [...new Set([...Object.keys(oldValues || {}), ...Object.keys(newValues || {})])];
        const filteredKeys = allKeys.filter(k => !k.toLowerCase().includes('password'));
        
        let html = `<div class="audit-section-title">✏️ เปรียบเทียบก่อน-หลังแก้ไข</div>`;
        html += `<div class="audit-diff-container">`;
        
        // Old panel
        html += `<div class="audit-diff-panel old">
            <div class="audit-diff-panel-header"><i class="bi bi-arrow-left-circle"></i> ก่อนแก้ไข</div>
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
            <div class="audit-diff-panel-header"><i class="bi bi-arrow-right-circle"></i> หลังแก้ไข</div>
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
            'CREATE': '➕',
            'UPDATE': '✏️',
            'DELETE': '🗑️',
            'LOGIN': '🔑',
            'LOGOUT': '🚪',
            'RESTORE': '♻️',
            'PERMANENT_DELETE': '💀'
        };
        return icons[action] || '📋';
    }

    function formatDateTime(dt) {
        if (!dt) return '-';
        const d = new Date(dt);
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
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
