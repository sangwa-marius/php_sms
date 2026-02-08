<?php
session_start();
require '../db/db.php';

// Error handling
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$student_id = $_SESSION['id'];

try {
    // Fetch student's enrolled classes
    $classesQuery = mysqli_query($conn, 
        "SELECT COUNT(*) as total FROM enrollments 
         WHERE student_id = '$student_id' AND status = 'enrolled'");
    if (!$classesQuery) throw new Exception("Error fetching classes");
    $totalClasses = mysqli_fetch_assoc($classesQuery)['total'];

    // Fetch average grades
    $gradesQuery = mysqli_query($conn,
        "SELECT AVG(r.marks_obtained) as avg_grade, COUNT(r.id) as total_assessments
         FROM results r
         WHERE r.student_id = '$student_id'");
    if (!$gradesQuery) throw new Exception("Error fetching grades");
    $gradesData = mysqli_fetch_assoc($gradesQuery);
    $avgGrade = $gradesData['avg_grade'] ? round($gradesData['avg_grade'], 1) : 0;
    $totalAssessments = $gradesData['total_assessments'];

    // Fetch attendance rate
    $attendanceQuery = mysqli_query($conn,
        "SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
            COUNT(*) as total
         FROM attendance
         WHERE student_id = '$student_id'
         AND MONTH(date) = MONTH(CURDATE())");
    if (!$attendanceQuery) throw new Exception("Error fetching attendance");
    $attendanceData = mysqli_fetch_assoc($attendanceQuery);
    $attendanceRate = $attendanceData['total'] > 0 
        ? round(($attendanceData['present'] / $attendanceData['total']) * 100) 
        : 0;

    // Fetch upcoming assessments
    $upcomingAssessments = mysqli_query($conn,
        "SELECT a.*, c.class_name, c.subject
         FROM assessments a
         JOIN classes c ON a.class_id = c.id
         JOIN enrollments e ON c.id = e.class_id
         WHERE e.student_id = '$student_id' 
         AND e.status = 'enrolled'
         AND a.date >= CURDATE()
         ORDER BY a.date ASC
         LIMIT 5");
    if (!$upcomingAssessments) throw new Exception("Error fetching assessments");

    // Fetch recent grades
    $recentGrades = mysqli_query($conn,
        "SELECT r.*, a.title, a.total_marks, a.type, c.class_name
         FROM results r
         JOIN assessments a ON r.assessment_id = a.id
         JOIN classes c ON a.class_id = c.id
         WHERE r.student_id = '$student_id'
         ORDER BY r.uploaded_at DESC
         LIMIT 5");
    if (!$recentGrades) throw new Exception("Error fetching recent grades");

} catch (Exception $e) {
    $error_message = "Error loading dashboard: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
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

        .top-bar {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            padding: 35px 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2);
            margin-bottom: 35px;
            color: white;
        }

        .welcome-text h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .welcome-text h1 span {
            color: #4dabf7;
            font-weight: 700;
        }

        .welcome-text p {
            opacity: 0.95;
            font-size: 15px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #4dabf7);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .stat-card h3 {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: 700;
            color: #007bff;
            margin: 12px 0;
        }

        .stat-card p {
            color: #95a5a6;
            font-size: 14px;
        }

        .stat-badge {
            background: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-block;
        }

        .stat-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
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
            color: #007bff;
        }

        .grade-item {
            padding: 18px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .grade-item:last-child {
            margin-bottom: 0;
        }

        .grade-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .grade-title {
            font-weight: 600;
            color: #2c3e50;
        }

        .grade-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .grade-A { background: #d4edda; color: #155724; }
        .grade-B { background: #d1ecf1; color: #0c5460; }
        .grade-C { background: #fff3cd; color: #856404; }
        .grade-D { background: #f8d7da; color: #721c24; }
        .grade-F { background: #f8d7da; color: #721c24; }

        .grade-details {
            font-size: 13px;
            color: #6c757d;
        }

        .upcoming-item {
            padding: 18px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }

        .upcoming-item:last-child {
            margin-bottom: 0;
        }

        .upcoming-date {
            font-size: 12px;
            color: #007bff;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .upcoming-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .error-box {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #f5c6cb;
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 48px;
            color: #e0e0e0;
            margin-bottom: 15px;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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

            .dashboard-grid {
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
        <a href="student_dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="student_classes.php"><i class="fas fa-book"></i> <span>My Classes</span></a>
        <a href="student_results.php"><i class="fas fa-chart-bar"></i> <span>My Results</span></a>
        <a href="student_attendance.php"><i class="fas fa-calendar-check"></i> <span>My Attendance</span></a>
        <a href="student_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <?php if (isset($error_message)): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="top-bar">
        <div class="welcome-text">
            <h1>Welcome back, <span><?= htmlspecialchars($_SESSION['name']) ?></span>!</h1>
            <p><i class="fas fa-calendar-alt"></i> <?= date('l, F j, Y') ?></p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card">
            <h3><i class="fas fa-book"></i> Enrolled Classes</h3>
            <div class="number"><?php echo $totalClasses ?? 0; ?></div>
            <p>Active this semester</p>
            <span class="stat-badge"><?php echo $totalClasses > 0 ? 'Currently enrolled' : 'No classes yet'; ?></span>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-chart-line"></i> Average Grade</h3>
            <div class="number"><?php echo $avgGrade; ?>%</div>
            <p>Across all assessments</p>
            <span class="stat-badge <?php echo $avgGrade < 50 ? 'warning' : ''; ?>">
                <?php 
                if ($avgGrade >= 80) echo 'Excellent!';
                elseif ($avgGrade >= 60) echo 'Good work';
                elseif ($avgGrade > 0) echo 'Keep improving';
                else echo 'No grades yet';
                ?>
            </span>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-clipboard-check"></i> Total Assessments</h3>
            <div class="number"><?php echo $totalAssessments ?? 0; ?></div>
            <p>Tests and assignments</p>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-user-check"></i> Attendance Rate</h3>
            <div class="number"><?php echo $attendanceRate; ?>%</div>
            <p>This month</p>
            <span class="stat-badge <?php echo $attendanceRate < 75 ? 'warning' : ''; ?>">
                <?php echo $attendanceRate >= 90 ? 'Excellent!' : ($attendanceRate > 0 ? 'Keep it up' : 'No data'); ?>
            </span>
        </div>
    </div>

    <div class="content-grid">
        <!-- Recent Grades -->
        <div class="card">
            <h2><i class="fas fa-star"></i> Recent Grades</h2>
            <?php if ($recentGrades && mysqli_num_rows($recentGrades) > 0): ?>
                <?php while ($grade = mysqli_fetch_assoc($recentGrades)): ?>
                    <div class="grade-item">
                        <div class="grade-header">
                            <span class="grade-title"><?php echo htmlspecialchars($grade['class_name']); ?> - <?php echo htmlspecialchars($grade['title']); ?></span>
                            <span class="grade-badge grade-<?php echo substr($grade['grade'], 0, 1); ?>"><?php echo $grade['grade']; ?></span>
                        </div>
                        <div class="grade-details">
                            Score: <?php echo $grade['marks_obtained']; ?> / <?php echo $grade['total_marks']; ?> 
                            (<?php echo round(($grade['marks_obtained'] / $grade['total_marks']) * 100, 1); ?>%) • 
                            <?php echo ucfirst($grade['type']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <p>No grades available yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Assessments -->
        <div class="card">
            <h2><i class="fas fa-calendar-alt"></i> Upcoming</h2>
            <?php if ($upcomingAssessments && mysqli_num_rows($upcomingAssessments) > 0): ?>
                <?php while ($assessment = mysqli_fetch_assoc($upcomingAssessments)): ?>
                    <div class="upcoming-item">
                        <div class="upcoming-date">
                            <i class="fas fa-calendar"></i> 
                            <?php 
                            $diff = floor((strtotime($assessment['date']) - time()) / 86400);
                            if ($diff == 0) echo 'Today';
                            elseif ($diff == 1) echo 'Tomorrow';
                            else echo date('M j', strtotime($assessment['date']));
                            ?>
                        </div>
                        <div class="upcoming-title"><?php echo htmlspecialchars($assessment['title']); ?></div>
                        <div class="grade-details">
                            <?php echo htmlspecialchars($assessment['class_name']); ?> • 
                            <?php echo ucfirst($assessment['type']); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-check"></i>
                    <p>No upcoming assessments</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>