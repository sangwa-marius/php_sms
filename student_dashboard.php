<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Dashboard</title>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f8;
}
.sidebar {
    width: 220px;
    height: 100vh;
    background: #007bff;
    color: white;
    position: fixed;
    padding: 20px;
}
.sidebar h2 {
    text-align: center;
}
.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px;
    margin: 8px 0;
    border-radius: 5px;
}
.sidebar a:hover {
    background: rgba(255,255,255,0.2);
}
.main {
    margin-left: 240px;
    padding: 30px;
}
.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

span{
    color:blue;
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Student</h2>
    <a href="profile.php">My Profile</a>
    <a href="#">My Courses</a>
    <a href="#">Results</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <h1>Welcome, Student  <span><?=$_SESSION['name']?></span></h1>
    <div class="card">
        <p>This is your student dashboard.</p>
        <p>Here you can view courses, grades, and announcements.</p>
    </div>
</div>

</body>
</html>
