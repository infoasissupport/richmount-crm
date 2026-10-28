# Rich Mount ERP — Hostinger Deployment Guide

This guide provides step-by-step instructions for deploying the **Rich Mount ERP** Laravel backend on **Hostinger** (Shared Web Hosting, Cloud Hosting, or VPS).

---

## 1. Hosting Prerequisites

* **PHP Version**: 8.2 or 8.3 (configured via Hostinger **hPanel** &rarr; **Advanced** &rarr; **PHP Configuration**)
* **Required PHP Extensions**:
  * `pdo_mysql`, `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `tokenizer`, `xml`, `zip`
* **PHP Settings** (Recommended in `php.ini` or hPanel PHP Options):
  * `upload_max_filesize = 25M`
  * `post_max_size = 30M`
  * `memory_limit = 256M`
  * `max_execution_time = 120`
* **MySQL Version**: MySQL 8.0+ or MariaDB 10.6+
* **SSL Certificate**: Activate Let's Encrypt SSL in hPanel & enable **Force HTTPS**.

---

## 2. Deployment Architecture Options

### Option A: Custom Document Root (Recommended for Cloud / Business / VPS)
If your Hostinger plan allows customizing the Document Root:
1. Upload the entire project to: `/home/u123456/domains/richmount.ae/`
2. In **hPanel** &rarr; **Websites** &rarr; **Manage** &rarr; **Website Configuration**:
   * Change **Document Root** to: `public_html/public` (or point directly to `/public`)
3. No `.htaccess` hacks are needed; your application is strictly isolated.

---

### Option B: Shared Hosting with Fixed `public_html` (Symlink or Directory Split)

On standard shared plans where `public_html` is fixed:

#### Step 1: Upload Project Outside Web Root
Upload the repository files to a directory parallel to `public_html`, for example:
```
/home/u123456/richmount-crm/
```

#### Step 2: Symlink or Copy `public` to `public_html`
SSH into your Hostinger account and run:
```bash
# Backup existing public_html
rm -rf public_html

# Create symbolic link pointing public_html to public/
ln -s /home/u123456/richmount-crm/public /home/u123456/public_html
```

*Alternatively, if SSH symlinks are disabled:*
1. Move all files from `richmount-crm/public/*` directly into `public_html/`.
2. Edit `/home/u123456/public_html/index.php`:
   ```php
   // Change path from:
   require __DIR__.'/../vendor/autoload.php';
   $app = require_once __DIR__.'/../bootstrap/app.php';

   // To point to your application folder:
   require __DIR__.'/../richmount-crm/vendor/autoload.php';
   $app = require_once __DIR__.'/../richmount-crm/bootstrap/app.php';
   ```

---

### Option C: Root `.htaccess` Rewrite Method
If you upload the entire project directly inside `public_html`:
A pre-configured [**`.htaccess`**](file:///Users/aromalmanohar/Documents/Playground/richmount/richmount-crm/.htaccess) is included in the project root:
* Safely blocks external web access to `.env`, `/app`, `/config`, `/storage/logs`, etc.
* Routes all traffic through `public/index.php`.
* Passes `Authorization: Bearer` headers to PHP for Sanctum API tokens.

---

## 3. Production Environment Configuration (`.env`)

In your production directory, copy `.env.example` to `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

Configure production settings:
```ini
APP_NAME="Rich Mount ERP"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://erp.richmount.ae

# Database (MySQL 8 on Hostinger)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456_richmount_erp
DB_USERNAME=u123456_admin
DB_PASSWORD=YourStrongDatabasePassword123!

# Session, Cache, & Queue (Reliable database driver on shared hosting)
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

# Zoho Books Integration (Set to false when live credentials are active)
ZOHO_CLIENT_ID=1000.YOUR_ZOHO_CLIENT_ID
ZOHO_CLIENT_SECRET=your_zoho_client_secret
ZOHO_REFRESH_TOKEN=1000.your_zoho_refresh_token
ZOHO_ORGANIZATION_ID=your_zoho_org_id
ZOHO_MOCK_DRIVER=false
```

---

## 4. File Permissions & Storage Linking

Run the following commands via Hostinger SSH terminal:
```bash
cd /home/u123456/richmount-crm

# Set permissions for web server writable folders
chmod -R 775 storage bootstrap/cache

# Create public symlink for uploaded receipts, client PO files, and PDFs
php artisan storage:link
```

---

## 5. Database Migration & Initial Seeding

Run database migrations to initialize all tables (Branches, Roles, Users, Leads, Quotations, Projects, Invoices, Expenses, Transfers, Zoho Logs):
```bash
php artisan migrate --force

# Seed default administrator, branches (Dubai, Abu Dhabi, Sharjah), and default roles:
php artisan db:seed --force
```

---

## 6. Performance Caching Commands

Always run these commands after code updates or deployments:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

*(To clear caches during maintenance: `php artisan optimize:clear`)*

---

## 7. Automated Cron Jobs in Hostinger hPanel

In **hPanel** &rarr; **Advanced** &rarr; **Cron Jobs**:

### 7.1 Laravel Task Scheduler
Run every minute (`* * * * *`):
```bash
/usr/bin/php /home/u123456/richmount-crm/artisan schedule:run >> /dev/null 2>&1
```

### 7.2 Background Queue Worker
To process Zoho Books synchronization and background emails:
* Set cron job to run every minute (`* * * * *`):
```bash
/usr/bin/php /home/u123456/richmount-crm/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```
*(On VPS or Cloud servers with Supervisor, run `php artisan queue:work --daemon --tries=3` instead)*

---

## 8. Hostinger Troubleshooting Checklist

| Issue | Root Cause | Solution |
| :--- | :--- | :--- |
| **HTTP 500 Internal Error** | Missing `.env` or permission denial | Run `chmod -R 775 storage bootstrap/cache` and verify `php artisan key:generate`. |
| **Blank White Screen** | PHP error suppressed because `APP_DEBUG=false` | Check `/storage/logs/laravel.log` or temporarily set `APP_DEBUG=true`. |
| **Sanctum Returns 401 on Mobile API** | Hostinger Apache stripping `Authorization` header | Confirm `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` is in root and `public/.htaccess`. |
| **Receipt Upload Fails (413 Payload Too Large)** | PHP upload limit exceeded | Increase `upload_max_filesize` to `25M` in hPanel PHP Options. |
| **Missing CSS/JS or Logo Images** | Broken storage symlink | Run `php artisan storage:unlink && php artisan storage:link`. |
