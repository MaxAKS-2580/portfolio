<?php
/**
 * One-time setup script: creates the SkillsAndCertifications DynamoDB
 * table and loads sample items. Run once from the EC2 instance (or any
 * machine with suitable AWS credentials):
 *
 *   php create_table_and_seed.php
 *
 * Table design (unstructured/semi-structured data — different item
 * "shapes" per category, which is why this lives in DynamoDB rather than
 * a rigid MySQL table):
 *   Partition key: category   (S)  e.g. "skill" | "certification"
 *   Sort key:      item_id    (S)  e.g. "skill#mern" | "cert#aws-cp"
 */

require __DIR__ . '/../config/dynamo_config.php';

$client = get_dynamodb_client();
$table  = get_dynamo_table_name();

// --- Create table (skips if it already exists) -----------------------
try {
    $client->describeTable(['TableName' => $table]);
    echo "Table {$table} already exists, skipping creation.\n";
} catch (\Aws\DynamoDb\Exception\DynamoDbException $e) {
    if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
        $client->createTable([
            'TableName' => $table,
            'AttributeDefinitions' => [
                ['AttributeName' => 'category', 'AttributeType' => 'S'],
                ['AttributeName' => 'item_id',  'AttributeType' => 'S'],
            ],
            'KeySchema' => [
                ['AttributeName' => 'category', 'KeyType' => 'HASH'],
                ['AttributeName' => 'item_id',  'KeyType' => 'RANGE'],
            ],
            'BillingMode' => 'PAY_PER_REQUEST',
        ]);
        echo "Creating table {$table}...\n";
        $client->waitUntil('TableExists', ['TableName' => $table]);
        echo "Table {$table} is now active.\n";
    } else {
        throw $e;
    }
}

// --- Seed items --------------------------------------------------------
$marshaler = get_marshaler();

$items = [
    [
        'category'    => 'skill',
        'item_id'     => 'skill#mern',
        'name'        => 'MERN Stack Development',
        'proficiency' => 'Advanced',
        'tags'        => ['MongoDB', 'Express', 'React', 'Node.js'],
    ],
    [
        'category'    => 'skill',
        'item_id'     => 'skill#event-driven',
        'name'        => 'Event-Driven Architecture',
        'proficiency' => 'Intermediate',
        'tags'        => ['Apache Kafka', 'WebRTC', 'Socket.IO'],
    ],
    [
        'category'    => 'skill',
        'item_id'     => 'skill#computer-vision',
        'name'        => 'Computer Vision',
        'proficiency' => 'Intermediate',
        'tags'        => ['YOLO', 'RT-DETR', 'EasyOCR', 'ByteTrack'],
    ],
    [
        'category'    => 'skill',
        'item_id'     => 'skill#agentic-ai',
        'name'        => 'Agentic AI Systems',
        'proficiency' => 'Intermediate',
        'tags'        => ['LangGraph', 'RAG', 'Anthropic API'],
    ],
    [
        'category'      => 'certification',
        'item_id'       => 'cert#aws-cp',
        'name'          => 'AWS Certified Cloud Practitioner',
        'issuer'        => 'Amazon Web Services',
        'year'          => '2026',
    ],
    [
        'category'      => 'certification',
        'item_id'       => 'cert#meta-frontend',
        'name'          => 'Meta Front-End Developer',
        'issuer'        => 'Meta (Coursera)',
        'year'          => '2025',
    ],
];

foreach ($items as $item) {
    $client->putItem([
        'TableName' => $table,
        'Item'      => $marshaler->marshalItem($item),
    ]);
    echo "Inserted {$item['item_id']}\n";
}

echo "Done.\n";
