# LaraMyAdmin ⚡

A modern, beautiful, and developer-friendly database management package for **Laravel** (a state-of-the-art alternative to phpMyAdmin, Adminer, and TablePlus).

Manage all your connected databases (MySQL, PostgreSQL, SQLite, SQL Server), dynamically connect to external databases, browse & edit data, design schemas, and execute raw SQL queries right from a sleek, dark-mode web studio.

---

## ✨ Features

- **🌐 Multi-Database Connections**:
  - Auto-discovers all connections configured in Laravel (`config/database.php`).
  - Connect dynamically to any database on the fly without touching `.env` or configuration files.
  - One-click connection switcher in the navigation bar.
- **📊 Interactive Data Browser (CRUD)**:
  - Tabular data viewer with sorting, pagination, and multi-condition query filter builder.
  - Insert new rows with type-aware input controls (JSON, dates, booleans, null toggles).
  - Inline row editing, duplication/cloning, and bulk record deletion.
- **🛠️ Schema & Structure Designer**:
  - Column inspector (Types, Nullability, Defaults, Primary/Foreign Keys, Auto Increment).
  - Add, modify, and drop columns.
  - Manage indexes (PRIMARY, UNIQUE, INDEX).
  - Inspect foreign key relationships and cascade rules.
  - View raw table DDL (`SHOW CREATE TABLE`) with one-click copy.
- **💻 SQL Query Studio (Console)**:
  - Syntax-highlighted SQL editor with line numbers and shortcut execution (`Ctrl + Enter` / `Cmd + Enter`).
  - Query execution timer (in milliseconds) and affected rows counter.
  - Integrated `EXPLAIN` query performance analyzer.
  - Execution history.
- **📦 Export & Import**:
  - Export tables or full databases to SQL Dump, CSV, or JSON.
  - Import SQL dump files or raw SQL statements.
- **🔒 Security & Permissions**:
  - Read-only mode protection.
  - Custom authorization gates (`LaraMyAdmin::auth(...)`).

---

## 🚀 Installation

Install LaraMyAdmin via Composer:

```bash
composer require laramyadmin/laramyadmin
```

Optionally publish the configuration file:

```bash
php artisan vendor:publish --tag=laramyadmin-config
```

Access LaraMyAdmin in your browser:
```
http://your-app.test/laramyadmin
```

---

## ⚙️ Configuration (`config/laramyadmin.php`)

```php
return [
    // URI path for LaraMyAdmin
    'path' => env('LARAMYADMIN_PATH', 'laramyadmin'),

    // Middleware assigned to all routes
    'middleware' => [
        'web',
        \LaraMyAdmin\Http\Middleware\AuthorizeLaraMyAdmin::class,
    ],

    // Read-only mode (blocks destructive queries & mutations)
    'read_only' => env('LARAMYADMIN_READ_ONLY', false),

    // Enable / disable dynamic database connections via UI
    'allow_dynamic_connections' => env('LARAMYADMIN_ALLOW_DYNAMIC_CONNECTIONS', true),

    // Default pagination limit
    'default_limit' => 100,

    // Allowed environments
    'allowed_environments' => ['local', 'testing', 'development', 'staging', 'production'],
];
```

---

## 🔐 Authorization Gate (Production Protection)

In non-local environments, protect LaraMyAdmin by defining an authorization callback in your `AppServiceProvider` or `AuthServiceProvider`:

```php
use LaraMyAdmin\LaraMyAdmin;

public function boot()
{
    LaraMyAdmin::auth(function ($request) {
        return $request->user() && $request->user()->is_admin;
    });
}
```

---

## 🧪 Testing

Run package tests with PHPUnit / Pest:

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
