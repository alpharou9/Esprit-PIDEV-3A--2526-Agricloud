# AgriCloud

AgriCloud is a Symfony web application for managing an agricultural platform with users, farms, fields, marketplace products, carts, orders, events, blog posts, notifications, and integration APIs.

## Features

- User registration, login, password reset, profile management, Google authentication, guest access, and face-auth endpoints
- Admin management for users and roles
- Farm and field management with approval/rejection workflow
- Marketplace with products, favorites, reviews, cart, checkout, order tracking, PDF exports, and Stripe payment support
- Event management with registrations, participant tracking, recommendations, calendar feeds, iCal export, and ticket/PDF support
- Blog module with posts, comments, AI-assisted summaries, chatbot support, and moderation
- Dashboard and notification endpoints
- REST-style integration APIs under `/api/integration`
- PHPUnit test suites and PHPStan configuration

## Tech Stack

- PHP 8.2+
- Symfony 6.4
- Doctrine ORM and Doctrine Migrations
- Twig
- MySQL or MariaDB
- KnpPaginatorBundle
- Dompdf
- Symfony Mailer
- Stripe Checkout integration
- PHPUnit and PHPStan

## Requirements

Before running the project, install:

- PHP 8.2 or newer
- Composer
- MySQL 8.0 or MariaDB
- Symfony CLI, optional but recommended

Make sure the PHP extensions required by Symfony and Doctrine are enabled, especially `ctype`, `iconv`, `pdo_mysql`, and `intl`.

## Installation

Clone the repository and install dependencies:

```bash
composer install
```

Create a local environment file:

```bash
cp .env .env.local
```

Update `.env.local` with your local database and service credentials. Do not commit real secrets.

```dotenv
APP_ENV=dev
APP_SECRET=change_me_to_a_random_secret

DATABASE_URL="mysql://root:password@127.0.0.1:3306/agricloud?serverVersion=8.0&charset=utf8mb4"

MAILER_DSN=null://null
MAILER_FROM=no-reply@example.com

HUGGINGFACE_API_TOKEN=
CURRENCY_API_URL=https://api.frankfurter.dev/v2

CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=

RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=

STRIPE_SECRET_KEY=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback

JAVA_API_KEY=
```

## Database Setup

Create the database:

```bash
php bin/console doctrine:database:create
```

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

If you prefer to import the included SQL dump instead, create the `agricloud` database and import:

```bash
mysql -u root -p agricloud < database/agricloud.sql
```

Optional fixtures and seed commands may be available depending on your local data needs:

```bash
php bin/console doctrine:fixtures:load
php bin/console app:seed:market-products
```

## Running the App

With Symfony CLI:

```bash
symfony server:start
```

Or with PHP's built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

Open:

```text
http://127.0.0.1:8000
```

## Main Routes

| Area | Path |
| --- | --- |
| Home | `/` |
| Login | `/login` |
| Register | `/register` |
| Dashboard | `/dashboard` |
| Profile | `/profile` |
| Farms | `/farms` |
| Fields | `/farms/{farmId}/fields` |
| Market | `/market` |
| Cart | `/market/cart` |
| Orders | `/market/orders` |
| Favorites | `/market/favorites` |
| Market quality page | `/market/quality` |
| Events | `/events` |
| Blog | `/blog` |
| Assistant | `/assistant` |
| Admin users | `/admin/users` |
| Admin roles | `/admin/roles` |
| Notifications | `/notifications` |

## Integration API

The project exposes API endpoints under `/api/integration`.

Common resources include:

- `/api/integration/users`
- `/api/integration/roles`
- `/api/integration/farms`
- `/api/integration/fields`
- `/api/integration/products`
- `/api/integration/shopping-cart`
- `/api/integration/orders`
- `/api/integration/events`
- `/api/integration/participations`
- `/api/integration/posts`
- `/api/integration/comments`
- `/api/integration/stats/dashboard`

Some API routes may require the configured `JAVA_API_KEY` or application-level authentication, depending on the controller.

## Quality Checks

Run all PHPUnit tests:

```bash
composer test:all
```

Run marketplace tests only:

```bash
composer test:market
```

Run PHPStan:

```bash
composer phpstan:all
```

Run Doctrine schema validation:

```bash
composer doctrine:doctor
```

Run the full QA suite:

```bash
composer qa:all
```

## Project Structure

```text
bin/                 Symfony console entry point
config/              Symfony configuration
database/            SQL dump and database helpers
docs/                Project notes and documentation
migrations/          Doctrine migrations
public/              Front controller, theme assets, uploads
src/Command/         Console commands
src/Controller/      Web and API controllers
src/DataFixtures/    Doctrine fixtures
src/Entity/          Doctrine entities
src/Form/            Symfony form types
src/Repository/      Doctrine repositories
src/Security/        Authentication and user checks
src/Service/         Business services and integrations
templates/           Twig templates
tests/               PHPUnit test suites
```

## Notes for Contributors

- Keep secrets in `.env.local`, not in committed files.
- Use Doctrine migrations for schema changes.
- Add or update tests when changing entity behavior, services, controllers, or checkout/order flows.
- Prefer existing Symfony services, forms, repositories, and templates before adding new patterns.
- Run `composer qa:all` before opening a pull request when possible.
