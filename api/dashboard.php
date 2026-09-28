<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/schema.php';

require_method('GET');
$config = api_config();
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && hash_equals((string) $config['allowed_origin'], $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}

try {
    $pdo = database();
    ensure_schema($pdo);
    $statement = $pdo->prepare(<<<'SQL'
        SELECT
            call_id AS id,
            DATE_FORMAT(call_started_at, '%Y-%m-%dT%H:%i:%s') AS timestamp,
            COALESCE(caller_name, '') AS name,
            COALESCE(city_or_zip, '') AS city,
            COALESCE(service_requested, '') AS need,
            urgency,
            COALESCE(lead_status, '') AS status,
            COALESCE(appointment_status, '') AS appointmentStatus,
            call_duration_seconds AS lengthSec,
            estimated_lead_value AS value,
            modeled_revenue_value AS modeledRevenueValue,
            call_cost AS aiCost,
            human_escalation AS escalated,
            callback_required AS callbackRequired,
            after_hours AS afterHours,
            agent_successful AS agentSuccessful,
            COALESCE(lead_source, '') AS leadSource,
            COALESCE(caller_sentiment, '') AS sentiment,
            COALESCE(full_summary, '') AS summary
        FROM calls
        WHERE data_type = :data_type
        ORDER BY call_started_at ASC, id ASC
    SQL);
    $statement->execute(['data_type' => 'Modeled Demo']);

    $rows = array_map(static function (array $row): array {
        $row['lengthSec'] = (int) $row['lengthSec'];
        $row['value'] = (float) $row['value'];
        $row['modeledRevenueValue'] = (float) $row['modeledRevenueValue'];
        $row['aiCost'] = (float) $row['aiCost'];
        $row['escalated'] = (bool) $row['escalated'];
        $row['callbackRequired'] = (bool) $row['callbackRequired'];
        $row['afterHours'] = (bool) $row['afterHours'];
        $row['agentSuccessful'] = (bool) $row['agentSuccessful'];
        return $row;
    }, $statement->fetchAll());

    header('Cache-Control: public, max-age=30, stale-while-revalidate=60');
    json_response(['ok' => true, 'rows' => $rows], 200, false);
} catch (Throwable $error) {
    error_log('HVAC dashboard error: ' . $error->getMessage());
    json_response(['ok' => false, 'error' => 'Dashboard data is unavailable'], 500);
}
