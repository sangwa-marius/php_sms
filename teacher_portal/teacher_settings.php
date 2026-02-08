<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    
    $sql = "UPDATE users SET 
            name = '$name', 
            email = '$email', 
            phone = '$phone',
            address = '$address',
            gender = '$gender',
            date_of_birth = ".($dob ? "'$dob'" : "NULL")."
            WHERE id = '$user_id'";
    
    if (mysqli_query($conn, $sql)) {
        $_SESSION['name'] = $name;
        $message = "Profile updated successfully!";
        $messageType = "success";
        
        // Refresh user data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $message = "Error updating profile: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            if (strlen($newPassword) >= 6) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = '$hashedPassword' WHERE id = '$user_id'";
                
                if (mysqli_query($conn, $sql)) {
                    $message = "Password changed successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error changing password.";
                    $messageType = "error";
                }
            } else {
                $message = "Password must be at least 6 characters long.";
                $messageType = "error";
            }
        } else {
            $message = "New passwords do not match.";
            $messageType = "error";
        }
    } else {
        $message = "Current password is incorrect.";
        $messageType = "error";
    }
}

// Handle profile picture upload
if (isset($_POST['upload_picture']) && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    if ($file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($fileExt, $allowed)) {
            if ($file['size'] <= 5000000) { // 5MB max
                $newFilename = uniqid() . '.' . $fileExt;
                $destination = 'uploads/profiles/' . $newFilename;
                
                if (move_uploaded_file($fileTmp, $destination)) {
                    // Delete old picture if not default
                    if ($user['profile_image'] !== 'default.png' && file_exists('uploads/profiles/' . $user['profile_image'])) {
                        unlink('uploads/profiles/' . $user['profile_image']);
                    }
                    
                    $sql = "UPDATE users SET profile_image = '$newFilename' WHERE id = '$user_id'";
                    if (mysqli_query($conn, $sql)) {
                        $message = "Profile picture updated successfully!";
                        $messageType = "success";
                        
                        // Refresh user data
                        $stmt->execute();
                        $user = $stmt->get_result()->fetch_assoc();
                    }
                } else {
                    $message = "Failed to upload file.";
                    $messageType = "error";
                }
            } else {
                $message = "File size must be less than 5MB.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TeacherHub</title>
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

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .settings-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        .settings-nav {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            height: fit-content;
        }

        .settings-nav h3 {
            font-size: 16px;
            color: #0c5a55;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2f5;
        }

        .settings-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .settings-nav a:hover,
        .settings-nav a.active {
            background: #e8f5f3;
            color: #0c5a55;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0c5a55;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
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
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .profile-picture-section {
            display: flex;
            align-items: center;
            gap: 30px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        .current-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .current-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .picture-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0c5a55, #4ecca3);
            color: white;
            font-size: 64px;
            font-weight: 700;
        }

        .picture-upload {
            flex: 1;
        }

        .info-box {
            background: #e8f5f3;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #0c5a55;
            margin-bottom: 20px;
        }

        .info-box strong {
            color: #0c5a55;
        }

        @media (max-width: 1024px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .settings-nav {
                display: none;
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

            .profile-picture-section {
                flex-direction: column;
                text-align: center;
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
        <a href="teacher_students.php"><i class="fas fa-users"></i> <span>Students</span></a>
        <a href="uploadResults.php"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="teacher_attendance.php"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="teacher_settings.php" class="active"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-cog"></i> Account Settings</h1>
        <p>Manage your profile and preferences</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <div class="settings-nav">
            <h3>Settings Menu</h3>
            <a href="#profile" class="active">
                <i class="fas fa-user"></i> Profile Information
            </a>
            <a href="#picture">
                <i class="fas fa-camera"></i> Profile Picture
            </a>
            <a href="#security">
                <i class="fas fa-lock"></i> Password & Security
            </a>
        </div>

        <div class="settings-content">
            <!-- Profile Information -->
            <div class="card" id="profile">
                <h2><i class="fas fa-user-edit"></i> Profile Information</h2>
                <div class="info-box">
                    <strong><i class="fas fa-info-circle"></i> Current Information</strong><br>
                    Update your personal details below. These details will be visible on your profile.
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+250 123 456 789">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <!-- Profile Picture -->
            <div class="card" id="picture">
                <h2><i class="fas fa-camera"></i> Profile Picture</h2>
                <div class="profile-picture-section">
                    <div class="current-picture">
                        <?php if ($user['profile_image'] && $user['profile_image'] !== 'default.png' && file_exists("uploads/profiles/" . $user['profile_image'])): ?>
                            <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                        <?php else: ?>
                            <div class="picture-avatar">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="picture-upload">
                        <h3>Change Profile Picture</h3>
                        <p style="color: #6c757d; margin: 10px 0;">Upload a new photo to update your profile picture.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Choose Image (Max 5MB)</label>
                                <input type="file" name="profile_picture" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload_picture" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload Picture
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Password & Security -->
            <div class="card" id="security">
                <h2><i class="fas fa-lock"></i> Change Password</h2>
                <div class="info-box">
                    <strong><i class="fas fa-shield-alt"></i> Security Tip</strong><br>
                    Choose a strong password with at least 6 characters, including letters and numbers.
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" required placeholder="Enter your current password">
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" required placeholder="Minimum 6 characters">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" required placeholder="Re-enter new password">
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>

            <!-- Account Information -->
            <div class="card">
                <h2><i class="fas fa-info-circle"></i> Account Information</h2>
                <div class="form-grid">
                    <div class="info-box">
                        <strong>Account Type:</strong> <?php echo ucfirst($user['role']); ?>
                    </div>
                    <div class="info-box">
                        <strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                    </div>
                    <div class="info-box">
                        <strong>Account Status:</strong> <?php echo ucfirst($user['status']); ?>
                    </div>
                    <div class="info-box">
                        <strong>User ID:</strong> #<?php echo $user['id']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Smooth scroll to sections
    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Update active state
            document.querySelectorAll('.settings-nav a').forEach(a => a.classList.remove('active'));
            this.classList.add('active');
        });
    });
</script>

</body>
</html>