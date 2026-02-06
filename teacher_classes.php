<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$teacher_id = $_SESSION['id'];

// Handle class creation
if (isset($_POST['create_class'])) {
    $className = mysqli_real_escape_string($conn, $_POST['class_name']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $academicYear = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $roomNumber = mysqli_real_escape_string($conn, $_POST['room_number']);
    $maxStudents = mysqli_real_escape_string($conn, $_POST['max_students']);
    $classCode = strtoupper(substr($subject, 0, 3) . rand(1000, 9999));
    
    $sql = "INSERT INTO classes (class_name, subject, grade, academic_year, teacher_id, class_code, room_number, max_students) 
            VALUES ('$className', '$subject', '$grade', '$academicYear', '$teacher_id', '$classCode', '$roomNumber', '$maxStudents')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Class '$className' created successfully! Class Code: $classCode";
        $messageType = "success";
    } else {
        $message = "Error creating class: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle class deletion
if (isset($_POST['delete_class'])) {
    $classId = mysqli_real_escape_string($conn, $_POST['class_id']);
    
    $sql = "DELETE FROM classes WHERE id = '$classId' AND teacher_id = '$teacher_id'";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Class deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting class: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle adding student to class
if (isset($_POST['add_student'])) {
    $classId = mysqli_real_escape_string($conn, $_POST['class_id']);
    $studentId = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    $sql = "INSERT INTO enrollments (class_id, student_id) VALUES ('$classId', '$studentId')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Student enrolled successfully!";
        $messageType = "success";
    } else {
        $message = "Error enrolling student: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle removing student from class
if (isset($_POST['remove_student'])) {
    $enrollmentId = mysqli_real_escape_string($conn, $_POST['enrollment_id']);
    
    $sql = "DELETE FROM enrollments WHERE id = '$enrollmentId'";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Student removed successfully!";
        $messageType = "success";
    } else {
        $message = "Error removing student: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Fetch all classes for this teacher
$classes = mysqli_query($conn, "SELECT * FROM classes WHERE teacher_id = '$teacher_id' ORDER BY created_date DESC");

// Fetch all students for dropdown
$allStudents = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role = 'student' AND status = 'active' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - TeacherHub</title>
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

        .header p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 15px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
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
            box-shadow: 0 5px 15px rgba(12, 90, 85, 0.3);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0c5a55;
        }

        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }

        .class-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border-left: 5px solid #0c5a55;
            transition: all 0.3s;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .class-header {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }

        .class-header h3 {
            font-size: 22px;
            color: #0c5a55;
            margin-bottom: 5px;
        }

        .class-code {
            background: #e8f5f3;
            color: #0c5a55;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .class-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .class-info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .class-info-item:last-child {
            margin-bottom: 0;
        }

        .class-info-label {
            color: #6c757d;
        }

        .class-info-value {
            color: #2c3e50;
            font-weight: 600;
        }

        .student-list {
            max-height: 250px;
            overflow-y: auto;
            margin-top: 15px;
        }

        .student-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.3s;
        }

        .student-item:hover {
            background: #e9ecef;
        }

        .student-name {
            font-size: 14px;
            color: #2c3e50;
            font-weight: 500;
        }

        .class-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .class-actions button,
        .class-actions a {
            flex: 1;
            text-align: center;
            text-decoration: none;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }

        .modal-header h3 {
            color: #333;
            font-size: 22px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            color: #e0e0e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #6c757d;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: linear-gradient(135deg, #0c5a55, #0a4a46);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-box .label {
            font-size: 13px;
            opacity: 0.9;
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

            .classes-grid {
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
        <a href="profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="teacher_classes.php" class="active"><i class="fas fa-chalkboard"></i> <span>My Classes</span></a>
        <a href="teacher_students.php"><i class="fas fa-users"></i> <span>Students</span></a>
        <a href="uploadResults.php"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="teacher_attendance.php"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-chalkboard-teacher"></i> My Classes</h1>
        <p>Manage your classes, students, and course materials</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Row -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="number"><?php echo mysqli_num_rows($classes); ?></div>
            <div class="label">Total Classes</div>
        </div>
        <div class="stat-box">
            <div class="number">
                <?php
                $totalStudents = mysqli_fetch_assoc(mysqli_query($conn, 
                    "SELECT COUNT(DISTINCT e.student_id) as total 
                     FROM enrollments e 
                     JOIN classes c ON e.class_id = c.id 
                     WHERE c.teacher_id = '$teacher_id' AND e.status = 'enrolled'"))['total'];
                echo $totalStudents;
                ?>
            </div>
            <div class="label">Total Students</div>
        </div>
        <div class="stat-box">
            <div class="number">
                <?php echo date('Y'); ?>-<?php echo date('Y') + 1; ?>
            </div>
            <div class="label">Academic Year</div>
        </div>
    </div>

    <!-- Create New Class Card -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Create New Class</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Class Name *</label>
                    <input type="text" name="class_name" required placeholder="e.g., Advanced Mathematics">
                </div>
                <div class="form-group">
                    <label>Subject *</label>
                    <input type="text" name="subject" required placeholder="e.g., Mathematics">
                </div>
                <div class="form-group">
                    <label>Grade Level *</label>
                    <select name="grade" required>
                        <option value="">Select Grade</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                        <option value="Grade 11">Grade 11</option>
                        <option value="Grade 12">Grade 12</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Academic Year *</label>
                    <input type="text" name="academic_year" required value="<?php echo date('Y') . '-' . (date('Y') + 1); ?>" placeholder="e.g., 2024-2025">
                </div>
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" placeholder="e.g., A-204">
                </div>
                <div class="form-group">
                    <label>Max Students *</label>
                    <input type="number" name="max_students" required value="40" min="1" max="100">
                </div>
            </div>
            <button type="submit" name="create_class" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Class
            </button>
        </form>
    </div>

    <!-- Classes List -->
    <div class="card">
        <h2><i class="fas fa-list"></i> Your Classes (<?php echo mysqli_num_rows($classes); ?>)</h2>
        <?php if (mysqli_num_rows($classes) > 0): ?>
            <div class="classes-grid">
                <?php 
                mysqli_data_seek($classes, 0);
                while ($class = mysqli_fetch_assoc($classes)): 
                    $classId = $class['id'];
                    
                    // Get enrolled students
                    $enrolledStudents = mysqli_query($conn, 
                        "SELECT e.id as enrollment_id, u.name, u.email 
                         FROM enrollments e 
                         JOIN users u ON e.student_id = u.id 
                         WHERE e.class_id = '$classId' AND e.status = 'enrolled' 
                         ORDER BY u.name");
                    $enrollmentCount = mysqli_num_rows($enrolledStudents);
                ?>
                    <div class="class-card">
                        <div class="class-header">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <span class="class-code"><?php echo htmlspecialchars($class['class_code']); ?></span>
                        </div>
                        
                        <div class="class-info">
                            <div class="class-info-item">
                                <span class="class-info-label"><i class="fas fa-book"></i> Subject:</span>
                                <span class="class-info-value"><?php echo htmlspecialchars($class['subject']); ?></span>
                            </div>
                            <div class="class-info-item">
                                <span class="class-info-label"><i class="fas fa-layer-group"></i> Grade:</span>
                                <span class="class-info-value"><?php echo htmlspecialchars($class['grade']); ?></span>
                            </div>
                            <div class="class-info-item">
                                <span class="class-info-label"><i class="fas fa-calendar"></i> Year:</span>
                                <span class="class-info-value"><?php echo htmlspecialchars($class['academic_year']); ?></span>
                            </div>
                            <div class="class-info-item">
                                <span class="class-info-label"><i class="fas fa-door-open"></i> Room:</span>
                                <span class="class-info-value"><?php echo htmlspecialchars($class['room_number'] ?: 'Not assigned'); ?></span>
                            </div>
                            <div class="class-info-item">
                                <span class="class-info-label"><i class="fas fa-users"></i> Enrolled:</span>
                                <span class="class-info-value"><?php echo $enrollmentCount; ?> / <?php echo $class['max_students']; ?></span>
                            </div>
                        </div>

                        <?php if ($enrollmentCount > 0): ?>
                            <div class="student-list">
                                <strong style="display: block; margin-bottom: 10px; color: #0c5a55;">
                                    <i class="fas fa-user-graduate"></i> Students (<?php echo $enrollmentCount; ?>)
                                </strong>
                                <?php while ($student = mysqli_fetch_assoc($enrolledStudents)): ?>
                                    <div class="student-item">
                                        <span class="student-name">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($student['name']); ?>
                                        </span>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this student from the class?');">
                                            <input type="hidden" name="enrollment_id" value="<?php echo $student['enrollment_id']; ?>">
                                            <button type="submit" name="remove_student" style="background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>

                        <div class="class-actions">
                            <button class="btn btn-success" onclick="openEnrollModal(<?php echo $class['id']; ?>, '<?php echo htmlspecialchars($class['class_name'], ENT_QUOTES); ?>')">
                                <i class="fas fa-user-plus"></i> Enroll Student
                            </button>
                            <form method="POST" style="flex: 1;" onsubmit="return confirm('Delete this class? All enrollments will be removed.');">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <button type="submit" name="delete_class" class="btn btn-danger" style="width: 100%;">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3>No classes yet</h3>
                <p>Create your first class using the form above</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enroll Student Modal -->
<div id="enrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Enroll Student</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="class_id" id="modalClassId">
            <div class="form-group">
                <label>Select Student *</label>
                <select name="student_id" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <option value="">Choose a student...</option>
                    <?php 
                    mysqli_data_seek($allStudents, 0);
                    while ($student = mysqli_fetch_assoc($allStudents)): 
                    ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add_student" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-plus"></i> Enroll Student
            </button>
        </form>
    </div>
</div>

<script>
    function openEnrollModal(classId, className) {
        document.getElementById('modalClassId').value = classId;
        document.getElementById('enrollModal').classList.add('active');
    }

    function closeModal() {
        document.getElementById('enrollModal').classList.remove('active');
    }

    document.getElementById('enrollModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

</body>
</html>