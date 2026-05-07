<?php

declare(strict_types=1);

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
$_SERVER['APP_SECRET'] = $_ENV['APP_SECRET'] = 'phpstan-test-secret';
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
$_SERVER['MAILER_DSN'] = $_ENV['MAILER_DSN'] = 'null://null';
$_SERVER['MAILER_FROM'] = $_ENV['MAILER_FROM'] = 'no-reply@example.test';
$_SERVER['STRIPE_SECRET_KEY'] = $_ENV['STRIPE_SECRET_KEY'] = '';
$_SERVER['RECAPTCHA_SITE_KEY'] = $_ENV['RECAPTCHA_SITE_KEY'] = '';
$_SERVER['RECAPTCHA_SECRET_KEY'] = $_ENV['RECAPTCHA_SECRET_KEY'] = '';
$_SERVER['GOOGLE_CLIENT_ID'] = $_ENV['GOOGLE_CLIENT_ID'] = '';
$_SERVER['GOOGLE_CLIENT_SECRET'] = $_ENV['GOOGLE_CLIENT_SECRET'] = '';
$_SERVER['GOOGLE_REDIRECT_URI'] = $_ENV['GOOGLE_REDIRECT_URI'] = 'http://127.0.0.1/auth/google/callback';
$_SERVER['HUGGINGFACE_API_TOKEN'] = $_ENV['HUGGINGFACE_API_TOKEN'] = '';
$_SERVER['CLOUDINARY_CLOUD_NAME'] = $_ENV['CLOUDINARY_CLOUD_NAME'] = '';
$_SERVER['CLOUDINARY_API_KEY'] = $_ENV['CLOUDINARY_API_KEY'] = '';
$_SERVER['CLOUDINARY_API_SECRET'] = $_ENV['CLOUDINARY_API_SECRET'] = '';

putenv('APP_ENV=test');
putenv('APP_DEBUG=0');
putenv('APP_SECRET=phpstan-test-secret');
putenv('DATABASE_URL=sqlite:///:memory:');
putenv('MAILER_DSN=null://null');
putenv('MAILER_FROM=no-reply@example.test');
putenv('STRIPE_SECRET_KEY=');
putenv('RECAPTCHA_SITE_KEY=');
putenv('RECAPTCHA_SECRET_KEY=');
putenv('GOOGLE_CLIENT_ID=');
putenv('GOOGLE_CLIENT_SECRET=');
putenv('GOOGLE_REDIRECT_URI=http://127.0.0.1/auth/google/callback');
putenv('HUGGINGFACE_API_TOKEN=');
putenv('CLOUDINARY_CLOUD_NAME=');
putenv('CLOUDINARY_API_KEY=');
putenv('CLOUDINARY_API_SECRET=');
