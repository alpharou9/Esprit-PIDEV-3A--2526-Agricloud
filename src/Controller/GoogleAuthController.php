<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleAuthController extends AbstractController
{
    private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USER_URL  = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public function __construct(
        #[Autowire('%google_client_id%')]
        private readonly string $clientId,
        #[Autowire('%google_client_secret%')]
        private readonly string $clientSecret,
        #[Autowire('%google_redirect_uri%')]
        private readonly string $redirectUri,
    ) {}

    /**
     * Redirect the user to Google's consent screen.
     */
    #[Route('/auth/google', name: 'auth_google', methods: ['GET'])]
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('oauth_state', $state);

        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return new RedirectResponse(self::AUTH_URL . '?' . $params);
    }

    /**
     * Handle the callback from Google.
     * Exchange the code for an access token, fetch the user's profile,
     * then log in or auto-register the user.
     */
    #[Route('/auth/google/callback', name: 'auth_google_callback', methods: ['GET'])]
    public function callback(
        Request                $request,
        HttpClientInterface    $http,
        EntityManagerInterface $em,
        Security               $security,
    ): Response {
        // 1 — CSRF / state check
        $state         = $request->query->get('state', '');
        $expectedState = $request->getSession()->get('oauth_state', '');
        if (!$state || $state !== $expectedState) {
            $this->addFlash('error', 'Invalid OAuth state. Please try again.');
            return $this->redirectToRoute('app_login');
        }
        $request->getSession()->remove('oauth_state');

        // 2 — Error returned by Google (user denied, etc.)
        if ($request->query->has('error')) {
            $this->addFlash('error', 'Google sign-in was cancelled.');
            return $this->redirectToRoute('app_login');
        }

        $code = $request->query->get('code', '');
        if (!$code) {
            $this->addFlash('error', 'Missing authorisation code from Google.');
            return $this->redirectToRoute('app_login');
        }

        // 3 — Exchange code for access token
        try {
            $tokenResponse = $http->request('POST', self::TOKEN_URL, [
                'body' => [
                    'code'          => $code,
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri'  => $this->redirectUri,
                    'grant_type'    => 'authorization_code',
                ],
            ]);
            $tokenData = $tokenResponse->toArray();
        } catch (\Throwable) {
            $this->addFlash('error', 'Failed to contact Google. Please try again.');
            return $this->redirectToRoute('app_login');
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            $this->addFlash('error', 'Could not obtain access token from Google.');
            return $this->redirectToRoute('app_login');
        }

        // 4 — Fetch Google user profile
        try {
            $profileResponse = $http->request('GET', self::USER_URL, [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $profile = $profileResponse->toArray();
        } catch (\Throwable) {
            $this->addFlash('error', 'Failed to retrieve your Google profile.');
            return $this->redirectToRoute('app_login');
        }

        $googleId    = $profile['sub']   ?? null;
        $googleEmail = $profile['email'] ?? null;
        $googleName  = $profile['name']  ?? 'Google User';

        if (!$googleId || !$googleEmail) {
            $this->addFlash('error', 'Incomplete profile returned by Google.');
            return $this->redirectToRoute('app_login');
        }

        // 5 — Find or create user
        $userRepo = $em->getRepository(User::class);

        // Try to find by oauth_id first (returning user)
        $user = $userRepo->findOneBy(['oauthId' => $googleId, 'oauthProvider' => 'google']);

        // Fall back to email match (account may have been created with password before)
        if (!$user) {
            $user = $userRepo->findOneBy(['email' => $googleEmail]);
        }

        if (!$user) {
            // Auto-register: create a new user account
            $user = new User();
            $user->setEmail($googleEmail);
            $user->setName($googleName);
            $user->setStatus('active');
            $user->setCreatedAt(new \DateTime());

            // Assign default Customer role
            $role = $em->getRepository(Role::class)->findOneBy(['name' => 'Customer']);
            if ($role) {
                $user->setRole($role);
            }

            $em->persist($user);
        }

        // Update OAuth fields every login (keeps name/provider fresh)
        $user->setOauthProvider('google');
        $user->setOauthId($googleId);
        $em->flush();

        // 6 — Check account status
        if ($user->getStatus() === 'blocked') {
            $this->addFlash('error', 'Your account has been blocked. Please contact support.');
            return $this->redirectToRoute('app_login');
        }

        // 7 — Programmatic login
        $security->login($user, 'form_login', 'main');

        return $this->redirectToRoute('dashboard');
    }
}
