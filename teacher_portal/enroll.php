<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$teacher_id = $_SESSION['id'];

// Handle bulk enrollment
if (isset($_POST['bulk_enroll'])) {
    $classId = mysqli_real_escape_string($conn, $_POST['class_id']);
    $selectedStudents = $_POST['students'] ?? [];
    
    $successCount = 0;
    $errorCount = 0;
    $duplicateCount = 0;
    
    foreach ($selectedStudents as $studentId) {
        $studentId = mysqli_real_escape_string($conn, $studentId);
        
        // Check if already enrolled
        $checkQuery = "SELECT id FROM enrollments WHERE class_id = '$classId' AND student_id = '$studentId'";
        $exists = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($exists) > 0) {
            $duplicateCount++;
            continue;
        }
        
        $sql = "INSERT INTO enrollments (class_id, student_id) VALUES ('$classId', '$studentId')";
        
        if (mysqli_query($conn, $sql)) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    $message = "$successCount student(s) enrolled successfully!";
    if ($duplicateCount > 0) {
        $message .= " $duplicateCount student(s) were already enrolled.";
    }
    if ($errorCount > 0) {
        $message .= " $errorCount error(s) occurred.";
    }
    $messageType = $successCount > 0 ? "success" : "error";
}

// Fetch teacher's classes
$classes = mysqli_query($conn, "SELECT * FROM classes WHERE teacher_id = '$teacher_id' AND status = 'active' ORDER BY class_name");

// Get selected class if any
$selectedClass = isset($_GET['class']) ? mysqli_real_escape_string($conn, $_GET['class']) : '';

// Fetch available students (not enrolled in selected class)
$availableStudents = null;
if ($selectedClass) {
    $availableStudents = mysqli_query($conn, 
        "SELECT u.id, u.name, u.email 
         FROM users u 
         WHERE u.role = 'student' 
         AND u.status = 'active'
         AND u.id NOT IN (
             SELECT student_id 
             FROM enrollments 
             WHERE class_id = '$selectedClass' 
             AND status = 'enrolled'
         )
         ORDER BY u.name");
}

// Get already enrolled students for reference
$enrolledStudents = null;
if ($selectedClass) {
    $enrolledStudents = mysqli_query($conn,
        "SELECT u.id, u.name, u.email
         FROM users u
         JOIN enrollments e ON u.id = e.student_id
         WHERE e.class_id = '$selectedClass' 
         AND e.status = 'enrolled'
         ORDER BY u.name");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Enrollment - TeacherHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            background:#0c5a55;
            color: white;
            position: fixed;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px 20px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            text-align: center;
            font-size: 13px;
            opacity: 0.9;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 14px 25px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .sidebar a i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
        }

        .sidebar a:hover {
            background: rgba(255,255,255,0.15);
            transform: translateX(5px);
        }

        .sidebar a.active {
            background: rgba(255,255,255,0.2);
        }

        .main {
            margin-left: 260px;
            padding: 30px 40px;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #0c5a55 0%, #0a4a46 100%);
            padding: 35px 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(12, 90, 85, 0.2);
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }

        .card h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            color: #0c5a55;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group select:focus {
            outline: none;
            border-color: #0c5a55;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #0c5a55;
            color: white;
        }

        .btn-primary:hover {
            background: #0a4a46;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .students-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        .students-box {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }

        .students-box h3 {
            margin-bottom: 15px;
            color: #0c5a55;
            font-size: 18px;
            position: sticky;
            top: 0;
            background: white;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .student-checkbox {
            padding: 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .student-checkbox:hover {
            background: #e9ecef;
        }

        .student-checkbox label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin: 0;
        }

        .student-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .student-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .student-email {
            font-size: 12px;
            color: #6c757d;
            margin-left: 28px;
        }

        .select-all-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .select-all-btn:hover {
            background: #138496;
        }

        .enrolled-student {
            padding: 12px;
            margin-bottom: 8px;
            background: #d4edda;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }

        .enrolled-student-name {
            font-weight: 600;
            color: #155724;
        }

        .info-box {
            background: #e8f5f3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0c5a55;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar-header h2,
            .sidebar-header p,
            .sidebar a span {
                display: none;
            }
            
            .sidebar a {
                justify-content: center;
            }
            
            .sidebar a i {
                margin-right: 0;
            }
            
            .main {
                margin-left: 70px;
                padding: 20px;
            }

            .students-selection {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>TeacherHub</h2>
        <p>Education Portal</p>
    </div>
    <div class="sidebar-menu">
        <a href="teacher_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="teacher_classes.php"><i class="fas fa-chalkboard"></i> <span>My Classes</span></a>
        <a href="teacher_students.php"><i class="fas fa-users"></i> <span>Students</span></a>
        <a href="uploadResults.php"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="teacher_attendance.php"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-user-plus"></i> Bulk Enrollment</h1>
        <p>Add multiple students to a class at once</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-chalkboard"></i> Select Class</h2>
        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> How it works:</strong><br>
            1. Select a class from the dropdown<br>
            2. Check the students you want to enroll<br>
            3. Click "Enroll Selected Students"
        </div>
        <form method="GET" action="">
            <div class="form-group">
                <label>Choose a Class *</label>
                <select name="class" onchange="this.form.submit()" required>
                    <option value="">-- Select a class --</option>
                    <?php 
                    mysqli_data_seek($classes, 0);
                    while ($class = mysqli_fetch_assoc($classes)): 
                    ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $selectedClass == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?> - 
                            <?php echo htmlspecialchars($class['subject']); ?> 
                            (<?php echo htmlspecialchars($class['grade']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selectedClass && $availableStudents): ?>
        <div class="card">
            <h2><i class="fas fa-users"></i> Enroll Students</h2>
            <form method="POST">
                <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                
                <div class="students-selection">
                    <!-- Available Students -->
                    <div class="students-box">
                        <h3><i class="fas fa-user-plus"></i> Available Students (<?php echo mysqli_num_rows($availableStudents); ?>)</h3>
                        <?php if (mysqli_num_rows($availableStudents) > 0): ?>
                            <button type="button" class="select-all-btn" onclick="selectAll()">
                                <i class="fas fa-check-double"></i> Select All
                            </button>
                            <button type="button" class="select-all-btn" onclick="deselectAll()">
                                <i class="fas fa-times"></i> Deselect All
                            </button>
                            <?php while ($student = mysqli_fetch_assoc($availableStudents)): ?>
                                <div class="student-checkbox">
                                    <label>
                                        <input type="checkbox" name="students[]" value="<?php echo $student['id']; ?>" class="student-select">
                                        <span class="student-name"><?php echo htmlspecialchars($student['name']); ?></span>
                                    </label>
                                    <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>All students are already enrolled in this class!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Already Enrolled Students -->
                    <div class="students-box">
                        <h3><i class="fas fa-user-check"></i> Already Enrolled (<?php echo mysqli_num_rows($enrolledStudents); ?>)</h3>
                        <?php if (mysqli_num_rows($enrolledStudents) > 0): ?>
                            <?php while ($student = mysqli_fetch_assoc($enrolledStudents)): ?>
                                <div class="enrolled-student">
                                    <div class="enrolled-student-name">
                                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($student['name']); ?>
                                    </div>
                                    <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <p>No students enrolled yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (mysqli_num_rows($availableStudents) > 0): ?>
                    <button type="submit" name="bulk_enroll" class="btn btn-success" style="width: 100%; margin-top: 20px; padding: 15px;">
                        <i class="fas fa-user-plus"></i> Enroll Selected Students
                    </button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    function selectAll() {
        const checkboxes = document.querySelectorAll('.student-select');
        checkboxes.forEach(cb => cb.checked = true);
    }

    function deselectAll() {
        const checkboxes = document.querySelectorAll('.student-select');
        checkboxes.forEach(cb => cb.checked = false);
    }
</script>

</body>
</html>