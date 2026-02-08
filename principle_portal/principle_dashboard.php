<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'principle') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Principal Dashboard</title>
<style>
body {
    margin: 0;
    background: #f4f6f8;
}
.sidebar {
    width: 220px;
    height: 100vh;
    background: #6f42c1;
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
    color:#6f42c1;
}
</style>
</head>
<body>

<div class="sidebar">
    <h2>Principal</h2>
    <a href="profile.php">My Profile</a>
    <a href="#">Manage Teachers</a>
    <a href="manage_students.php">Manage Students</a>
    <a href="#">Reports</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <h1>Welcome, Principal <span><?=$_SESSION['name']?></span></h1>
    <div class="card">
        <p>This is the principal dashboard.</p>
        <p>Oversee the entire school system from here.</p>
    </div>
</div>

</body>
</html>
