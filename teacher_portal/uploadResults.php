<?php
session_start();
require '../db/db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit;
}

$teacher_id = $_SESSION['id'];

// Handle creating new assessment
if (isset($_POST['create_assessment'])) {
    $classId = mysqli_real_escape_string($conn, $_POST['class_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $totalMarks = mysqli_real_escape_string($conn, $_POST['total_marks']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $sql = "INSERT INTO assessments (class_id, title, type, total_marks, date, description, created_by) 
            VALUES ('$classId', '$title', '$type', '$totalMarks', '$date', '$description', '$teacher_id')";
    
    if (mysqli_query($conn, $sql)) {
        $message = "Assessment created successfully!";
        $messageType = "success";
    } else {
        $message = "Error creating assessment: " . mysqli_error($conn);
        $messageType = "error";
    }
}

// Handle uploading results
if (isset($_POST['upload_results'])) {
    $assessmentId = mysqli_real_escape_string($conn, $_POST['assessment_id']);
    
    $errorCount = 0;
    $successCount = 0;
    
    foreach ($_POST['marks'] as $studentId => $marks) {
        if ($marks !== '') {
            $studentId = mysqli_real_escape_string($conn, $studentId);
            $marks = mysqli_real_escape_string($conn, $marks);
            
            // Calculate grade
            $grade = '';
            if ($marks >= 90) $grade = 'A+';
            elseif ($marks >= 80) $grade = 'A';
            elseif ($marks >= 70) $grade = 'B+';
            elseif ($marks >= 60) $grade = 'B';
            elseif ($marks >= 50) $grade = 'C';
            elseif ($marks >= 40) $grade = 'D';
            else $grade = 'F';
            
            $sql = "INSERT INTO results (assessment_id, student_id, marks_obtained, grade, uploaded_by) 
                    VALUES ('$assessmentId', '$studentId', '$marks', '$grade', '$teacher_id')
                    ON DUPLICATE KEY UPDATE marks_obtained = '$marks', grade = '$grade', uploaded_by = '$teacher_id'";
            
            if (mysqli_query($conn, $sql)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
    }
    
    if ($successCount > 0) {
        $message = "Results uploaded successfully for $successCount student(s)!";
        $messageType = "success";
    }
    if ($errorCount > 0) {
        $message .= " $errorCount error(s) occurred.";
        $messageType = "error";
    }
}

// Fetch teacher's classes
$classes = mysqli_query($conn, "SELECT * FROM classes WHERE teacher_id = '$teacher_id' AND status = 'active' ORDER BY class_name");

// Fetch assessments
$assessments = mysqli_query($conn, 
    "SELECT a.*, c.class_name, c.subject 
     FROM assessments a 
     JOIN classes c ON a.class_id = c.id 
     WHERE c.teacher_id = '$teacher_id' 
     ORDER BY a.date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results - TeacherHub</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card h2 i {
            color: #0c5a55;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0c5a55;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
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
            box-shadow: 0 5px 15px rgba(12, 90, 85, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        table thead {
            background: #0c5a55;
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

        table input[type="number"] {
            width: 100px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
        }

        .assessment-list {
            display: grid;
            gap: 20px;
        }

        .assessment-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #0c5a55;
            transition: all 0.3s;
        }

        .assessment-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .assessment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .assessment-title {
            font-size: 18px;
            font-weight: 600;
            color: #0c5a55;
        }

        .assessment-badge {
            background: #0c5a55;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .assessment-info {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }

        .modal-header h3 {
            color: #333;
            font-size: 22px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
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
        <a href="uploadResults.php" class="active"><i class="fas fa-upload"></i> <span>Upload Results</span></a>
        <a href="teacher_attendance.php"><i class="fas fa-user-check"></i> <span>Attendance</span></a>
        <a href="viewReports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a>
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>
</div>

<div class="main">
    <div class="header">
        <h1><i class="fas fa-file-upload"></i> Upload Results</h1>
        <p>Create assessments and upload student grades</p>
    </div>

    <?php if (isset($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Create Assessment Card -->
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Create New Assessment</h2>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Class *</label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php 
                        mysqli_data_seek($classes, 0);
                        while ($class = mysqli_fetch_assoc($classes)): 
                        ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['subject']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Assessment Title *</label>
                    <input type="text" name="title" required placeholder="e.g., Mid-Term Exam">
                </div>
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <option value="quiz">Quiz</option>
                        <option value="test">Test</option>
                        <option value="midterm">Mid-Term</option>
                        <option value="final">Final Exam</option>
                        <option value="assignment">Assignment</option>
                        <option value="project">Project</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Marks *</label>
                    <input type="number" name="total_marks" required value="100" min="1" max="1000">
                </div>
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Additional details about this assessment..."></textarea>
            </div>
            <button type="submit" name="create_assessment" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Assessment
            </button>
        </form>
    </div>

    <!-- Assessments List -->
    <div class="card">
        <h2><i class="fas fa-list"></i> Recent Assessments</h2>
        <?php if (mysqli_num_rows($assessments) > 0): ?>
            <div class="assessment-list">
                <?php while ($assessment = mysqli_fetch_assoc($assessments)): 
                    $assessmentId = $assessment['id'];
                    $resultsCount = mysqli_fetch_assoc(mysqli_query($conn, 
                        "SELECT COUNT(*) as count FROM results WHERE assessment_id = '$assessmentId'"))['count'];
                ?>
                    <div class="assessment-item">
                        <div class="assessment-header">
                            <div>
                                <div class="assessment-title"><?php echo htmlspecialchars($assessment['title']); ?></div>
                                <div class="assessment-info">
                                    <i class="fas fa-book"></i> <?php echo htmlspecialchars($assessment['class_name']); ?> - 
                                    <?php echo htmlspecialchars($assessment['subject']); ?> | 
                                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($assessment['date'])); ?>
                                </div>
                                <div class="assessment-info">
                                    <i class="fas fa-star"></i> Total Marks: <?php echo $assessment['total_marks']; ?> | 
                                    <i class="fas fa-check-circle"></i> Results Uploaded: <?php echo $resultsCount; ?>
                                </div>
                            </div>
                            <span class="assessment-badge"><?php echo strtoupper($assessment['type']); ?></span>
                        </div>
                        <button class="btn btn-success" onclick="openUploadModal(<?php echo $assessment['id']; ?>, '<?php echo htmlspecialchars($assessment['title'], ENT_QUOTES); ?>', <?php echo $assessment['class_id']; ?>)">
                            <i class="fas fa-upload"></i> Upload Results
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">No assessments created yet. Create one above to get started.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Upload Results Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> Upload Results: <span id="modalAssessmentTitle"></span></h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="uploadForm">
            <input type="hidden" name="assessment_id" id="modalAssessmentId">
            <div class="table-responsive" id="studentsTable">
                <p style="text-align: center; padding: 20px;">Loading students...</p>
            </div>
            <button type="submit" name="upload_results" class="btn btn-success" style="width: 100%; margin-top: 20px;">
                <i class="fas fa-save"></i> Save Results
            </button>
        </form>
    </div>
</div>

<script>
    function openUploadModal(assessmentId, assessmentTitle, classId) {
        document.getElementById('modalAssessmentId').value = assessmentId;
        document.getElementById('modalAssessmentTitle').textContent = assessmentTitle;
        document.getElementById('uploadModal').classList.add('active');
        
        // Fetch students for this class
        fetch(`get_class_students.php?class_id=${classId}&assessment_id=${assessmentId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('studentsTable').innerHTML = html;
            });
    }

    function closeModal() {
        document.getElementById('uploadModal').classList.remove('active');
    }

    document.getElementById('uploadModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
</script>

</body>
</html>