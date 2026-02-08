<?php
session_start();
require '../db/db.php';

// Error handling
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: ../index.php");
    exit;
}

$principle_id = $_SESSION['id'];

try {
    // System-wide statistics
    $totalTeachers = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'"))['total'];

    $totalStudents = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'"))['total'];

    $totalClasses = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as total FROM classes WHERE status = 'active'"))['total'];

    $totalEnrollments = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as total FROM enrollments WHERE status = 'enrolled'"))['total'];

    // Overall attendance
    $overallAttendance = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT 
            ROUND((COUNT(CASE WHEN status = 'present' THEN 1 END) / COUNT(*)) * 100, 1) as rate
         FROM attendance
         WHERE MONTH(date) = MONTH(CURDATE())"))['rate'] ?? 0;

    // Average performance
    $avgPerformance = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT AVG(marks_obtained) as avg FROM results"))['avg'];
    $avgPerformance = $avgPerformance ? round($avgPerformance, 1) : 0;

    // Recent activity
    $recentActivity = mysqli_query($conn,
        "SELECT 
            'class_created' as type,
            c.class_name as title,
            u.name as teacher_name,
            c.created_date as activity_date
         FROM classes c
         JOIN users u ON c.teacher_id = u.id
         ORDER BY c.created_date DESC
         LIMIT 5");

    // Top performing classes
    $topClasses = mysqli_query($conn,
        "SELECT 
            c.class_name,
            c.subject,
            u.name as teacher_name,
            COUNT(DISTINCT e.student_id) as student_count,
            AVG(r.marks_obtained) as avg_marks
         FROM classes c
         JOIN users u ON c.teacher_id = u.id
         LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
         LEFT JOIN assessments a ON c.id = a.class_id
         LEFT JOIN results r ON a.id = r.assessment_id
         WHERE c.status = 'active'
         GROUP BY c.id
         HAVING AVG(r.marks_obtained) IS NOT NULL
         ORDER BY avg_marks DESC
         LIMIT 5");

    // Teacher workload
    $teacherWorkload = mysqli_query($conn,
        "SELECT 
            u.name,
            COUNT(DISTINCT c.id) as class_count,
            COUNT(DISTINCT e.student_id) as student_count
         FROM users u
         LEFT JOIN classes c ON u.id = c.teacher_id AND c.status = 'active'
         LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
         WHERE u.role = 'teacher' AND u.status = 'active'
         GROUP BY u.id
         ORDER BY class_count DESC
         LIMIT 5");

    // Recent announcements
    $recentAnnouncements = mysqli_query($conn,
        "SELECT a.*, u.name as created_by_name
         FROM announcements a
         JOIN users u ON a.created_by = u.id
         ORDER BY a.created_at DESC
         LIMIT 3");

} catch (Exception $e) {
    error_log($e->getMessage());
    $error = "Unable to load dashboard data. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Principal Dashboard</title>
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
            background: linear-gradient(180deg, #6f42c1 0%, #5a3399 100%);
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
            background: linear-gradient(135deg, #6f42c1 0%, #5a3399 100%);
            padding: 35px 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.2);
            margin-bottom: 35px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .welcome-text h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .welcome-text h1 span {
            color: #9775fa;
            font-weight: 700;
        }

        .welcome-text p {
            opacity: 0.95;
            font-size: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .user-details {
            text-align: right;
        }

        .user-details h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .user-details p {
            font-size: 13px;
            opacity: 0.9;
        }

        .user-avatar {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #9775fa, #7c5ce3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #6f42c1, #9775fa);
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
            color: #6f42c1;
            margin: 12px 0;
        }

        .stat-card p {
            color: #95a5a6;
            font-size: 14px;
        }

        .stat-badge {
            background: #e8d9f7;
            color: #6f42c1;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
            display: inline-block;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .card h2 {
            color: #6f42c1;
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            font-size: 24px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px 25px;
            background: linear-gradient(135deg, #6f42c1, #5a3399);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.2);
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(111, 66, 193, 0.35);
            background: linear-gradient(135deg, #5a3399, #4a2a7f);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table thead {
            background: #6f42c1;
            color: white;
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 18px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #6f42c1;
        }

        .activity-item:last-child {
            margin-bottom: 0;
        }

        .activity-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .activity-details {
            font-size: 13px;
            color: #6c757d;
        }

        .announcement-item {
            padding: 18px;
            background: #f3e5f5;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #6f42c1;
        }

        .announcement-title {
            font-weight: 600;
            color: #6f42c1;
            margin-bottom: 5px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #f5c6cb;
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

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
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

            .top-bar {
                flex-direction: column;
                gap: 20px;
                padding: 25px;
            }

            .user-info {
                width: 100%;
                justify-content: space-between;
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
        <h2>Principal</h2>
        <p>Admin Portal</p>
    </div>
    <div class="sidebar-menu">
        <a href="principle_dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a>
        <a href="manage_classes.php"><i class="fas fa-book"></i> <span>All Classes</span></a>
        <a href="principle_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        <a href="announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>
        <a href="principle_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="top-bar">
        <div class="welcome-text">
            <h1>Welcome, Principal <span><?= htmlspecialchars($_SESSION['name']) ?></span>!</h1>
            <p><i class="fas fa-calendar-alt"></i> <?= date('l, F j, Y') ?></p>
        </div>
        <div class="user-info">
            <div class="user-details">
                <h3><?= htmlspecialchars($_SESSION['name']) ?></h3>
                <p>Principal Account</p>
            </div>
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card">
            <h3><i class="fas fa-chalkboard-teacher"></i> Total Teachers</h3>
            <div class="number"><?= $totalTeachers ?? 0 ?></div>
            <p>Active teaching staff</p>
            <span class="stat-badge">Currently active</span>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-user-graduate"></i> Total Students</h3>
            <div class="number"><?= $totalStudents ?? 0 ?></div>
            <p>Currently enrolled</p>
            <span class="stat-badge">Active students</span>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-book"></i> Active Classes</h3>
            <div class="number"><?= $totalClasses ?? 0 ?></div>
            <p>This semester</p>
            <span class="stat-badge"><?= $totalEnrollments ?? 0 ?> enrollments</span>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-chart-line"></i> Avg Performance</h3>
            <div class="number"><?= $avgPerformance ?>%</div>
            <p>Overall school average</p>
            <span class="stat-badge">
                <?php echo $avgPerformance >= 75 ? 'Excellent' : ($avgPerformance >= 60 ? 'Good' : 'Improving'); ?>
            </span>
        </div>

        <div class="stat-card">
            <h3><i class="fas fa-calendar-check"></i> Attendance Rate</h3>
            <div class="number"><?= $overallAttendance ?>%</div>
            <p>This month average</p>
            <span class="stat-badge">
                <?php echo $overallAttendance >= 90 ? 'Excellent' : 'Monitor'; ?>
            </span>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="quick-actions">
            <a href="manage_teachers.php" class="action-btn">
                <i class="fas fa-user-plus"></i> Add Teacher
            </a>
            <a href="manage_students.php" class="action-btn">
                <i class="fas fa-user-plus"></i> Add Student
            </a>
            <a href="manage_classes.php" class="action-btn">
                <i class="fas fa-book"></i> View Classes
            </a>
            <a href="principle_reports.php" class="action-btn">
                <i class="fas fa-chart-line"></i> View Reports
            </a>
            <a href="announcements.php" class="action-btn">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
        </div>
    </div>

    <div class="content-grid">
        <div>
            <div class="card">
                <h2><i class="fas fa-trophy"></i> Top Performing Classes</h2>
                <?php if ($topClasses && mysqli_num_rows($topClasses) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                                <th>Students</th>
                                <th>Avg Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($class = mysqli_fetch_assoc($topClasses)): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($class['class_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($class['subject']) ?></td>
                                    <td><?= htmlspecialchars($class['teacher_name']) ?></td>
                                    <td><?= $class['student_count'] ?></td>
                                    <td><strong><?= round($class['avg_marks'], 1) ?>%</strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <p>No performance data available yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><i class="fas fa-users-cog"></i> Teacher Workload Overview</h2>
                <?php if ($teacherWorkload && mysqli_num_rows($teacherWorkload) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Teacher Name</th>
                                <th>Classes Teaching</th>
                                <th>Total Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($teacher = mysqli_fetch_assoc($teacherWorkload)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                                    <td><?= $teacher['class_count'] ?></td>
                                    <td><?= $teacher['student_count'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No teacher data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <?php if ($recentActivity && mysqli_num_rows($recentActivity) > 0): ?>
                    <ul class="activity-list">
                        <?php while ($activity = mysqli_fetch_assoc($recentActivity)): ?>
                            <li class="activity-item">
                                <div class="activity-title">
                                    New Class: <?= htmlspecialchars($activity['title']) ?>
                                </div>
                                <div class="activity-details">
                                    Teacher: <?= htmlspecialchars($activity['teacher_name']) ?> • 
                                    <?= date('M j, Y', strtotime($activity['activity_date'])) ?>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2><i class="fas fa-bullhorn"></i> Latest Announcements</h2>
                <?php if ($recentAnnouncements && mysqli_num_rows($recentAnnouncements) > 0): ?>
                    <?php while ($announcement = mysqli_fetch_assoc($recentAnnouncements)): ?>
                        <div class="announcement-item">
                            <div class="announcement-title">
                                <?= htmlspecialchars($announcement['title']) ?>
                            </div>
                            <div class="activity-details">
                                <?= date('M j, Y', strtotime($announcement['created_at'])) ?> • 
                                <?= ucfirst($announcement['target_audience']) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p>No announcements yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>