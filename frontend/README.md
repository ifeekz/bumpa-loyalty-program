# Loyalty Service — Frontend

React + Vite SPA for the loyalty program. Serves both the customer dashboard and the admin panel from a single codebase with role-based route protection.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | React 18 + Vite |
| Language | TypeScript |
| Styling | Tailwind CSS + Shadcn UI |
| Data fetching | TanStack Query v5 |
| HTTP client | Axios |
| Routing | React Router v6 |
| Forms | React Hook Form + Zod |

---

## Prerequisites

- Node.js 18+
- Backend running on `http://localhost:8000`

---

## Setup

```bash
cp .env.example .env
pnpm install
pnpm run dev
```

App runs at `http://localhost:3000`. API calls are proxied to `http://localhost:8000` via the Vite dev proxy — no CORS issues in development.

---

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `VITE_API_URL` | Backend API base URL | `/api` (proxied in dev) |

In production set `VITE_API_URL=https://api.yourapp.com/api`.

---

## Project Structure

```
src/
├── api/            # Axios API call functions (auth, loyalty, purchases, admin)
├── components/
│   ├── auth/       # RouteGuards (ProtectedRoute, AdminRoute, GuestRoute)
│   ├── layout/     # AppLayout (customer), AdminLayout
│   ├── shared/     # PageLoader, Skeleton, StatCard, BadgePill, ErrorBoundary
│   └── ui/         # Shadcn UI primitives (Button, Input, Card, etc.)
├── hooks/          # useApi (TanStack Query hooks), useDebounce
├── lib/            # apiClient (Axios + interceptors), tokenStorage
├── pages/
│   ├── auth/       # LoginPage, RegisterPage
│   ├── customer/   # DashboardPage, AchievementsPage, PurchasesPage
│   └── admin/      # AdminUsersPage, AdminUserDetailPage
├── router/         # Route definitions (lazy-loaded, code-split)
├── store/          # AuthContext (login, register, logout state)
├── types/          # Global TypeScript types
└── utils/          # cn(), formatNaira(), formatPoints(), formatDate()
```

---

## Authentication Flow

```
Login / Register
  └─► POST /api/auth/login
  └─► JWT extracted from Authorization header (Axios interceptor)
  └─► Token stored in localStorage via tokenStorage
  └─► User stored in localStorage + AuthContext

Every request
  └─► Axios request interceptor attaches Bearer token

401 Token expired
  └─► Axios response interceptor: silent POST /api/auth/refresh
  └─► New token stored, original request retried
  └─► If refresh fails → localStorage cleared → redirect /login

Logout
  └─► POST /api/auth/logout (blacklists token on server)
  └─► localStorage cleared
  └─► Redirect /login
```

---

## Route Protection

| Route | Guard | Redirects to |
|---|---|---|
| `/login`, `/register` | `GuestRoute` | `/dashboard` or `/admin` if already logged in |
| `/dashboard`, `/purchases`, `/achievements` | `ProtectedRoute` | `/login` if unauthenticated |
| `/admin/*` | `AdminRoute` | `/login` if unauthenticated, `/dashboard` if customer |

The `AdminRoute` guard is a UI-level guard only. The backend `AdminMiddleware` enforces the same check at the API level — a customer who bypasses the frontend guard will still receive `403 Forbidden` from the API.

---

## Pages

### Customer
- **Dashboard** `/dashboard` — points, badge progress, recent achievements, simulated unlock animation
- **Achievements** `/achievements` — all achievements with locked/unlocked state
- **Purchases** `/purchases` — purchase history, initiate Paystack payment

### Admin
- **Users** `/admin` — searchable, filterable paginated user list
- **User detail** `/admin/users/:id` — full profile, achievements, recent purchases, cashback summary

---

## Running in Production

```bash
pnpm run build
```

Outputs to `dist/`. Serve with any static file server. Ensure your server redirects all routes to `index.html` for client-side routing.
