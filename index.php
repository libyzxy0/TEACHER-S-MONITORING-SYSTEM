<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['teacher_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    include 'database.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (strlen($username) < 3 || strlen($username) > 20) {
        $error = "Username must be 3-20 characters long.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if user exists and is approved
        $stmt = $connection->prepare("SELECT id, password, fullName, status, role FROM teachers WHERE username = ?");
        if ($stmt === false) {
            $error = "Database error: " . $connection->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Check if user is approved
                if ($row['status'] !== 'active') {
                    $error = "Your account is pending admin approval. Please wait before logging in.";
                } else if (password_verify($password, $row['password']) || $row['password'] == md5($password)) {
                  $_SESSION['teacher_id'] = $row['id'];
                  $_SESSION['username'] = $username;
                  $_SESSION['user_role'] = $row['role'];
                  // Save full name and first name for display
                  $_SESSION['fullName'] = $row['fullName'];
                  $first = trim(explode(' ', $row['fullName'])[0]);
                  $_SESSION['first_name'] = $first ?: $username;
                  header("Location: dashboard.php");
                  exit();
                } else {
                  $error = "Invalid password.";
                }
            } else {
                $error = "User not found.";
            }
            $stmt->close();
        }
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    include 'database.php';
    
    $fullName = trim($_POST['fullName']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($fullName)) {
        $error = "Full name is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = "Username must be 3-20 characters long.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check if username already exists
        $stmt = $connection->prepare("SELECT id FROM teachers WHERE username = ?");
        if ($stmt === false) {
            $error = "Database error: " . $connection->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username already taken.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new teacher with pending status
                $stmt = $connection->prepare("INSERT INTO teachers (fullName, username, password, role, status) VALUES (?, ?, ?, 'teacher', 'pending')");
                if ($stmt === false) {
                    $error = "Database error: " . $connection->error;
                } else {
                    $stmt->bind_param("sss", $fullName, $username, $hashed_password);
                    
                    if ($stmt->execute()) {
                        $success = "Registration successful! Please wait for admin approval before you can login.";
                    } else {
                        $error = "Registration failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher's Monitoring System</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h1 class="card-title text-center mb-4">Teacher's Monitoring System</h1>
            
            <!-- Error/Success Messages -->
            <?php if ($error): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div id="loginSection">
              <h3 class="mb-3">Login</h3>
              <form method="POST" action="">
                <div class="mb-3">
                  <label for="loginUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="loginUsername" name="username" required>
                  <small class="text-muted">3-20 characters</small>
                </div>
                <div class="mb-3">
                  <label for="loginPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="loginPassword" name="password" required>
                  <small class="text-muted">At least 6 characters</small>
                </div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
              </form>
              <p class="text-center mt-3">
                Don't have an account? <a href="#" onclick="toggleForms(); return false;">Register here</a>
              </p>
            </div>

            <!-- Registration Form -->
            <div id="registrationSection" style="display: none;">
              <h3 class="mb-3">Registration</h3>
              <form method="POST" action="">
                <div class="mb-3">
                  <label for="fullName" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="fullName" name="fullName" required>
                </div>
                <div class="mb-3">
                  <label for="regUsername" class="form-label">Username</label>
                  <input type="text" class="form-control" id="regUsername" name="username" required>
                  <small class="text-muted">3-20 characters</small>
                </div>
                <div class="mb-3">
                  <label for="regPassword" class="form-label">Password</label>
                  <input type="password" class="form-control" id="regPassword" name="password" required>
                  <small class="text-muted">At least 6 characters</small>
                </div>
                <button type="submit" name="register" class="btn btn-success w-100">Register</button>
              </form>
              <p class="text-center mt-3">
                Already have an account? <a href="#" onclick="toggleForms(); return false;">Login here</a>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
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
  </script>
</body>
</html>
