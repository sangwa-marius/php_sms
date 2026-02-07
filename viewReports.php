<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$teacher_id = $_SESSION['id'];

// Overall statistics
$totalClasses = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM classes WHERE teacher_id = '$teacher_id' AND status = 'active'"))['total'];

$totalStudents = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT e.student_id) as total 
     FROM enrollments e 
     JOIN classes c ON e.class_id = c.id 
     WHERE c.teacher_id = '$teacher_id' AND e.status = 'enrolled'"))['total'];

$totalAssessments = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM assessments a
     JOIN classes c ON a.class_id = c.id
     WHERE c.teacher_id = '$teacher_id'"))['total'];

$avgAttendance = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT 
        ROUND((COUNT(CASE WHEN att.status = 'present' THEN 1 END) / COUNT(*)) * 100, 1) as rate
     FROM attendance att
     JOIN classes c ON att.class_id = c.id
     WHERE c.teacher_id = '$teacher_id'
     AND MONTH(att.date) = MONTH(CURDATE())"))['rate'] ?? 0;

// Class performance report
$classPerformance = mysqli_query($conn,
    "SELECT 
        c.id,
        c.class_name,
        c.subject,
        COUNT(DISTINCT e.student_id) as enrolled_students,
        COUNT(DISTINCT a.id) as total_assessments,
        AVG(r.marks_obtained) as avg_marks,
        ROUND((COUNT(CASE WHEN att.status = 'present' THEN 1 END) / NULLIF(COUNT(att.id), 0)) * 100, 1) as attendance_rate
     FROM classes c
     LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
     LEFT JOIN assessments a ON c.id = a.class_id
     LEFT JOIN results r ON a.id = r.assessment_id
     LEFT JOIN attendance att ON c.id = att.class_id AND MONTH(att.date) = MONTH(CURDATE())
     WHERE c.teacher_id = '$teacher_id' AND c.status = 'active'
     GROUP BY c.id
     ORDER BY c.class_name");

// Top performing students
$topStudents = mysqli_query($conn,
    "SELECT 
        u.id,
        u.name,
        c.class_name,
        AVG(r.marks_obtained) as avg_marks,
        COUNT(r.id) as total_assessments
     FROM users u
     JOIN results r ON u.id = r.student_id
     JOIN assessments a ON r.assessment_id = a.id
     JOIN classes c ON a.class_id = c.id
     WHERE c.teacher_id = '$teacher_id'
     GROUP BY u.id, c.id
     HAVING AVG(r.marks_obtained) >= 80
     ORDER BY avg_marks DESC
     LIMIT 10");

// Recent activity
$recentActivity = mysqli_query($conn,
    "SELECT 
        'assessment' as type,
        a.title,
        c.class_name,
        a.date,
        a.type as assessment_type,
        COUNT(r.id) as submissions
     FROM assessments a
     JOIN classes c ON a.class_id = c.id
     LEFT JOIN results r ON a.id = r.assessment_id
     WHERE c.teacher_id = '$teacher_id'
     GROUP BY a.id
     ORDER BY a.created_at DESC
     LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - TeacherHub</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border-left: 5px solid #0c5a55;
        }

        .stat-card h3 {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .stat-card .number {
            font-size: 40px;
            font-weight: 700;
            color: #0c5a55;
            margin-bottom: 8px;
        }

        .stat-card p {
            color: #95a5a6;
            font-size: 14px;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #0c5a55;
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

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0c5a55, #4ecca3);
            transition: width 0.3s;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            padding: 18px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #0c5a55;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            table {
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
        <a href="profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="teacher_classes.php"><i class="fas fa-chalkboard"></i> <span>My Classes</span></a>
        <a href="teacher_students.php"><i class="fas fa-users"></i> <span>Students</span></a>
        <a href="uploadResults.php"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="teacher_attendance.php"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php" class="active"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
        <p>Comprehensive overview of your teaching performance</p>
    </div>

    <!-- Summary Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><i class="fas fa-chalkboard"></i> Total Classes</h3>
            <div class="number"><?php echo $totalClasses; ?></div>
            <p>Active classes this semester</p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-users"></i> Total Students</h3>
            <div class="number"><?php echo $totalStudents; ?></div>
            <p>Across all your classes</p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-file-alt"></i> Total Assessments</h3>
            <div class="number"><?php echo $totalAssessments; ?></div>
            <p>Tests, quizzes, and exams</p>
        </div>
        <div class="stat-card">
            <h3><i class="fas fa-chart-pie"></i> Avg Attendance</h3>
            <div class="number"><?php echo $avgAttendance; ?>%</div>
            <p>Current month average</p>
        </div>
    </div>

    <!-- Class Performance -->
    <div class="card">
        <h2><i class="fas fa-chart-line"></i> Class Performance Report</h2>
        <?php if (mysqli_num_rows($classPerformance) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Students</th>
                        <th>Assessments</th>
                        <th>Avg Grade</th>
                        <th>Attendance</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($class = mysqli_fetch_assoc($classPerformance)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($class['subject']); ?></td>
                            <td><?php echo $class['enrolled_students']; ?></td>
                            <td><?php echo $class['total_assessments']; ?></td>
                            <td>
                                <?php 
                                $avgMarks = $class['avg_marks'] ? round($class['avg_marks'], 1) : 0;
                                echo $avgMarks . '%';
                                ?>
                            </td>
                            <td><?php echo $class['attendance_rate'] ?? 0; ?>%</td>
                            <td>
                                <?php
                                if ($avgMarks >= 80) {
                                    echo '<span class="badge badge-success">Excellent</span>';
                                } elseif ($avgMarks >= 60) {
                                    echo '<span class="badge badge-warning">Good</span>';
                                } elseif ($avgMarks > 0) {
                                    echo '<span class="badge badge-danger">Needs Improvement</span>';
                                } else {
                                    echo '<span class="badge">No Data</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">No class data available yet.</p>
        <?php endif; ?>
    </div>

    <!-- Top Performers -->
    <div class="card">
        <h2><i class="fas fa-trophy"></i> Top Performing Students</h2>
        <?php if (mysqli_num_rows($topStudents) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Average Grade</th>
                        <th>Assessments Taken</th>
                        <th>Performance</th>
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
                            <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                            <td><strong><?php echo round($student['avg_marks'], 1); ?>%</strong></td>
                            <td><?php echo $student['total_assessments']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo round($student['avg_marks']); ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">No student performance data available yet.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <h2><i class="fas fa-history"></i> Recent Assessment Activity</h2>
        <?php if (mysqli_num_rows($recentActivity) > 0): ?>
            <ul class="activity-list">
                <?php while ($activity = mysqli_fetch_assoc($recentActivity)): ?>
                    <li class="activity-item">
                        <div class="activity-title">
                            <?php echo htmlspecialchars($activity['class_name']); ?> - 
                            <?php echo htmlspecialchars($activity['title']); ?>
                            <span class="badge"><?php echo strtoupper($activity['assessment_type']); ?></span>
                        </div>
                        <div class="activity-details">
                            <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($activity['date'])); ?> • 
                            <i class="fas fa-user-check"></i> <?php echo $activity['submissions']; ?> submission(s)
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">No recent activity to display.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>