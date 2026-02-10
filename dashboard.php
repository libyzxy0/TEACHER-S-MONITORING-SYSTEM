<?php
include 'database.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Get user role if not in session
if (!isset($_SESSION['user_role'])) {
    $stmt = $connection->prepare("SELECT role FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_role'] = $user['role'];
    }
    $stmt->close();
}

// Get all classes for this teacher
$stmt = $connection->prepare("SELECT * FROM classes WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes_result = $stmt->get_result();
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle adding new class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_class') {
    $className = $_POST['className'];
    $section = $_POST['section'];
    $is_advisory = isset($_POST['is_advisory']) ? 1 : 0;
    $academic_year = date('Y');
    
    $stmt = $connection->prepare("INSERT INTO classes (teacher_id, className, section, academic_year, is_advisory) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $teacher_id, $className, $section, $academic_year, $is_advisory);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Class added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add class']);
    }
    exit();
}

// Handle getting students for a class (seating arrangement)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_students') {
    $class_id = $_GET['class_id'];
    
    $stmt = $connection->prepare("SELECT * FROM students WHERE class_id = ? ORDER BY seat_row, seat_column");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['success' => true, 'students' => $students]);
    exit();
}

// Handle adding student with auto-seat assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_student_auto') {
    $class_id = $_POST['class_id'];
    $student_name = $_POST['student_name'];
    
    // Find next available seat (10 cols x 5 rows)
    $stmt = $connection->prepare("
        SELECT COALESCE(MAX(seat_row), 0) as max_row, COALESCE(MAX(seat_column), 0) as max_col 
        FROM students 
        WHERE class_id = ? 
        ORDER BY seat_row, seat_column
    ");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row_data = $result->fetch_assoc();
    $stmt->close();
    
    $seat_row = $row_data['max_row'] ?: 1;
    $seat_column = ($row_data['max_col'] ?: 0) + 1;
    
    // If column exceeds 10, move to next row
    if ($seat_column > 10) {
        $seat_row++;
        $seat_column = 1;
    }
    
    // Prevent exceeding 5 rows
    if ($seat_row > 5) {
        echo json_encode(['success' => false, 'message' => 'Seating arrangement is full (max 50 students)']);
        exit();
    }
    
    $stmt = $connection->prepare("INSERT INTO students (class_id, student_name, seat_row, seat_column) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $class_id, $student_name, $seat_row, $seat_column);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add student']);
    }
    exit();
}

// Handle adding student to specific seat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_student_specific_seat') {
    $class_id = $_POST['class_id'];
    $student_name = $_POST['student_name'];
    $seat_row = $_POST['seat_row'];
    $seat_column = $_POST['seat_column'];
    
    // Check if seat is already taken
    $stmt = $connection->prepare("SELECT id FROM students WHERE class_id = ? AND seat_row = ? AND seat_column = ?");
    $stmt->bind_param("iii", $class_id, $seat_row, $seat_column);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This seat is already occupied']);
        exit();
    }
    $stmt->close();
    
    $stmt = $connection->prepare("INSERT INTO students (class_id, student_name, seat_row, seat_column) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $class_id, $student_name, $seat_row, $seat_column);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add student']);
    }
    exit();
}

// Handle deleting a class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_class') {
    $class_id = $_POST['class_id'];
    $teacher_id = $_SESSION['teacher_id'];
    
    // Verify the class belongs to this teacher
    $stmt = $connection->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $class_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Class not found']);
        exit();
    }
    $stmt->close();
    
    // Delete any seating plans linked to seat requests for this class
    $stmt = $connection->prepare("DELETE sp FROM seating_plans sp JOIN seat_requests sr ON sp.seat_request_id = sr.id WHERE sr.class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->close();

    // Delete any seat requests for this class
    $stmt = $connection->prepare("DELETE FROM seat_requests WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->close();

    // Delete all students in this class
    $stmt = $connection->prepare("DELETE FROM students WHERE class_id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $stmt->close();
    
    // Delete the class
    $stmt = $connection->prepare("DELETE FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $class_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Class deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete class']);
    }
    $stmt->close();
    exit();
}

// Handle editing student
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_student') {
    $student_id = $_POST['student_id'];
    $student_name = $_POST['student_name'];
    
    $stmt = $connection->prepare("UPDATE students SET student_name = ? WHERE id = ?");
    $stmt->bind_param("si", $student_name, $student_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update student']);
    }
    exit();
}

// Handle getting scores for a class
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_scores') {
    $class_id = $_GET['class_id'];
    
    $stmt = $connection->prepare("
        SELECT s.id, s.student_name, sc.subject, sc.score 
        FROM students s 
        LEFT JOIN scores sc ON s.id = sc.student_id 
        WHERE s.class_id = ? 
        ORDER BY sc.created_at DESC 
        LIMIT 20
    ");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $scores = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['success' => true, 'scores' => $scores]);
    exit();
}

// Handle adding score (modal version)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_score_modal') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];
    $subject = $_POST['subject'];
    $score = $_POST['score'];
    
    $stmt = $connection->prepare("INSERT INTO scores (student_id, class_id, subject, score) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $student_id, $class_id, $subject, $score);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Score added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add score']);
    }
    exit();
}

// Handle adding score (old version - kept for backward compatibility)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_score') {
    $student_id = $_POST['student_id'];
    $class_id = $_POST['class_id'];
    $subject = $_POST['subject'];
    $score = $_POST['score'];
    
    $stmt = $connection->prepare("INSERT INTO scores (student_id, class_id, subject, score) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $student_id, $class_id, $subject, $score);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Score added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add score']);
    }
    exit();
}

// Get list of advisers
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_advisers') {
    $stmt = $connection->query("SELECT id, fullName FROM teachers ORDER BY fullName");
    $advisers = $stmt->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'advisers' => $advisers]);
    exit();
}

// Request seating arrangement from adviser
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'request_seating') {
    $adviser_id = $_POST['adviser_id'];
    $class_id = $_POST['class_id'];
    $notes = $_POST['notes'] ?? '';
    
    // Check if request already exists
    $stmt = $connection->prepare("SELECT id FROM seat_requests WHERE requesting_teacher_id = ? AND adviser_teacher_id = ? AND class_id = ? AND status = 'pending'");
    $stmt->bind_param("iii", $teacher_id, $adviser_id, $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Request already pending for this class']);
        exit();
    }
    
    // Create new request
    $stmt = $connection->prepare("INSERT INTO seat_requests (requesting_teacher_id, adviser_teacher_id, class_id, status, notes) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iiis", $teacher_id, $adviser_id, $class_id, $notes);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Seating request sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send request']);
    }
    $stmt->close();
    exit();
}

// Get seating requests for adviser
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_seating_requests') {
    $stmt = $connection->prepare("
        SELECT sr.id, sr.requesting_teacher_id, t.fullName as requester_name, c.className, c.section, sr.notes, sr.status, sr.created_at
        FROM seat_requests sr
        JOIN teachers t ON sr.requesting_teacher_id = t.id
        JOIN classes c ON sr.class_id = c.id
        WHERE sr.adviser_teacher_id = ?
        ORDER BY sr.created_at DESC
    ");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['success' => true, 'requests' => $requests]);
    exit();
}

// Get adviser for a class
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'get_class_adviser') {
    $class_id = $_GET['class_id'];
    
    $stmt = $connection->prepare("SELECT t.id, t.fullName FROM classes c LEFT JOIN teachers t ON c.adviser_id = t.id WHERE c.id = ?");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $adviser = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['success' => true, 'adviser' => $adviser]);
    exit();
}

// Approve seating request (adviser action)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'approve_seating_request') {
    $request_id = $_POST['request_id'];
    
    // Update request status
    $stmt = $connection->prepare("UPDATE seat_requests SET status = 'approved' WHERE id = ? AND adviser_teacher_id = ?");
    $stmt->bind_param("ii", $request_id, $teacher_id);

    if (!$stmt->execute()) {
        $err = $connection->error;
        echo json_encode(['success' => false, 'message' => 'Failed to approve', 'error' => $err]);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // If a seating plan exists for this request and is marked approved, apply it to students table
    $stmt = $connection->prepare("SELECT seating_data, approved FROM seating_plans WHERE seat_request_id = ? LIMIT 1");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $plan = $result->fetch_assoc();
        $stmt->close();

        if (!empty($plan['seating_data']) && intval($plan['approved']) === 1) {
            $seating = json_decode($plan['seating_data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($seating)) {
                // Get class id for this request
                $stmt = $connection->prepare("SELECT class_id FROM seat_requests WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();

                if ($row && isset($row['class_id'])) {
                    $class_id = $row['class_id'];
                    // Replace students for this class with seating plan
                    $stmt = $connection->prepare("DELETE FROM students WHERE class_id = ?");
                    $stmt->bind_param("i", $class_id);
                    $stmt->execute();
                    $stmt->close();

                    $insert = $connection->prepare("INSERT INTO students (class_id, student_name, seat_row, seat_column) VALUES (?, ?, ?, ?)");
                    foreach ($seating as $s) {
                        // expect each item to have student_name, seat_row, seat_column
                        $sname = $s['student_name'] ?? '';
                        $srow = isset($s['seat_row']) ? intval($s['seat_row']) : null;
                        $scol = isset($s['seat_column']) ? intval($s['seat_column']) : null;
                        if ($sname !== '' && $srow && $scol) {
                            $insert->bind_param("isii", $class_id, $sname, $srow, $scol);
                            $insert->execute();
                        }
                    }
                    $insert->close();
                }
            }
        }
    } else {
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Request approved']);
    exit();
}

// Reject seating request (adviser action)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'reject_seating_request') {
    $request_id = $_POST['request_id'];
    
    $stmt = $connection->prepare("UPDATE seat_requests SET status = 'rejected' WHERE id = ? AND adviser_teacher_id = ?");
    $stmt->bind_param("ii", $request_id, $teacher_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Request rejected']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject']);
    }
    $stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Teacher's Monitoring System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Teacher's Monitoring System</span>
            <div>
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars(isset($_SESSION['first_name']) ? $_SESSION['first_name'] : ($_SESSION['username'] ?? '')); ?></span>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-warning btn-sm me-2">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col-lg-8 dashboard-main">
                <!-- My Advisory Class Section -->
                <?php 
                $advisory_classes = array_filter($classes, function($c) { return $c['is_advisory'] == 1; });
                if (count($advisory_classes) > 0): 
                ?>
                <div class="advisory-classes-section">
                    <h3>My Advisory Class</h3>
                    <?php foreach ($advisory_classes as $class): ?>
                        <div class="card advisory-card mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <h5 class="card-title"><?php echo htmlspecialchars($class['className']) . " - " . htmlspecialchars($class['section']); ?></h5>
                                        <small class="text-muted">Academic Year: <?php echo htmlspecialchars($class['academic_year']); ?></small>
                                        <?php if ($class['adviser_id']): ?>
                                            <div style="margin-top: 0.5rem;">
                                                <?php 
                                                    $adviser_stmt = $connection->prepare("SELECT fullName FROM teachers WHERE id = ?");
                                                    $adviser_stmt->bind_param("i", $class['adviser_id']);
                                                    $adviser_stmt->execute();
                                                    $adviser_result = $adviser_stmt->get_result();
                                                    if ($adviser_result->num_rows > 0) {
                                                        $adviser = $adviser_result->fetch_assoc();
                                                        echo '<small class="text-success"><strong>Adviser: ' . htmlspecialchars($adviser['fullName']) . '</strong></small>';
                                                    }
                                                    $adviser_stmt->close();
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-5 text-end">
                                        <button class="btn btn-sm btn-info view-seating" data-class-id="<?php echo $class['id']; ?>">Seating</button>
                                        <button class="btn btn-sm btn-success view-scores" data-class-id="<?php echo $class['id']; ?>">Scores</button>
                                        <button class="btn btn-sm btn-danger delete-class" data-class-id="<?php echo $class['id']; ?>">Delete</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <h2 class="mb-4">My Classes</h2>
                
                <!-- Add Class Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Class</h5>
                    </div>
                    <div class="card-body">
                        <form id="addClassForm">
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label for="className" class="form-label">Class Name</label>
                                    <select class="form-select" id="className" name="className" required>
                                        <option value="">Select Class</option>
                                        <option value="STEM">STEM</option>
                                        <option value="HUMSS">HUMSS</option>
                                        <option value="ABM">ABM</option>
                                        <option value="GAS">GAS</option>
                                        <option value="AD">AD</option>
                                        <option value="HE">HE</option>
                                        <option value="ICT">ICT</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="section" class="form-label">Section</label>
                                    <select class="form-select" id="section" name="section" required>
                                        <option value="">Select Section</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_advisory" name="is_advisory">
                                        <label class="form-check-label" for="is_advisory">
                                            Mark as Advisory
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Class</button>
                        </form>
                    </div>
                </div>

                <!-- Classes List (excluding advisory) -->
                <div id="classesList">
                    <?php 
                    $regular_classes = array_filter($classes, function($c) { return $c['is_advisory'] == 0; });
                    if (count($regular_classes) > 0): 
                    ?>
                        <?php foreach ($regular_classes as $class): ?>
                            <div class="card mb-3 class-card" data-class-id="<?php echo $class['id']; ?>">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-7">
                                            <h5 class="card-title"><?php echo htmlspecialchars($class['className']) . " - " . htmlspecialchars($class['section']); ?></h5>
                                            <p class="card-text mb-0">
                                                <br><small class="text-muted">Academic Year: <?php echo htmlspecialchars($class['academic_year']); ?></small>
                                            </p>
                                        </div>
                                        <div class="col-md-5 text-end">
                                            <button class="btn btn-sm btn-info view-seating" data-class-id="<?php echo $class['id']; ?>">Seating</button>
                                            <button class="btn btn-sm btn-success view-scores" data-class-id="<?php echo $class['id']; ?>">Scores</button>
                                            <button class="btn btn-sm btn-danger delete-class" data-class-id="<?php echo $class['id']; ?>">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="alert alert-info">No regular classes added yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Sidebar for Scores Only -->
            <div class="col-lg-4 dashboard-sidebar">
                <!-- Scores Section -->
                <div id="scoresSection" class="card" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="scoresStudentName">Add Scores</h5>
                        <button class="btn btn-sm btn-secondary" onclick="closeScores()">Close</button>
                    </div>
                    <div class="card-body">
                        <div id="scoresList" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Seating Arrangement Modal -->
    <div class="modal fade" id="seatingModal" tabindex="-1" aria-labelledby="seatingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="seatingModalLabel">Seating Arrangement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Left side: Seating Grid -->
                        <div class="col-lg-8">
                            <div class="blackboard mb-3 p-2 text-center text-white">Front (Blackboard)</div>
                            <input type="hidden" id="currentClassId" name="class_id">
                            <p class="small text-muted">Click on a seat to edit or add a student</p>
                            <div id="seatingGrid" class="mt-3"></div>
                        </div>
                        
                        <!-- Right side: Requests & Actions -->
                        <div class="col-lg-4">
                            <!-- Pending Requests for Advisers -->
                            <div id="pendingRequestsSection" style="display: none; margin-bottom: 2rem;">
                                <h6 class="mb-3">Seating Requests</h6>
                                <div id="pendingRequestsList" class="list-group"></div>
                            </div>
                            
                            <!-- Request Seating for Non-Advisers -->
                            <div id="requestSeatingSection" style="display: none;">
                                <h6 class="mb-3">Request Seating from Adviser</h6>
                                <form id="seatingRequestForm">
                                    <input type="hidden" id="requestClassId" name="class_id">
                                    <div class="mb-3">
                                        <label for="adviserId" class="form-label">Select Adviser</label>
                                        <select class="form-select" id="adviserId" name="adviser_id" required>
                                            <option value="">Loading advisers...</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="requestNotes" class="form-label">Notes (Optional)</label>
                                        <textarea class="form-control" id="requestNotes" name="notes" rows="3" placeholder="Any special instructions..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Send Request</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editStudentForm">
                    <div class="modal-body">
                        <input type="hidden" id="editStudentId" name="student_id">
                        <input type="hidden" id="currentClassIdForStudent" name="class_id">
                        <div class="mb-3">
                            <label for="editStudentName" class="form-label">Student Name</label>
                            <input type="text" class="form-control" id="editStudentName" name="student_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-success" id="addScoresFromStudentBtn" style="display: none;" data-bs-toggle="modal" data-bs-target="#scoreSheetModal">Add Scores</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Score Sheet Modal -->
    <div class="modal fade" id="scoreSheetModal" tabindex="-1" aria-labelledby="scoreSheetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scoreSheetModalLabel">Score Sheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="scoreSheetForm">
                    <div class="modal-body">
                        <input type="hidden" id="modalStudentId" name="student_id">
                        <input type="hidden" id="modalClassId" name="class_id">
                        
                        <div class="score-categories">
                            <!-- Quiz -->
                            <div class="category-group">
                                <h6>Quiz</h6>
                                <div class="score-input-group">
                                    <label for="quiz1">Quiz 1</label>
                                    <input type="number" class="form-control form-control-sm" id="quiz1" name="quiz_1" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                                <div class="score-input-group">
                                    <label for="quiz2">Quiz 2</label>
                                    <input type="number" class="form-control form-control-sm" id="quiz2" name="quiz_2" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                                <div class="score-input-group">
                                    <label for="quiz3">Quiz 3</label>
                                    <input type="number" class="form-control form-control-sm" id="quiz3" name="quiz_3" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                            </div>

                            <!-- Activity -->
                            <div class="category-group">
                                <h6>Activity</h6>
                                <div class="score-input-group">
                                    <label for="activity1">Activity 1</label>
                                    <input type="number" class="form-control form-control-sm" id="activity1" name="activity_1" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                                <div class="score-input-group">
                                    <label for="activity2">Activity 2</label>
                                    <input type="number" class="form-control form-control-sm" id="activity2" name="activity_2" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                                <div class="score-input-group">
                                    <label for="activity3">Activity 3</label>
                                    <input type="number" class="form-control form-control-sm" id="activity3" name="activity_3" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                            </div>

                            <!-- Project -->
                            <div class="category-group">
                                <h6>Project</h6>
                                <div class="score-input-group">
                                    <label for="project1">Project 1</label>
                                    <input type="number" class="form-control form-control-sm" id="project1" name="project_1" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                                <div class="score-input-group">
                                    <label for="project2">Project 2</label>
                                    <input type="number" class="form-control form-control-sm" id="project2" name="project_2" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                            </div>

                            <!-- Exams -->
                            <div class="category-group">
                                <h6>Exams</h6>
                                <div class="score-input-group">
                                    <label for="monthlyExam">Monthly Exam</label>
                                    <input type="number" class="form-control form-control-sm" id="monthlyExam" name="monthly_exam" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                                <div class="score-input-group">
                                    <label for="quarterlyExam">Quarterly Exam</label>
                                    <input type="number" class="form-control form-control-sm" id="quarterlyExam" name="quarterly_exam" min="0" max="100" step="0.01" placeholder="0-100">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Scores</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
