# Tradie SaaS — Laravel Backend

AI-powered call handling and job management for trade service professionals.

## Architecture Overview

```
Customer calls Twilio number
    → Twilio webhook → CallWebhookController
    → IncomingCallPipeline (orchestration layer)
    → Available tradie? → Forward via Twilio <Dial>
    → No tradie / No answer → Retell AI via Twilio <Stream>
    → Post-call → Retell webhook → Job created from AI transcript
```

### Domain Structure

```
app/
├── Domain/             ← Pure business logic. No framework dependencies inside.
│   ├── Call/           ← Routing, call lifecycle, Twilio abstraction
│   ├── Job/            ← Job CRUD, state machine, repositories
│   ├── Tradie/         ← Availability, onboarding, profile
│   ├── AI/             ← Retell agent management, transcript extraction
│   └── Tenant/         ← Multi-tenant context, subscription state
├── Application/        ← Cross-domain orchestration (Pipeline, Listeners)
├── Infrastructure/     ← Concrete provider implementations (Twilio, Retell)
└── Http/               ← Controllers, Requests, Resources (thin layer only)
```

---

## Quick Start

### 1. Install dependencies

```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Configure environment

Edit `.env` with:
- `TWILIO_ACCOUNT_SID` and `TWILIO_AUTH_TOKEN` from Twilio Console
- `RETELL_API_KEY` from Retell dashboard
- `APP_URL` — must be publicly accessible (use ngrok in local dev)

### 3. Run migrations and seed

```bash
php artisan migrate
php artisan db:seed
# Demo login: admin@acepiping.com / password
```

### 4. Expose locally for Twilio webhooks

Twilio cannot reach `localhost`. Use ngrok:

```bash
ngrok http 8000
# Copy the https URL into APP_URL in .env
```

### 5. Start queue worker

```bash
php artisan queue:work redis
```

Availability release and notifications run as queued jobs — the worker must be running.

### 6. Run tests

```bash
php artisan test
php artisan test --testsuite=Unit     # Fast, no DB
php artisan test --testsuite=Feature  # Full flow tests
```

---

## Key Design Decisions

### Idempotent Webhooks
Both the Twilio and Retell webhooks are idempotent by design:
- Twilio: `Call::firstOrCreate(['twilio_call_sid' => ...])` — retries don't create duplicates
- Retell: `if ($call->job()->exists()) return` — duplicate `call_analyzed` events are ignored

### Race Condition Protection
`TradieAvailabilityService::claimAvailableTradie()` wraps the availability check and `markUnavailable()` in a Redis lock. Two simultaneous calls cannot claim the same tradie.

### Provider Abstraction
`CallProviderInterface` and `AIProviderInterface` live in the Domain layer. Twilio and Retell implementations live in Infrastructure. Swapping providers is a single binding change in `AppServiceProvider`.

### State Machine
`Job::canTransitionTo()` enforces valid state transitions. Invalid transitions throw `InvalidArgumentException`, caught by the exception handler and returned as `422`.

### Job Table Name
The jobs table is named `service_jobs` to avoid collision with Laravel's built-in `jobs` queue table.

---

## API Endpoints

### Auth
```
POST /api/auth/register    — Tradie registration + number provisioning
POST /api/auth/login       — Returns Sanctum token
POST /api/auth/logout      — Revokes current token
```

### Tradie (authenticated)
```
GET    /api/tradie/me                — Current tradie profile
PATCH  /api/tradie/availability      — Toggle availability {is_available: bool}
```

### Jobs (authenticated)
```
GET    /api/jobs                     — List jobs (tradies see own, dispatchers see all)
GET    /api/jobs/{id}                — Single job detail
PATCH  /api/jobs/{id}/status         — Transition status {status, cancellation_reason?}
PATCH  /api/jobs/{id}/assign         — Assign to tradie (dispatcher/admin only) {tradie_id}
```

### Webhooks (Twilio signature protected)
```
POST /api/webhooks/call/inbound      — Twilio inbound call
POST /api/webhooks/call/status/{id}  — Twilio dial status callback (no-answer, completed)
```

### Webhooks (Retell API key protected)
```
POST /api/webhooks/retell/events     — Retell post-call events (call_ended, call_analyzed)
```

---

## Job Status Lifecycle

```
[pending]    → assigned | cancelled
[ai_created] → assigned | cancelled
[assigned]   → in_progress | cancelled
[in_progress]→ completed | cancelled
```

## Adding a New Call Provider (e.g. Vonage)

1. Create `app/Infrastructure/Providers/Call/VonageCallProvider.php`
2. Implement all methods from `CallProviderInterface`
3. Write a contract test that runs against `VonageCallProvider` (same test class as Twilio)
4. Change the binding in `AppServiceProvider`: `$this->app->bind(CallProviderInterface::class, VonageCallProvider::class)`

No other files change.
