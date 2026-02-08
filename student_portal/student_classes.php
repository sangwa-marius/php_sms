<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$student_id = $_SESSION['id'];

// Fetch enrolled classes with teacher info
$classes = mysqli_query($conn,
    "SELECT c.*, u.name as teacher_name, u.email as teacher_email,
            COUNT(DISTINCT e2.student_id) as total_students
     FROM enrollments e
     JOIN classes c ON e.class_id = c.id
     JOIN users u ON c.teacher_id = u.id
     LEFT JOIN enrollments e2 ON c.id = e2.class_id AND e2.status = 'enrolled'
     WHERE e.student_id = '$student_id' AND e.status = 'enrolled'
     GROUP BY c.id
     ORDER BY c.class_name");

if (!$classes) {
    $error = "Error loading classes: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Student Portal</title>
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
            background: linear-gradient(180deg, #007bff 0%, #0056b3 100%);
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
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            padding: 35px 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
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
            border-left: 5px solid #007bff;
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
            color: #007bff;
            margin-bottom: 5px;
        }

        .class-code {
            background: #e7f3ff;
            color: #007bff;
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

        .empty-state {
            text-align: center;
            padding: 80px 20px;
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

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #f5c6cb;
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
        <h2>StudentHub</h2>
        <p>Learning Portal</p>
    </div>
    <div class="sidebar-menu">
        <a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="student_classes.php" class="active"><i class="fas fa-book"></i> <span>My Classes</span></a>
        <a href="student_results.php"><i class="fas fa-chart-bar"></i> <span>My Results</span></a>
        <a href="student_attendance.php"><i class="fas fa-calendar-check"></i> <span>My Attendance</span></a>
        <a href="student_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-book"></i> My Classes</h1>
        <p>View all your enrolled classes and course details</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($classes && mysqli_num_rows($classes) > 0): ?>
        <div class="classes-grid">
            <?php while ($class = mysqli_fetch_assoc($classes)): ?>
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
                            <span class="class-info-label"><i class="fas fa-chalkboard-teacher"></i> Teacher:</span>
                            <span class="class-info-value"><?php echo htmlspecialchars($class['teacher_name']); ?></span>
                        </div>
                        <div class="class-info-item">
                            <span class="class-info-label"><i class="fas fa-door-open"></i> Room:</span>
                            <span class="class-info-value"><?php echo htmlspecialchars($class['room_number'] ?: 'TBA'); ?></span>
                        </div>
                        <div class="class-info-item">
                            <span class="class-info-label"><i class="fas fa-users"></i> Classmates:</span>
                            <span class="class-info-value"><?php echo $class['total_students']; ?> students</span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>No classes yet</h3>
            <p>You haven't been enrolled in any classes yet. Contact your teacher or administrator.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>