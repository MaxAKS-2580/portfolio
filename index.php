<?php require_once __DIR__ . '/nav.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Portfolio</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php render_nav('home'); ?>
<main class="wrap">
  <h1>Student Portfolio</h1>
  <p class="sub">A small demo app showing two AWS data services side by side: structured academic records in RDS MySQL, and unstructured skills/certifications in DynamoDB.</p>
  <div class="card-grid">
    <div class="card">
      <h3>Academic Records</h3>
      <p class="meta">Structured data · queried from RDS (MySQL) via PDO</p>
      <a href="academic.php" class="tag">View academic.php →</a>
    </div>
    <div class="card">
      <h3>Skills &amp; Certifications</h3>
      <p class="meta">Unstructured data · queried from DynamoDB via the AWS SDK</p>
      <a href="skills.php" class="tag">View skills.php →</a>
    </div>
  </div>
</main>
<footer><div class="wrap">Served from EC2 · RDS MySQL + DynamoDB</div></footer>
</body>
</html>
