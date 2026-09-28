<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/schema.php';

require_method('POST');
require_ingest_auth();
$input = request_json();

$callId = clean_string($input['call_id'] ?? null, 191);
if ($callId === null) {
    json_response(['ok' => false, 'error' => 'call_id is required'], 422);
}

$urgency = strtolower(clean_string($input['urgency'] ?? 'routine', 20) ?? 'routine');
if (!in_array($urgency, ['urgent', 'soon', 'routine'], true)) {
    $urgency = 'routine';
}

function mysql_datetime(mixed $value): ?string
{
    $value = clean_string($value, 64);
    if ($value === null) {
        return null;
    }

    $timestamp = strtotime($value);
    return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
}

$row = [
    'call_id' => $callId,
    'call_started_at' => mysql_datetime($input['call_started_at'] ?? $input['timestamp'] ?? null),
    'call_ended_at' => mysql_datetime($input['call_ended_at'] ?? null),
    'caller_name' => clean_string($input['caller_name'] ?? null, 191),
    'phone' => clean_string($input['phone'] ?? null, 64),
    'email' => clean_string($input['email'] ?? null, 191),
    'city_or_zip' => clean_string($input['city_or_zip'] ?? $input['city'] ?? null, 191),
    'service_requested' => clean_string($input['service_requested'] ?? $input['service_need'] ?? null),
    'issue_description' => clean_string($input['issue_description'] ?? null, 5000),
    'urgency' => $urgency,
    'lead_status' => clean_string($input['lead_status'] ?? null, 64),
    'appointment_status' => clean_string($input['appointment_status'] ?? null, 64),
    'callback_required' => clean_bool($input['callback_required'] ?? false),
    'human_escalation' => clean_bool($input['human_escalation'] ?? $input['escalated'] ?? false),
    'after_hours' => clean_bool($input['after_hours'] ?? false),
    'agent_successful' => clean_bool($input['agent_successful'] ?? false),
    'estimated_lead_value' => clean_decimal($input['estimated_lead_value'] ?? $input['estimated_value'] ?? 0),
    'modeled_revenue_value' => clean_decimal($input['modeled_revenue_value'] ?? 0),
    'call_cost' => clean_decimal($input['call_cost'] ?? $input['ai_cost'] ?? 0),
    'call_duration_seconds' => clean_int($input['call_duration_seconds'] ?? $input['call_length_sec'] ?? 0),
    'lead_source' => clean_string($input['lead_source'] ?? null, 100),
    'caller_sentiment' => clean_string($input['caller_sentiment'] ?? null, 100),
    'full_summary' => clean_string($input['full_summary'] ?? null, 10000),
    'data_type' => clean_string($input['data_type'] ?? 'Modeled Demo', 64) ?? 'Modeled Demo',
    'source_updated_at' => mysql_datetime($input['source_updated_at'] ?? null),
];

try {
    $pdo = database();
    ensure_schema($pdo);

    $columns = array_keys($row);
    $placeholders = array_map(static fn (string $column): string => ':' . $column, $columns);
    $updates = array_map(
        static fn (string $column): string => sprintf('%s = VALUES(%s)', $column, $column),
        array_filter($columns, static fn (string $column): bool => $column !== 'call_id')
    );

    $sql = sprintf(
        'INSERT INTO calls (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
        implode(', ', $columns),
        implode(', ', $placeholders),
        implode(', ', $updates)
    );
    $statement = $pdo->prepare($sql);
    $statement->execute($row);

    json_response([
        'ok' => true,
        'call_id' => $callId,
        'action' => $statement->rowCount() === 1 ? 'inserted' : 'updated',
    ], $statement->rowCount() === 1 ? 201 : 200);
} catch (Throwable $error) {
    error_log('HVAC ingest error: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => 'Unable to store call record'], 500);
}
