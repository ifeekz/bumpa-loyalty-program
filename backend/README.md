# Loyalty Service — Backend

A loyalty program microservice built with **Laravel 11**, **JWT**, **Redis**, **MySQL**, and **Paystack**.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 |
| Auth | JWT (`php-open-source-saver/jwt-auth`) |
| Database | MySQL 8 |
| Queue / Cache | Redis 7 |
| Payments | Paystack |
| Testing | Pest PHP |
| Container | Docker + docker-compose |

---

## Prerequisites

- Docker Desktop
- Git

No local PHP, MySQL, or Redis required.

---

## Setup

```bash
# 1. Clone and configure
git clone <repo-url>
cd loyalty-service
cp .env.example .env
```

Add your keys to `.env`:
```env
PAYSTACK_SECRET_KEY=sk_test_xxxxxx
PAYSTACK_PUBLIC_KEY=pk_test_xxxxxx
FRONTEND_URL=http://localhost:3000
ADMIN_URL=http://localhost:5173
```

```bash
# 2. Start containers
docker-compose up -d --build

# 3. Install dependencies
docker-compose exec app composer install

# 4. Generate keys
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret

# 5. Run migrations and seed
docker-compose exec app php artisan migrate --seed
```

Verify:
```bash
curl http://localhost:8000/api/auth/me
# → {"success":false,"status_code":401,"message":"Token not provided."}
```

> Always run `php artisan` commands inside the container via `docker-compose exec app` to ensure consistent environment.

---

## Default Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@loyalty.test | password |
| Customer | (seeded ×10) | password |

---

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `JWT_SECRET` | Signing secret — run `jwt:secret` | — |
| `JWT_TTL` | Access token lifetime (minutes) | `60` |
| `JWT_REFRESH_TTL` | Refresh window (minutes) | `20160` |
| `PAYSTACK_SECRET_KEY` | Paystack secret key | — |
| `PAYSTACK_PUBLIC_KEY` | Paystack public key | — |
| `LOYALTY_CASHBACK_PERCENT` | Default cashback % (no badge) | `5` |
| `FRONTEND_URL` | Customer dashboard origin | `http://localhost:3000` |
| `ADMIN_URL` | Admin panel origin | `http://localhost:5173` |

---

## API Reference

Base URL: `http://localhost:8000/api`

JWT is returned in the `Authorization: Bearer <token>` response header on login, register, and refresh.

### Auth

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/auth/register` | None | Register customer |
| POST | `/auth/login` | None | Login |
| POST | `/auth/logout` | JWT | Blacklist token |
| POST | `/auth/refresh` | JWT | Rotate token |
| GET | `/auth/me` | JWT | Current user |

### Customer

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/users/{id}/achievements` | JWT | Full loyalty profile |
| POST | `/purchases` | JWT | Initiate payment |
| GET | `/purchases` | JWT | Purchase history |

### Admin

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/admin/users/achievements` | JWT + Admin | All customers (paginated) |
| GET | `/admin/users/{id}` | JWT + Admin | Single user detail |

Query params for admin list: `search`, `badge` (slug), `per_page` (default: 20, max: 100).

### Webhook

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/purchases/webhook` | None | Paystack webhook (HMAC verified) |

---

## Response Envelope

All responses follow this shape:

```json
{
    "success": true,
    "status_code": 200,
    "message": "Login successful",
    "data": {}
}
```

Validation errors include an `errors` key:

```json
{
    "success": false,
    "status_code": 422,
    "message": "The email field is required. (+1 more)",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

---

## Event-Driven Pipeline

```
POST /api/purchases
  └─► Paystack: initialize → return authorization_url

User completes payment on Paystack

Paystack → POST /api/purchases/webhook
  └─► ProcessPurchaseJob (queue: purchases)
        └─► Verify with Paystack
        └─► PurchaseRecorded event
              ├─► HandleAchievementUnlock  (queue: achievements)
              ├─► HandleBadgePromotion     (queue: achievements, delay: 2s)
              └─► HandleCashbackPayout     (queue: default)
```

---

## Loyalty Rules

### Badges

| Badge | Min Points | Cashback |
|---|---|---|
| Bronze | 0 | 2% |
| Silver | 500 | 5% |
| Gold | 2,000 | 8% |
| Platinum | 5,000 | 12% |

### Achievements

| Achievement | Condition | Points |
|---|---|---|
| First Purchase | 1 purchase | 50 |
| Regular Shopper | 5 purchases | 100 |
| Loyal Customer | 10 purchases | 200 |
| Dedicated Shopper | 25 purchases | 500 |
| Spender | ₦10,000 spent | 100 |
| Big Spender | ₦50,000 spent | 300 |
| High Roller | ₦200,000 spent | 1,000 |
| Power Purchase | Single ≥ ₦5,000 | 75 |
| Whale | Single ≥ ₦20,000 | 250 |

---

## Security

### Authentication — JWT
- Tokens are stateless and verified cryptographically — no database lookup per request.
- `role` and `email` claims are embedded in the payload at issue time.
- Logout blacklists the token in Redis (TTL matches `JWT_TTL`).
- Token rotation on refresh — old token is immediately blacklisted.
- `JWT_SECRET` must be at least 32 characters. The app throws a `RuntimeException` at boot time in production if the secret is missing or too short.

### Authorization
- `auth:api` middleware validates JWT signature and expiry on all protected routes.
- `AdminMiddleware` reads the `role` claim directly from the decoded payload — zero DB calls.
- Customers are forbidden from viewing other customers' profiles.

### Mass Assignment Protection
- All models use explicit `$fillable` lists — no `$guarded = []` anywhere.
- `role` is never accepted from request input. It is hardcoded to `customer` on registration and only elevated directly in the database or via seeder.

### Security Headers
Every API response includes the following headers via `SecurityHeadersMiddleware`:

| Header | Value |
|---|---|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `geolocation=(), microphone=(), camera=()` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (production only) |

### Sensitive Data Protection
- `paystack_response` is listed in `$hidden` on `Purchase` and `CashbackTransaction` models — excluded from all serialization and API responses.
- Queue job logs only reference fields (`reference`, `user_id`) — full payloads are never written to logs.

### Webhook Security
- Paystack webhooks are verified using HMAC SHA-512 signature before any processing occurs.
- Unverified requests are rejected with `400 Bad Request`.

### Idempotency
- `processed_for_loyalty` flag on purchases prevents double-crediting points if a queue job is retried after a crash.

### SSL (Local Docker)
- The PHP container uses a `cacert.pem` bundle (`docker/php/cacert.pem`) for outbound SSL verification.
- Production servers use the system CA bundle automatically — no extra config needed.

---

## CORS

Allowed origins are configured via `.env`. In production replace with your actual domains:

```env
FRONTEND_URL=https://yourapp.com
ADMIN_URL=https://admin.yourapp.com
```

The `Authorization` and `X-Token-TTL` headers are explicitly exposed so the React frontend can read the JWT from cross-origin responses.

---

## Rate Limits

| Route group | Limit | Keyed by |
|---|---|---|
| `/auth/register`, `/auth/login` | 5/min | IP |
| Authenticated routes | 60/min | User ID |
| `/purchases/webhook` | 30/min | IP |

Exceeding a limit returns:
```json
{
    "success": false,
    "status_code": 429,
    "message": "Too many attempts. Please try again in a minute."
}
```

---

## Running Tests

```bash
# All suites
docker-compose exec app php artisan test

# By suite
docker-compose exec app php artisan test --testsuite=Unit
docker-compose exec app php artisan test --testsuite=Feature
docker-compose exec app php artisan test --testsuite=Integration

# With coverage
docker-compose exec app php artisan test --coverage
```

Tests use in-memory SQLite with `QUEUE_CONNECTION=sync` — no Redis worker needed.

---

## Known Limitations & Future Improvements

Given the 2-day assessment window, the following were considered but deferred. They are documented here to demonstrate awareness of production requirements.

### Audit Trail
A dedicated `audit_logs` table would track every state-changing action across the system:

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('action');           // e.g. 'achievement.unlocked', 'badge.promoted'
    $table->string('auditable_type');   // Polymorphic — Achievement, Badge, Purchase
    $table->unsignedBigInteger('auditable_id');
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();
    $table->timestamps();
});
```

This provides a tamper-evident history for compliance, dispute resolution (e.g. "why didn't I get cashback?"), and debugging loyalty pipeline issues in production.

---

### Laravel Horizon
The current setup uses a plain `queue:work` process in a Docker container. In production, [Laravel Horizon](https://laravel.com/docs/horizon) would replace this with:
- A real-time dashboard showing queue throughput, job failures, and wait times.
- Automatic worker balancing based on queue load.
- Tagged jobs for filtering and monitoring specific users or purchase references.
- Metrics retention for alerting on SLA breaches.

---

### Notification System
Users currently have no visibility into loyalty events outside the API response. A notification layer would deliver:
- **Email** — badge promotion, cashback confirmation, new achievement.
- **Push** — real-time in-app via Laravel Echo + Redis broadcasting (the events already implement `ShouldBroadcast` — the infrastructure is in place).
- **SMS** — Termii or Twilio for Nigerian users who prefer SMS over email.

---

### Cashback Payout Improvements
The current implementation creates a Paystack transfer recipient per transaction. A production-grade implementation would:
- Store the `recipient_code` on the user profile after first creation and reuse it.
- Introduce a `bank_accounts` table so users can manage multiple payout accounts.
- Add a manual payout approval step for large cashback amounts (fraud prevention).
- Implement a retry scheduler for failed transfers using Laravel's task scheduling.

---

### Input Sanitization
While all inputs are validated, a `SanitizeInput` middleware stripping HTML tags was considered but deprioritised since this is a JSON API with no HTML rendering. It would be essential if any field value is ever rendered in a web view.

---

### API Versioning
Routes are currently unversioned (`/api/purchases`). A versioned structure (`/api/v1/purchases`) would allow breaking changes to be introduced without affecting existing consumers. Laravel supports this cleanly via route prefixes and separate controller namespaces.

---

### Refresh Token Rotation Strategy
The current JWT refresh extends the session silently. A stricter implementation would:
- Issue a one-time-use refresh token stored in Redis.
- Detect reuse of an already-rotated refresh token as a compromise signal and invalidate all sessions for that user.
- This is the OAuth 2.0 refresh token rotation pattern recommended for SPAs.

---

### Pagination on Achievements
The `GET /api/users/{user}/achievements` endpoint returns all achievements in a single response. For users with long histories this should be paginated with cursor-based pagination (more efficient than offset for large datasets).

---

### Test Coverage Gaps
Due to time constraints the following test scenarios were not covered:
- Concurrent purchase processing (race condition on `processed_for_loyalty`).
- Paystack webhook replay attacks.
- Queue job failure and retry behaviour end-to-end.
- `HandleBadgePromotion` 2-second delay race condition under parallel workers.

---

### Database Optimisation
- A read replica could be introduced for the admin `GET /api/admin/users/achievements` query which scans the full users table.
- `loyalty_points` and `total_spent` are denormalised onto the `users` table for read performance — a background reconciliation job should periodically verify these against the source-of-truth tables to catch any drift.
