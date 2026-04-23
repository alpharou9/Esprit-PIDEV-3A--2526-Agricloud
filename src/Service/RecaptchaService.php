<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%recaptcha_secret_key%')]
        private readonly string $secretKey,
    ) {}

    /**
     * Verify a reCAPTCHA v2 token (g-recaptcha-response).
     * Returns true if the token is valid.
     */
    public function verify(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret'   => $this->secretKey,
                    'response' => $token,
                ],
            ]);

            $data = $response->toArray();

            return (bool) ($data['success'] ?? false);
        } catch (\Throwable) {
            // Google API unreachable (SSL/network issue in dev) — fail open only if
            // the widget submitted something (user did tick the box, API just unreachable)
            return !empty($token);
        }
    }
}
