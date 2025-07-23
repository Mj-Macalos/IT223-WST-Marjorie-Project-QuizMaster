
let questionIndex = 0;
// Store the original draft data for change detection
let originalDraftData = null;

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

function openModal() {
    document.getElementById('quizModal').style.display = 'block';
    // Only clear fields if adding a new quiz (quizId is empty)
    if (!document.getElementById('quizId').value) {
        document.getElementById('quizId').value = '';
        document.getElementById('quizToken').value = '';
        document.getElementById('quizStatus').value = '';
        document.getElementById('quizTitle').value = '';
        document.getElementById('quizSubject').value = '';
        document.getElementById('quizInstructions').value = '';
        document.getElementById('quizTime').value = '';
        document.getElementById('quizAttempts').value = '';
        document.getElementById('quizDeadline').value = '';
        document.getElementById('questionsContainer').innerHTML = '';
        questionIndex = 0;
        // Do NOT clear originalDraftData here!
    }
}

function closeModal() {
    document.getElementById('quizModal').style.display = 'none';
    document.getElementById('questionsContainer').innerHTML = '';
    document.getElementById('quizId').value = '';
    document.getElementById('quizStatus').value = ''; // Clear status on close
}

function addQuestion() {
    const qID = questionIndex++;
    const div = document.createElement('div');
    div.classList.add('question');

    div.innerHTML = `
        <div class="question-header">
            <div class="question-number">Question ${questionIndex}</div>
            <button class="btn-delete-question" onclick="deleteQuestionFromForm(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <textarea data-qid="${qID}" class="qtext" placeholder="Enter your question here..."></textarea>
        <div class="question-controls">
            <select class="qtype" onchange="renderChoices(this, ${qID})">
                <option value="mcq">Multiple Choice</option>
                <option value="true_false">True/False</option>
                <option value="short_answer">Short Answer</option>
                <option value="essay">Essay</option>
            </select>
            <input type="number" class="numChoices" placeholder="Number of choices" min="2" value="4" onchange="renderChoices(this.previousElementSibling, ${qID})">
        </div>
        <div id="choices_${qID}" class="choices"></div>
    `;

    document.getElementById('questionsContainer').appendChild(div);
    renderChoices(div.querySelector('.qtype'), qID);
    renumberQuestions();
}

function renumberQuestions() {
    const questionDivs = document.querySelectorAll('.question');
    questionDivs.forEach((qDiv, index) => {
        const label = qDiv.querySelector('.question-number');
        if (label) {
            label.textContent = `Question ${index + 1}`;
        }
    });
}

function renderChoices(sel, qid) {
    const val = sel.value;
    const div = document.getElementById('choices_' + qid);
    div.innerHTML = '';
    const numChoicesInput = sel.parentElement.querySelector('.numChoices');
    if (val === 'mcq') {
        if (numChoicesInput) numChoicesInput.style.display = '';
        const num = numChoicesInput?.value || 4;
        for (let i = 0; i < num; i++) {
            div.innerHTML += `
                <div class='choice'>
                    <input type='radio' name='correct_${qid}' value='${i}' id='choice_${qid}_${i}'>
                    <label for='choice_${qid}_${i}' class='choice-label'>Correct</label>
                    <input type='text' class='choiceText' placeholder='Choice ${i + 1}'>
                </div>
            `;
        }
    } else {
        if (numChoicesInput) numChoicesInput.style.display = 'none';
        if (val === 'true_false') {
        div.innerHTML += `
            <div class='choice'>
                <input type='radio' name='correct_${qid}' value='True' id='true_${qid}'>
                <label for='true_${qid}' class='choice-label'>True</label>
            </div>
            <div class='choice'>
                <input type='radio' name='correct_${qid}' value='False' id='false_${qid}'>
                <label for='false_${qid}' class='choice-label'>False</label>
            </div>
        `;
    } else if (val === 'short_answer') {
        div.innerHTML += `<input type='text' class='correctAnswer' placeholder='Enter the correct answer'>`;
    } else {
        div.innerHTML += `<textarea disabled placeholder='Students will provide essay answers here'></textarea>`;
    }
}
}

// Deep equality check for objects and arrays, with normalization
function normalizeValue(val) {
    if (val === null || val === undefined) return '';
    if (typeof val === 'number') return String(val);
    if (typeof val === 'string') return val.trim();
    return val;
}
function deepEqual(a, b) {
    if (typeof a !== typeof b) return false;
    if (typeof a !== 'object' || a === null || b === null) {
        return normalizeValue(a) === normalizeValue(b);
    }
    if (Array.isArray(a) !== Array.isArray(b)) return false;
    if (Array.isArray(a)) {
        if (a.length !== b.length) return false;
        for (let i = 0; i < a.length; i++) {
            if (!deepEqual(a[i], b[i])) return false;
        }
        return true;
    }
    const aKeys = Object.keys(a).sort();
    const bKeys = Object.keys(b).sort();
    if (aKeys.length !== bKeys.length) return false;
    for (let i = 0; i < aKeys.length; i++) {
        if (aKeys[i] !== bKeys[i]) return false;
        if (!deepEqual(a[aKeys[i]], b[bKeys[i]])) return false;
    }
    return true;
}

function submitQuiz(status) {
    let deadlineVal = document.getElementById('quizDeadline').value;
    let deadline = null;
    if (deadlineVal) {
        // Parse as local time and convert to Asia/Manila
        let localDate = new Date(deadlineVal);
        let utc = localDate.getTime() + (localDate.getTimezoneOffset() * 60000);
        let manilaDate = new Date(utc + (8 * 60 * 60 * 1000));
        deadline = manilaDate.getFullYear() + '-' +
            String(manilaDate.getMonth() + 1).padStart(2, '0') + '-' +
            String(manilaDate.getDate()).padStart(2, '0') + ' ' +
            String(manilaDate.getHours()).padStart(2, '0') + ':' +
            String(manilaDate.getMinutes()).padStart(2, '0') + ':00';
    }
    const quiz = {
        id: document.getElementById('quizId').value,
        token: document.getElementById('quizToken').value,
        title: document.getElementById('quizTitle').value,
        subject: document.getElementById('quizSubject').value,
        instructions: document.getElementById('quizInstructions').value,
        time_limit: parseInt(document.getElementById('quizTime').value),
        attempts_allowed: parseInt(document.getElementById('quizAttempts').value),
        deadline: deadline,
        status: status,
        questions: []
    };
    // Custom warning modals for validation
    if (!quiz.title.trim()) {
        showWarningModal('Quiz title cannot be empty.');
        return;
    }
    const questionDivs = document.querySelectorAll('.question');
    if (questionDivs.length === 0) {
        showWarningModal('Quiz must have at least one question.');
        return;
    }
    questionDivs.forEach((qdiv) => {
        const text = qdiv.querySelector('.qtext').value.trim();
        const type = qdiv.querySelector('.qtype').value;
        const correctEl = qdiv.querySelector('.correctAnswer') || qdiv.querySelector('input[type=radio]:checked');
        const correctAns = correctEl ? correctEl.value : '';
        const question = { text, type, correct_answer: correctAns, choices: [] };
        if (type === 'mcq') {
            const choices = qdiv.querySelectorAll('.choiceText');
            choices.forEach((ch, i) => {
                question.choices.push({
                    text: ch.value,
                    is_correct: parseInt(correctAns) === i
                });
            });
        } else if (type === 'true_false') {
            question.choices = [
                { text: 'True', is_correct: correctAns === 'True' },
                { text: 'False', is_correct: correctAns === 'False' }
            ];
        }
        quiz.questions.push(question);
    });
    const currentStatus = document.getElementById('quizStatus') ? document.getElementById('quizStatus').value : '';
    // Correct logic: Only show confirmation if new quiz or changes, show warning if no changes to existing draft
    if (status === 'draft' && currentStatus === 'draft') {
        if (originalDraftData) {
            // Parse the stored originalDraftData for deep comparison
            const original = JSON.parse(originalDraftData);
            const current = {
                title: normalizeValue(quiz.title),
                subject: normalizeValue(quiz.subject),
                instructions: normalizeValue(quiz.instructions),
                time_limit: normalizeValue(quiz.time_limit),
                attempts_allowed: normalizeValue(quiz.attempts_allowed),
                deadline: normalizeValue(quiz.deadline),
                questions: quiz.questions.map(q => ({
                    text: normalizeValue(q.text),
                    type: normalizeValue(q.type),
                    correct_answer: normalizeValue(q.correct_answer),
                    choices: (q.choices || []).map(c => ({
                        text: normalizeValue(c.text),
                        is_correct: !!c.is_correct
                    }))
                }))
            };
            if (deepEqual(current, original)) {
                showWarningModal('The quiz is already saved as draft.');
                return;
            }
        }
        showConfirmModal('Are you sure you want to save this quiz as draft?', function(confirmed) {
            if (!confirmed) return;
            actuallySubmitQuiz(quiz);
        });
        return;
    }
    if (status === 'draft') {
        showConfirmModal('Are you sure you want to save this quiz as draft?', function(confirmed) {
            if (!confirmed) return;
            actuallySubmitQuiz(quiz);
        });
        return;
    }
    if (status === 'published' && currentStatus === 'published') {
        showWarningModal('The quiz is already published.');
        return;
    }
    if (status === 'published') {
        showPublishOverview();
        return;
    }
    actuallySubmitQuiz(quiz);
}

function actuallySubmitQuiz(quiz) {
    const form = new FormData();
    form.append('quiz_data', JSON.stringify(quiz));
    fetch('quiz.php', { method: 'POST', body: form })
        .then(res => res.json())
        .then(res => {
            if (res.error) {
                showWarningModal(res.error);
                return;
            }
            showSuccessModal('Quiz saved successfully!', function() { location.reload(); });
        });
}

function editQuiz(id) {
    fetch('quiz.php?get_quiz=' + id)
        .then(res => res.json())
        .then(data => {
            openModal(); // Open modal FIRST, so fields are ready

            document.getElementById('quizId').value = data.id;
            document.getElementById('quizToken').value = data.quiz_token;
            document.getElementById('quizStatus').value = data.status;
            document.getElementById('quizTitle').value = data.title;
            document.getElementById('quizSubject').value = data.subject;
            document.getElementById('quizInstructions').value = data.instructions;
            document.getElementById('quizTime').value = data.time_limit;
            document.getElementById('quizAttempts').value = data.attempts_allowed;
            // Fix deadline formatting for input type="datetime-local" (Asia/Manila to local)
            if (data.deadline && data.deadline !== '0000-00-00 00:00:00' && data.deadline !== null) {
                let [datePart, timePart] = data.deadline.split(' ');
                let [year, month, day] = datePart.split('-');
                let [hour, min, sec] = timePart.split(':');
                let manilaDate = new Date(year, month - 1, day, hour, min, sec);
                let tzOffset = manilaDate.getTimezoneOffset() * 60000;
                let localDate = new Date(manilaDate.getTime() - tzOffset);
                let localStr = localDate.toISOString().slice(0,16);
                document.getElementById('quizDeadline').value = localStr;
            } else {
                document.getElementById('quizDeadline').value = '';
            }
            document.getElementById('questionsContainer').innerHTML = '';
            questionIndex = 0;

            data.questions.forEach((q) => {
                addQuestion();
                const qDivs = document.querySelectorAll('.question');
                const qDiv = qDivs[qDivs.length - 1];
                const qid = questionIndex - 1;
                if (q.id) qDiv.setAttribute('data-qid', q.id);

                qDiv.querySelector('.qtext').value = q.question_text;
                qDiv.querySelector('.qtype').value = q.question_type;

                if (q.question_type === 'mcq') {
                    qDiv.querySelector('.numChoices').value = q.choices.length;
                }

                renderChoices(qDiv.querySelector('.qtype'), qid);

                if (q.question_type === 'mcq') {
                    const choiceTexts = qDiv.querySelectorAll('.choiceText');
                    q.choices.forEach((choice, index) => {
                        if (choiceTexts[index]) {
                            choiceTexts[index].value = choice.choice_text;
                        }
                        if (choice.is_correct) {
                            const correctRadio = qDiv.querySelector(`input[name='correct_${qid}'][value='${index}']`);
                            if (correctRadio) correctRadio.checked = true;
                        }
                    });
                } else if (q.question_type === 'true_false') {
                    const correctRadio = qDiv.querySelector(`input[name='correct_${qid}'][value='${q.correct_answer}']`);
                    if (correctRadio) correctRadio.checked = true;
                } else if (q.question_type === 'short_answer') {
                    const answerInput = qDiv.querySelector('.correctAnswer');
                    if (answerInput) answerInput.value = q.correct_answer;
                }
            });

            // Make all fields read-only/disabled if published, but do not hide any buttons
            const isPublished = data.status === 'published';
            document.getElementById('quizTitle').readOnly = isPublished;
            document.getElementById('quizSubject').readOnly = isPublished;
            document.getElementById('quizInstructions').readOnly = isPublished;
            document.getElementById('quizTime').readOnly = isPublished;
            document.getElementById('quizAttempts').readOnly = isPublished;
            document.getElementById('quizDeadline').readOnly = isPublished;
            document.querySelectorAll('.question').forEach(qDiv => {
                qDiv.querySelector('.qtext').readOnly = isPublished;
                qDiv.querySelector('.qtype').disabled = isPublished;
                const numChoices = qDiv.querySelector('.numChoices');
                if (numChoices) numChoices.disabled = isPublished;
                qDiv.querySelectorAll('input, textarea, select').forEach(inp => {
                    inp.disabled = isPublished;
                });
            });

            // Set originalDraftData LAST, after all DOM is updated, and normalize
            if (data.status === 'draft') {
                originalDraftData = JSON.stringify({
                    title: normalizeValue(data.title),
                    subject: normalizeValue(data.subject),
                    instructions: normalizeValue(data.instructions),
                    time_limit: normalizeValue(data.time_limit),
                    attempts_allowed: normalizeValue(data.attempts_allowed),
                    deadline: normalizeValue(data.deadline),
                    questions: (data.questions || []).map(q => ({
                        text: normalizeValue(q.question_text),
                        type: normalizeValue(q.question_type),
                        correct_answer: normalizeValue(q.correct_answer),
                        choices: (q.choices || []).map(c => ({
                            text: normalizeValue(c.choice_text),
                            is_correct: !!c.is_correct
                        }))
                    }))
                });
            } else {
                originalDraftData = null;
            }
        });
}

function viewQuiz(id) {
    fetch('quiz.php?get_quiz=' + id)
        .then(res => res.json())
        .then(data => {
            document.getElementById('viewTitle').innerText = data.title;
            document.getElementById('viewSubject').innerText = data.subject;
            document.getElementById('viewInstructions').innerText = data.instructions;
            document.getElementById('viewTime').innerText = data.time_limit;
            document.getElementById('viewAttempts').innerText = data.attempts_allowed;
            // Display deadline as-is, formatted for readability
            let deadlineDisplay = 'No deadline set';
            if (data.deadline && data.deadline !== '0000-00-00 00:00:00' && data.deadline !== null) {
                deadlineDisplay = data.deadline.replace(/:00$/, ''); // remove seconds for display
            }
            document.getElementById('viewDeadline').innerText = deadlineDisplay;
            let html = '';
            data.questions.forEach((q, i) => {
                html += `<div class='question-preview'>`;
                html += `<div class='question-preview-header'>`;
                html += `<span class='question-preview-number'>Q${i + 1}</span>`;
                html += `<span class='question-preview-type'>${q.question_type.replace('_', ' ').toUpperCase()}</span>`;
                html += `</div>`;
                html += `<p class='question-preview-text'>${q.question_text}</p>`;
                if (q.choices && q.choices.length > 0) {
                    html += `<div class='choices-preview'>`;
                    // Only highlight the correct answer
                    if (q.question_type === 'mcq' || q.question_type === 'true_false') {
                    q.choices.forEach(c => {
                            if (c.is_correct) {
                                html += `<div class='choice-preview correct'><span class='choice-marker'>✓</span><span>${c.choice_text}</span></div>`;
                            } else {
                                html += `<div class='choice-preview'><span class='choice-marker'></span><span>${c.choice_text}</span></div>`;
                            }
                        });
                    } else {
                        // fallback for other types with choices
                        q.choices.forEach(c => {
                            html += `<div class='choice-preview'><span>${c.choice_text}</span></div>`;
                    });
                    }
                    html += `</div>`;
                } else if (q.correct_answer) {
                    html += `<div class='answer-preview'><strong>Answer:</strong> ${q.correct_answer}</div>`;
                }
                html += `</div>`;
            });
            document.getElementById('viewQuestions').innerHTML = html;
            document.getElementById('viewModal').style.display = 'block';
        });
}

function deleteQuestionFromForm(btn) {
    const qDiv = btn.closest('.question');
    const qid = qDiv.getAttribute('data-qid');
    const quizStatus = document.getElementById('quizStatus') ? document.getElementById('quizStatus').value : '';
    if (quizStatus === 'published') {
        showWarningModal('Cannot delete a question from a published quiz.');
        return;
    }
    if (qid) {
        // Show confirm modal before attempting server-side delete
        showConfirmModal("Remove this question from the quiz?", function(confirmed) {
            if (confirmed) {
                fetch('quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `delete_question=${qid}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success === false) {
                        showWarningModal(data.message);
                    } else {
                        qDiv.remove();
        renumberQuestions();
                        showSuccessModal('Question deleted.');
                    }
                });
            }
        });
    } else {
        // Only show confirm modal for unsaved questions
        showConfirmModal("Remove this question from the quiz?", function(confirmed) {
            if (confirmed) {
                qDiv.remove();
                renumberQuestions();
            }
        });
    }
}

function searchQuizzes() {
    const value = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('#quizTable tbody tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(value) ? '' : 'none';
    });
}

function shareQuiz(token) {
    const shareUrl = `${window.location.origin}/qm/student.php?token=${token}`;
    navigator.clipboard.writeText(shareUrl).then(() => {
        alert("Shareable quiz link copied to clipboard!");
    });
}

function confirmDelete(id) {
    showConfirmModal('Are you sure you want to delete this quiz? This action cannot be undone.', function(confirmed) {
        if (confirmed) {
        fetch('quiz.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `delete_quiz=${id}`
        })
        .then(res => res.json())
        .then(data => {
                if (data.success === false) {
                    showWarningModal(data.message);
                } else {
                    showSuccessModal(data.message, function() { location.reload(); });
                }
            });
        }
    });
}

// Custom confirm modal
function showConfirmModal(message, callback) {
    let modal = document.getElementById('customConfirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'customConfirmModal';
        modal.innerHTML = `
            <div class="custom-modal-overlay"></div>
            <div class="custom-modal-content">
                <div class="custom-modal-message"></div>
                <div class="custom-modal-actions">
                    <button id="customConfirmYes" class="btn-primary">Yes</button>
                    <button id="customConfirmNo" class="btn-cancel">No</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    modal.querySelector('.custom-modal-message').innerText = message;
    modal.style.display = 'flex';
    modal.querySelector('#customConfirmYes').onclick = function() {
        modal.style.display = 'none';
        if (callback) callback(true);
    };
    modal.querySelector('#customConfirmNo').onclick = function() {
        modal.style.display = 'none';
        if (callback) callback(false);
    };
}

// Add custom modal functions for success and warning if not present
function showSuccessModal(message, callback) {
    let modal = document.getElementById('customSuccessModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'customSuccessModal';
        modal.innerHTML = `
            <div class="custom-modal-overlay"></div>
            <div class="custom-modal-content">
                <div class="custom-modal-message"></div>
                <div class="custom-modal-actions">
                    <button id="customSuccessOk" class="btn-primary">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    modal.querySelector('.custom-modal-message').innerText = message;
    modal.style.display = 'flex';
    modal.querySelector('#customSuccessOk').onclick = function() {
        modal.style.display = 'none';
        if (callback) callback();
    };
}
function showWarningModal(message, callback) {
    let modal = document.getElementById('customWarningModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'customWarningModal';
        modal.innerHTML = `
            <div class="custom-modal-overlay"></div>
            <div class="custom-modal-content warning">
                <div class="custom-modal-message"></div>
                <div class="custom-modal-actions">
                    <button id="customWarningOk" class="btn-cancel">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    modal.querySelector('.custom-modal-message').innerText = message;
    modal.style.display = 'flex';
    modal.querySelector('#customWarningOk').onclick = function() {
        modal.style.display = 'none';
        if (callback) callback();
    };
}

function showPublishOverview() {
    // Gather all quiz data as in submitQuiz
    const quiz = {
        title: document.getElementById('quizTitle').value,
        subject: document.getElementById('quizSubject').value,
        instructions: document.getElementById('quizInstructions').value,
        time_limit: document.getElementById('quizTime').value,
        attempts_allowed: document.getElementById('quizAttempts').value,
        deadline: document.getElementById('quizDeadline').value,
        questions: []
    };
    document.querySelectorAll('.question').forEach((qdiv, idx) => {
        const text = qdiv.querySelector('.qtext').value.trim();
        const type = qdiv.querySelector('.qtype').value;
        let choices = [];
        if (type === 'mcq' || type === 'true_false') {
            qdiv.querySelectorAll('.choiceText').forEach(ch => choices.push(ch.value));
        }
        quiz.questions.push({ text, type, choices });
    });
    // Render enhanced overview HTML
    let html = `
      <div class="overview-section">
        <div class="overview-field"><span class="overview-label"><i class='fas fa-heading'></i> Title:</span> <span class="overview-value">${quiz.title}</span></div>
        <div class="overview-field"><span class="overview-label"><i class='fas fa-book'></i> Subject:</span> <span class="overview-value">${quiz.subject}</span></div>
        <div class="overview-field"><span class="overview-label"><i class='fas fa-info-circle'></i> Instructions:</span> <span class="overview-value">${quiz.instructions}</span></div>
        <div class="overview-field"><span class="overview-label"><i class='fas fa-clock'></i> Time Limit:</span> <span class="overview-value">${quiz.time_limit} minutes</span></div>
        <div class="overview-field"><span class="overview-label"><i class='fas fa-redo'></i> Attempts Allowed:</span> <span class="overview-value">${quiz.attempts_allowed}</span></div>
        <div class="overview-field"><span class="overview-label"><i class='fas fa-calendar-alt'></i> Deadline:</span> <span class="overview-value">${quiz.deadline}</span></div>
      </div>
      <hr>
      <div class="overview-section">
        <div class="overview-questions-title"><i class='fas fa-question-circle'></i> <strong>Questions</strong></div>
        <ol class="overview-questions-list">
    `;
    quiz.questions.forEach((q, i) => {
        html += `<li class="overview-question-card">
          <div class="overview-question-header"><span class="overview-qnum">Q${i+1}</span> <span class="overview-qtype">(${q.type.replace('_',' ').toUpperCase()})</span></div>
          <div class="overview-qtext">${q.text}</div>`;
        if (q.choices && q.choices.length) {
            html += `<ul class="overview-choices-list">`;
            q.choices.forEach((c, j) => {
                html += `<li class="overview-choice"><i class='fas fa-circle'></i> ${c}</li>`;
            });
            html += `</ul>`;
        }
        html += `</li>`;
    });
    html += `</ol></div>`;
    document.getElementById('publishOverviewBody').innerHTML = html;
    document.getElementById('publishOverviewModal').style.display = 'block';
}
function closePublishOverview() {
    document.getElementById('publishOverviewModal').style.display = 'none';
}
function confirmPublishQuiz() {
    showConfirmModal('Are you sure you want to publish this quiz? Once students start answering, it cannot be edited.', function(confirmed) {
        if (!confirmed) return;
        closePublishOverview();
        actuallySubmitQuiz({ ...collectQuizData(), status: 'published' });
    });
}
function collectQuizData() {
    let deadlineVal = document.getElementById('quizDeadline').value;
    let deadline = null;
    if (deadlineVal) {
        let localDate = new Date(deadlineVal);
        let utc = localDate.getTime() + (localDate.getTimezoneOffset() * 60000);
        let manilaDate = new Date(utc + (8 * 60 * 60 * 1000));
        deadline = manilaDate.getFullYear() + '-' +
            String(manilaDate.getMonth() + 1).padStart(2, '0') + '-' +
            String(manilaDate.getDate()).padStart(2, '0') + ' ' +
            String(manilaDate.getHours()).padStart(2, '0') + ':' +
            String(manilaDate.getMinutes()).padStart(2, '0') + ':00';
    }
    const quiz = {
        id: document.getElementById('quizId').value,
        token: document.getElementById('quizToken').value,
        title: document.getElementById('quizTitle').value,
        subject: document.getElementById('quizSubject').value,
        instructions: document.getElementById('quizInstructions').value,
        time_limit: parseInt(document.getElementById('quizTime').value),
        attempts_allowed: parseInt(document.getElementById('quizAttempts').value),
        deadline: deadline,
        status: 'published',
        questions: []
    };
    document.querySelectorAll('.question').forEach((qdiv) => {
        const text = qdiv.querySelector('.qtext').value.trim();
        const type = qdiv.querySelector('.qtype').value;
        const correctEl = qdiv.querySelector('.correctAnswer') || qdiv.querySelector('input[type=radio]:checked');
        const correctAns = correctEl ? correctEl.value : '';
        const question = { text, type, correct_answer: correctAns, choices: [] };
        if (type === 'mcq') {
            const choices = qdiv.querySelectorAll('.choiceText');
            choices.forEach((ch, i) => {
                question.choices.push({
                    text: ch.value,
                    is_correct: parseInt(correctAns) === i
                });
            });
        } else if (type === 'true_false') {
            question.choices = [
                { text: 'True', is_correct: correctAns === 'True' },
                { text: 'False', is_correct: correctAns === 'False' }
            ];
        }
        quiz.questions.push(question);
    });
    return quiz;
}

function renderQuizTable(quizzes) {
    const tbody = document.getElementById('quizTableBody');
    tbody.innerHTML = '';
    quizzes.forEach(q => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${q.title}</td>
            <td>${q.subject}</td>
            <td>${q.total_questions}</td>
            <td>${q.created_at}</td>
            <td><span class="status-badge status-${q.status}">${q.status.charAt(0).toUpperCase() + q.status.slice(1)}</span></td>
            <td>
                <button class="btn-action btn-edit" onclick="editQuiz(${q.id})" type="button">
                    <i class="fas fa-edit"></i> <span>Edit</span>
                </button>
                <button class="btn-action btn-view" onclick="viewQuiz(${q.id})">
                    <i class="fas fa-eye"></i> <span>View</span>
                </button>
                <button class="btn-action btn-share" onclick="shareQuiz('${q.quiz_token}')">
                    <i class="fas fa-share"></i> <span>Share</span>
                </button>
                <button class="btn-action btn-delete" onclick="confirmDelete(${q.id})">
                    <i class="fas fa-trash"></i> <span>Delete</span>
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
    prevBtn.onclick = () => fetchQuizPage(currentPage - 1);
    pagination.appendChild(prevBtn);
    // Page numbers
    for (let i = 1; i <= pageCount; i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        btn.className = (i === currentPage) ? 'active' : '';
        btn.onclick = () => fetchQuizPage(i);
        pagination.appendChild(btn);
    }
    // Next arrow
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '&#8594;';
    nextBtn.disabled = currentPage === pageCount;
    nextBtn.onclick = () => fetchQuizPage(currentPage + 1);
    pagination.appendChild(nextBtn);
}

function fetchQuizPage(page = 1) {
    fetch('quiz.php?fetch_quizzes=1&page=' + page)
        .then(res => res.json())
        .then(data => {
            renderQuizTable(data.quizzes);
            renderPagination(data.total, data.perPage, data.page);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    fetchQuizPage(1);
});

// Close modal when clicking outside
window.onclick = function(event) {
    const quizModal = document.getElementById('quizModal');
    const viewModal = document.getElementById('viewModal');
    const publishOverviewModal = document.getElementById('publishOverviewModal');
    if (event.target === quizModal) {
        closeModal();
    }
    if (event.target === viewModal) {
        viewModal.style.display = 'none';
    }
    if (event.target === publishOverviewModal) {
        closePublishOverview();
    }
}