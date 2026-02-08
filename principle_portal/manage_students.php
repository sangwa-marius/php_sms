<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: index.php");
    exit;
}

// Handle adding new student
if (isset($_POST['add_student'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $sql = "INSERT INTO users (name, email, password, role, gender, phone, status) 
            VALUES ('$name', '$email', '$password', 'student', '$gender', '$phone', 'active')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Student added successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle delete student
if (isset($_POST['delete_student'])) {
    $studentId = mysqli_real_escape_string($conn, $_POST['student_id']);
    
    $sql = "DELETE FROM users WHERE id = '$studentId' AND role = 'student'";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Student deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting student: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Fetch all students
$students = mysqli_query($conn, "SELECT * FROM users WHERE role = 'student' ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Principal</title>
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
            background: #6f42c1;
            color: white;
            position: fixed;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            margin: 8px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255,255,255,0.2);
        }

        .main {
            margin-left: 260px;
            padding: 30px 40px;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #6f42c1 0%, #5a3399 100%);
            padding: 35px 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.2);
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6f42c1;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #6f42c1;
            color: white;
        }

        .btn-primary:hover {
            background: #5a3399;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background: #6f42c1;
            color: white;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .stat-box {
            background: linear-gradient(135deg, #6f42c1, #5a3399);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
        }

        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 16px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar h2,
            .sidebar a span {
                display: none;
            }
            
            .main {
                margin-left: 70px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Principal</h2>
    <a href="principle_dashboard.php"><i class="fas fa-home"></i> <span>Dashboard</span></a>
    <a href="../profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
    <a href="manage_students.php" class="active"><i class="fas fa-user-graduate"></i> <span>Manage Students</span></a>
    <a href="manage_teachers.php"><i class="fas fa-chalkboard-teacher"></i> <span>Manage Teachers</span></a>
    <a href="principle_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    <a href="../settings.php"><i class="fas fa-settings"></i> <span>settings</span></a>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-user-graduate"></i> Manage Students</h1>
        <p>Add, view, and manage all students in the system</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stat-box">
        <div class="stat-number"><?php echo mysqli_num_rows($students); ?></div>
        <div class="stat-label">Total Students in System</div>
    </div>

    <!-- Add Student Form -->
    <div class="card">
        <h2><i class="fas fa-user-plus"></i> Add New Student</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g., John Smith">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required placeholder="student@school.com">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Minimum 6 characters">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g., +250 123 456 789">
                </div>
            </div>
            <button type="submit" name="add_student" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Student
            </button>
        </form>
    </div>

    <!-- Students List -->
    <div class="card">
        <h2><i class="fas fa-list"></i> All Students (<?php echo mysqli_num_rows($students); ?>)</h2>
        <?php if (mysqli_num_rows($students) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td><?php echo $student['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['gender'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($student['phone'] ?? '-'); ?></td>
                            <td>
                                <span style="background: <?php echo $student['status'] === 'active' ? '#d4edda' : '#f8d7da'; ?>; 
                                             color: <?php echo $student['status'] === 'active' ? '#155724' : '#721c24'; ?>; 
                                             padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: 600;">
                                    <?php echo strtoupper($student['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this student? This will also remove all their enrollments and data.');">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="delete_student" class="btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">
                No students in the system yet. Add your first student above.
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>