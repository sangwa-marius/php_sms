<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

$teacher_id = $_SESSION['id'];

// Handle attendance marking
if (isset($_POST['mark_attendance'])) {
    $classId = mysqli_real_escape_string($conn, $_POST['class_id']);
    $date = mysqli_real_escape_string($conn, $_POST['attendance_date']);
    $attendanceData = $_POST['attendance'] ?? [];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($attendanceData as $studentId => $status) {
        $studentId = mysqli_real_escape_string($conn, $studentId);
        $status = mysqli_real_escape_string($conn, $status);
        
        // Insert or update attendance
        $sql = "INSERT INTO attendance (class_id, student_id, date, status, marked_by) 
                VALUES ('$classId', '$studentId', '$date', '$status', '$teacher_id')
                ON DUPLICATE KEY UPDATE status = '$status', marked_by = '$teacher_id'";
        
        if (mysqli_query($conn, $sql)) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    if ($successCount > 0) {
        $message = "Attendance marked for $successCount student(s)!";
        $messageType = "success";
    }
    if ($errorCount > 0) {
        $message .= " $errorCount error(s) occurred.";
        $messageType = "error";
    }
}

// Fetch teacher's classes
$classes = mysqli_query($conn, "SELECT * FROM classes WHERE teacher_id = '$teacher_id' AND status = 'active' ORDER BY class_name");

// Get selected class and date
$selectedClass = isset($_GET['class']) ? mysqli_real_escape_string($conn, $_GET['class']) : '';
$selectedDate = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : date('Y-m-d');

// Fetch students for selected class
$students = null;
$classInfo = null;
if ($selectedClass) {
    $classInfo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM classes WHERE id = '$selectedClass'"));
    
    $students = mysqli_query($conn,
        "SELECT u.id, u.name, u.email, a.status as attendance_status
         FROM enrollments e
         JOIN users u ON e.student_id = u.id
         LEFT JOIN attendance a ON a.student_id = u.id AND a.class_id = '$selectedClass' AND a.date = '$selectedDate'
         WHERE e.class_id = '$selectedClass' AND e.status = 'enrolled'
         ORDER BY u.name");
}

// Fetch attendance statistics for selected class
$stats = [];
if ($selectedClass) {
    $statsQuery = mysqli_query($conn,
        "SELECT 
            COUNT(DISTINCT CASE WHEN a.status = 'present' THEN a.student_id END) as total_present,
            COUNT(DISTINCT CASE WHEN a.status = 'absent' THEN a.student_id END) as total_absent,
            COUNT(DISTINCT CASE WHEN a.status = 'late' THEN a.student_id END) as total_late,
            COUNT(DISTINCT a.date) as total_days
         FROM attendance a
         WHERE a.class_id = '$selectedClass'
         AND MONTH(a.date) = MONTH(CURDATE())");
    $stats = mysqli_fetch_assoc($statsQuery);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - TeacherHub</title>
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

        .filter-section {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 250px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-group select:focus,
        .filter-group input:focus {
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

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, #0c5a55, #0a4a46);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
            text-transform: uppercase;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .attendance-table thead {
            background: #0c5a55;
            color: white;
        }

        .attendance-table th,
        .attendance-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .attendance-table tbody tr:hover {
            background: #f8f9fa;
        }

        .attendance-options {
            display: flex;
            gap: 10px;
        }

        .attendance-radio {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .attendance-radio input[type="radio"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-present {
            background: #d4edda;
            color: #155724;
        }

        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }

        .status-late {
            background: #fff3cd;
            color: #856404;
        }

        .status-excused {
            background: #d1ecf1;
            color: #0c5460;
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

            .filter-section {
                flex-direction: column;
            }

            .attendance-table {
                font-size: 13px;
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
        <a href="teacher_attendance.php" class="active"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="teacher_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-user-check"></i> Attendance Management</h1>
        <p>Mark and track student attendance</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <?php if ($selectedClass && !empty($stats)): ?>
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_present']; ?></div>
                <div class="stat-label">Present This Month</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_absent']; ?></div>
                <div class="stat-label">Absent This Month</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_late']; ?></div>
                <div class="stat-label">Late This Month</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total_days']; ?></div>
                <div class="stat-label">Days Tracked</div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-filter"></i> Select Class & Date</h2>
        <form method="GET" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>Class *</label>
                    <select name="class" required onchange="this.form.submit()">
                        <option value="">Select a class</option>
                        <?php 
                        mysqli_data_seek($classes, 0);
                        while ($class = mysqli_fetch_assoc($classes)): 
                        ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $selectedClass == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['subject']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date *</label>
                    <input type="date" name="date" value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>" required onchange="this.form.submit()">
                </div>
            </div>
        </form>
    </div>

    <?php if ($selectedClass && $students): ?>
        <div class="card">
            <h2><i class="fas fa-clipboard-check"></i> Mark Attendance - <?php echo htmlspecialchars($classInfo['class_name']); ?></h2>
            <p style="color: #6c757d; margin-bottom: 20px;">
                <i class="fas fa-calendar"></i> <?php echo date('l, F j, Y', strtotime($selectedDate)); ?>
            </p>

            <?php if (mysqli_num_rows($students) > 0): ?>
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $selectedClass; ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo $selectedDate; ?>">
                    
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($student = mysqli_fetch_assoc($students)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td>
                                        <div class="attendance-options">
                                            <label class="attendance-radio">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['id']; ?>]" 
                                                       value="present" 
                                                       <?php echo $student['attendance_status'] === 'present' ? 'checked' : ''; ?>
                                                       required>
                                                <span class="status-badge status-present">Present</span>
                                            </label>
                                            <label class="attendance-radio">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['id']; ?>]" 
                                                       value="absent"
                                                       <?php echo $student['attendance_status'] === 'absent' ? 'checked' : ''; ?>>
                                                <span class="status-badge status-absent">Absent</span>
                                            </label>
                                            <label class="attendance-radio">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['id']; ?>]" 
                                                       value="late"
                                                       <?php echo $student['attendance_status'] === 'late' ? 'checked' : ''; ?>>
                                                <span class="status-badge status-late">Late</span>
                                            </label>
                                            <label class="attendance-radio">
                                                <input type="radio" 
                                                       name="attendance[<?php echo $student['id']; ?>]" 
                                                       value="excused"
                                                       <?php echo $student['attendance_status'] === 'excused' ? 'checked' : ''; ?>>
                                                <span class="status-badge status-excused">Excused</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <button type="submit" name="mark_attendance" class="btn btn-success" style="width: 100%; margin-top: 20px; padding: 15px;">
                        <i class="fas fa-save"></i> Save Attendance
                    </button>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h3>No students enrolled</h3>
                    <p>Enroll students in this class to mark attendance</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>