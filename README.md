# Flash-Sale Checkout API (Laravel 12)

This project implements a flash-sale checkout system that ensures **correctness under high concurrency**, supports **temporary holds**, **orders**, and an **idempotent payment webhook**.

The system prevents overselling, handles out-of-order payment callbacks, and automatically expires holds.

---

## 🚀 Features & Invariants

### 🔹 Product
- One seeded product with a finite stock.
- `/api/products/{id}` returns always-correct available stock.
- Stock is never oversold due to DB row locking (`SELECT ... FOR UPDATE`).

### 🔹 Holds (Reservation)
- `POST /api/holds` creates a temporary 2-minute reservation.
- Creating a hold immediately reduces available stock for others.
- Holds auto-expire via **queued jobs** using the **database queue driver**.
- Expired holds restore the reserved stock.

### 🔹 Orders
- `POST /api/orders` creates an order linked to a valid, unexpired hold.
- Each hold can be used **once**.
- Order starts in `pending_payment`.

### 🔹 Payment Webhook (Idempotent)
- `POST /api/payments/webhook` updates order status:
  - `paid` on success  
  - `cancelled` on failure (and stock gets restored)
- Uses **idempotency keys**:
  - Prevents double-processing of the same payment event.
  - Safe against duplicate or delayed webhooks.

### 🔹 Concurrency Strategy
- MySQL `SELECT ... FOR UPDATE` ensures atomic stock updates.
- Laravel transactions prevent race conditions.
- Cache used for fast product reads without returning stale stock.

---

## 🗄️ Tech Stack
- **Laravel 12**
- **MySQL (InnoDB required)**
- **Database queue driver**
- Laravel Cache (Database/File)

---

## 📦 Installation & Setup

```bash
git clone (https://github.com/yara35/Flash-Sale-Checkout)
cd FLASH-SALE-CHECKOUT

composer install
cp .env.example .env

# Configure database in .env
php artisan migrate

# Configure queue driver
QUEUE_CONNECTION=database
php artisan queue:table
php artisan migrate

# Start queue worker
php artisan queue:work

```
---

## ▶️ Running the Project
```bash
#Start Laravel server
php artisan serve
#Start queue worker
php artisan queue:work
#🧪 Running Tests
php artisan test
```
---

## Automated tests included for:
- Parallel hold creation (no oversell)
- Hold expiry restores stock
- Webhook idempotency
- Webhook arriving before order creation

---

## 🧩 Assumptions:
- Only one product exists in the system (seeded).
-Stock availability = initial_stock – active_holds – paid_orders
-Hold lifetime = 2 minutes.
-All external payment providers send:
idempotency_key
order_id
status: success|failed
