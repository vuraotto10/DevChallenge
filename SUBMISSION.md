# Submission

## What I picked and why

I chose two Level 2 tasks:

- **#3 Seeder and factories** — realistic, resettable demo data.
- **#5 Basic API listing** — `GET /api/bookings?email=...` to list a client's bookings.

I kept the scope intentionally small: both tasks build directly on the
existing `Booking` model and `scheduled_at` column, without requiring a
larger schema change (e.g. introducing a `Hairdresser` model, which several
of the Level 1 tasks would need). That let me focus on correctness, tests,
and clean code rather than a bigger, riskier refactor.

## What I built

### 1. `BookingFactory` (`database/factories/BookingFactory.php`)
Generates a booking with a random name/email and a random weekday,
business-hours (08:00-16:00) `scheduled_at`, so it's usable both in tests
and in the seeder. When a specific slot matters (tests, seeding), callers
just pass an explicit `scheduled_at` to `create()`.

### 2. `BookingSeeder` (`database/seeders/BookingSeeder.php`)
- Clears existing bookings, then builds the full list of valid
  (weekday, business-hour) slots for the next 10 business days.
- Randomly fills ~40% of those slots, so the seeded calendar looks like a
  realistic, partially-booked schedule rather than empty or fully saturated.
- Because slots are constructed explicitly and taken without replacement,
  re-running the seeder can never violate the `scheduled_at` unique index —
  it's safe to reset/reseed as many times as you like.
- Wired into `DatabaseSeeder`, so `php artisan db:seed` (or
  `php artisan migrate:fresh --seed`) is all that's needed.

### 3. `GET /api/bookings?email=...` (`app/Http/Controllers/Api/BookingController.php`)
- Validates `email` (required, valid email format) and returns a `422`
  with a consistent `{ success, message, errors }` shape on failure.
- On success, returns `{ success: true, data: [...] }`, ordered
  chronologically, reusing the existing `date` / `hour` accessors on the
  `Booking` model for consistency with the rest of the app.
- Registered as a named route (`api.bookings.index`) in `routes/api.php`.

## Assumptions and trade-offs

- **Email matching is exact**, using the database's default collation. I
  didn't add explicit case-insensitive matching since the rest of the app
  (e.g. the booking form) doesn't do it either — kept it consistent rather
  than introducing a new convention in just one place.
- **No pagination** on the listing endpoint. Given the current one-client's-worth
  scope, a plain array was simpler and matches "basic API listing" in the
  task description. Adding `paginate()` instead of `get()` would be a
  small, backward-incompatible follow-up if needed.
- **The seeder fills ~40% of slots** (a constant in the class) rather than
  100%, so the data looks realistic for manual testing/demoing the booking
  form (there are still free slots to book). This is an arbitrary but
  documented choice.
- I did **not** touch the existing `HairdresserSeeder` (present but unused)
  or introduce a `hairdresser_id` — out of scope for the two tasks I picked.

## How to run and test

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure DB_* in .env to point at a real database (the project's
# existing migration for `bookings` uses MySQL-specific SQL, so MySQL
# is the safest choice — e.g. via `php artisan sail:install` or a local
# MySQL instance).

php artisan migrate:fresh --seed
php artisan serve
```

Try the new endpoint (after seeding, pick any email `php artisan tinker`
shows you in the `bookings` table, or use the booking form at `/` to
create one yourself):

```bash
curl "http://127.0.0.1:8000/api/bookings?email=someone@example.com"
curl "http://127.0.0.1:8000/api/bookings"                # -> 422, email required
curl "http://127.0.0.1:8000/api/bookings?email=not-valid" # -> 422, invalid email
```

Run the automated tests:

```bash
php artisan test
# or just the new ones:
php artisan test --filter=BookingListTest
php artisan test --filter=BookingSeederTest
```
