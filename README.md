# Invoice Manager for Moving Company

A web-based invoice management system built for moving companies. It manages companies, drivers, and two invoice types — company invoices and driver invoices — with PDF export and CSV data export.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8+ |
| Database | MySQL 5.7+ / MariaDB |
| Frontend | Vanilla JavaScript, HTML5, CSS3 |
| PDF Generation | [html2pdf.js](https://ekoopmans.github.io/html2pdf.js/) (client-side) |
| DB Access | PDO with prepared statements |

No frameworks or build tools are required.

---

## Features

- **Dashboard** — at-a-glance stats (total companies, drivers, invoices, revenue) with a Recent Invoices feed sorted by date
- **Companies** — create, edit, and delete company records (name, address, city, phone, DOT #, MC #)
- **Drivers** — create, edit, and delete driver records (name, phone, license #)
- **Company Invoices (CI)** — invoices billed to a company; each line item captures job #, driver, customer, from/to location, cubic feet, rate, balance due, and new balance; 10% carrier fee applied automatically
- **Driver Invoices (DI)** — invoices billed to a driver; same line-item structure, with company reference instead of driver
- **View & PDF** — view any invoice in a formatted modal and download it as a PDF (landscape A4)
- **CSV Export** — export all data as a ZIP containing four CSV files: `companies.csv`, `drivers.csv`, `company-invoices.csv`, `driver-invoices.csv`
- **Pagination** — all invoice tables paginate at 30 rows per page
- **Authentication** — session-based login with bcrypt-hashed passwords; change password from any page via the header

---

## Project Structure

```
invoice-manager-for-moving-company/
├── api/
│   ├── change-password.php   # POST: update admin password
│   ├── clear.php             # POST: wipe all data (danger)
│   ├── companies.php         # CRUD for companies
│   ├── drivers.php           # CRUD for drivers
│   ├── export.php            # GET: download ZIP of CSVs
│   ├── inv-company.php       # CRUD for company invoices + line items
│   └── inv-driver.php        # CRUD for driver invoices + line items
├── assets/
│   ├── css/style.css         # All styles
│   └── js/
│       ├── app.js            # Page-specific logic
│       ├── data.js           # Shared data layer (API calls, global arrays)
│       └── utils.js          # Shared utilities (toast, esc, PDF, pagination)
├── config/
│   └── db.php                # DB credentials, PDO factory, jsonOut/jsonIn helpers
├── includes/
│   ├── auth.php              # Session guard for page routes
│   ├── auth-api.php          # Session guard for API routes (returns 401 JSON)
│   ├── header.php            # Shared HTML header + nav
│   └── footer.php            # Shared HTML footer + script tags
├── schema.sql                # Full database schema — run once to set up
├── create-admin.php          # One-time admin account creation (delete after use)
├── login.php                 # Login page
├── logout.php                # Session destroy + redirect
├── dashboard.php             # Dashboard overview
├── companies.php             # Companies management page
├── drivers.php               # Drivers management page
├── inv-company.php           # Company invoices page
└── inv-driver.php            # Driver invoices page
```

---

## Setup

### Requirements

- PHP 8.0 or higher with the `pdo_mysql` and `zip` extensions enabled
- MySQL 5.7+ or MariaDB 10.3+
- A web server (Apache, Nginx, or PHP's built-in server for local development)

### 1. Create the database

```bash
mysql -u root -p < schema.sql
```

This creates the `invoice_manager` database and all required tables. Alternatively, run the contents of `schema.sql` in phpMyAdmin.

### 2. Configure the database connection

Edit `config/db.php` and update the four constants to match your environment:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'invoice_manager');
```

### 3. Create the first admin account

Navigate to `create-admin.php` in your browser:

```
http://your-server/create-admin.php
```

Fill in a username and password (minimum 8 characters). The page is only accessible when no admin user exists yet.

> **Important:** Delete `create-admin.php` from your server immediately after creating the admin account.

### 4. Log in

Go to `login.php` (or the root URL, which redirects there) and sign in with the credentials you just created.

---

## Database Schema

```
companies
  id, name, address, city, phone, dot_number, mc_number, created_at

drivers
  id, first_name, last_name, phone, license, created_at

company_invoices
  id, company_id (FK), date, subtotal, carrier_fee, total, created_at

company_invoice_items
  id, invoice_id (FK), sort_order, job_number, driver_id,
  customer_name, from_location, to_location, cubic_feet, rate,
  balance_due, new_balance, remarks

driver_invoices
  id, driver_id (FK), date, subtotal, carrier_fee, total, created_at

driver_invoice_items
  id, invoice_id (FK), sort_order, job_number, company_id,
  customer_name, from_location, to_location, cubic_feet, rate,
  balance_due, new_balance, remarks

users
  id, username, password_hash, created_at
```

Invoice line items cascade-delete when their parent invoice is deleted. Company invoices cascade-delete when their company is deleted; driver invoices cascade-delete when their driver is deleted.

---

## Invoice Pricing

For both invoice types the totals are calculated as follows:

```
Job total   = cubic_feet × rate
Subtotal    = sum of all job totals
Carrier fee = subtotal × 10%
Total due   = subtotal + carrier fee
```

---

## API Endpoints

All endpoints require an authenticated session. Unauthenticated requests return `401 {"error":"Unauthorized"}`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `api/companies.php` | List all companies |
| POST | `api/companies.php` | Create company |
| PUT | `api/companies.php?id=N` | Update company |
| DELETE | `api/companies.php?id=N` | Delete company |
| GET | `api/drivers.php` | List all drivers |
| POST | `api/drivers.php` | Create driver |
| PUT | `api/drivers.php?id=N` | Update driver |
| DELETE | `api/drivers.php?id=N` | Delete driver |
| GET | `api/inv-company.php` | List all company invoices (includes line items) |
| POST | `api/inv-company.php` | Create company invoice |
| PUT | `api/inv-company.php?id=N` | Update company invoice |
| DELETE | `api/inv-company.php?id=N` | Delete company invoice |
| GET | `api/inv-driver.php` | List all driver invoices (includes line items) |
| POST | `api/inv-driver.php` | Create driver invoice |
| PUT | `api/inv-driver.php?id=N` | Update driver invoice |
| DELETE | `api/inv-driver.php?id=N` | Delete driver invoice |
| GET | `api/export.php` | Download ZIP of all data as CSV files |
| POST | `api/change-password.php` | Change the logged-in admin's password |
| POST | `api/clear.php` | Delete all companies, drivers, and invoices |

---

## Local Development

PHP's built-in server works for quick local testing:

```bash
cd invoice-manager-for-moving-company
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

Make sure your local MySQL instance is running and `config/db.php` points to it.

---

## Security Notes

- All database queries use PDO prepared statements — no raw string interpolation.
- Passwords are hashed with `bcrypt` via `password_hash()`.
- All API routes enforce session authentication and return JSON errors for unauthorized access.
- HTML output uses an `esc()` helper to prevent XSS.
- `create-admin.php` blocks access once any admin exists; delete it after first use.
- `api/clear.php` permanently deletes all data — restrict or remove it in production if not needed.
