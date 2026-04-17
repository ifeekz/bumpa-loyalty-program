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
# 2. Build and start containers
docker-compose up -d --build
```

> Wait for all containers to show **Started** before proceeding.

```bash
# 3. Install dependencies
docker-compose exec app composer install

# 4. Generate keys
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret

# 5. Run migrations
docker-compose exec app php artisan migrate

# 6. Seed the database (badges, achievements, admin user, sample customers)
docker-compose exec app php artisan db:seed
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

## Inspecting the Database

**Option 1 — MySQL CLI inside the container:**
```bash
docker-compose exec mysql mysql -u loyalty_user -psecret loyalty_db
```
Then run any SQL:
```sql
SHOW TABLES;
SELECT * FROM users LIMIT 5;
SELECT * FROM badges;
EXIT;
```

**Option 2 — GUI client (TablePlus, DBeaver, HeidiSQL):**

| Field | Value |
|---|---|
| Host | `127.0.0.1` |
| Port | `3307` |
| Database | `loyalty_db` |
| Username | `loyalty_user` |
| Password | `secret` |

Port `3307` is the host-mapped port — used to avoid conflicts with any local MySQL installation.

**Option 3 — Laravel Tinker (Eloquent queries):**
```bash
docker-compose exec app php artisan tinker
```
```php
>>> \App\Domain\Loyalty\Models\User::count();
>>> \App\Domain\Loyalty\Models\Badge::all();
>>> \App\Domain\Loyalty\Models\User::first();
```

**Option 4 — Check migration status:**
```bash
docker-compose exec app php artisan migrate:status
```

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

Given the assessment window, the following were considered but deferred.

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

This provides a tamper-evident history for compliance, dispute resolution, and debugging loyalty pipeline issues in production.

### Laravel Horizon
The current setup uses a plain `queue:work` process in a Docker container. In production, [Laravel Horizon](https://laravel.com/docs/horizon) would replace this with a real-time dashboard, automatic worker balancing, tagged jobs, and metrics retention for alerting.

### Notification System
Users have no visibility into loyalty events outside the API response. A notification layer would deliver email, push (via Laravel Echo — the events already implement `ShouldBroadcast`), and SMS via Termii for Nigerian users.

### Cashback Payout Improvements
- Store `recipient_code` on the user profile after first creation and reuse it.
- Introduce a `bank_accounts` table for multiple payout accounts.
- Add manual approval for large cashback amounts (fraud prevention).
- Retry scheduler for failed transfers via Laravel task scheduling.

### Input Sanitization
A `SanitizeInput` middleware was deprioritised since this is a JSON API with no HTML rendering. It would be essential if any field value is ever rendered in a web view.

### API Versioning
Routes are currently unversioned. A versioned structure (`/api/v1/`) would allow breaking changes without affecting existing consumers.

### Refresh Token Rotation
A stricter implementation would issue one-time-use refresh tokens stored in Redis, detecting reuse as a compromise signal and invalidating all sessions — the OAuth 2.0 pattern recommended for SPAs.

### Pagination on Achievements
The achievements endpoint returns all records in one response. Cursor-based pagination would be more efficient for users with long histories.

### Test Coverage Gaps
- Concurrent purchase processing (race condition on `processed_for_loyalty`).
- Paystack webhook replay attacks.
- Queue job failure and retry behaviour end-to-end.
- `HandleBadgePromotion` 2-second delay race condition under parallel workers.

### Database Optimisation
- A read replica for the admin users query which scans the full users table.
- Background reconciliation job to verify denormalised `loyalty_points` and `total_spent` against source-of-truth tables.
