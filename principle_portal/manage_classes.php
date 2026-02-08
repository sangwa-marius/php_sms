<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: ../index.php");
    exit;
}

// Filter by teacher
$filterTeacher = isset($_GET['teacher']) ? mysqli_real_escape_string($conn, $_GET['teacher']) : '';

// Fetch all teachers for filter
$teachers = mysqli_query($conn, "SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name");

// Fetch classes
$query = "SELECT c.*, u.name as teacher_name, 
          COUNT(DISTINCT e.student_id) as student_count,
          AVG(r.marks_obtained) as avg_marks
          FROM classes c
          JOIN users u ON c.teacher_id = u.id
          LEFT JOIN enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
          LEFT JOIN assessments a ON c.id = a.class_id
          LEFT JOIN results r ON a.id = r.assessment_id
          WHERE 1=1";

if ($filterTeacher) {
    $query .= " AND c.teacher_id = '$filterTeacher'";
}

$query .= " GROUP BY c.id ORDER BY c.class_name";
$classes = mysqli_query($conn, $query);

// Calculate statistics
$totalClasses = mysqli_num_rows($classes);
$totalEnrollments = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM enrollments WHERE status = 'enrolled'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Principal</title>
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 4px solid #6f42c1; }
        .stat-number { font-size: 36px; font-weight: 700; color: #6f42c1; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #6f42c1; margin-bottom: 25px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: #6f42c1; }
        .filter-section { margin-bottom: 25px; }
        .filter-group { margin-bottom: 15px; }
        .filter-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .filter-group select { width: 100%; max-width: 400px; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table thead { background: #6f42c1; color: white; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        table tbody tr:hover { background: #f8f9fa; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state i { font-size: 64px; color: #e0e0e0; margin-bottom: 20px; }
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
        <a href="manage_classes.php" class="active"><i class="fas fa-book"></i> <span>All Classes</span></a>
        <a href="principle_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        <a href="announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>
        <a href="principle_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-book"></i> All Classes</h1>
        <p>View and monitor all classes in the system</p>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalClasses; ?></div>
            <div class="stat-label">Total Classes</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalEnrollments; ?></div>
            <div class="stat-label">Total Enrollments</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $totalClasses > 0 ? round($totalEnrollments / $totalClasses, 1) : 0; ?></div>
            <div class="stat-label">Avg Students/Class</div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-filter"></i> Filter Classes</h2>
        <form method="GET" action="" class="filter-section">
            <div class="filter-group">
                <label>Filter by Teacher</label>
                <select name="teacher" onchange="this.form.submit()">
                    <option value="">All Teachers</option>
                    <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                        <option value="<?php echo $teacher['id']; ?>" <?php echo $filterTeacher == $teacher['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($teacher['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list"></i> Classes Overview (<?php echo $totalClasses; ?>)</h2>
        <?php if ($classes && mysqli_num_rows($classes) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Class Name</th>
                        <th>Subject</th>
                        <th>Grade</th>
                        <th>Teacher</th>
                        <th>Students</th>
                        <th>Avg Performance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($classes, 0);
                    while ($class = mysqli_fetch_assoc($classes)): 
                        $avgMarks = $class['avg_marks'] ? round($class['avg_marks'], 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($class['class_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($class['subject']); ?></td>
                            <td><?php echo htmlspecialchars($class['grade']); ?></td>
                            <td><?php echo htmlspecialchars($class['teacher_name']); ?></td>
                            <td><?php echo $class['student_count']; ?> / <?php echo $class['max_students']; ?></td>
                            <td>
                                <?php if ($avgMarks > 0): ?>
                                    <strong><?php echo $avgMarks; ?>%</strong>
                                <?php else: ?>
                                    <span style="color: #999;">No data</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($avgMarks >= 80): ?>
                                    <span class="badge badge-success">Excellent</span>
                                <?php elseif ($avgMarks >= 60): ?>
                                    <span class="badge badge-warning">Good</span>
                                <?php elseif ($avgMarks > 0): ?>
                                    <span class="badge badge-danger">Needs Attention</span>
                                <?php else: ?>
                                    <span class="badge">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No classes found</h3>
                <p>No classes match your filter criteria</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>