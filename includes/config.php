<?php
/**
 * CivicScan – Configuration
 * Empowering Your Vote
 */

// ─── Environment ────────────────────────────────────────────────────────────
define('APP_NAME',    'CivicScan');
define('APP_TAGLINE', 'Empowering Your Vote');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/civicscan'); // change for production
define('APP_ROOT',    dirname(__DIR__));

// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'voter_list_management');
define('DB_USER',     'root');      // change for production
define('DB_PASS',     '');          // change for production
define('DB_CHARSET',  'utf8mb4');

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME',    'civicscan_sess');
define('SESSION_LIFETIME', 7200);   // 2 hours

// ─── Security ────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', '_csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// ─── File Upload ─────────────────────────────────────────────────────────────
define('UPLOAD_DIR',      APP_ROOT . '/uploads/pdfs/');
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50 MB
define('UPLOAD_ALLOWED',  ['application/pdf']);

// ─── Pagination ──────────────────────────────────────────────────────────────
define('PER_PAGE', 25);

// ─── Mail (configure SMTP in production) ─────────────────────────────────────
define('MAIL_HOST',     'smtp.mailtrap.io');
define('MAIL_PORT',     587);
define('MAIL_USER',     '');
define('MAIL_PASS',     '');
define('MAIL_FROM',     'noreply@civicscan.in');
define('MAIL_FROM_NAME', 'CivicScan');

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Kolkata');

// ─── Error Reporting (set to 0 in production) ────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);
