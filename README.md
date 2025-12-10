# Order Exchange Mini Engine (Laravel + Vue)

Limit-order exchange with real-time Pusher updates, Laravel API, and Vue 3 front-end.

## Prerequisites
- PHP 8.2+, Composer
- Node 18+ (with npm)
- MySQL/PostgreSQL
- Pusher account (Channels)

## 1. Clone & install
```bash
git clone git@github.com:VincentLau-1031/Order-Exchange-Mini.git
cd Order-Exchange
composer install
npm install
```

## 2. Environment
```bash
cp .env.example .env
php artisan key:generate
```
Set database credentials in `.env`, then set Pusher + broadcast:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster

DB_CONNECTION=pgsql,mysql
DB_HOST=your_db_host
DB_PORT=your_db_port
DB_DATABASE=your_database
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

```
For SPA auth (Sanctum), add your front-end host to:
```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

## 3. Database
```bash
php artisan migrate
```

## 4. Run dev servers (two terminals)
**Terminal A (API + queue if needed):**
```bash
php artisan serve
```
If you later add queued jobs, run:
```bash
php artisan queue:work
```

**Terminal B (frontend / Vite):**
```bash
npm run dev
```

## 5. API quick refs
- `POST /api/register` → {name, email, password, password_confirmation}
- `POST /api/login` → {email, password}
- `POST /api/logout`
- `GET /api/profile`
- `GET /api/orders?symbol=BTC&status=1&side=buy`
- `POST /api/orders` → {symbol: BTC|ETH, side: buy|sell, price, amount}
- `POST /api/orders/{id}/cancel`

Auth: use Sanctum token from login/register response as `Authorization: Bearer {token}`.

## 6. Frontend pages
- `/login`, `/register`
- `/trading` (order form, orderbook, wallet, orders list) with live updates via Pusher.

## 7. Real-time
- Ensure Pusher env vars are set.
- Private channel pattern: `private-user.{id}`; event: `order.matched`.

