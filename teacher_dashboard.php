<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Teacher Dashboard</title>
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

.top-bar {
    background: linear-gradient(135deg, #0c5a55 0%, #0a4a46 100%);
    padding: 35px 40px;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(12, 90, 85, 0.2);
    margin-bottom: 35px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
}

.welcome-text h1 {
    font-size: 32px;
    font-weight: 600;
    color: white;
    margin-bottom: 8px;
}

.welcome-text h1 span {
    color: #4ecca3;
    font-weight: 700;
}

.welcome-text p {
    color: rgba(255, 255, 255, 0.85);
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.welcome-text p i {
    font-size: 14px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 20px;
    background: rgba(255, 255, 255, 0.1);
    padding: 12px 20px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.user-details {
    text-align: right;
}

.user-details h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 3px;
}

.user-details p {
    font-size: 13px;
    opacity: 0.9;
}

.user-avatar {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #4ecca3, #3db88f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    font-weight: 700;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 35px;
}

.stat-card {
    background: white;
    padding: 28px;
    border-radius: 15px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #0c5a55, #4ecca3);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.stat-card-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #0c5a55, #4ecca3);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 26px;
    box-shadow: 0 4px 15px rgba(12, 90, 85, 0.25);
}

.stat-card h3 {
    font-size: 13px;
    color: #7f8c8d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.stat-card .number {
    font-size: 36px;
    font-weight: 700;
    color: #0c5a55;
    margin: 12px 0;
    line-height: 1;
}

.stat-card p {
    color: #95a5a6;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-badge {
    background: #d4edda;
    color: #155724;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
    display: inline-block;
}

.stat-badge.warning {
    background: #fff3cd;
    color: #856404;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f2f5;
}

.card-header h2 {
    color: #2c3e50;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header h2 i {
    color: #0c5a55;
    font-size: 22px;
}

.view-all {
    color: #0c5a55;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: color 0.3s;
}

.view-all:hover {
    color: #4ecca3;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 18px 25px;
    background: linear-gradient(135deg, #0c5a55, #0a4a46);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(12, 90, 85, 0.2);
}

.action-btn i {
    font-size: 18px;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(12, 90, 85, 0.35);
    background: linear-gradient(135deg, #0a4a46, #083d39);
}

.recent-activity {
    list-style: none;
}

.activity-item {
    padding: 18px;
    border-left: 3px solid #0c5a55;
    margin-bottom: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.3s;
}

.activity-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.activity-item:last-child {
    margin-bottom: 0;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.activity-title {
    font-weight: 600;
    color: #2c3e50;
    font-size: 15px;
}

.activity-time {
    font-size: 12px;
    color: #7f8c8d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.activity-description {
    color: #6c757d;
    font-size: 13px;
    line-height: 1.5;
}

.upcoming-section {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.upcoming-item {
    padding: 18px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 10px;
    margin-bottom: 15px;
    border-left: 4px solid #0c5a55;
}

.upcoming-item:last-child {
    margin-bottom: 0;
}

.upcoming-date {
    font-size: 12px;
    color: #0c5a55;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.upcoming-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
    font-size: 15px;
}

.upcoming-details {
    font-size: 13px;
    color: #6c757d;
}

.full-width-card {
    grid-column: 1 / -1;
}

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
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
    
    .top-bar {
        flex-direction: column;
        gap: 20px;
        padding: 25px;
    }
    
    .user-info {
        width: 100%;
        justify-content: space-between;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
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
        <a href="teacher_dashboard.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a>
        <a href="profile.php"><i class="fas fa-user"></i> <span>My Profile</span></a>
        <a href="teacher_classes.php"><i class="fas fa-chalkboard"></i> <span>My Classes</span></a>
        <a href="teacher_students.php"><i class="fas fa-users"></i> <span>Students</span></a>
        <a href="#"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="#"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="#"><i class="fas fa-cog"></i> <span>Settings</span></a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="top-bar">
        <div class="welcome-text">
            <h1>Welcome back, <span><?=$_SESSION['name']?></span>!</h1>
            <p><i class="fas fa-calendar-alt"></i> <?=date('l, F j, Y')?></p>
        </div>
        <div class="user-info">
            <div class="user-details">
                <h3><?=$_SESSION['name']?></h3>
                <p>Teacher Account</p>
            </div>
            <div class="user-avatar">
                <?=strtoupper(substr($_SESSION['name'], 0, 1))?>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <h3>Total Classes</h3>
                    <div class="number">8</div>
                    <p><i class="fas fa-check-circle" style="color: #28a745;"></i> Active this semester</p>
                    <span class="stat-badge">+2 from last term</span>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <h3>Total Students</h3>
                    <div class="number">156</div>
                    <p><i class="fas fa-users" style="color: #0c5a55;"></i> Across all classes</p>
                    <span class="stat-badge">Average: 19.5/class</span>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <h3>Pending Tasks</h3>
                    <div class="number">12</div>
                    <p><i class="fas fa-exclamation-circle" style="color: #ffc107;"></i> Results to upload</p>
                    <span class="stat-badge warning">Due this week</span>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <h3>Attendance Rate</h3>
                    <div class="number">94%</div>
                    <p><i class="fas fa-chart-line" style="color: #28a745;"></i> This month average</p>
                    <span class="stat-badge">+3% from last month</span>
                </div>
                <div class="stat-card-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <a href="#" class="view-all">View All →</a>
            </div>
            <ul class="recent-activity">
                <li class="activity-item">
                    <div class="activity-header">
                        <span class="activity-title">Grade 10 Mathematics - Test Results Uploaded</span>
                        <span class="activity-time"><i class="fas fa-clock"></i> 2 hours ago</span>
                    </div>
                    <p class="activity-description">Successfully uploaded test results for 24 students</p>
                </li>
                <li class="activity-item">
                    <div class="activity-header">
                        <span class="activity-title">New Class Created: Advanced Physics</span>
                        <span class="activity-time"><i class="fas fa-clock"></i> 5 hours ago</span>
                    </div>
                    <p class="activity-description">Class created with 18 enrolled students for Fall 2024</p>
                </li>
                <li class="activity-item">
                    <div class="activity-header">
                        <span class="activity-title">Attendance Marked - Grade 11 Chemistry</span>
                        <span class="activity-time"><i class="fas fa-clock"></i> Yesterday</span>
                    </div>
                    <p class="activity-description">22 out of 23 students present (95.7% attendance)</p>
                </li>
                <li class="activity-item">
                    <div class="activity-header">
                        <span class="activity-title">Assignment Graded - Biology Lab Report</span>
                        <span class="activity-time"><i class="fas fa-clock"></i> 2 days ago</span>
                    </div>
                    <p class="activity-description">Completed grading for 28 submissions, average score: 82%</p>
                </li>
            </ul>
        </div>

        <div class="upcoming-section">
            <div class="card-header">
                <h2><i class="fas fa-calendar-check"></i> Upcoming</h2>
            </div>
            <div class="upcoming-item">
                <div class="upcoming-date"><i class="fas fa-calendar"></i> Tomorrow, 10:00 AM</div>
                <div class="upcoming-title">Grade 12 Physics - Final Exam</div>
                <div class="upcoming-details">Room 204 • 2 hours duration</div>
            </div>
            <div class="upcoming-item">
                <div class="upcoming-date"><i class="fas fa-calendar"></i> Friday, 2:00 PM</div>
                <div class="upcoming-title">Parent-Teacher Conference</div>
                <div class="upcoming-details">Conference Room A • 15 meetings scheduled</div>
            </div>
            <div class="upcoming-item">
                <div class="upcoming-date"><i class="fas fa-calendar"></i> Next Monday</div>
                <div class="upcoming-title">Grade 9 Math - Assignment Due</div>
                <div class="upcoming-details">Chapter 5 Problem Set • 32 students</div>
            </div>
            <div class="upcoming-item">
                <div class="upcoming-date"><i class="fas fa-calendar"></i> Next Wednesday</div>
                <div class="upcoming-title">Department Meeting</div>
                <div class="upcoming-details">Staff Room • Curriculum review</div>
            </div>
        </div>
    </div>

    <div class="card full-width-card">
        <div class="card-header">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="quick-actions">
            <a href="teacher_classes.php" class="action-btn">
                <i class="fas fa-plus-circle"></i> Create New Class
            </a>
            <a href="uploadResults.php" class="action-btn">
                <i class="fas fa-upload"></i> Upload Results
            </a>
            <a href="viewReports.php" class="action-btn">
                <i class="fas fa-file-alt"></i> View Reports
            </a>
            <a href="#" class="action-btn">
                <i class="fas fa-user-check"></i> Mark Attendance
            </a>
        </div>
    </div>
</div>

</body>
</html>