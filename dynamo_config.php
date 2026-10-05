<?php
/**
 * DynamoDB connection via the AWS SDK for PHP.
 *
 * On EC2, the recommended setup is to attach an IAM role to the instance
 * (e.g. "PortfolioAppRole") with a policy granting dynamodb:GetItem,
 * dynamodb:Query and dynamodb:Scan on the SkillsAndCertifications table.
 * The SDK then picks up credentials automatically from the instance
 * metadata service — no keys need to live on disk or in env vars.
 *
 * DYNAMO_REGION and DYNAMO_TABLE are still read from the environment so
 * the region/table name aren't hardcoded.
 */

require __DIR__ . '/../vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;

function get_dynamodb_client(): DynamoDbClient
{
    $region = getenv('DYNAMO_REGION') ?: 'ap-south-1';

    $config = ['region' => $region, 'version' => 'latest'];

    // Local/dev fallback only — on EC2 the instance role is used instead
    // and these two env vars should simply be left unset.
    $key    = getenv('AWS_ACCESS_KEY_ID');
    $secret = getenv('AWS_SECRET_ACCESS_KEY');
    if ($key && $secret) {
        $config['credentials'] = ['key' => $key, 'secret' => $secret];
    }

    return new DynamoDbClient($config);
}

function get_dynamo_table_name(): string
{
    return getenv('DYNAMO_TABLE') ?: 'SkillsAndCertifications';
}

function get_marshaler(): Marshaler
{
    return new Marshaler();
}
