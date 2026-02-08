<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$teacher_id = $_SESSION['id'];

// Get filter parameters
$filterClass = isset($_GET['class']) ? mysqli_real_escape_string($conn, $_GET['class']) : '';
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Fetch teacher's classes for filter dropdown
$classes = mysqli_query($conn, "SELECT * FROM classes WHERE teacher_id = '$teacher_id' AND status = 'active' ORDER BY class_name");

// Build query for students
$query = "SELECT DISTINCT u.id, u.name, u.email, u.phone, u.profile_image,
          GROUP_CONCAT(DISTINCT c.class_name SEPARATOR ', ') as classes,
          COUNT(DISTINCT e.class_id) as total_classes
          FROM users u
          JOIN enrollments e ON u.id = e.student_id
          JOIN classes c ON e.class_id = c.id
          WHERE u.role = 'student' 
          AND u.status = 'active'
          AND c.teacher_id = '$teacher_id'
          AND e.status = 'enrolled'";

if ($filterClass) {
    $query .= " AND c.id = '$filterClass'";
}

if ($searchTerm) {
    $query .= " AND (u.name LIKE '%$searchTerm%' OR u.email LIKE '%$searchTerm%')";
}

$query .= " GROUP BY u.id ORDER BY u.name";

$students = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - TeacherHub</title>
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

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 30px;
        }

        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
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
        }

        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .student-card {
            background: white;
            border: 2px solid #f0f2f5;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
            display: flex;
            gap: 15px;
        }

        .student-card:hover {
            border-color: #0c5a55;
            box-shadow: 0 4px 15px rgba(12, 90, 85, 0.1);
            transform: translateY(-2px);
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0c5a55, #4ecca3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .student-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .student-email {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .student-classes {
            font-size: 13px;
            color: #0c5a55;
            background: #e8f5f3;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 5px;
        }

        .stats-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            flex: 1;
            background: linear-gradient(135deg, #0c5a55, #0a4a46);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            opacity: 0.9;
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

            .students-grid {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                flex-direction: column;
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
        <a href="teacher_students.php" class="active"><i class="fas fa-users"></i> <span>Students</span></a>
        <a href="uploadResults.php"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="teacher_attendance.php"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-user-graduate"></i> My Students</h1>
        <p>View and manage all students enrolled in your classes</p>
    </div>

    <!-- Statistics -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-number"><?php echo mysqli_num_rows($students); ?></div>
            <div class="stat-label">Total Students</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo mysqli_num_rows($classes); ?></div>
            <div class="stat-label">Active Classes</div>
        </div>
        <div class="stat-item">
            <div class="stat-number">
                <?php 
                $totalEnrollments = mysqli_fetch_assoc(mysqli_query($conn, 
                    "SELECT COUNT(*) as total FROM enrollments e 
                     JOIN classes c ON e.class_id = c.id 
                     WHERE c.teacher_id = '$teacher_id' AND e.status = 'enrolled'"))['total'];
                echo $totalEnrollments;
                ?>
            </div>
            <div class="stat-label">Total Enrollments</div>
        </div>
    </div>

    <div class="card">
        <!-- Filters -->
        <form method="GET" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search Student</label>
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Filter by Class</label>
                    <select name="class">
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
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>

        <!-- Students Grid -->
        <?php if (mysqli_num_rows($students) > 0): ?>
            <div class="students-grid">
                <?php while ($student = mysqli_fetch_assoc($students)): ?>
                    <div class="student-card">
                        <div class="student-avatar">
                            <?php if ($student['profile_image'] && $student['profile_image'] !== 'default.png'): ?>
                                <img src="uploads/profiles/<?php echo htmlspecialchars($student['profile_image']); ?>" alt="Profile">
                            <?php else: ?>
                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                            <?php endif; ?>
                        </div>
                        <div class="student-info">
                            <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                            <div class="student-email">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                            </div>
                            <?php if ($student['phone']): ?>
                                <div class="student-email">
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="student-classes">
                                <i class="fas fa-book"></i> <?php echo $student['total_classes']; ?> class(es): 
                                <?php echo htmlspecialchars($student['classes']); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-graduate"></i>
                <h3>No students found</h3>
                <p>
                    <?php if ($searchTerm || $filterClass): ?>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        Enroll students in your classes to see them here.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>