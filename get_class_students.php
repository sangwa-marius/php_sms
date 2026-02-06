<?php
session_start();
require 'db.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'teacher') {
    exit('Unauthorized');
}

// Check if parameters are provided
if (!isset($_GET['class_id']) || !isset($_GET['assessment_id'])) {
    exit('Missing required parameters');
}

$classId = mysqli_real_escape_string($conn, $_GET['class_id']);
$assessmentId = mysqli_real_escape_string($conn, $_GET['assessment_id']);

// Get students and their existing results
$query = "SELECT u.id, u.name, u.email, r.marks_obtained, r.grade 
          FROM enrollments e 
          JOIN users u ON e.student_id = u.id 
          LEFT JOIN results r ON r.student_id = u.id AND r.assessment_id = '$assessmentId'
          WHERE e.class_id = '$classId' AND e.status = 'enrolled'
          ORDER BY u.name";

$students = mysqli_query($conn, $query);

if (mysqli_num_rows($students) > 0):
?>
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Email</th>
                <th>Marks Obtained</th>
                <th>Current Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($student = mysqli_fetch_assoc($students)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td>
                        <input type="number" 
                               name="marks[<?php echo $student['id']; ?>]" 
                               min="0" 
                               max="100" 
                               step="0.01"
                               value="<?php echo $student['marks_obtained'] ?? ''; ?>"
                               placeholder="Enter marks">
                    </td>
                    <td>
                        <span style="font-weight: 600; color: <?php echo $student['grade'] === 'F' ? '#dc3545' : '#28a745'; ?>;">
                            <?php echo $student['grade'] ?? '-'; ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align: center; padding: 40px; color: #999;">
        No students enrolled in this class yet.
    </p>
<?php endif; ?>