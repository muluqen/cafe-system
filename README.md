# Cafe System Frontend (Vue)

Vue 3 + Vite frontend scaffold for a cafe management backend in Laravel.

## Included entities (15)

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

## Quick start

```bash
npm install
npm run dev
```

Set `VITE_API_BASE_URL` to your Laravel API root:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000/api
```

Backend is expected at `http://127.0.0.1:8000`.

## API assumptions

- Each entity is exposed as `GET /api/<entity_name>`
- Optional search support uses query param `?search=...`
- Responses can be either:
  - raw array (`[]`)
  - Laravel resource pagination/object with `data` array (`{ data: [] }`)

## Next steps

- Add auth (Laravel Sanctum or JWT)
- Add create/edit/delete forms for each entity
- Add role-based UI restrictions
