<?php
/**
 * RDS MySQL connection.
 *
 * Values are read from environment variables so the real credentials never
 * sit in source control. Set these on the EC2 instance (e.g. in
 * /etc/environment, a .env loaded by your shell, or your process manager's
 * env block) before starting the app:
 *
 *   RDS_HOST=your-instance.xxxxxxxxxx.ap-south-1.rds.amazonaws.com
 *   RDS_PORT=3306
 *   RDS_DB=student_portfolio
 *   RDS_USER=portfolio_app
 *   RDS_PASS=********
 */

function get_rds_connection(): PDO
{
    $host = getenv('RDS_HOST') ?: '127.0.0.1';
    $port = getenv('RDS_PORT') ?: '3306';
    $db   = getenv('RDS_DB')   ?: 'student_portfolio';
    $user = getenv('RDS_USER') ?: 'portfolio_app';
    $pass = getenv('RDS_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Never leak host/user/pass in the error shown to a browser.
        error_log('RDS connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('Could not connect to the academic records database. Check server logs.');
    }
}
