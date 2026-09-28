# AI Lead Intake and Revenue Recovery Dashboard

A portfolio concept demonstrating how an AI-assisted customer intake workflow can be connected to automation, structured storage, and an operational reporting dashboard.

**Live demo:** [https://hvac-ai.nisdevelopment.com/](https://hvac-ai.nisdevelopment.com/)

> **Modeled-project disclosure:** Sonoran Desert Peak HVAC is a fictional company. The names, calls, financial values, and performance metrics shown in the public dashboard are simulated or modeled for demonstration purposes. They are not results from a live client engagement.

## What This Project Demonstrates

This project shows how a fragmented business process can be turned into a structured, measurable automation system. The HVAC scenario is illustrative; the same architecture can be adapted to sales intake, customer support, scheduling, onboarding, billing, document processing, and other operational workflows.

The implementation demonstrates:

- AI-assisted collection and classification of customer requests
- Structured webhook ingestion from an automation platform
- Bearer-token authentication for write operations
- Validation and normalization of incoming data
- Idempotent record creation and updates
- MySQL-backed operational storage
- A read-only dashboard API
- A responsive dashboard for leads, calls, urgency, bookings, cost, and modeled ROI
- Human-escalation and callback fields for workflows that require staff involvement
- Separation between the conversational, automation, data, API, and reporting layers

## System Flow

```text
Customer call
    -> AI intake agent
    -> Business knowledge and safety rules
    -> Workflow automation webhook
    -> POST /api/calls-ingest.php
    -> MySQL call record
    -> GET /api/dashboard.php
    -> Dashboard metrics and operational views
```

The AI and automation services are intentionally outside this repository. They send a normalized JSON payload to the authenticated ingestion endpoint. The PHP API validates the request, creates or updates the call record, and exposes modeled demonstration records to the dashboard.

## Repository Structure

```text
.
├── api/
│   ├── .htaccess          # API access and response-header protections
│   ├── bootstrap.php      # Configuration, database, auth, and input helpers
│   ├── calls-ingest.php   # Authenticated webhook ingestion endpoint
│   ├── config.example.php # Safe configuration template
│   ├── dashboard.php      # Read-only dashboard data endpoint
│   ├── health.php         # Database connectivity check
│   └── schema.php         # Idempotent calls-table creation
├── assets/                # Compiled dashboard assets
├── .gitignore
├── index.html
├── index.php
└── logo.svg
```

The private `api/config.php` file is deliberately excluded from version control.

## Server Requirements

- PHP 8.1 or newer
- MySQL 8+ or a compatible MariaDB release
- PDO MySQL extension
- PHP `mbstring` extension
- Apache or another web server capable of serving PHP
- Apache `mod_headers` when using the included API `.htaccess`
- HTTPS for all production traffic

No PHP package manager or third-party PHP dependencies are required.

## Database Setup

1. Create an empty MySQL database.
2. Create a dedicated database user.
3. Grant that user only the permissions required by this application.
4. Add the connection details to the private configuration file described below.

The API calls `ensure_schema()` before database operations. On its first successful connection, it creates the `calls` table if it does not already exist.

The runtime database user currently requires permission to:

- Create the initial table
- Select dashboard records
- Insert incoming records
- Update existing records

For a more restrictive production deployment, create the schema separately and remove the runtime user's `CREATE` permission afterward.

## Configuration

Copy the example configuration without committing the resulting private file:

```bash
cp api/config.example.php api/config.php
```

Update `api/config.php` with values for:

```php
<?php

declare(strict_types=1);

return [
    'db_host' => 'localhost',
    'db_name' => 'your_database',
    'db_user' => 'your_username',
    'db_pass' => 'your_password',
    'ingest_token' => 'generate-a-long-random-token',
    'allowed_origin' => 'https://your-dashboard.example',
    'timezone' => 'UTC',
];
```

Generate the ingest token with a cryptographically secure password generator. Store the same value in the automation platform that sends webhook requests.

Never put database credentials or the ingest token in JavaScript, committed files, screenshots, example payloads, or frontend environment variables.

## API Endpoints

### `POST /api/calls-ingest.php`

Creates a call record or updates the existing record with the same `call_id`.

Required headers:

```http
Authorization: Bearer YOUR_INGEST_TOKEN
Content-Type: application/json
```

The endpoint requires `call_id`. Other fields are optional and receive safe defaults where appropriate. Supported urgency values are `urgent`, `soon`, and `routine`.

Successful creation:

```json
{
  "ok": true,
  "call_id": "demo-call-001",
  "action": "inserted"
}
```

Sending the same `call_id` again updates that record instead of creating a duplicate. This makes normal webhook retries idempotent.

### `GET /api/dashboard.php`

Returns the modeled demonstration records used by the public dashboard:

```json
{
  "ok": true,
  "rows": []
}
```

The endpoint queries only records whose `data_type` is `Modeled Demo`. Do not assign that value to real customer records in a public deployment.

### `GET /api/health.php`

Checks whether the application can connect to the database and initialize its schema. Consider limiting or simplifying this endpoint before adapting the project to production use.

## Example Ingestion Payload

```json
{
  "call_id": "demo-call-001",
  "call_started_at": "2026-09-28T14:30:00-07:00",
  "call_ended_at": "2026-09-28T14:33:12-07:00",
  "caller_name": "Sample Customer",
  "phone": "+1-555-010-0101",
  "email": "sample@example.test",
  "city_or_zip": "Phoenix, AZ 85001",
  "service_requested": "Seasonal HVAC tune-up",
  "issue_description": "Customer requested routine maintenance before peak season.",
  "urgency": "routine",
  "lead_status": "Follow-up",
  "appointment_status": "Callback requested",
  "callback_required": true,
  "human_escalation": false,
  "after_hours": false,
  "agent_successful": true,
  "estimated_lead_value": 325,
  "modeled_revenue_value": 0,
  "call_cost": 0.42,
  "call_duration_seconds": 192,
  "lead_source": "AI voice agent",
  "caller_sentiment": "Positive",
  "full_summary": "Routine maintenance lead requesting a callback for scheduling.",
  "data_type": "Modeled Demo",
  "source_updated_at": "2026-09-28T14:33:15-07:00"
}
```

All information in this example is fictional.

## Security Measures

The demonstration includes several production-oriented controls:

- The private configuration file is excluded from version control.
- Write requests require a bearer token.
- Token comparison uses `hash_equals()`.
- The API accepts only the intended HTTP method for each endpoint.
- JSON parsing rejects missing, invalid, or non-object payloads.
- Input values are normalized and length-limited before storage.
- Urgency is restricted to an allowlist.
- Database writes use PDO prepared statements.
- PDO emulated prepares are disabled.
- `call_id` has a unique database constraint.
- Repeated call IDs update rather than duplicate records.
- Sensitive internal exceptions are logged server-side and replaced with generic API errors.
- Directory indexes and direct access to configuration, bootstrap, and schema files are blocked by the API `.htaccess`.
- CORS is restricted to the configured dashboard origin.
- Security response headers disable MIME sniffing and referrer disclosure.

Before adapting this project to real customer data, also add requirements appropriate to the deployment, such as rate limiting, retention policies, access controls for dashboard data, encrypted backups, monitoring, secret rotation, and a formal privacy review.

## Portfolio Context

This repository is published to demonstrate full-stack AI automation architecture rather than to provide a turnkey production product. It shows how an AI interaction can become a structured, auditable business record and then support operational decisions through a custom dashboard.

The tools used in the demonstration are replaceable. The same pattern can work with different voice or chat platforms, workflow orchestrators, CRMs, databases, application frameworks, and hosting environments.

