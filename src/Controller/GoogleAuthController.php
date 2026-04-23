<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/auth/google')]
class GoogleAuthController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(GOOGLE_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
        private readonly HttpClientInterface $httpClient,
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $em,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    #[Route('', name: 'auth_google')]
    public function start(): Response
    {
        if (empty($this->clientId)) {
            $this->addFlash('error', 'Google login is not configured yet.');
            return $this->redirectToRoute('app_login');
        }

        $callbackUrl = $this->generateUrl('auth_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $callbackUrl,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);

        return new RedirectResponse('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    #[Route('/callback', name: 'auth_google_callback')]
    public function callback(Request $request): Response
    {
        $code  = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error || !$code) {
            $this->addFlash('error', 'Google sign-in was cancelled or failed.');
            return $this->redirectToRoute('app_login');
        }

        $callbackUrl = $this->generateUrl('auth_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // Exchange code for access token
        try {
            $tokenResponse = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code'          => $code,
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri'  => $callbackUrl,
                    'grant_type'    => 'authorization_code',
                ],
            ]);
            $tokenData = $tokenResponse->toArray();
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Could not connect to Google. Please try again.');
            return $this->redirectToRoute('app_login');
        }

        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            $this->addFlash('error', 'Google authentication failed.');
            return $this->redirectToRoute('app_login');
        }

        // Get user info from Google
        try {
            $infoResponse = $this->httpClient->request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);
            $googleUser = $infoResponse->toArray();
        } catch (\Throwable) {
            $this->addFlash('error', 'Could not retrieve your Google profile.');
            return $this->redirectToRoute('app_login');
        }

        $googleId = $googleUser['sub']  ?? null;
        $email    = $googleUser['email'] ?? null;
        $name     = $googleUser['name']  ?? ($googleUser['given_name'] ?? 'Google User');

        if (!$email) {
            $this->addFlash('error', 'Google did not provide an email address.');
            return $this->redirectToRoute('app_login');
        }

        // Find or create user
        $user = $this->userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setStatus('active');
            $user->setCreatedAt(new \DateTime());
            $user->setOauthProvider('google');
            $user->setOauthId($googleId);
            // Set random password so the account is valid
            $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(16))));

            $role = $this->em->getRepository(Role::class)->findOneBy(['name' => 'Customer']);
            if ($role) {
                $user->setRole($role);
            }

            $this->em->persist($user);
            $this->em->flush();
        } elseif ($user->getOauthProvider() === null) {
            // Existing email account — link Google to it
            $user->setOauthProvider('google');
            $user->setOauthId($googleId);
            $this->em->flush();
        }

        if ($user->getStatus() === 'blocked') {
            $this->addFlash('error', 'Your account has been blocked.');
            return $this->redirectToRoute('app_login');
        }

        // Manually log the user in
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $request->getSession()->set('_security_main', serialize($token));

        return $this->redirectToRoute('dashboard');
    }
}
