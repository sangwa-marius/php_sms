<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: ../index.php");
    exit;
}

// Handle create announcement
if (isset($_POST['create_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $targetAudience = mysqli_real_escape_string($conn, $_POST['target_audience']);
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $createdBy = $_SESSION['id'];
    
    $sql = "INSERT INTO announcements (title, content, target_audience, priority, created_by) 
            VALUES ('$title', '$content', '$targetAudience', '$priority', '$createdBy')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Announcement created successfully!";
        $messageType = "success";
    } else {
        $message = "Error: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle delete announcement
if (isset($_POST['delete_announcement'])) {
    $announcementId = mysqli_real_escape_string($conn, $_POST['announcement_id']);
    if (mysqli_query($conn, "DELETE FROM announcements WHERE id = '$announcementId'")) {
        $message = "Announcement deleted!";
        $messageType = "success";
    }
}

// Fetch announcements
$announcements = mysqli_query($conn,
    "SELECT a.*, u.name as created_by_name
     FROM announcements a
     JOIN users u ON a.created_by = u.id
     ORDER BY a.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Principal</title>
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
        .message { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 30px; }
        .card h2 { color: #6f42c1; margin-bottom: 25px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-family: inherit; }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #6f42c1; color: white; }
        .btn-primary:hover { background: #5a3399; }
        .btn-danger { background: #dc3545; color: white; padding: 8px 15px; font-size: 13px; }
        .announcement-item { padding: 20px; background: #f8f9fa; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #6f42c1; }
        .announcement-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
        .announcement-title { font-size: 20px; font-weight: 700; color: #2c3e50; margin-bottom: 8px; }
        .announcement-meta { display: flex; gap: 15px; font-size: 13px; color: #6c757d; margin-bottom: 15px; }
        .announcement-content { color: #495057; line-height: 1.6; margin-bottom: 15px; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .badge-all { background: #e7f3ff; color: #0066cc; }
        .badge-teachers { background: #e8f5e9; color: #2e7d32; }
        .badge-students { background: #fff3e0; color: #e65100; }
        .priority-high { border-left-color: #dc3545; }
        .priority-medium { border-left-color: #ffc107; }
        .priority-low { border-left-color: #28a745; }
        .priority-badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .priority-high .priority-badge { background: #f8d7da; color: #721c24; }
        .priority-medium .priority-badge { background: #fff3cd; color: #856404; }
        .priority-low .priority-badge { background: #d4edda; color: #155724; }
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
        <a href="principle_reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a>
        <a href="announcements.php" class="active"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>
        <a href="principle_settings.php"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-bullhorn"></i> Announcements</h1>
        <p>Create and manage school-wide announcements</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Create Announcement</h2>
        <form method="POST">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required placeholder="e.g., School Holiday Notice">
            </div>
            <div class="form-group">
                <label>Content *</label>
                <textarea name="content" required placeholder="Write your announcement here..."></textarea>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label>Target Audience *</label>
                    <select name="target_audience" required>
                        <option value="all">Everyone (Teachers & Students)</option>
                        <option value="teachers">Teachers Only</option>
                        <option value="students">Students Only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority *</label>
                    <select name="priority" required>
                        <option value="high">High (Urgent)</option>
                        <option value="medium">Medium (Important)</option>
                        <option value="low">Low (General Info)</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="create_announcement" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Publish Announcement
            </button>
        </form>
    </div>

    <div class="card">
        <h2><i class="fas fa-list"></i> All Announcements (<?php echo mysqli_num_rows($announcements); ?>)</h2>
        <?php if (mysqli_num_rows($announcements) > 0): ?>
            <?php while ($announcement = mysqli_fetch_assoc($announcements)): ?>
                <div class="announcement-item priority-<?php echo $announcement['priority']; ?>">
                    <div class="announcement-header">
                        <div>
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <div class="announcement-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($announcement['created_by_name']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></span>
                                <span class="badge badge-<?php echo $announcement['target_audience']; ?>">
                                    <?php echo ucfirst($announcement['target_audience']); ?>
                                </span>
                                <span class="priority-badge">
                                    <?php echo ucfirst($announcement['priority']); ?> Priority
                                </span>
                            </div>
                        </div>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                            <button type="submit" name="delete_announcement" class="btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    <div class="announcement-content"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; padding: 40px; color: #999;">No announcements yet. Create your first announcement above.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>