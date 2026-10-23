# RiverBird Letter — Document Management Portal

Private document portal for **RiverBird Digital Solutions Pvt Ltd**: clients, invoices (automatic `RB-MMYYNN` numbering), proposals, quotations, letters, PDF export, and audit logging. Built for **Hostinger Shared Hosting** (PHP 8.1+, MariaDB/MySQL).

Original reference Word files remain in [`doc/`](doc/) and are never modified by the app.

## Requirements

- PHP **8.1+** with extensions: `pdo_mysql`, `json`, `mbstring`, `fileinfo`, `gd` (recommended for Dompdf)
- MariaDB/MySQL 10.3+
- Composer (on server or deploy vendor via CI)
- HTTPS in production

## Local setup

1. Clone the repository and copy environment file:
   ```bash
   cp .env.example .env
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create database and configure `.env` (`DB_*`, `APP_URL`).
4. Run migrations:
   ```bash
   php database/migrate.php
   ```
5. Create the first administrator (only when no users exist):
   - **Browser:** visit `/setup`
   - **CLI:** `php bin/create-admin.php <username> <email> <password> "Full Name"`
6. Seed system templates (optional):
   ```bash
   php database/seed/seed.php
   ```
7. Import historical reference invoices (optional, preserves `RB2026010` and `RB-092602`):
   ```bash
   php database/seed/import_reference_data.php
   ```
8. Point the web server document root to **`public/`** (see Hostinger below).

## Running tests

```bash
composer test
# or
vendor/bin/phpunit
```

**Note:** PHPUnit tests for invoice formatting/calculations run without a database. Concurrency tests for `InvoiceNumberService::allocateForDate()` must be run on an environment with MySQL (staging/Hostinger).

Tests were **not executed** in the initial development environment (PHP CLI was unavailable locally). Run them after `composer install`.

## Project layout

| Path | Purpose |
|------|---------|
| `public/` | Web document root (`index.php`, assets) |
| `app/` | Application code (auth, documents, invoices, files) |
| `config/` | App and database configuration |
| `database/` | `schema.sql`, migrate script, seeds |
| `templates/` | A4 document render templates |
| `views/` | UI templates |
| `storage/` | Private uploads, generated PDFs, logs (not in Git) |
| `doc/` | Original reference `.docx` files (read-only source) |

## Hostinger deployment (GitHub)

1. Connect the private GitHub repo in hPanel → **Websites → Git**.
2. Set the deployment **document root** to the repository’s `public` folder. If Hostinger only allows `public_html`, either:
   - Configure the site to use `public` as the web root in hPanel, or
   - Copy/symlink `public/*` into `public_html` and adjust paths (document root must hit `public/index.php`).
3. On the server (SSH or hPanel terminal), from the project root:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   # Edit .env with production DB credentials and APP_URL=https://your-domain
   php database/migrate.php
   ```
4. Create admin via `/setup` or `php bin/create-admin.php`.
5. Ensure `storage/` is **outside** public web access. `storage/.htaccess` denies direct access; generated files are served only via authenticated PHP download routes.
6. Set `APP_DEBUG=false` in production.
7. Enable SSL (Let’s Encrypt) in hPanel.

**Important:** Git deploy may overwrite code but must **not** delete `storage/` or `.env`. Keep uploads and `.env` on the server outside the deploy target or use Hostinger “retain” paths if available.

## Private storage

Configure `STORAGE_PATH` in `.env` (default: `storage`). On Hostinger, if you can place storage above `public_html`, set an absolute path in `.env`:

```env
STORAGE_PATH=/home/u123456/domains/yourdomain.com/private_storage
```

## Backups

- **Database:** regular mysqldump via hPanel or cron.
- **Files:** backup `storage/documents` and `storage/uploads`.
- Restore: import SQL dump, restore storage directory, verify `.env`.

## Invoice numbering

New invoices receive numbers **`RB-MMYYNN`** only when **issued** (drafts do not consume numbers). Sequence resets each calendar month. Historical numbers from reference documents are imported as-is and are never renamed.

## Reference documents (`doc/`)

| File | Type |
|------|------|
| `Dermiiz_website_advance_invoice.docx` | Invoice `RB2026010` |
| `invoice_RB092602.docx` | Invoice `RB-092602` |
| `SMM_Proposal_Mano_Bakery.docx` | Proposal template source (Mano Bakery) |

Logo used in the UI is extracted from the invoice documents: `public/assets/images/riverbird-logo.png`.

## Troubleshooting

- **500 after deploy:** check PHP version ≥ 8.1, run `composer install`, verify `.env` DB settings.
- **PDF errors:** ensure `vendor/` exists and Dompdf can load DejaVu fonts (default).
- **Routes 404:** enable `mod_rewrite`; use `public/.htaccess`.
- **Login lockout:** wait for `LOGIN_LOCKOUT_MINUTES` or clear `login_attempts` table.

## Security

- CSRF on POST, session cookies `HttpOnly` / `Secure` (when HTTPS), password hashing via `password_hash()`.
- Role-based access: administrator, staff, viewer.
- No default passwords; first user via `/setup` only when the users table is empty.
