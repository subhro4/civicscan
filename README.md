# CivicScan — Voter List Management Platform
> **Empowering Your Vote** · v1.0.0

A secure, role-based internal voter data platform built with **PHP 8+**, **MySQL 8**, **Tailwind CSS**, and **Vanilla JS**.

---

## Quick Setup

### 1. Requirements
- PHP 8.1+
- MySQL 8.0+
- Apache with `mod_rewrite` enabled
- Composer (optional — not required for base setup)

### 2. Database
```sql
-- Import the schema file:
mysql -u root -p < database/civicscan.sql

-- Then insert your first admin user:
INSERT INTO users (role, name, email, phone, password_hash, status, theme_preference)
VALUES (
  'administrator',
  'Super Admin',
  'admin@civicscan.in',
  '9999999999',
  '$2y$12$REPLACE_WITH_PHP_PASSWORD_HASH',
  'active',
  'dark'
);
```
Generate a real hash in PHP:
```php
echo password_hash('YourPassword123!', PASSWORD_BCRYPT, ['cost' => 12]);
```

### 3. Configuration
Edit `includes/config.php`:
```php
define('APP_URL',  'http://localhost/civicscan');  // your base URL
define('DB_HOST',  'localhost');
define('DB_NAME',  'voter_list_management');
define('DB_USER',  'root');
define('DB_PASS',  '');
```

### 4. File Permissions
```bash
chmod 755 uploads/pdfs/
chown www-data:www-data uploads/pdfs/
```

### 5. Virtual Host (Apache)
```apache
<VirtualHost *:80>
    ServerName civicscan.local
    DocumentRoot /var/www/civicscan
    <Directory /var/www/civicscan>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## Project Structure
```
civicscan/
├── assets/
│   ├── css/app.css             # Custom styles
│   ├── js/app.js               # UI JavaScript
│   └── images/                 # Logos, favicon, icon (SVG)
├── database/
│   └── civicscan.sql           # Full MySQL schema
├── includes/
│   ├── config.php              # App constants & DB config
│   ├── db.php                  # PDO connection helpers
│   ├── auth.php                # Session, login, CSRF
│   ├── functions.php           # Utility functions
│   └── layout/                 # head, sidebar, topbar, flash partials
├── modules/
│   ├── auth/logout.php
│   ├── users/                  # User management (admin only)
│   ├── constituencies/         # Constituency CRUD
│   ├── voters/                 # State→District→Constituency→Part→Records + Search
│   ├── import/                 # PDF upload & batch tracking
│   └── settings/               # Profile, password, theme
├── uploads/pdfs/               # Uploaded voter list PDFs (writable)
├── index.php                   # Public landing page
├── login.php                   # Login
├── dashboard.php               # Main dashboard
└── .htaccess                   # Apache rules & security
```

---

## Modules

| Module | Path | Access |
|--------|------|--------|
| Landing Page | `/` | Public |
| Login | `/login` | Public |
| Dashboard | `/dashboard` | All Users |
| Voter Directory | `/modules/voters` | All Users |
| Voter Search | `/modules/voters/search` | All Users |
| PDF Import | `/modules/import` | All Users |
| Constituencies | `/modules/constituencies` | All Users |
| User Management | `/modules/users` | **Admin Only** |
| Settings | `/modules/settings` | All Users |

---

## Security Features
- `password_hash()` with bcrypt (cost 12) — no plain text passwords
- PDO prepared statements — SQL injection safe
- CSRF tokens on all state-changing forms
- Session-based auth with route guards
- Role checks server-side on every sensitive action
- File upload validation (MIME, extension, size, SHA256 deduplicate)
- Full audit log in `audit_logs` table
- `.htaccess` blocks direct access to `includes/` and sensitive files

---

## PDF Import Notes
The upload module queues PDFs. Actual extraction depends on the PDF type:
- **Text-based PDFs** — use `pdfparser/pdfparser` (Composer) or `pdftotext` (poppler-utils)
- **Scanned PDFs** — use `tesseract-ocr` + `thiagoalessio/tess_two`

Add a cron job or queue worker that runs extraction logic and updates the batch via:
```bash
* * * * * php /var/www/civicscan/modules/import/process.php >> /var/log/civicscan_import.log 2>&1
```

---

## Branding Assets
All SVGs are in `assets/images/`:
| File | Usage |
|------|-------|
| `favicon.svg` | Browser tab icon |
| `logo-icon.svg` | Square icon (sidebar, login) |
| `logo-dark.svg` | Full horizontal logo — dark backgrounds |
| `logo-light.svg` | Full horizontal logo — light backgrounds |

---

## License
Internal use only. Not for public distribution.

Built per SRS v1.0 · CivicScan · Empowering Your Vote
