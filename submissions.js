
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Open grading modal
        function openModal(attemptId) {
            document.getElementById('gradingModal').style.display = 'block';
            document.getElementById('modalBody').innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            fetch(`submissions.php?ajax=1&view=${attemptId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalBody').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('modalBody').innerHTML = '<div class="error"><i class="fas fa-exclamation-triangle"></i> Error loading submission details.</div>';
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('gradingModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('gradingModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll("#submissionsTable tbody tr");
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? "" : "none";
            });
        });

        function renderSubmissionsTable(submissions) {
            const tbody = document.getElementById('submissionsTableBody');
            tbody.innerHTML = '';
            submissions.forEach(row => {
                const tr = document.createElement('tr');
                let scoreDisplay = row.score === null ? '0' : row.score;
                tr.innerHTML = `
                    <td>
                        <div class="student-info">
                            <div class="student-name">${row.first_name} ${row.middle_initial} ${row.last_name} ${row.suffix}</div>
                            <div class="student-details">
                                <span class="program-year">${row.program} - ${row.year_level}</span>
                            </div>
                        </div>
                    </td>
                    <td class="quiz-title">${row.quiz_title}</td>
                    <td class="score-cell"><div class="score-value">${scoreDisplay}</div></td>
                    <td class="date-cell">${row.submitted_at ? new Date(row.submitted_at).toLocaleString() : ''}</td>
                    <td>
                        <span class="status-badge ${row.has_essay > 0 && row.is_graded == 0 ? 'status-pending' : 'status-graded'}">
                            ${row.has_essay > 0 && row.is_graded == 0 ? 'Pending' : 'Graded'}
                        </span>
                    </td>
                    <td class="actions-cell">
                        <button type="button" class="btn-action btn-view" onclick="openModal(${row.id})" title="View & Grade">
                            <i class="fas fa-eye"></i> <span>View & Grade</span>
                        </button>
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
            prevBtn.onclick = () => fetchSubmissionsPage(currentPage - 1);
            pagination.appendChild(prevBtn);
            // Page numbers
            for (let i = 1; i <= pageCount; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = (i === currentPage) ? 'active' : '';
                btn.onclick = () => fetchSubmissionsPage(i);
                pagination.appendChild(btn);
            }
            // Next arrow
            const nextBtn = document.createElement('button');
            nextBtn.innerHTML = '&#8594;';
            nextBtn.disabled = currentPage === pageCount;
            nextBtn.onclick = () => fetchSubmissionsPage(currentPage + 1);
            pagination.appendChild(nextBtn);
        }

        function fetchSubmissionsPage(page = 1) {
            fetch('submissions.php?fetch_submissions=1&page=' + page)
                .then(res => res.json())
                .then(data => {
                    renderSubmissionsTable(data.submissions);
                    renderPagination(data.total, data.perPage, data.page);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            fetchSubmissionsPage(1);
        });

        // In the JS that attaches the custom confirm handler to the Finalize Grade button:
        // Change the confirmation message to warn that finalizing is permanent
        const form = document.getElementById('gradingForm');
        if (form) {
            const btn = form.querySelector('button[name="grade"]');
            if (btn) {
                btn.onclick = function(e) {
                    e.preventDefault();
                    showConfirmModal('Are you sure you want to finalize the grading? Once finalized, the grade cannot be edited.', function(confirmed) {
                        if (confirmed) form.submit();
                    });
                    return false;
                };
            }
        }
