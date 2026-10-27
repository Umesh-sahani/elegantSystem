# Elegant System — Products & Orders

A small Laravel app for managing products and orders. Placing an order reduces stock, cancelling an order puts the stock back, and the inventory stays correct even when two requests hit the same product at the same time.

I focused on correctness and data integrity. The UI is kept simple (Blade + Bootstrap).

## Requirements

- PHP 8.3+
- Composer
- MySQL 8

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty MySQL database (the example `.env` uses `elegant_system`) and set your database details in `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then:

```bash
php artisan migrate --seed
php artisan serve
```

Open http://localhost:8000. The seeder creates two users, and the login page has a small "Demo accounts" box that fills the form for you:

| Email | Password |
|---|---|
| admin@example.com | 123456 |
| user@example.com | 123456 |

Products are not seeded. Add a few from the Products page first.

## Running the tests

```bash
php artisan test
```

By default the tests use SQLite in memory (see `phpunit.xml`), so they don't touch your data. To run the same tests on MySQL, create an empty test database and point the tests at it:

```bash
DB_CONNECTION=mysql DB_DATABASE=elegant_test php artisan test
```

On Windows PowerShell:

```powershell
$env:DB_CONNECTION="mysql"; $env:DB_DATABASE="elegant_test"; php artisan test
```

The tests are in `tests/Feature`:

- `OrderTest.php`
  - successful order (server-side total, duplicate rows merged, stock reduced)
  - insufficient stock
  - cancel and restore stock
  - cancelling twice doesn't restore stock twice
  - a completed order can't be cancelled
  - a failed order leaves no partial changes, including a failure in the middle of saving the items
- `ProductTest.php`: SKU from name, duplicate names, negative price/stock
- `ApiTest.php`: the public endpoints and their pagination

## How it works

### Products

A product has a name, SKU, price and stock. The SKU is generated from the name (`Wireless Mouse` becomes `WIRELESS-MOUSE`), and both the name and the SKU have a unique index. Price and stock are validated with `min:0`, and the columns are unsigned, so MySQL rejects a negative value even if it comes from somewhere other than the app.

### Placing an order

`OrderController@store` does the following inside one transaction:

1. Merge duplicate lines. If the same product is sent twice, the quantities are added together.
2. Lock the product rows with `lockForUpdate()`, always in product id order.
3. Check stock for every product first. If any of them is short, roll back and return a 422 that lists what's missing. Nothing has been written at this point.
4. Calculate the total from the prices in the database. A total sent by the client is ignored.
5. Create the order and its items, and decrement the stock.
6. Commit.

Each order item stores the `unit_price` at the time of the order, so changing a product's price later doesn't change old orders.

If something unexpected fails, the transaction is rolled back, the real error is written to `storage/logs/laravel.log`, and the user only gets "Something went wrong, please try again." (`Controller::serverError()`).

### Concurrent orders

Because the product rows are locked, a second order for the same product waits until the first one commits, and then reads the reduced stock. Stock can't go negative this way. Locking in id order avoids deadlocks when two orders contain the same products in a different order.

### Cancelling

`OrderController@cancel` locks the order row first, then checks the status. A cancelled order can't be cancelled again, and a completed order can't be cancelled. So if the cancel button is clicked twice, the second request sees the order is already cancelled, and the stock is restored only once.

### Order status

A new order is **Active**. An active order can be **Completed** (`OrderController@complete`) or **Cancelled** (`OrderController@cancel`, which also returns the stock). Completed and Cancelled are final: they can't be completed or cancelled again. Both actions lock the order row and check `Order::isActive()` on the server, so a disabled button forced on in the browser still gets refused.

### Live stock check

On the create order screen, each row asks the server for the price, the stock and the line total while you type (debounced, through `GET /order-check-stock`). The same product can be added in more than one row; the stock check then uses the total of those rows, and on save the server adds them into one order line. If the quantity is more than the stock, the field is marked red and cleared. This only helps the user; the real check is the one in `store`.

## Public API

Two read-only endpoints. They need no login and return 100 records per page (`?page=2` for the next page):

| Endpoint | Notes |
|---|---|
| `GET /api/users` | id, name, email, created_at |
| `GET /api/orders` | orders with their items; optional `?status=` (1 Active, 2 Completed, 3 Cancelled) |

They are public by request and only return safe fields (no passwords or tokens).

## Project structure

```
app/Http/Controllers/
    LoginController.php     login / logout
    HomeController.php      dashboard
    ProductController.php   product list, create, edit
    OrderController.php     order list, create, stock check, complete, cancel
    ApiController.php       public users / orders API
app/Models/                 User, Product, Order, OrderItem
database/migrations/        products, orders, order_items
resources/views/            Blade views (layout, products, orders)
routes/web.php              app routes (behind auth)
routes/api.php              public API
tests/Feature/              feature tests
```

## Assumptions

- Stock is reduced when the order is placed (Active), not when it's completed. Cancelling returns it.
- Products can't be deleted. It wasn't part of the task, and deleting a product that has orders would break the order history (the foreign key also prevents it).
- Renaming a product also changes its SKU, since the SKU comes from the name. Orders reference products by id, so existing orders are not affected.
- Money uses `decimal(12,2)`, and totals are rounded to 2 decimals on the server.
