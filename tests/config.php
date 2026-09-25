<?php
declare(strict_types=1);
require '/var/www/app/src/status.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS $message\n";
}

$result = databaseStatus();
expect($result['ok'] && $result['version'] !== '', 'real database version');
$password = setting('DB_PASSWORD');
putenv('DB_PASSWORD=deliberately-invalid-credential');
expect(!databaseStatus()['ok'], 'invalid credentials rejected');
putenv('DB_PASSWORD=' . $password);
putenv('DB_TLS=verify');
putenv('DB_SSL_CA=/missing-ca.pem');
expect(!databaseStatus()['ok'], 'TLS without trust material fails closed');
putenv('DB_TLS=disabled');
putenv('DB_HOST=db;dbname=other');
expect(!databaseStatus()['ok'], 'DSN separator injection rejected');
