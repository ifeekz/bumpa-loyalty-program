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

| Method | Endpoint | Description |
|---|---|---|
| GET | `/users/{id}/achievements` | Full loyalty profile |
| POST | `/purchases` | Initiate payment |
| GET | `/purchases` | Purchase history |

### Admin

| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/users/achievements` | All customers (paginated) |
| GET | `/admin/users/{id}` | Single user detail |

### Webhook

| Method | Endpoint | Description |
|---|---|---|
| POST | `/purchases/webhook` | Paystack webhook (public) |

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
