<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$student_id = $_SESSION['id'];

// Filter by class and month
$filterClass = isset($_GET['class']) ? mysqli_real_escape_string($conn, $_GET['class']) : '';
$filterMonth = isset($_GET['month']) ? mysqli_real_escape_string($conn, $_GET['month']) : date('Y-m');

// Fetch classes for filter
$classes = mysqli_query($conn,
    "SELECT DISTINCT c.id, c.class_name
     FROM enrollments e
     JOIN classes c ON e.class_id = c.id
     WHERE e.student_id = '$student_id' AND e.status = 'enrolled'
     ORDER BY c.class_name");

// Fetch attendance records
$attendanceQuery = "SELECT a.*, c.class_name, c.subject
                    FROM attendance a
                    JOIN classes c ON a.class_id = c.id
                    WHERE a.student_id = '$student_id'";

if ($filterClass) {
    $attendanceQuery .= " AND c.id = '$filterClass'";
}

if ($filterMonth) {
    $attendanceQuery .= " AND DATE_FORMAT(a.date, '%Y-%m') = '$filterMonth'";
}

$attendanceQuery .= " ORDER BY a.date DESC";
$attendance = mysqli_query($conn, $attendanceQuery);

// Calculate statistics
$statsQuery = "SELECT 
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
                COUNT(CASE WHEN status = 'excused' THEN 1 END) as excused,
                COUNT(*) as total
                FROM attendance
                WHERE student_id = '$student_id'";

if ($filterMonth) {
    $statsQuery .= " AND DATE_FORMAT(date, '%Y-%m') = '$filterMonth'";
}

$stats = mysqli_fetch_assoc(mysqli_query($conn, $statsQuery));
$attendanceRate = $stats['total'] > 0 ? round(($stats['present'] / $stats['total']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; color: #333; }
        .sidebar { width: 260px; height: 100vh; background: linear-gradient(180deg, #007bff 0%, #0056b3 100%); color: white; position: fixed; padding: 0; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; overflow-y: auto; }
        .sidebar-header { padding: 30px 20px; background: rgba(0,0,0,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { text-align: center; font-size: 24px; font-weight: 600; margin-bottom: 5px; }
        .sidebar-header p { text-align: center; font-size: 13px; opacity: 0.9; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar a { display: flex; align-items: center; color: white; text-decoration: none; padding: 14px 25px; margin: 5px 15px; border-radius: 8px; transition: all 0.3s ease; font-size: 15px; }
        .sidebar a i { margin-right: 12px; font-size: 18px; width: 20px; }
        .sidebar a:hover { background: rgba(255,255,255,0.15); transform: translateX(5px); }
        .sidebar a.active { background: rgba(255,255,255,0.2); }
        .main { margin-left: 260px; padding: 30px 40px; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); padding: 35px 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0, 123, 255, 0.2); margin-bottom: 30px; color: white; }
        .header h1 { font-size: 32px; font-weight: 600; margin-bottom: 8px; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); text-align: center; }
        .stat-number { font-size: 36px; font-weight: 700; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .stat-present { color: #28a745; }
        .stat-absent { color: #dc3545; }
        .stat-late { color: #ffc107; }
        .stat-rate { color: #007bff; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #333; margin-bottom: 25px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: #007bff; }
        .filter-section { display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        .filter-group select, .filter-group input { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table thead { background: #007bff; color: white; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        table tbody tr:hover { background: #f8f9fa; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-excused { background: #d1ecf1; color: #0c5460; }
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
        <h2>StudentHub</h2>
        <p>Learning Portal</p>
    </div>
    <div class="sidebar-menu">
        <a href="student_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="student_classes.php"><i class="fas fa-book"></i> <span>My Classes</span></a>
        <a href="student_results.php"><i class="fas fa-chart-bar"></i> <span>My Results</span></a>
        <a href="student_attendance.php" class="active"><i class="fas fa-calendar-check"></i> <span>My Attendance</span></a>
        <a href="student_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-calendar-check"></i> My Attendance</h1>
        <p>View your attendance records and statistics</p>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-number stat-rate"><?php echo $attendanceRate; ?>%</div>
            <div class="stat-label">Attendance Rate</div>
        </div>
        <div class="stat-box">
            <div class="stat-number stat-present"><?php echo $stats['present'] ?? 0; ?></div>
            <div class="stat-label">Present</div>
        </div>
        <div class="stat-box">
            <div class="stat-number stat-absent"><?php echo $stats['absent'] ?? 0; ?></div>
            <div class="stat-label">Absent</div>
        </div>
        <div class="stat-box">
            <div class="stat-number stat-late"><?php echo $stats['late'] ?? 0; ?></div>
            <div class="stat-label">Late</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['excused'] ?? 0; ?></div>
            <div class="stat-label">Excused</div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-filter"></i> Filter Attendance</h2>
        <form method="GET" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>Filter by Class</label>
                    <select name="class" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php 
                        mysqli_data_seek($classes, 0);
                        while ($class = mysqli_fetch_assoc($classes)): 
                        ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $filterClass == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Filter by Month</label>
                    <input type="month" name="month" value="<?php echo $filterMonth; ?>" onchange="this.form.submit()">
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list"></i> Attendance Records</h2>
        <?php if ($attendance && mysqli_num_rows($attendance) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Class</th>
                        <th>Subject</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($record = mysqli_fetch_assoc($attendance)): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                            <td><?php echo date('l', strtotime($record['date'])); ?></td>
                            <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['subject']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $record['status']; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No attendance records</h3>
                <p>Your attendance will be tracked here once classes begin</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>