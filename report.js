
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

function searchQuizzes() {
    const searchValue = document.getElementById('searchBox').value.toLowerCase();
    document.querySelectorAll("#quizTable tbody tr").forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(searchValue) ? "" : "none";
    });
}

function printCurrentView() {
    const section = document.getElementById('printSection');
    if (!section) return;
    const content = section.cloneNode(true);
    content.querySelectorAll('.no-print').forEach(el => el.remove());

    const win = window.open('', '', 'width=900,height=600');
    win.document.write(`
        <html><head><title>Quiz Report</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
            th { background: #e9ecef; }
            .quiz-detail-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .quiz-detail-info h3 { margin: 0 0 15px 0; color: #333; }
            .quiz-detail-info p { margin: 5px 0; color: #666; }
            .quiz-detail-info strong { color: #333; }
            .scores-section h4 { margin: 20px 0 15px 0; color: #333; }
        </style>
        </head><body>${content.innerHTML}</body></html>
    `);
    win.document.close();
    win.focus();
    win.print();
    win.close();
}

function renderReportTable(quizzes) {
    const tbody = document.getElementById('quizTableBody');
    tbody.innerHTML = '';
    quizzes.forEach(q => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${q.title}</td>
            <td>${q.subject}</td>
            <td>${q.deadline ? q.deadline : ''}</td>
            <td>${q.submissions_count}</td>
            <td>
                <span class="status-badge status-${(() => {
                    if (!q.deadline) return 'ongoing';
                    // Parse deadline as Manila time
                    const deadlineDate = new Date(q.deadline.replace(' ', 'T') + '+08:00');
                    const now = new Date();
                    return deadlineDate < now ? 'completed' : 'ongoing';
                })()}" >
                    ${(() => {
                        if (!q.deadline) return 'Ongoing';
                        const deadlineDate = new Date(q.deadline.replace(' ', 'T') + '+08:00');
                        const now = new Date();
                        return deadlineDate < now ? 'Done' : 'Ongoing';
                    })()}
                </span>
            </td>
            <td>
                <a href="report.php?view=${q.id}" class="btn-action btn-view">
                    <i class="fas fa-eye"></i>
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderPagination(total, perPage, currentPage) {
    const pageCount = Math.ceil(total / perPage);
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    // Previous arrow
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '&#8592;';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => fetchReportPage(currentPage - 1);
    pagination.appendChild(prevBtn);
    // Page numbers
    for (let i = 1; i <= pageCount; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = (i === currentPage) ? 'active' : '';
        btn.onclick = () => fetchReportPage(i);
        pagination.appendChild(btn);
    }
    // Next arrow
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '&#8594;';
    nextBtn.disabled = currentPage === pageCount;
    nextBtn.onclick = () => fetchReportPage(currentPage + 1);
    pagination.appendChild(nextBtn);
}

function fetchReportPage(page = 1) {
    fetch('report.php?fetch_reports=1&page=' + page)
        .then(res => res.json())
        .then(data => {
            renderReportTable(data.quizzes);
            renderPagination(data.total, data.perPage, data.page);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    fetchReportPage(1);
});

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.mobile-menu-toggle');
    if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('active');
    }
});