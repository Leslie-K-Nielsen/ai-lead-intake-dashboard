<?php

declare(strict_types=1);

function api_config(): array
{
    static $config;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
        date_default_timezone_set($config['timezone'] ?? 'UTC');
    }

    return $config;
}

function json_response(array $payload, int $status = 200, bool $noStore = true): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    if ($noStore) {
        header('Cache-Control: no-store');
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
    }
}

function request_bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) !== 1) {
        return '';
    }

    return trim($matches[1]);
}

function require_ingest_auth(): void
{
    $expected = (string) (api_config()['ingest_token'] ?? '');
    $provided = request_bearer_token();

    if ($expected === '' || str_starts_with($expected, 'REPLACE_')) {
        json_response(['ok' => false, 'error' => 'API token is not configured'], 503);
    }

    if ($provided === '' || !hash_equals($expected, $provided)) {
        header('WWW-Authenticate: Bearer');
        json_response(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        json_response(['ok' => false, 'error' => 'JSON body is required'], 400);
    }

    try {
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        json_response(['ok' => false, 'error' => 'Invalid JSON body'], 400);
    }

    if (!is_array($payload)) {
        json_response(['ok' => false, 'error' => 'JSON object is required'], 400);
    }

    return $payload;
}

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = api_config();
    foreach (['db_name', 'db_user', 'db_pass'] as $key) {
        if (!isset($config[$key]) || str_starts_with((string) $config[$key], 'REPLACE_')) {
            throw new RuntimeException('Database credentials are not configured');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $config['db_host'] ?? 'localhost',
        $config['db_name']
    );
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function clean_string(mixed $value, int $maxLength = 255): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    return mb_substr($value, 0, $maxLength);
}

function clean_bool(mixed $value): int
{
    return filter_var($value, FILTER_VALIDATE_BOOL) ? 1 : 0;
}

function clean_decimal(mixed $value): float
{
    $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);
    return is_numeric($normalized) ? round((float) $normalized, 2) : 0.0;
}

function clean_int(mixed $value): int
{
    return max(0, (int) $value);
}
