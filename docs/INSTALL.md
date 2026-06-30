# Installation Guide — Palghar LIVE News Portal

> A step-by-step guide to setting up the Palghar LIVE news portal on a local development machine or shared hosting.

---

## Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 7.4+ | 8.2+ |
| MySQL | 5.7+ | 8.0+ |
| Web Server | Apache / Nginx | Apache + mod_rewrite |
| GD Extension | Required | With WebP + AVIF |
| PDO Extension | Required | With PDO MySQL |
| mbstring Extension | Required | — |
| openssl Extension | Required | — |

---

## Local Setup (XAMPP)

### 1. Clone the repository

```bash
git clone https://github.com/Aryan750m/palghar-live.git
cd palghar-live
```

### 2. Copy it into the XAMPP htdocs directory

```
C:\xampp\htdocs\news-channel\
```

### 3. Create a MySQL Database

Open phpMyAdmin (`http://localhost/phpmyadmin`) and create a database named `palghar_live`.

### 4. Configure the database connection

Edit `config/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'palghar_live');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 5. Import the schema

Run the SQL schema file to create all required tables:

```bash
mysql -u root palghar_live < schema.sql
```

Or import via phpMyAdmin → Import → `schema.sql`.

### 6. Seed the database

Visit this URL in your browser to seed sample data:

```
http://localhost/news-channel/seed_remote.php
```

> ⚠️ Delete `seed_remote.php` after seeding on production!

### 7. Access the application

- **Homepage**: `http://localhost/news-channel/`
- **Admin Panel**: `http://localhost/news-channel/admin/login.php`
- **Default credentials**: `admin` / `admin123`

---

## File Permissions

Ensure these directories are writable by the web server:

```bash
chmod 755 uploads/
chmod 755 uploads/news/
chmod 755 logs/
chmod 755 assets/css/
chmod 755 assets/js/
```

---

## PSR-4 Autoloading

The project uses a custom PSR-4 autoloader at `app/autoload.php`. If you have Composer installed, you can also run:

```bash
composer install
```

This will generate `vendor/autoload.php` which is loaded automatically.

---

## Configuration

All configuration is centralized in `app/Config/`:

| File | Purpose |
|---|---|
| `app.php` | App name, URL, debug mode, timezone |
| `database.php` | DB credentials and connection settings |
| `cache.php` | In-memory caching configuration |
| `seo.php` | SEO defaults, social URLs, schema data |
| `security.php` | CSRF, CSP, rate-limiting, session settings |

Update `app/Config/app.php` with your production URL:

```php
return [
    'name'  => 'Palghar LIVE',
    'url'   => 'https://your-domain.com',
    'debug' => false, // Always false in production!
    ...
];
```

---

## Changing the Admin Password

1. Log in to the admin panel
2. Go to **Settings & Logs**
3. Use the password change form

Or via MySQL:

```sql
UPDATE users SET password = '$2y$12$HASH_HERE' WHERE username = 'admin';
```

Generate a bcrypt hash using PHP:

```php
echo password_hash('your_new_password', PASSWORD_BCRYPT, ['cost' => 12]);
```

---

## PWA / Service Worker

The PWA manifest and service worker are at:
- `manifest.json`
- `service-worker.js`

These enable offline reading and "Add to Home Screen" on mobile.

---

## Troubleshooting

| Issue | Solution |
|---|---|
| Blank page | Enable PHP error display; check `logs/application.log` |
| DB connection failed | Verify `config/db.php` credentials |
| Images not uploading | Check `uploads/news/` is writable (chmod 755) |
| CSS not loading | Run asset compilation or check `assets/css/style.min.css` |
| 403 on admin | Clear browser cache; check session cookie settings |
