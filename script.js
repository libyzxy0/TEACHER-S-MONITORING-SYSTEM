// Toggle between login and registration forms
function toggleForms() {
    const loginSection = document.getElementById('loginSection');
    const registrationSection = document.getElementById('registrationSection');
    
    if (loginSection.style.display === 'none') {
        loginSection.style.display = 'block';
        registrationSection.style.display = 'none';
    } else {
        loginSection.style.display = 'none';
        registrationSection.style.display = 'block';
    }
}

// Handle login form submission
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validation
            const username = formData.get('username');
            const password = formData.get('password');
            
            if (username.length < 3 || username.length > 20) {
                showMessage('Username must be 3-20 characters long.', 'error');
                return;
            }
            
            if (password.length < 6) {
                showMessage('Password must be at least 6 characters.', 'error');
                return;
            }
            
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard.php';
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        });
    }

    // Handle registration form submission
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validation
            const fullName = formData.get('fullName');
            const username = formData.get('username');
            const password = formData.get('password');
            
            if (!fullName.trim()) {
                showMessage('Full name is required.', 'error');
                return;
            }
            
            if (username.length < 3 || username.length > 20) {
                showMessage('Username must be 3-20 characters long.', 'error');
                return;
            }
            
            if (password.length < 6) {
                showMessage('Password must be at least 6 characters.', 'error');
                return;
            }
            
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        toggleForms();
                        registrationForm.reset();
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        });
    }

    // Dashboard scripts
    if (document.getElementById('addClassForm')) {
        document.getElementById('addClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const className = document.getElementById('className').value;
            const section = document.getElementById('section').value;
            const isAdvisory = document.getElementById('is_advisory').checked;
            
            const formData = new FormData();
            formData.append('action', 'add_class');
            formData.append('className', className);
            formData.append('section', section);
            if (isAdvisory) {
                formData.append('is_advisory', 1);
            }
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    document.getElementById('addClassForm').reset();
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred.', 'error');
            });
        });
    }

    // Delete class
    document.querySelectorAll('.delete-class').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this class?')) {
                const classId = this.dataset.classId;
                const formData = new FormData();
                formData.append('action', 'delete_class');
                formData.append('class_id', classId);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Class deleted successfully', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showMessage(data.message || 'Failed to delete', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred', 'error');
                });
            }
        });
    });

    // View seating arrangement
    document.querySelectorAll('.view-seating').forEach(btn => {
        btn.addEventListener('click', function() {
            const classId = this.getAttribute('data-class-id');
            showSeatingArrangement(classId);
        });
    });

    // View scores
    document.querySelectorAll('.view-scores').forEach(btn => {
        btn.addEventListener('click', function() {
            const classId = this.getAttribute('data-class-id');
            showScoresView(classId);
        });
    });

    // Add score form (Modal)
    if (document.getElementById('scoreSheetForm')) {
        document.getElementById('scoreSheetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const studentId = document.getElementById('modalStudentId').value;
            const classId = document.getElementById('modalClassId').value;
            
            if (!studentId) {
                showMessage('Please select a student', 'error');
                return;
            }
            
            // Collect all scores
            const scores = {
                'Quiz 1': document.getElementById('quiz1').value || null,
                'Quiz 2': document.getElementById('quiz2').value || null,
                'Quiz 3': document.getElementById('quiz3').value || null,
                'Activity 1': document.getElementById('activity1').value || null,
                'Activity 2': document.getElementById('activity2').value || null,
                'Activity 3': document.getElementById('activity3').value || null,
                'Project 1': document.getElementById('project1').value || null,
                'Project 2': document.getElementById('project2').value || null,
                'Monthly Exam': document.getElementById('monthlyExam').value || null,
                'Quarterly Exam': document.getElementById('quarterlyExam').value || null,
            };
            
            // Save each score
            let savedCount = 0;
            let totalScores = 0;
            
            for (const [subject, score] of Object.entries(scores)) {
                if (score !== null && score !== '') {
                    totalScores++;
                    const formData = new FormData();
                    formData.append('action', 'add_score_modal');
                    formData.append('student_id', studentId);
                    formData.append('class_id', classId);
                    formData.append('subject', subject);
                    formData.append('score', score);
                    
                    fetch('dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            savedCount++;
                        }
                        if (savedCount === totalScores) {
                            showMessage('All scores saved successfully', 'success');
                            document.getElementById('scoreSheetForm').reset();
                            const modal = bootstrap.Modal.getInstance(document.getElementById('scoreSheetModal'));
                            modal.hide();
                            loadScores(classId);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            }
            
            if (totalScores === 0) {
                showMessage('Please enter at least one score', 'error');
            }
        });
    }
}); 

// Show seating arrangement
function showSeatingArrangement(classId) {
    document.getElementById('currentClassId').value = classId;
    document.getElementById('requestClassId').value = classId;
    const seatingModal = new bootstrap.Modal(document.getElementById('seatingModal'));
    seatingModal.show();
    loadStudents(classId);
    
    // Load pending seating requests for this class if user is adviser
    fetch(`dashboard.php?action=get_seating_requests`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.requests && data.requests.length > 0) {
                document.getElementById('pendingRequestsSection').style.display = 'block';
                displayPendingRequests(data.requests);
            } else {
                document.getElementById('pendingRequestsSection').style.display = 'none';
            }
        })
        .catch(error => console.error('Error:', error));
    
    // Load advisers for request form
    fetch(`dashboard.php?action=get_advisers`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.advisers && data.advisers.length > 0) {
                const advisersSelect = document.getElementById('adviserId');
                advisersSelect.innerHTML = '<option value="">Select adviser...</option>';
                data.advisers.forEach(adviser => {
                    const option = document.createElement('option');
                    option.value = adviser.id;
                    option.textContent = adviser.fullName;
                    advisersSelect.appendChild(option);
                });
                document.getElementById('requestSeatingSection').style.display = 'block';
            }
        })
        .catch(error => console.error('Error:', error));
}

// Close seating section
function closeSeating() {
    document.getElementById('seatingSection').style.display = 'none';
}

// Close scores section
function closeScores() {
    document.getElementById('scoresSection').style.display = 'none';
}

// Show scores view
function showScoresView(classId) {
    document.getElementById('scoresSection').style.display = 'block';
    loadScoresForClass(classId);
}

// Load scores for a class
function loadScoresForClass(classId) {
    fetch(`dashboard.php?action=get_scores&class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayScoresForClass(data.scores);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Display scores grouped by student
function displayScoresForClass(scores) {
    const scoresList = document.getElementById('scoresList');
    
    if (scores.length === 0) {
        scoresList.innerHTML = '<p class="text-muted">No scores added yet</p>';
        return;
    }

    // Group scores by student (use student id + name key to avoid name collisions)
    const groupedScores = {};
    scores.forEach(score => {
        const key = `${score.id}::${score.student_name}`;
        if (!groupedScores[key]) groupedScores[key] = [];
        groupedScores[key].push(score);
    });

    // Build clickable list of student names
    let html = '<h6 class="mb-3">Students</h6><div class="list-group mb-3" id="studentsList">';
    for (const key of Object.keys(groupedScores)) {
        const parts = key.split('::');
        const studentId = parts[0];
        const studentName = parts.slice(1).join('::');
        html += `<button type="button" class="list-group-item list-group-item-action" data-student-id="${studentId}" data-student-name="${studentName}">${studentName}</button>`;
    }
    html += '</div>';

    // Detail area where selected student's scores will appear
    html += '<div id="studentScoresDetail"><p class="text-muted">Select a student to view scores</p></div>';

    scoresList.innerHTML = html;
    scoresList.dataset.selectedStudent = ''; // Track which student is selected

    // Attach click handlers to student buttons
    document.querySelectorAll('#studentsList .list-group-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const sid = this.getAttribute('data-student-id');
            const sname = this.getAttribute('data-student-name');
            const key = `${sid}::${sname}`;
            const currentSelected = scoresList.dataset.selectedStudent;
            
            // Toggle: if clicking same student again, hide the detail
            if (currentSelected === key) {
                document.getElementById('studentScoresDetail').innerHTML = '<p class="text-muted">Select a student to view scores</p>';
                scoresList.dataset.selectedStudent = '';
                return;
            }
            
            // Show scores for clicked student
            scoresList.dataset.selectedStudent = key;
            const studentScores = groupedScores[key] || [];
            let detailHtml = `<h6>${sname}</h6>`;
            if (studentScores.length === 0) {
                detailHtml += '<p class="text-muted">No scores yet</p>';
            } else {
                detailHtml += '<table class="table table-sm table-bordered"><tbody>';
                studentScores.forEach(score => {
                    // Show blank instead of null for empty scores (handle both null and "null" string)
                    let scoreValue = score.score;
                    if (scoreValue === null || scoreValue === 'null' || scoreValue === undefined || scoreValue === '') {
                        scoreValue = '';
                    }
                    detailHtml += `<tr><td>${score.subject}</td><td>${scoreValue}</td></tr>`;
                });
                detailHtml += '</tbody></table>';
            }
            document.getElementById('studentScoresDetail').innerHTML = detailHtml;
        });
    });
}

// Load students for a class
function loadStudents(classId) {
    fetch(`dashboard.php?action=get_students&class_id=${classId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySeatingGrid(data.students);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Display seating grid (10 columns x 5 rows)
function displaySeatingGrid(students) {
    const grid = document.getElementById('seatingGrid');
    grid.innerHTML = '';
    
    let studentMap = {};
    students.forEach(student => {
        studentMap[`${student.seat_row}-${student.seat_column}`] = student;
    });
    
    const gridDiv = document.createElement('div');
    gridDiv.className = 'seating-grid';

    for (let row = 1; row <= 5; row++) {
        for (let col = 1; col <= 10; col++) {
            // Insert aisle element before column 6 (i.e., between 5 and 6)
            if (col === 6) {
                const aisle = document.createElement('div');
                aisle.className = 'aisle';
                // make aisle span the current row (just a spacer)
                gridDiv.appendChild(aisle);
            }

            const seat = document.createElement('div');
            seat.className = 'seat';
            seat.style.cursor = 'pointer';
            const key = `${row}-${col}`;

            if (studentMap[key]) {
                const studentData = studentMap[key];
                seat.textContent = studentData.student_name;
                seat.classList.add('occupied');
                seat.onclick = () => openEditStudentModal(studentData);
            } else {
                seat.textContent = '';
                seat.classList.remove('occupied');
                seat.onclick = () => addNewStudent(row, col);
            }

            // store coordinates for debugging/actions
            seat.dataset.seatRow = row;
            seat.dataset.seatCol = col;

            gridDiv.appendChild(seat);
        }
    }

    grid.appendChild(gridDiv);
}

// Edit student on seat click
function editStudentSeat(student) {
    const newName = prompt(`Edit student name for seat ${student.seat_row}×${student.seat_column}:`, student.student_name);
    
    if (newName && newName.trim()) {
        fetch('dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=edit_student&student_id=${student.id}&student_name=${encodeURIComponent(newName)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Student updated', 'success');
                const classId = document.getElementById('currentClassId').value;
                loadStudents(classId);
            } else {
                showMessage(data.message || 'Failed to update', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred', 'error');
        });
    }
}

// Open edit student modal
function openEditStudentModal(student) {
    const classId = document.getElementById('currentClassId').value;
    document.getElementById('editStudentId').value = student.id;
    document.getElementById('editStudentName').value = student.student_name;
    document.getElementById('currentClassIdForStudent').value = classId;
    
    // Set up scores button for existing student
    const addScoresBtn = document.getElementById('addScoresFromStudentBtn');
    addScoresBtn.style.display = 'block';
    // use onclick to avoid adding multiple listeners on repeated modal opens
    addScoresBtn.onclick = function() {
        document.getElementById('modalStudentId').value = student.id;
        document.getElementById('modalClassId').value = classId;
    };
    
    const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    editModal.show();
}

// Add new student to empty seat
function addNewStudent(row, col) {
    document.getElementById('editStudentId').value = '';
    document.getElementById('editStudentName').value = '';
    document.getElementById('editStudentModal').dataset.newSeat = `${row},${col}`;
    
    // Hide Add Scores button for new students
    const addScoresBtn = document.getElementById('addScoresFromStudentBtn');
    addScoresBtn.style.display = 'none';
    
    const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
    editModal.show();
}

// Handle edit student form submission
document.addEventListener('DOMContentLoaded', function() {
    const editStudentForm = document.getElementById('editStudentForm');
    if (editStudentForm) {
        editStudentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const studentId = document.getElementById('editStudentId').value;
            const studentName = document.getElementById('editStudentName').value;
            const classId = document.getElementById('currentClassId').value;
            
            if (!studentName.trim()) {
                showMessage('Student name is required', 'error');
                return;
            }
            
            if (studentId) {
                // Edit existing student
                fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=edit_student&student_id=${studentId}&student_name=${encodeURIComponent(studentName)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Student updated', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
                        loadStudents(classId);
                    } else {
                        showMessage(data.message || 'Failed to update', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred', 'error');
                });
            } else {
                // Add new student
                const seatCoords = document.getElementById('editStudentModal').dataset.newSeat;
                const formData = new FormData();
                const action = seatCoords ? 'add_student_specific_seat' : 'add_student_auto';
                formData.append('action', action);
                formData.append('class_id', classId);
                formData.append('student_name', studentName);
                if (seatCoords) {
                    const [row, col] = seatCoords.split(',');
                    formData.append('seat_row', row);
                    formData.append('seat_column', col);
                }
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage('Student added', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('editStudentModal')).hide();
                        loadStudents(classId);
                    } else {
                        showMessage(data.message || 'Failed to add', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showMessage('An error occurred', 'error');
                });
            }
        });
    }
});

// Add student to specific seat (or auto-assign)
function addStudentToSeat(row, col) {
    const studentName = prompt(`Enter student name:`);
    
    if (studentName && studentName.trim()) {
        const classId = document.getElementById('currentClassId').value;
        const formData = new FormData();
        formData.append('action', 'add_student_auto');
        formData.append('class_id', classId);
        formData.append('student_name', studentName);
        
        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Student added', 'success');
                loadStudents(classId);
            } else {
                showMessage(data.message || 'Failed to add', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred', 'error');
        });
    }
}

// Display pending seating requests for adviser
function displayPendingRequests(requests) {
    const requestsList = document.getElementById('pendingRequestsList');
    requestsList.innerHTML = '';
    
    requests.forEach(req => {
        const requestItem = document.createElement('div');
        requestItem.className = 'list-group-item list-group-item-action mb-2';
        requestItem.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div style="flex: 1;">
                    <h6 class="mb-1">${req.requester_name}</h6>
                    <small class="text-muted">${req.className} - ${req.section}</small>
                    ${req.notes ? `<p class="small mt-1 mb-0"><em>Note: ${req.notes}</em></p>` : ''}
                </div>
                <div>
                    <button class="btn btn-sm btn-success approve-seating-btn me-1" data-request-id="${req.id}">✓</button>
                    <button class="btn btn-sm btn-danger reject-seating-btn" data-request-id="${req.id}">✕</button>
                </div>
            </div>
        `;
        requestsList.appendChild(requestItem);
    });
    
    // Attach event listeners
    document.querySelectorAll('.approve-seating-btn').forEach(btn => {
        btn.onclick = function() {
            const requestId = this.getAttribute('data-request-id');
            approveSeatingRequest(requestId);
        };
    });
    
    document.querySelectorAll('.reject-seating-btn').forEach(btn => {
        btn.onclick = function() {
            const requestId = this.getAttribute('data-request-id');
            rejectSeatingRequest(requestId);
        };
    });
}

// Approve seating request
function approveSeatingRequest(requestId) {
    const classId = document.getElementById('currentClassId').value;
    const formData = new FormData();
    formData.append('action', 'approve_seating_request');
    formData.append('request_id', requestId);
    
    fetch('dashboard.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Request approved!', 'success');
            showSeatingArrangement(classId);
        } else {
            showMessage(data.message || 'Failed to approve', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred', 'error');
    });
}

// Reject seating request
function rejectSeatingRequest(requestId) {
    if (confirm('Are you sure you want to reject this request?')) {
        const classId = document.getElementById('currentClassId').value;
        const formData = new FormData();
        formData.append('action', 'reject_seating_request');
        formData.append('request_id', requestId);
        
        fetch('dashboard.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Request rejected', 'success');
                showSeatingArrangement(classId);
            } else {
                showMessage(data.message || 'Failed to reject', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('An error occurred', 'error');
        });
    }
}

function showMessage(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} position-fixed`;
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.textContent = message;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Request seating arrangement
document.addEventListener('DOMContentLoaded', function() {
    const seatingRequestForm = document.getElementById('seatingRequestForm');
    if (seatingRequestForm) {
        seatingRequestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const classId = document.getElementById('requestClassId').value;
            const adviserId = document.getElementById('adviserId').value;
            const notes = document.getElementById('requestNotes').value;
            
            if (!adviserId) {
                showMessage('Please select an adviser', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'request_seating');
            formData.append('class_id', classId);
            formData.append('adviser_id', adviserId);
            formData.append('notes', notes);
            
            fetch('dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Request sent successfully', 'success');
                    seatingRequestForm.reset();
                } else {
                    showMessage(data.message || 'Failed to send request', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred', 'error');
            });
        });
    }
});

function requestSeatingArrangement(classId) {
    document.getElementById('requestClassId').value = classId;
    
    // Load advisers
    fetch(`dashboard.php?action=get_advisers`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const advisersSelect = document.getElementById('adviserId');
                advisersSelect.innerHTML = '<option value="">Select adviser...</option>';
                data.advisers.forEach(adviser => {
                    const option = document.createElement('option');
                    option.value = adviser.id;
                    option.textContent = adviser.fullName;
                    advisersSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error:', error));
    
    const modal = new bootstrap.Modal(document.getElementById('seatingRequestModal'));
    modal.show();
}


