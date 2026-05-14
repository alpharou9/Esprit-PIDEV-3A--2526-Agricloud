# AgriCloud Deployment Guide

This guide explains how to deploy the Symfony AgriCloud project safely without committing real secrets or changing application code.

## Safe Deployment Rules

- Deploy from `master` or a dedicated deployment branch.
- Keep real credentials outside Git.
- Point the web server document root to `public/`.
- Back up the production database before running migrations.
- Run migrations once per release, not during every web request.
- Keep `var/` and `public/uploads/` writable by the web server.

## Production Requirements

- PHP 8.2 or newer
- Composer 2
- MySQL 8.0+ or MariaDB
- Required PHP extensions: `ctype`, `iconv`, `pdo_mysql`, `intl`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `opcache`
- Apache or Nginx, or a platform that can run PHP/Symfony apps
- HTTPS for Google OAuth, Stripe, and secure login cookies

## Environment Variables

Create production variables in the hosting dashboard, server environment, or a private `.env.local` file on the server.

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=replace_with_a_real_random_secret

DATABASE_URL="mysql://db_user:db_password@db_host:3306/agricloud?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=gmail://your-email@gmail.com:your-app-password@default
MAILER_FROM=your-email@gmail.com

HUGGINGFACE_API_TOKEN=
CURRENCY_API_URL=https://api.frankfurter.dev/v2

CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=

RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=

STRIPE_SECRET_KEY=sk_live_or_test_key

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback

JAVA_API_KEY=change_me_to_a_strong_shared_api_key
```

## Build Commands

Run these commands on the server after pulling the latest code:

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
```

## Database Deployment

Create the production database if it does not exist:

```bash
php bin/console doctrine:database:create --if-not-exists --env=prod
```

Before migrations, make a backup:

```bash
mysqldump -u db_user -p agricloud > backup-agricloud.sql
```

Run migrations:

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

If the hosting provider does not allow CLI migrations, import `database/agricloud.sql` through phpMyAdmin, then apply any later migration SQL manually with care.

## File Permissions

The web server must be able to write to:

```text
var/
public/uploads/
```

On Linux VPS hosting, a common setup is:

```bash
chmod -R ug+rwX var public/uploads
```

Use the correct web server user for your host, such as `www-data`, `apache`, or the cPanel account user.

## Apache Virtual Host Example

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/agricloud/public

    <Directory /var/www/agricloud/public>
        AllowOverride All
        Require all granted
        FallbackResource /index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/agricloud_error.log
    CustomLog ${APACHE_LOG_DIR}/agricloud_access.log combined
</VirtualHost>
```

Enable HTTPS with Certbot or your hosting provider's SSL tool.

## Nginx Server Block Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/agricloud/public;

    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## Railway, Render, or Nixpacks Platforms

The repository includes `nixpacks.toml`, which installs PHP dependencies, warms the production cache, and starts the app with PHP's built-in server.

Set these variables in the platform dashboard:

```text
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=...
DATABASE_URL=...
MAILER_DSN=...
MAILER_FROM=...
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://your-deployed-domain/auth/google/callback
STRIPE_SECRET_KEY=...
```

After the first deploy, run migrations from the platform shell or one-off command feature:

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## Shared Hosting or cPanel

For shared hosting:

1. Upload the project outside `public_html` when possible.
2. Set the domain document root to the Symfony `public/` directory.
3. If the host forces `public_html`, copy the contents of `public/` into `public_html` and adjust `index.php` paths carefully.
4. Run `composer install --no-dev --optimize-autoloader` locally or over SSH.
5. Upload `vendor/` only if Composer is unavailable on the server.
6. Create `.env.local` on the server with production credentials.
7. Import the database with phpMyAdmin if SSH access is not available.

## External Service Checklist

- Gmail SMTP: use a Gmail App Password, not the regular password.
- Google OAuth: update authorized redirect URI to the production callback URL.
- Stripe: use test keys for testing and live keys only when ready.
- reCAPTCHA: add the production domain in the reCAPTCHA console.
- Cloudinary: configure production cloud name, API key, and API secret.
- Hugging Face: configure the token only if AI features are enabled.

## Pre-Deployment Checklist

- `composer install` succeeds.
- `php bin/console cache:warmup --env=prod` succeeds.
- Database backup exists.
- Migrations were reviewed.
- `APP_ENV=prod` and `APP_DEBUG=0` are set.
- `APP_SECRET` is unique and private.
- Web root points to `public/`.
- `var/` and `public/uploads/` are writable.
- OAuth, Stripe, email, reCAPTCHA, and Cloudinary credentials match the production domain.

## Rollback Plan

If a deployment fails:

1. Revert to the previous Git commit or release folder.
2. Restore the latest database backup if migrations changed data/schema.
3. Clear the production cache:

```bash
php bin/console cache:clear --env=prod --no-debug
```

4. Check server logs and Symfony logs under `var/log/`.
