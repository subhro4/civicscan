<?php
/**
 * CivicScan – Head Layout Partial
 * Usage: include with $pageTitle set beforehand
 */
$pageTitle = $pageTitle ?? APP_NAME;
$user      = current_user();
$theme     = $user['theme_preference'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $theme === 'light' ? '' : 'dark' ?>" data-theme="<?= h($theme) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?> — <?= APP_NAME ?></title>
<meta name="description" content="<?= APP_TAGLINE ?>">
<link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">

<!-- Google Fonts: Syne + DM Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">

<!-- Tailwind CSS Play CDN -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        display: ['Syne', 'sans-serif'],
        body:    ['DM Sans', 'sans-serif'],
      },
      colors: {
        brand: {
          50:  '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        surface: {
          950: '#030712',
          900: '#0d1117',
          800: '#161b22',
          700: '#1c2333',
          600: '#21262d',
          500: '#30363d',
          400: '#484f58',
        }
      },
      backgroundImage: {
        'grid-pattern': "url(\"data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232563eb' fill-opacity='0.04'%3E%3Cpath d='M40 0H0v40h40V0zm-1 1H1v38h38V1z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E\")",
      }
    }
  }
}
</script>

<!-- App CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/app.css">
</head>
<body class="font-body bg-surface-900 text-slate-200 dark:bg-surface-900 dark:text-slate-200 light:bg-slate-50 light:text-slate-900 min-h-screen antialiased">
