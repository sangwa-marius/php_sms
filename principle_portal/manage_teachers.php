<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: ../index.php");
    exit;
}

// Handle add teacher
if (isset($_POST['add_teacher'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $sql = "INSERT INTO users (name, email, password, role, gender, phone, status) VALUES ('$name', '$email', '$password', 'teacher', '$gender', '$phone', 'active')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Teacher added successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle delete teacher
if (isset($_POST['delete_teacher'])) {
    $teacherId = mysqli_real_escape_string($conn, $_POST['teacher_id']);
    if (mysqli_query($conn, "DELETE FROM users WHERE id = '$teacherId' AND role = 'teacher'")) {
        $message = "Teacher deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting teacher.";
        $messageType = "error";
    }
}

// Fetch teachers
$teachers = mysqli_query($conn, "SELECT u.*, COUNT(DISTINCT c.id) as class_count FROM users u LEFT JOIN classes c ON u.id = c.teacher_id WHERE u.role = 'teacher' GROUP BY u.id ORDER BY u.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Teachers - Principal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; }
        .sidebar { width: 260px; height: 100vh; background: linear-gradient(180deg, #6f42c1 0%, #5a3399 100%); color: white; position: fixed; padding: 0; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 1000; overflow-y: auto; }
        .sidebar-header { padding: 30px 20px; background: rgba(0,0,0,0.1); border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h2 { font-size: 24px; font-weight: 600; margin-bottom: 5px; }
        .sidebar-menu { padding: 20px 0; }
        .sidebar a { display: flex; align-items: center; color: white; text-decoration: none; padding: 14px 25px; margin: 5px 15px; border-radius: 8px; transition: all 0.3s; font-size: 15px; }
        .sidebar a i { margin-right: 12px; font-size: 18px; width: 20px; }
        .sidebar a:hover { background: rgba(255,255,255,0.15); transform: translateX(5px); }
        .sidebar a.active { background: rgba(255,255,255,0.2); }
        .main { margin-left: 260px; padding: 30px 40px; }
        .header { background: linear-gradient(135deg, #6f42c1 0%, #5a3399 100%); padding: 35px 40px; border-radius: 15px; color: white; margin-bottom: 30px; }
        .header h1 { font-size: 32px; margin-bottom: 8px; }
        .message { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #6f42c1; margin-bottom: 25px; font-size: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #6f42c1; color: white; }
        .btn-primary:hover { background: #5a3399; }
        .btn-danger { background: #dc3545; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table thead { background: #6f42c1; color: white; }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        table tbody tr:hover { background: #f8f9fa; }
        .stat-box { background: linear-gradient(135deg, #6f42c1, #5a3399); color: white; padding: 25px; border-radius: 12px; text-align: center; margin-bottom: 20px; }
        .stat-number { font-size: 48px; font-weight: 700; margin-bottom: 10px; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header h2, .sidebar a span { display: none; }
            .sidebar a { justify-content: center; }
            .sidebar a i { margin-right: 0; }
            .main { margin-left: 70px; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header"><h2>Principal</h2><p>Admin Portal</p></div>
    <div class="sidebar-menu">
        <a href="principle_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="manage_teachers.php" class="active"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
        <a href="manage_students.php"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a>
        <a href="manage_classes.php"><i class="fas fa-book"></i> <span>All Classes</span></a>
        <a href="principle_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        <a href="announcements.php"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>
        <a href="principle_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Manage Teachers</h1>
        <p>Add, view, and manage all teachers</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="stat-box">
        <div class="stat-number"><?php echo mysqli_num_rows($teachers); ?></div>
        <div>Total Teachers in System</div>
    </div>

    <div class="card">
        <h2><i class="fas fa-user-plus"></i> Add New Teacher</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
            </div>
            <button type="submit" name="add_teacher" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Teacher
            </button>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list"></i> All Teachers (<?php echo mysqli_num_rows($teachers); ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Classes</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($teacher = mysqli_fetch_assoc($teachers)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($teacher['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['gender'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone'] ?? '-'); ?></td>
                        <td><?php echo $teacher['class_count']; ?> classes</td>
                        <td><?php echo ucfirst($teacher['status']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this teacher?');">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <button type="submit" name="delete_teacher" class="btn-danger" style="padding: 8px 15px;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>