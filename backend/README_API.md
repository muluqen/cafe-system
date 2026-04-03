# Cafe System API (Laravel)

This backend includes CRUD APIs for 15 entities:

- users
- restaurants
- menu_categories
- menu_items
- orders
- order_items
- order_status_history
- preferences
- ingredients
- inventory_transactions
- tables
- table_sessions
- shifts
- staff_shift_assignments
- payment_events

## Run

```bash
cd backend
php artisan migrate
php artisan serve
```

Base URL:

```text
http://127.0.0.1:8000/api
```

PostgreSQL env is configured for:

- database: `cafe_system_db`
- host: `127.0.0.1`
- port: `5432`
- username: `postgres`
- password: `postgres`

Use `?search=<text>` on list endpoints to filter by common text fields.

## Notes

- API routes are defined in `routes/api.php`.
- Each entity uses `Route::apiResource(...)`.
- There is a shared `BaseApiController` for consistent CRUD behavior.
