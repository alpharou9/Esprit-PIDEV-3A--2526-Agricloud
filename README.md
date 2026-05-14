# AgriCloud - Smart Farm Management System

AgriCloud is a full-featured Symfony web application for managing farms, products, blog posts, events, users, notifications, orders, and integration APIs in a unified agricultural ecosystem.

The platform connects administrators, farmers, customers, and guests through one web interface with role-based access, marketplace workflows, farm validation, event participation, blog moderation, PDF exports, email notifications, AI-assisted features, and REST-style integration endpoints.

## Team

| Module | Developer | Main Entities |
| --- | --- | --- |
| Module 1 - User Management | Farouk | User, Role, Notification |
| Module 2 - Farm Management | Shahed | Farm, Field |
| Module 3 - Market Management | Ghada | Product, Order, CartItem, Favorite, Review |
| Module 4 - Blog Management | Rania | Post, Comment |
| Module 5 - Event Management | Ayman | Event, Participation |

## Technology Stack

| Layer | Technology |
| --- | --- |
| Language | PHP 8.2+ |
| Framework | Symfony 6.4 |
| Template Engine | Twig |
| ORM | Doctrine ORM |
| Database | MySQL 8.0+ or MariaDB |
| Forms and Validation | Symfony Form, Validator |
| Authentication | Symfony Security |
| Email | Symfony Mailer, Gmail SMTP compatible |
| Payments | Stripe Checkout |
| PDF Export | Dompdf |
| Pagination | KnpPaginatorBundle |
| AI Services | Hugging Face compatible service integration |
| Image Hosting | Cloudinary integration |
| OAuth | Google OAuth 2.0 |
| Quality Tools | PHPUnit, PHPStan, Doctrine schema validation |

## Features

### Module 1 - User Management (Farouk)

- Login and logout with Symfony Security
- User registration with role assignment
- Forgot password and reset-password workflow by email
- Google OAuth login/register flow
- Guest login for marketplace browsing
- Profile display and profile editing
- Profile PDF export
- Admin user CRUD
- Admin role CRUD
- Block/unblock users
- Notifications with unread count, mark-as-read, and read-all actions
- Dashboard page for authenticated users
- API endpoints for users, roles, login, and dashboard statistics

### Module 2 - Farm Management (Shahed)

- Farm CRUD
- Farm approval workflow: pending, approved, rejected, inactive
- Field management nested under farms
- Farmer access to manage owned farms and fields
- Admin access to review, approve, and reject farms
- Farm status emails through the email service
- Farm and field integration APIs
- Farm/field insight service for agricultural recommendations

### Module 3 - Market Management (Ghada)

- Product CRUD with approval workflow
- Marketplace product listing and product details
- Product image uploads and Cloudinary-compatible service support
- Product reviews
- Favorite products
- Shopping cart: add item, update quantity, remove item, clear cart
- Checkout with full shipping details
- Stripe Checkout support
- Order creation, order details, order cancellation, and order status changes
- Order confirmation emails
- Order PDF export
- Farmer product management and incoming order management
- Admin product approval/rejection and order management
- Market quality page
- Marketplace API endpoints for products, cart, orders, and dashboard stats
- Focused marketplace tests and QA scripts

### Module 4 - Blog Management (Rania)

- Blog post CRUD
- My posts page for authors
- Public blog listing and post detail pages
- Comment submission
- Admin comment moderation: approve, reject, delete
- Comment moderation service
- AI-assisted blog chatbot endpoint
- AI-assisted post summary endpoint
- Text-to-speech compatible blog service hooks
- Blog and comment integration APIs

### Module 5 - Event Management (Ayman)

- Event CRUD
- Public event listing and event details
- Event registration and cancellation
- Participant management
- Mark participant as attended
- My events page
- My registrations page
- Event capacity handling
- Event recommendation service
- Calendar API and calendar feed
- iCal export
- Event ticket context builder and PDF ticket template
- Event and participation integration APIs

## Role Permissions

| Feature | Admin | Farmer | Customer | Guest |
| --- | --- | --- | --- | --- |
| Users / Roles CRUD | Yes | No | No | No |
| Dashboard | Yes | Yes | Yes | No |
| Profile editing | Yes | Yes | Yes | No |
| Farm management | Yes | Yes | No | No |
| Field management | Yes | Yes | No | No |
| Product browsing | Yes | Yes | Yes | Yes |
| Product management | Yes | Yes | No | No |
| Product approval | Yes | No | No | No |
| Shopping cart | Yes | Yes | Yes | Yes |
| Orders | Yes | Yes | Yes | No |
| Blog view | Yes | Yes | Yes | Yes |
| Blog posts | Yes | Yes | Yes | No |
| Blog comments | Yes | Yes | Yes | No |
| Comment moderation | Yes | No | No | No |
| Events | Yes | Yes | Yes | Yes |
| Event management | Yes | Yes | No | No |
| Event registration | Yes | Yes | Yes | No |
| Notifications | Yes | Yes | Yes | No |

## Database Schema

The project contains 14 main Doctrine entities:

```text
Role
User
Farm
Field
Product
CartItem
Order
Favorite
Review
Post
Comment
Event
Participation
Notification
```

The repository also includes SQL resources:

- `database/agricloud.sql`
- `database_patch_reviews_favorites.sql`
- Doctrine migrations in `migrations/`

## Database Tables Reference

### Module 1 - User Management

| Entity | Description |
| --- | --- |
| Role | System roles used for authorization |
| User | User accounts, authentication data, profile fields, and status |
| Notification | User notifications and read/unread state |

### Module 2 - Farm Management

| Entity | Description |
| --- | --- |
| Farm | Farm records with owner and approval status |
| Field | Fields belonging to farms |

### Module 3 - Market Management

| Entity | Description |
| --- | --- |
| Product | Marketplace listings with stock, category, image, and approval status |
| CartItem | Shopping cart rows linked to users and products |
| Order | Purchase orders with shipping, payment, and status data |
| Favorite | User favorite products |
| Review | Product reviews and ratings |

### Module 4 - Blog Management

| Entity | Description |
| --- | --- |
| Post | Blog articles created by users |
| Comment | Comments linked to blog posts and users |

### Module 5 - Event Management

| Entity | Description |
| --- | --- |
| Event | Events with organizer, date, capacity, and location data |
| Participation | User event registrations and attendance status |

## Setup

### 1. Install prerequisites

- PHP 8.2 or newer
- Composer
- MySQL 8.0+ or MariaDB
- Symfony CLI, optional but recommended
- Enabled PHP extensions: `ctype`, `iconv`, `pdo_mysql`, `intl`

### 2. Clone the repository

```bash
git clone https://github.com/alpharou9/Esprit-PIDEV-3A--2526-Agricloud.git
cd Esprit-PIDEV-3A--2526-Agricloud
```

### 3. Install dependencies

```bash
composer install
```

### 4. Configure environment variables

Create a local environment file:

```bash
cp .env .env.local
```

Update `.env.local` with your local credentials. Keep real secrets out of Git.

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

### 5. Create the database

```sql
CREATE DATABASE agricloud CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or let Symfony create it:

```bash
php bin/console doctrine:database:create
```

### 6. Run migrations

```bash
php bin/console doctrine:migrations:migrate
```

Alternative import using the SQL dump:

```bash
mysql -u root -p agricloud < database/agricloud.sql
```

### 7. Optional data loading

```bash
php bin/console doctrine:fixtures:load
php bin/console app:seed:market-products
```

## Running the Application

With Symfony CLI:

```bash
symfony server:start
```

With PHP's built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

Open the application:

```text
http://127.0.0.1:8000
```

## Gmail SMTP Setup

Email features are used for password reset, order confirmations, farm status updates, welcome emails, and event tickets.

1. Enable 2-Factor Authentication on the Gmail account.
2. Generate a Gmail App Password.
3. Add the mailer DSN to `.env.local`.

```dotenv
MAILER_DSN=gmail://your-email@gmail.com:your-app-password@default
MAILER_FROM=your-email@gmail.com
```

## Stripe Setup

Stripe is used for marketplace checkout and order payment.

1. Create a Stripe account.
2. Get a test secret key from the Stripe dashboard.
3. Add it to `.env.local`.

```dotenv
STRIPE_SECRET_KEY=sk_test_your_key_here
```

## Google OAuth Setup

Google OAuth is used for login/register.

1. Create a project in Google Cloud Console.
2. Create OAuth 2.0 credentials.
3. Add this redirect URI:

```text
http://127.0.0.1:8000/auth/google/callback
```

4. Add credentials to `.env.local`.

```dotenv
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

## Main Routes

| Area | Path |
| --- | --- |
| Home | `/` |
| Login | `/login` |
| Register | `/register` |
| Guest login | `/guest-login` |
| Forgot password | `/forgot-password` |
| Dashboard | `/dashboard` |
| Profile | `/profile` |
| Profile PDF | `/profile/pdf` |
| Google OAuth | `/auth/google` |
| Farms | `/farms` |
| Fields | `/farms/{farmId}/fields` |
| Market | `/market` |
| Product details | `/market/product/{id}` |
| My products | `/market/my-products` |
| Cart | `/market/cart` |
| Checkout | `/market/cart/checkout` |
| Orders | `/market/orders` |
| Favorites | `/market/favorites` |
| Market quality | `/market/quality` |
| Events | `/events` |
| Event recommendations | `/events/recommendations` |
| Blog | `/blog` |
| My posts | `/blog/my-posts` |
| Blog comment admin | `/blog/admin/comments` |
| Assistant | `/assistant` |
| Admin users | `/admin/users` |
| Admin roles | `/admin/roles` |
| Notifications | `/notifications` |

## Integration API

The project exposes API endpoints under `/api/integration`.

| Resource | Endpoint |
| --- | --- |
| Users | `/api/integration/users` |
| User login | `/api/integration/users/login` |
| Roles | `/api/integration/roles` |
| Farms | `/api/integration/farms` |
| Fields | `/api/integration/fields` |
| Products | `/api/integration/products` |
| Shopping cart | `/api/integration/shopping-cart` |
| Orders | `/api/integration/orders` |
| Events | `/api/integration/events` |
| Participations | `/api/integration/participations` |
| Posts | `/api/integration/posts` |
| Comments | `/api/integration/comments` |
| Dashboard stats | `/api/integration/stats/dashboard` |

Some integration endpoints can be protected by the configured `JAVA_API_KEY` or by application-level authentication.

## Quality Checks

Run all tests:

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

Run the marketplace QA suite:

```bash
composer qa:market
```

## Test Suites

| Suite | Path |
| --- | --- |
| All tests | `tests/` |
| Market | `tests/Market` |
| User | `tests/User` |
| Role | `tests/Role` |
| Farm | `tests/Farm` |
| Field | `tests/Field` |
| Event | `tests/Event` |
| Participation | `tests/Participation` |
| Post | `tests/Post` |
| Comment | `tests/Comment` |
| Favorite | `tests/Favorite` |
| Review | `tests/Review` |
| Notification | `tests/Notification` |

## Project Structure

```text
bin/
  console

config/
  packages/
  routes.yaml
  services.yaml

database/
  agricloud.sql

docs/
  market-doctrine-doctor.md

migrations/
  Version20260419194000.php
  Version20260423110000.php

public/
  index.php
  theme/
  uploads/

src/
  Command/
  Controller/
  Controller/Api/
  DataCollector/
  DataFixtures/
  Entity/
  Form/
  Repository/
  Security/
  Service/
  Kernel.php

templates/
  blog/
  chatbot/
  dashboard/
  emails/
  event/
  farm/
  field/
  market/
  profile/
  registration/
  role/
  security/
  user/

tests/
  Market/
  User/
  Role/
  Farm/
  Field/
  Event/
  Participation/
  Post/
  Comment/
  Favorite/
  Review/
  Notification/
```

## Architecture

The application follows a classic Symfony layered structure:

- Entity layer: Doctrine entities represent business data and database tables.
- Repository layer: Doctrine repositories handle data queries.
- Form layer: Symfony form types manage input structure and validation.
- Service layer: business logic, integrations, payments, emails, AI helpers, PDFs, and order workflows.
- Controller layer: web and API controllers receive requests, delegate logic, and return Twig views or JSON responses.
- Template layer: Twig templates render the front office, admin pages, emails, PDFs, and market/event/blog views.

The API module is separated under `src/Controller/Api`, while browser pages are handled by the main controllers in `src/Controller`.

## Common Issues

| Issue | Solution |
| --- | --- |
| Database connection fails | Check `DATABASE_URL`, confirm MySQL is running, and verify database name `agricloud`. |
| Migrations fail | Make sure the database exists and your MySQL user has schema permissions. |
| Emails do not send | Use a Gmail App Password or set `MAILER_DSN=null://null` for local development. |
| Stripe checkout fails | Verify `STRIPE_SECRET_KEY` exists in `.env.local`. |
| Google login fails | Confirm `GOOGLE_REDIRECT_URI` matches Google Cloud Console exactly. |
| Uploaded images do not show | Check `public/uploads`, Cloudinary credentials, and file permissions. |
| PHPUnit cannot find classes | Run `composer install` and confirm `vendor/autoload.php` exists. |
| PHPStan cache errors | Delete `.phpstan-cache` and rerun the PHPStan command. |

## Contributor Notes

- Keep real credentials in `.env.local`; never commit private keys or passwords.
- Use Doctrine migrations for schema changes.
- Keep module logic inside the matching controller, service, entity, repository, form, and template areas.
- Add or update tests when changing entity behavior, services, checkout, orders, authentication, or APIs.
- Run `composer qa:all` before submitting major changes.
