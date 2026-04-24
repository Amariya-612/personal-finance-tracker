# 💰 Finance Tracker

A production-ready Personal Finance Tracker built with PHP, MySQL, Bootstrap 5, and Chart.js.

---

## 🚀 Quick Setup (XAMPP / WAMP)

### Step 1 — Copy project files

Place the `finance-tracker/` folder inside your web root:

- **XAMPP:** `C:/xampp/htdocs/finance-tracker/`
- **WAMP:**  `C:/wamp64/www/finance-tracker/`

---

### Step 2 — Configure database credentials

Open `config/database.php` and update if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // your MySQL password
define('DB_NAME', 'finance_tracker');
```

Also update `APP_URL` in `config/config.php`:

```php
define('APP_URL', 'http://localhost/finance-tracker');
```

---

### Step 3 — Download vendor libraries

Visit in your browser (or run via CLI):

```
http://localhost/finance-tracker/install_vendors.php
```

This downloads Bootstrap 5, Bootstrap Icons, and Chart.js into `assets/vendor/`.

> **Delete `install_vendors.php` after running it.**

---

### Step 4 — Initialize the database

Visit:

```
http://localhost/finance-tracker/database/init.php
```

Or via CLI:

```bash
php database/init.php
```

Expected output:
```
✔ Database 'finance_tracker' ready.
✔ Table 'users' ready.
✔ Table 'categories' ready.
✔ Table 'transactions' ready.
✔ Table 'budgets' ready.
✅ Database initialisation complete.
```

---

### Step 5 — Seed sample data

Visit:

```
http://localhost/finance-tracker/database/seed.php
```

Or via CLI:

```bash
php database/seed.php
```

Expected output:
```
✔ Demo user created  (email: demo@financetracker.com  password: demo1234)
✔ Default categories ready.
✔ Sample transactions inserted (21 records).
✔ Sample budgets inserted.
✅ Seeding complete.
```

---

### Step 6 — Open the app

```
http://localhost/finance-tracker/
```

---

## 🔑 Default Login Credentials

| Field    | Value                        |
|----------|------------------------------|
| Email    | demo@financetracker.com      |
| Password | demo1234                     |

---

## 📁 Project Structure

```
finance-tracker/
├── assets/
│   ├── css/style.css          # Custom styles
│   ├── js/app.js              # UI interactions
│   ├── js/charts.js           # Chart.js initialization
│   └── vendor/                # Bootstrap, Chart.js (after install)
├── config/
│   ├── database.php           # PDO connection
│   └── config.php             # App constants & session
├── includes/
│   ├── header.php / footer.php
│   ├── navbar.php / sidebar.php
│   └── auth_check.php
├── auth/                      # Login, register, logout
├── dashboard/                 # Main dashboard
├── transactions/              # CRUD for transactions
├── categories/                # CRUD for categories
├── budgets/                   # Budget management
├── reports/                   # Monthly, yearly, export
├── api/                       # JSON endpoints for charts
├── models/                    # DB layer (User, Transaction, Category, Budget)
├── controllers/               # Business logic
├── utils/                     # functions.php, validator.php
├── database/
│   ├── init.php               # Create tables
│   └── seed.php               # Insert sample data
└── install_vendors.php        # Download Bootstrap & Chart.js
```

---

## ✨ Features

- **Authentication** — Register, login, logout with secure sessions and bcrypt passwords
- **Dashboard** — Summary cards, pie chart (expenses by category), bar chart (6-month trend)
- **Transactions** — Add, edit, delete, filter, paginate
- **Categories** — Global defaults + custom user categories with icon & color picker
- **Budgets** — Set monthly budgets per category with progress bars
- **Reports** — Monthly & yearly reports with charts, CSV export
- **API** — `/api/get_chart_data.php` and `/api/get_summary.php` for AJAX chart data
- **Security** — PDO prepared statements, CSRF tokens, XSS output escaping, session hardening

---

## 🔒 Security Notes

- All DB queries use PDO prepared statements (no SQL injection)
- All output is escaped with `htmlspecialchars()` via the `e()` helper
- CSRF tokens protect all state-changing forms
- Sessions are regenerated on login to prevent fixation
- Set `'secure' => true` in `config/config.php` when deploying over HTTPS

---

## 🛠 Tech Stack

| Layer      | Technology              |
|------------|-------------------------|
| Backend    | PHP 8.x (procedural + OOP models) |
| Database   | MySQL 5.7+ / MariaDB    |
| Frontend   | Bootstrap 5.3, Bootstrap Icons |
| Charts     | Chart.js 4.x            |
| Auth       | PHP Sessions + bcrypt   |
