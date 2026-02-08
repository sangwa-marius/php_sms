<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: ../index.php");
    exit;
}

// System-wide statistics
$totalTeachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'teacher' AND status = 'active'"))['total'];
$totalStudents = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'student' AND status = 'active'"))['total'];
$totalClasses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM classes WHERE status = 'active'"))['total'];
$totalEnrollments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM enrollments WHERE status = 'enrolled'"))['total'];

// Overall average performance
$overallAvg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(marks_obtained) as avg FROM results"))['avg'];
$overallAvg = $overallAvg ? round($overallAvg, 1) : 0;

// Attendance statistics
$attendanceStats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
        COUNT(*) as total
     FROM attendance
     WHERE MONTH(date) = MONTH(CURDATE())"));
$attendanceRate = $attendanceStats['total'] > 0 ? round(($attendanceStats['present'] / $attendanceStats['total']) * 100, 1) : 0;

// Top performing students
$topStudents = mysqli_query($conn,
    "SELECT u.name, u.email, AVG(r.marks_obtained) as avg_marks, COUNT(r.id) as total_assessments
     FROM users u
     JOIN results r ON u.id = r.student_id
     WHERE u.role = 'student'
     GROUP BY u.id
     HAVING AVG(r.marks_obtained) >= 80
     ORDER BY avg_marks DESC
     LIMIT 10");

// Class performance breakdown
$classPerformance = mysqli_query($conn,
    "SELECT c.class_name, c.subject, u.name as teacher_name,
            COUNT(DISTINCT e.student_id) as students,
            AVG(r.marks_obtained) as avg_marks,
            ROUND((COUNT(CASE WHEN a.status = 'present' THEN 1 END) / NULLIF(COUNT(a.id), 0)) * 100, 1) as attendance_rate
     FROM classes c
     JOIN users u ON c.teacher_id = u.id
     LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
     LEFT JOIN results r ON r.student_id = e.student_id
     LEFT JOIN attendance a ON a.class_id = c.id AND a.student_id = e.student_id AND MONTH(a.date) = MONTH(CURDATE())
     WHERE c.status = 'active'
     GROUP BY c.id
     ORDER BY avg_marks DESC");

// Grade distribution
$gradeDistribution = mysqli_query($conn,
    "SELECT grade, COUNT(*) as count
     FROM results
     GROUP BY grade
     ORDER BY FIELD(grade, 'A+', 'A', 'B+', 'B', 'C', 'D', 'F')");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - Principal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .sidebar { width: 260px; height: 100vh; background: linear-gradient(180deg, #6f42c1 0%, #5a3399 100%); color: white; position: fixed; padding: 0; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; overflow-y: auto; }
        .sidebar-header { padding: 30px 20px; background: rgba(0,0,0,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h2 { font-size: 24px; font-weight: 600; margin-bottom: 5px; }
        .sidebar-header p { font-size: 13px; opacity: 0.9; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar a { display: flex; align-items: center; color: white; text-decoration: none; padding: 14px 25px; margin: 5px 15px; border-radius: 8px; transition: all 0.3s; font-size: 15px; }
        .sidebar a i { margin-right: 12px; font-size: 18px; width: 20px; }
        .sidebar a:hover { background: rgba(255,255,255,0.15); transform: translateX(5px); }
        .sidebar a.active { background: rgba(255,255,255,0.2); }
        .main { margin-left: 260px; padding: 30px 40px; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #6f42c1 0%, #5a3399 100%); padding: 35px 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(111, 66, 193, 0.2); margin-bottom: 30px; color: white; }
        .header h1 { font-size: 32px; font-weight: 600; margin-bottom: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 4px solid #6f42c1; }
        .stat-number { font-size: 36px; font-weight: 700; color: #6f42c1; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #6f42c1; margin-bottom: 25px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table thead { background: #6f42c1; color: white; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        table tbody tr:hover { background: #f8f9fa; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .progress-bar { width: 100%; height: 8px; background: #e0e0e0; border-radius: 4px; overflow: hidden; margin-top: 5px; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #6f42c1, #9775fa); transition: width 0.3s; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h2, .sidebar-header p, .sidebar a span { display: none; }
            .sidebar a { justify-content: center; }
            .sidebar a i { margin-right: 0; }
            .main { margin-left: 70px; padding: 20px; }
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
        <a href="principle_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a>
        <a href="manage_classes.php"><i class="fas fa-book"></i> <span>All Classes</span></a>
        <a href="principle_reports.php" class="active"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        <a href="announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>
        <a href="principle_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-chart-bar"></i> System Reports & Analytics</h1>
        <p>Comprehensive overview of school performance</p>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalTeachers; ?></div>
            <div class="stat-label">Teachers</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalStudents; ?></div>
            <div class="stat-label">Students</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalClasses; ?></div>
            <div class="stat-label">Classes</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalEnrollments; ?></div>
            <div class="stat-label">Enrollments</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $overallAvg; ?>%</div>
            <div class="stat-label">Overall Average</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $attendanceRate; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-chart-pie"></i> Grade Distribution</h2>
        <table>
            <thead>
                <tr>
                    <th>Grade</th>
                    <th>Count</th>
                    <th>Distribution</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalGrades = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM results"))['total'];
                while ($grade = mysqli_fetch_assoc($gradeDistribution)): 
                    $percentage = $totalGrades > 0 ? round(($grade['count'] / $totalGrades) * 100, 1) : 0;
                ?>
                    <tr>
                        <td><strong><?php echo $grade['grade']; ?></strong></td>
                        <td><?php echo $grade['count']; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="min-width: 50px;"><?php echo $percentage; ?>%</span>
                                <div class="progress-bar" style="flex: 1;">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2><i class="fas fa-trophy"></i> Top Performing Students</h2>
        <?php if (mysqli_num_rows($topStudents) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Average Score</th>
                        <th>Assessments Taken</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while ($student = mysqli_fetch_assoc($topStudents)): 
                    ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><strong><?php echo round($student['avg_marks'], 1); ?>%</strong></td>
                            <td><?php echo $student['total_assessments']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">No data available yet</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><i class="fas fa-chart-line"></i> Class Performance Report</h2>
        <?php if (mysqli_num_rows($classPerformance) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Students</th>
                        <th>Avg Performance</th>
                        <th>Attendance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($class = mysqli_fetch_assoc($classPerformance)): 
                        $avg = $class['avg_marks'] ? round($class['avg_marks'], 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($class['subject']); ?></td>
                            <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                            <td><?php echo $class['students']; ?></td>
                            <td><strong><?php echo $avg; ?>%</strong></td>
                            <td><?php echo $class['attendance_rate'] ?? 0; ?>%</td>
                            <td>
                                <?php if ($avg >= 80): ?>
                                    <span class="badge badge-success">Excellent</span>
                                <?php elseif ($avg >= 60): ?>
                                    <span class="badge badge-warning">Good</span>
                                <?php elseif ($avg > 0): ?>
                                    <span class="badge badge-danger">Needs Improvement</span>
                                <?php else: ?>
                                    <span class="badge">No Data</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">No class data available</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>