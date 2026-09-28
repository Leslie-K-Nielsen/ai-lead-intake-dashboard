<?php

declare(strict_types=1);

function ensure_schema(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS calls (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            call_id VARCHAR(191) NOT NULL,
            call_started_at DATETIME NULL,
            call_ended_at DATETIME NULL,
            caller_name VARCHAR(191) NULL,
            phone VARCHAR(64) NULL,
            email VARCHAR(191) NULL,
            city_or_zip VARCHAR(191) NULL,
            service_requested VARCHAR(255) NULL,
            issue_description TEXT NULL,
            urgency ENUM('urgent', 'soon', 'routine') NOT NULL DEFAULT 'routine',
            lead_status VARCHAR(64) NULL,
            appointment_status VARCHAR(64) NULL,
            callback_required TINYINT(1) NOT NULL DEFAULT 0,
            human_escalation TINYINT(1) NOT NULL DEFAULT 0,
            after_hours TINYINT(1) NOT NULL DEFAULT 0,
            agent_successful TINYINT(1) NOT NULL DEFAULT 0,
            estimated_lead_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            modeled_revenue_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            call_cost DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
            call_duration_seconds INT UNSIGNED NOT NULL DEFAULT 0,
            lead_source VARCHAR(100) NULL,
            caller_sentiment VARCHAR(100) NULL,
            full_summary TEXT NULL,
            data_type VARCHAR(64) NOT NULL DEFAULT 'Modeled Demo',
            source_updated_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY calls_call_id_unique (call_id),
            KEY calls_data_type_index (data_type),
            KEY calls_started_at_index (call_started_at),
            KEY calls_status_index (lead_status),
            KEY calls_urgency_index (urgency)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    SQL);
}
