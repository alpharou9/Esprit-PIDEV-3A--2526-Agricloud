<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FaceAuthController extends AbstractController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    #[Route('/face/enroll', name: 'face_enroll', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enroll(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data       = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return new JsonResponse(['error' => 'Invalid face descriptor.'], 400);
        }

        /** @var \App\Entity\User $user */
        $user  = $this->getUser();
        $bytes = pack('G128', ...$descriptor);
        $user->setFaceEmbeddings(json_encode([['embedding' => base64_encode($bytes)]]));
        $user->setFaceEnrolledAt(new \DateTime());
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(Request $request, UserRepository $userRepo): JsonResponse
    {
        $data       = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return new JsonResponse(['error' => 'Invalid face descriptor.'], 400);
        }

        $users    = $userRepo->findWithFaceEmbeddings();
        $bestUser = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            $stored = $this->decodeEmbedding($user->getFaceEmbeddings());
            if (!$stored) continue;

            $dist = $this->euclideanDistance($descriptor, $stored);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $bestUser = $user;
            }
        }

        if ($bestUser && $bestDist < 0.6) {
            if ($bestUser->getStatus() === 'blocked') {
                return new JsonResponse(['error' => 'Your account has been blocked.'], 403);
            }

            $token = new UsernamePasswordToken($bestUser, 'main', $bestUser->getRoles());
            $this->tokenStorage->setToken($token);
            $request->getSession()->set('_security_main', serialize($token));

            return new JsonResponse(['success' => true, 'redirect' => '/dashboard']);
        }

        return new JsonResponse(['error' => 'Face not recognised. Try again or use your password.'], 401);
    }

    private function decodeEmbedding(?string $json): ?array
    {
        if (!$json) return null;
        $data = json_decode($json, true);
        if (!isset($data[0]['embedding'])) return null;

        $bytes = base64_decode($data[0]['embedding']);
        if (strlen($bytes) !== 512) return null;

        return array_values(unpack('G128', $bytes));
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $d    = ($a[$i] ?? 0.0) - ($b[$i] ?? 0.0);
            $sum += $d * $d;
        }
        return sqrt($sum);
    }
}
