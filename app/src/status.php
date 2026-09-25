<?php
declare(strict_types=1);

function setting(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

/** @return array{ok: bool, version: ?string, tls: ?string, error: ?string} */
function databaseStatus(?callable $connect = null): array
{
    try {
        $host = setting('DB_HOST');
        $name = setting('DB_NAME');
        $port = setting('DB_PORT', '3306');
        $user = setting('DB_USER');
        $password = setting('DB_PASSWORD');
        // A DSN is structured text: reject separators rather than concatenating arbitrary input.
        if (!preg_match('/\A[a-zA-Z0-9._-]+\z/', $host)
            || !preg_match('/\A[a-zA-Z0-9_-]+\z/', $name)
            || !ctype_digit($port) || (int)$port < 1 || (int)$port > 65535
            || $user === '' || $password === '') {
            throw new InvalidArgumentException('configuration');
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $tls = setting('DB_TLS', 'disabled');
        if ($tls === 'verify') {
            $ca = setting('DB_SSL_CA');
            if ($ca === '' || !is_readable($ca)) {
                throw new InvalidArgumentException('configuration');
            }
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        } elseif ($tls !== 'disabled') {
            throw new InvalidArgumentException('configuration');
        }
        $connect ??= static fn($dsn, $username, $secret, $opts) => new PDO($dsn, $username, $secret, $opts);
        $db = $connect("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $password, $options);
        $version = (string)$db->query('SELECT VERSION()')->fetchColumn();
        $row = $db->query("SHOW SESSION STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_ASSOC);
        $cipher = is_array($row) ? (string)($row['Value'] ?? '') : '';
        if ($tls === 'verify' && $cipher === '') {
            throw new RuntimeException('tls_required');
        }
        return ['ok' => true, 'version' => $version, 'tls' => $cipher !== '' ? $cipher : null, 'error' => null];
    } catch (Throwable $error) {
        // Never log exception messages: drivers may include hosts, user names, or connection details.
        $category = $error instanceof InvalidArgumentException ? 'configuration' : 'connection';
        error_log('[database] ' . $category . '_failed');
        return ['ok' => false, 'version' => null, 'tls' => null, 'error' => $category];
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
