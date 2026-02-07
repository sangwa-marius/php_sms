<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_SESSION['id'];
$role = $_SESSION['role'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get role-specific statistics
$stats = [];

if ($role === 'teacher') {
    // Teacher statistics
    $classesQuery = mysqli_query($conn, 
        "SELECT COUNT(*) as total FROM classes WHERE teacher_id = '$id' AND status = 'active'");
    $stats['classes'] = mysqli_fetch_assoc($classesQuery)['total'];
    
    $studentsQuery = mysqli_query($conn,
        "SELECT COUNT(DISTINCT e.student_id) as total 
         FROM enrollments e 
         JOIN classes c ON e.class_id = c.id 
         WHERE c.teacher_id = '$id' AND e.status = 'enrolled'");
    $stats['students'] = mysqli_fetch_assoc($studentsQuery)['total'];
    
    $assessmentsQuery = mysqli_query($conn,
        "SELECT COUNT(*) as total FROM assessments a
         JOIN classes c ON a.class_id = c.id
         WHERE c.teacher_id = '$id'");
    $stats['assessments'] = mysqli_fetch_assoc($assessmentsQuery)['total'];
    
} elseif ($role === 'student') {
    // Student statistics
    $classesQuery = mysqli_query($conn,
        "SELECT COUNT(*) as total FROM enrollments 
         WHERE student_id = '$id' AND status = 'enrolled'");
    $stats['enrolled_classes'] = mysqli_fetch_assoc($classesQuery)['total'];
    
    $resultsQuery = mysqli_query($conn,
        "SELECT AVG(r.marks_obtained) as avg_marks
         FROM results r
         WHERE r.student_id = '$id'");
    $avgResult = mysqli_fetch_assoc($resultsQuery);
    $stats['average_grade'] = $avgResult['avg_marks'] ? round($avgResult['avg_marks'], 1) : 0;
    
    $attendanceQuery = mysqli_query($conn,
        "SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
            COUNT(*) as total
         FROM attendance
         WHERE student_id = '$id'");
    $attendanceData = mysqli_fetch_assoc($attendanceQuery);
    $stats['attendance_rate'] = $attendanceData['total'] > 0 
        ? round(($attendanceData['present'] / $attendanceData['total']) * 100) 
        : 0;
        
} elseif ($role === 'principle') {
    // Principal statistics
    $teachersQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
    $stats['teachers'] = mysqli_fetch_assoc($teachersQuery)['total'];
    
    $studentsQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $stats['students'] = mysqli_fetch_assoc($studentsQuery)['total'];
    
    $classesQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM classes WHERE status = 'active'");
    $stats['active_classes'] = mysqli_fetch_assoc($classesQuery)['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - <?= htmlspecialchars($user['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, 
        <?php 
        if ($role === 'teacher') echo '#0c5a55, #0a4a46';
        elseif ($role === 'student') echo '#007bff, #0056b3';
        else echo '#6f42c1, #5a3399';
        ?>
    );
    min-height: 100vh;
    padding: 40px 20px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    margin-bottom: 20px;
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    transition: all 0.3s;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(-5px);
}

.profile-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.profile-header {
    background: linear-gradient(135deg, 
        <?php 
        if ($role === 'teacher') echo '#0c5a55, #0a4a46';
        elseif ($role === 'student') echo '#007bff, #0056b3';
        else echo '#6f42c1, #5a3399';
        ?>
    );
    padding: 40px 30px;
    text-align: center;
    color: white;
    position: relative;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>');
    opacity: 0.3;
}

.profile-image {
    width: 140px;
    height: 140px;
    margin: 0 auto 20px;
    border-radius: 50%;
    overflow: hidden;
    border: 5px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    z-index: 1;
    background: white;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, 
        <?php 
        if ($role === 'teacher') echo '#4ecca3, #3db88f';
        elseif ($role === 'student') echo '#4dabf7, #339af0';
        else echo '#9775fa, #845ef7';
        ?>
    );
    font-size: 64px;
    font-weight: 700;
    color: white;
}

.profile-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}

.profile-header p {
    font-size: 16px;
    opacity: 0.95;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

.profile-body {
    padding: 40px 30px;
}

.info-section {
    margin-bottom: 35px;
}

.info-section h2 {
    font-size: 20px;
    color: #2c3e50;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f2f5;
}

.info-section h2 i {
    color: <?php 
        if ($role === 'teacher') echo '#0c5a55';
        elseif ($role === 'student') echo '#007bff';
        else echo '#6f42c1';
    ?>;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    background: #f8f9fa;
    padding: 18px;
    border-radius: 12px;
    border-left: 4px solid <?php 
        if ($role === 'teacher') echo '#0c5a55';
        elseif ($role === 'student') echo '#007bff';
        else echo '#6f42c1';
    ?>;
}

.info-label {
    font-size: 13px;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.info-value {
    font-size: 16px;
    color: #2c3e50;
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: linear-gradient(135deg, 
        <?php 
        if ($role === 'teacher') echo '#0c5a55, #0a4a46';
        elseif ($role === 'student') echo '#007bff, #0056b3';
        else echo '#6f42c1, #5a3399';
        ?>
    );
    color: white;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.stat-number {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 8px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.actions a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-primary {
    background: <?php 
        if ($role === 'teacher') echo '#0c5a55';
        elseif ($role === 'student') echo '#007bff';
        else echo '#6f42c1';
    ?>;
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: <?php echo $user['status'] === 'active' ? '#d4edda' : '#f8d7da'; ?>;
    color: <?php echo $user['status'] === 'active' ? '#155724' : '#721c24'; ?>;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .profile-body {
        padding: 30px 20px;
    }
    
    .info-grid,
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .actions {
        flex-direction: column;
    }
    
    .actions a {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>

<body>

<div class="container">
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-image">
                <?php if ($user['profile_image'] && $user['profile_image'] !== 'default.png' && file_exists("uploads/profiles/" . $user['profile_image'])): ?>
                    <img src="uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <h1><?= htmlspecialchars($user['name']) ?></h1>
            <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>

            <div class="role-badge">
                <i class="fas fa-<?php 
                    if ($role === 'teacher') echo 'chalkboard-teacher';
                    elseif ($role === 'student') echo 'user-graduate';
                    else echo 'user-shield';
                ?>"></i>
                <?= ucfirst(htmlspecialchars($user['role'])) ?>
            </div>
        </div>

        <div class="profile-body">
            <?php if (!empty($stats)): ?>
                <!-- Statistics Section -->
                <div class="info-section">
                    <h2><i class="fas fa-chart-bar"></i> Statistics</h2>
                    <div class="stats-grid">
                        <?php if ($role === 'teacher'): ?>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['classes'] ?></div>
                                <div class="stat-label">Active Classes</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['students'] ?></div>
                                <div class="stat-label">Total Students</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['assessments'] ?></div>
                                <div class="stat-label">Assessments</div>
                            </div>
                        <?php elseif ($role === 'student'): ?>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['enrolled_classes'] ?></div>
                                <div class="stat-label">Enrolled Classes</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['average_grade'] ?>%</div>
                                <div class="stat-label">Average Grade</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['attendance_rate'] ?>%</div>
                                <div class="stat-label">Attendance Rate</div>
                            </div>
                        <?php elseif ($role === 'principle'): ?>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['teachers'] ?></div>
                                <div class="stat-label">Total Teachers</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['students'] ?></div>
                                <div class="stat-label">Total Students</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number"><?= $stats['active_classes'] ?></div>
                                <div class="stat-label">Active Classes</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Personal Information -->
            <div class="info-section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-id-card"></i> User ID</div>
                        <div class="info-value">#<?= $user['id'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <?php if ($user['phone']): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone"></i> Phone Number</div>
                            <div class="info-value"><?= htmlspecialchars($user['phone']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($user['gender']): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-venus-mars"></i> Gender</div>
                            <div class="info-value"><?= htmlspecialchars($user['gender']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($user['date_of_birth']): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-birthday-cake"></i> Date of Birth</div>
                            <div class="info-value"><?= date('F j, Y', strtotime($user['date_of_birth'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-calendar"></i> Member Since</div>
                        <div class="info-value"><?= date('F j, Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="fas fa-info-circle"></i> Account Status</div>
                        <div class="info-value">
                            <span class="status-badge">
                                <i class="fas fa-<?= $user['status'] === 'active' ? 'check-circle' : 'times-circle' ?>"></i>
                                <?= ucfirst(htmlspecialchars($user['status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($user['address']): ?>
                <div class="info-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Address</h2>
                    <div class="info-item">
                        <div class="info-value"><?= nl2br(htmlspecialchars($user['address'])) ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="actions">
                <a href="dashboard.php" class="btn-primary">
                    <i class="fas fa-home"></i> Back to Dashboard
                </a>
                <a href="edit_profile.php" class="btn-secondary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>