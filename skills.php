<?php
require_once __DIR__ . '/../config/dynamo_config.php';
require_once __DIR__ . '/nav.php';

$skills = [];
$certifications = [];
$error = null;

try {
    $client = get_dynamodb_client();
    $table  = get_dynamo_table_name();
    $marshaler = get_marshaler();

    // Unstructured / semi-structured data — items in the same table have
    // different attribute shapes (skills carry "tags", certifications
    // carry "issuer"/"year"), which is what DynamoDB is well suited to
    // and a rigid SQL table is not.
    $result = $client->query([
        'TableName' => $table,
        'KeyConditionExpression' => 'category = :cat',
        'ExpressionAttributeValues' => $marshaler->marshalItem([':cat' => 'skill']),
    ]);
    foreach ($result['Items'] as $item) {
        $skills[] = $marshaler->unmarshalItem($item);
    }

    $result = $client->query([
        'TableName' => $table,
        'KeyConditionExpression' => 'category = :cat',
        'ExpressionAttributeValues' => $marshaler->marshalItem([':cat' => 'certification']),
    ]);
    foreach ($result['Items'] as $item) {
        $certifications[] = $marshaler->unmarshalItem($item);
    }
} catch (Throwable $e) {
    error_log('skills.php query failed: ' . $e->getMessage());
    $error = 'Could not load skills and certifications right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Skills & Certifications</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php render_nav('skills'); ?>
<main class="wrap">
  <h1>Skills &amp; Certifications</h1>
  <p class="sub">Unstructured data, fetched live from <strong>Amazon DynamoDB</strong> via the AWS SDK for PHP.</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php else: ?>
    <h3 style="margin-bottom:14px;">Skills</h3>
    <div class="card-grid">
      <?php foreach ($skills as $s): ?>
      <div class="card">
        <h3><?= htmlspecialchars($s['name']) ?></h3>
        <div class="meta"><span class="badge"><?= htmlspecialchars($s['proficiency']) ?></span></div>
        <?php foreach (($s['tags'] ?? []) as $tag): ?>
          <span class="tag"><?= htmlspecialchars($tag) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <h3 style="margin:32px 0 14px;">Certifications</h3>
    <div class="card-grid">
      <?php foreach ($certifications as $c): ?>
      <div class="card">
        <h3><?= htmlspecialchars($c['name']) ?></h3>
        <div class="meta"><?= htmlspecialchars($c['issuer']) ?> · <?= htmlspecialchars($c['year']) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<footer><div class="wrap">Source: DynamoDB · <?= count($skills) + count($certifications) ?> item(s) loaded</div></footer>
</body>
</html>
