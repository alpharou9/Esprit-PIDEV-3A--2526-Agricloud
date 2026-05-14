# Railway Deployment

Railway is the recommended quick deployment target for this project.

The repository already includes:

- `railway.json` for Railway config-as-code
- `nixpacks.toml` for PHP dependency/build/start behavior
- `.env.example` for safe environment variable names
- `DEPLOYMENT.md` for the full production checklist

## Required Railway Variables

Add these variables to the Railway web service:

```text
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=generate_a_real_secret
COMPOSER_ALLOW_SUPERUSER=1
RAILPACK_PHP_ROOT_DIR=/app/public
DATABASE_URL=mysql://USER:PASSWORD@HOST:PORT/DATABASE?serverVersion=8.0&charset=utf8mb4
MAILER_DSN=null://null
MAILER_FROM=no-reply@example.com
CURRENCY_API_URL=https://api.frankfurter.dev/v2
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
STRIPE_SECRET_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://your-railway-domain/auth/google/callback
JAVA_API_KEY=generate_a_shared_api_key
```

## Railway Steps

1. Go to Railway and create a new project.
2. Choose "Deploy from GitHub repo".
3. Select `alpharou9/Esprit-PIDEV-3A--2526-Agricloud`.
4. Add a MySQL database service.
5. Copy the MySQL connection URL into `DATABASE_URL`.
6. Add the required variables listed above.
7. Deploy the web service from `master`.
8. Generate a public Railway domain.
9. Update `GOOGLE_REDIRECT_URI` to use the generated domain.
10. Run migrations from Railway shell:

```bash
php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

## Notes

- Keep `MAILER_DSN=null://null` until you are ready to configure Gmail SMTP.
- Keep Stripe empty until checkout testing starts.
- If uploads need to persist across redeploys, move product/event images to Cloudinary or add Railway persistent storage.
