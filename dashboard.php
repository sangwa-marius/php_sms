<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

switch ($_SESSION['role']) {
    case 'student':
        header("Location: student_dashboard.php");
        break;

    case 'teacher':
        header("Location: teacher_dashboard.php");
        break;

    case 'principle':
        header("Location: principle_dashboard.php");
        break;

    default:
        header("Location: index.php");
}
exit;
