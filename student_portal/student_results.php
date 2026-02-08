<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit;
}

$student_id = $_SESSION['id'];

// Filter by class
$filterClass = isset($_GET['class']) ? mysqli_real_escape_string($conn, $_GET['class']) : '';

// Fetch all classes for filter
$allClasses = mysqli_query($conn,
    "SELECT DISTINCT c.id, c.class_name
     FROM enrollments e
     JOIN classes c ON e.class_id = c.id
     WHERE e.student_id = '$student_id' AND e.status = 'enrolled'
     ORDER BY c.class_name");

// Build results query
$query = "SELECT r.*, a.title, a.type, a.total_marks, a.date, c.class_name, c.subject
          FROM results r
          JOIN assessments a ON r.assessment_id = a.id
          JOIN classes c ON a.class_id = c.id
          WHERE r.student_id = '$student_id'";

if ($filterClass) {
    $query .= " AND c.id = '$filterClass'";
}

$query .= " ORDER BY a.date DESC";

$results = mysqli_query($conn, $query);

// Calculate overall statistics
$statsQuery = "SELECT 
                AVG(marks_obtained) as avg_marks,
                COUNT(*) as total_assessments,
                SUM(CASE WHEN grade IN ('A+', 'A') THEN 1 ELSE 0 END) as a_grades,
                SUM(CASE WHEN grade IN ('B+', 'B') THEN 1 ELSE 0 END) as b_grades
                FROM results
                WHERE student_id = '$student_id'";

$stats = mysqli_fetch_assoc(mysqli_query($conn, $statsQuery));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results - Student Portal</title>
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
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 4px solid #007bff; }
        .stat-number { font-size: 32px; font-weight: 700; color: #007bff; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: #6c757d; text-transform: uppercase; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #333; margin-bottom: 25px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: #007bff; }
        .filter-section { margin-bottom: 25px; }
        .filter-group { margin-bottom: 15px; }
        .filter-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        .filter-group select { width: 100%; max-width: 400px; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table thead { background: #007bff; color: white; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        table tbody tr:hover { background: #f8f9fa; }
        .grade-badge { padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-block; }
        .grade-A { background: #d4edda; color: #155724; }
        .grade-B { background: #d1ecf1; color: #0c5460; }
        .grade-C { background: #fff3cd; color: #856404; }
        .grade-D { background: #f8d7da; color: #721c24; }
        .grade-F { background: #f8d7da; color: #721c24; }
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
        <a href="student_results.php" class="active"><i class="fas fa-chart-bar"></i> <span>My Results</span></a>
        <a href="student_attendance.php"><i class="fas fa-calendar-check"></i> <span>My Attendance</span></a>
        <a href="student_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-trophy"></i> My Results</h1>
        <p>View your grades and academic performance</p>
    </div>

    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-number"><?php echo round($stats['avg_marks'] ?? 0, 1); ?>%</div>
            <div class="stat-label">Overall Average</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['total_assessments'] ?? 0; ?></div>
            <div class="stat-label">Total Assessments</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['a_grades'] ?? 0; ?></div>
            <div class="stat-label">A Grades</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $stats['b_grades'] ?? 0; ?></div>
            <div class="stat-label">B Grades</div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-filter"></i> Filter Results</h2>
        <form method="GET" action="" class="filter-section">
            <div class="filter-group">
                <label>Filter by Class</label>
                <select name="class" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    <?php while ($class = mysqli_fetch_assoc($allClasses)): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $filterClass == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list"></i> Grade History</h2>
        <?php if ($results && mysqli_num_rows($results) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Class</th>
                        <th>Assessment</th>
                        <th>Type</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($result = mysqli_fetch_assoc($results)): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($result['date'])); ?></td>
                            <td><?php echo htmlspecialchars($result['class_name']); ?></td>
                            <td><strong><?php echo htmlspecialchars($result['title']); ?></strong></td>
                            <td><?php echo ucfirst($result['type']); ?></td>
                            <td><?php echo $result['marks_obtained']; ?> / <?php echo $result['total_marks']; ?></td>
                            <td><?php echo round(($result['marks_obtained'] / $result['total_marks']) * 100, 1); ?>%</td>
                            <td><span class="grade-badge grade-<?php echo substr($result['grade'], 0, 1); ?>"><?php echo $result['grade']; ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard"></i>
                <h3>No results yet</h3>
                <p>Your grades will appear here once your teachers upload them</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>