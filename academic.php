<?php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/nav.php';

$records = [];
$error = null;

try {
    $pdo = get_rds_connection();

    // Structured, relational data — a normal SQL join is the natural fit,
    // which is exactly why this lives in RDS rather than DynamoDB.
    $stmt = $pdo->prepare(
        'SELECT s.full_name, s.university, s.program, s.grad_year,
                r.course_code, r.course_name, r.semester, r.credits, r.grade
         FROM academic_records r
         JOIN students s ON s.student_id = r.student_id
         ORDER BY r.semester, r.course_code'
    );
    $stmt->execute();
    $records = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('academic.php query failed: ' . $e->getMessage());
    $error = 'Could not load academic records right now.';
}

$student = $records[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Records</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php render_nav('academic'); ?>
<main class="wrap">
  <h1>Academic Records</h1>
  <p class="sub">
    Structured data, queried live from <strong>Amazon RDS (MySQL)</strong>.
    <?php if ($student): ?>
      <?= htmlspecialchars($student['full_name']) ?> · <?= htmlspecialchars($student['program']) ?>,
      <?= htmlspecialchars($student['university']) ?> · Class of <?= htmlspecialchars($student['grad_year']) ?>
    <?php endif; ?>
  </p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Course Code</th><th>Course Name</th><th>Semester</th><th>Credits</th><th>Grade</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r): ?>
        <tr>
          <td data-label="Code"><?= htmlspecialchars($r['course_code']) ?></td>
          <td data-label="Course"><?= htmlspecialchars($r['course_name']) ?></td>
          <td data-label="Semester"><?= htmlspecialchars($r['semester']) ?></td>
          <td data-label="Credits"><?= htmlspecialchars($r['credits']) ?></td>
          <td data-label="Grade" class="grade"><?= htmlspecialchars($r['grade']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>
<footer><div class="wrap">Source: RDS MySQL · <?= count($records) ?> record(s) loaded</div></footer>
</body>
</html>
