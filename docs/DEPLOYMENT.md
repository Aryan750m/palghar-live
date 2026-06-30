# Deployment Guide — Palghar LIVE News Portal

> Instructions for deploying Palghar LIVE to Render.com (current live host) and Hostinger shared hosting.

---

## Live URLs

| Environment | URL |
|---|---|
| Production (Render) | https://palghar-live.onrender.com |
| GitHub Repository | https://github.com/Aryan750m/palghar-live |
| Remote Database | sql12.freesqldatabase.com (freesqldatabase.com) |

---

## Deploying to Render.com

### First-time Setup

1. Push your code to GitHub
2. Create a new **Web Service** on [Render](https://render.com)
3. Connect to the GitHub repository `Aryan750m/palghar-live`
4. Set the following:
   - **Environment**: `Docker`
   - **Build Command**: *(auto-detected via Dockerfile)*
   - **Root Directory**: `/`

### Environment Variables (Render Dashboard)

Set these in **Dashboard → Your Service → Environment**:

| Variable | Value |
|---|---|
| `DB_HOST` | `sql12.freesqldatabase.com` |
| `DB_NAME` | `sql12831909` |
| `DB_USER` | `sql12831909` |
| `DB_PASS` | `4iPM62DEpD` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |

### Automatic Deploys

Render auto-deploys on every push to the `main` branch. To trigger manually:

```bash
git add .
git commit -m "feat: your change description"
git push origin main
```

---

## Deploying to Hostinger Shared Hosting

### File Upload

1. Compress the project: `zip -r palghar-live.zip . --exclude ".git/*"`
2. Upload via Hostinger File Manager or FTP (FileZilla)
3. Extract to `public_html/` (or a subdirectory)

### Database Setup on Hostinger

1. Log in to Hostinger hPanel
2. Go to **Databases → MySQL Databases**
3. Create a new database and user
4. Import `schema.sql` using phpMyAdmin

### Update Config for Hostinger

Edit `app/Config/database.php`:

```php
return [
    'host'     => 'localhost',      // Hostinger uses localhost
    'database' => 'your_db_name',
    'username' => 'your_db_user',
    'password' => 'your_db_pass',
    'port'     => 3306,
];
```

And `app/Config/app.php`:

```php
return [
    'url'   => 'https://yourdomain.com',
    'debug' => false,
];
```

### .htaccess

The `.htaccess` file in the project root handles:
- Clean URL rewrites (`/news/123/title`)
- Asset caching headers
- Gzip compression
- Security headers fallback

Ensure `AllowOverride All` is enabled in your hosting plan (it is by default on Hostinger).

### File Permissions on Hostinger

```bash
chmod 755 uploads/
chmod 755 uploads/news/
chmod 755 logs/
chmod 755 assets/css/
chmod 755 assets/js/
```

---

## GitHub Actions CI/CD

The project includes three automated workflows in `.github/workflows/`:

| Workflow | Trigger | What it does |
|---|---|---|
| `phpstan.yml` | Push / PR to main | Static analysis (Level 5) |
| `phpcs.yml` | Push / PR to main | Code style checks (PSR-12) |
| `lighthouse.yml` | Push to main | Performance & accessibility audit |

### Viewing Workflow Results

Go to **GitHub → Actions** tab on the repository to see run results.

---

## Post-Deployment Checklist

- [ ] Verify homepage loads: `https://palghar-live.onrender.com`
- [ ] Verify admin login works: `/admin/login.php`
- [ ] Confirm breaking news ticker is visible and scrolling
- [ ] Confirm "Watch Live (YouTube)" button links to `@palgharlivenews`
- [ ] Check favicon is visible in browser tab
- [ ] Test contact inquiry form submission
- [ ] Test news article comment submission
- [ ] Open admin → Health Monitor → confirm all checks pass
- [ ] Verify sitemap is accessible: `/sitemap.php`
- [ ] Verify RSS feed works: `/feed.php`
- [ ] Check PWA manifest: `/manifest.json`
- [ ] Confirm HTTPS is active
- [ ] Delete `seed_remote.php` if present

---

## Rollback

To rollback to a previous version, find the last working commit hash:

```bash
git log --oneline -10
git revert <commit-hash>
git push origin main
```

Or in Render dashboard, use **Manual Deploy → Deploy specific commit**.

---

## Support

For technical issues, check:
1. `logs/application.log` — General errors
2. `logs/security.log` — Auth and CSRF events
3. `logs/sql.log` — Slow queries
4. Admin → Health Monitor — Server diagnostics
