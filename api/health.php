<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/schema.php';

require_method('GET');

try {
    $pdo = database();
    ensure_schema($pdo);
    $count = (int) $pdo->query('SELECT COUNT(*) FROM calls')->fetchColumn();
    json_response([
        'ok' => true,
        'database' => 'connected',
        'records' => $count,
        'time' => date(DATE_ATOM),
    ]);
} catch (Throwable $error) {
    error_log('HVAC health error: ' . $error->getMessage());
    json_response([
        'ok' => false,
        'database' => 'unavailable',
        'error' => 'Check the private database configuration',
    ], 503);
}
