<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    min-height: 100vh;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
}

.profile-card {
    width: 360px;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
}

.profile-image {
    width: 120px;
    height: 120px;
    margin: 0 auto 15px;
    border-radius: 50%;
    overflow: hidden;
    border:none;
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-card h2 {
    margin: 10px 0 5px;
    color: #333;
}

.profile-card p {
    margin: 6px 0;
    color: #555;
    font-size: 14px;
}

.role-badge {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 14px;
    background: #e9f2ff;
    color: #007bff;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
}

.actions {
    margin-top: 20px;
}

.actions a {
    display: inline-block;
    margin: 6px;
    padding: 10px 18px;
    background: #007bff;
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
}

.actions a.secondary {
    background: #6c757d;
}

.actions a:hover {
    opacity: 0.9;
}
</style>
</head>

<body>

<div class="profile-card">
    <div class="profile-image">
        <img src="uploads/profiles/<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Picture">
    </div>

    <h2><?= htmlspecialchars($user['name']) ?></h2>
    <p><?= htmlspecialchars($user['email']) ?></p>

    <div class="role-badge">
        <?= ucfirst(htmlspecialchars($user['role'])) ?>
    </div>

    <div class="actions">
        <a href="dashboard.php">Dashboard</a>
        <a href="edit_profile.php" class="secondary">Edit Profile</a>
    </div>
</div>

</body>
</html>
