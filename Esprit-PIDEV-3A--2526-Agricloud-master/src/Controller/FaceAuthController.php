<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

class FaceAuthController extends AbstractController
{
    /**
     * Enroll the current user's face (requires login).
     * Receives a JSON array of 128 floats from face-api.js.
     * Stores in the same big-endian float32 base64 format as the JavaFX app.
     */
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
        $bytes = pack('G128', ...$descriptor);   // big-endian float32 — same as JavaFX
        $user->setFaceEmbeddings(json_encode([['embedding' => base64_encode($bytes)]]));
        $user->setFaceEnrolledAt(new \DateTime());
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Face login — public route.
     * Compares the submitted descriptor against all stored embeddings.
     * Logs the matching user in programmatically if Euclidean distance < 0.55.
     */
    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request        $request,
        UserRepository $userRepo,
        Security       $security
    ): JsonResponse {
        $data       = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!is_array($descriptor) || count($descriptor) !== 128) {
            return new JsonResponse(['error' => 'Invalid face descriptor.'], 400);
        }

        $users      = $userRepo->findWithFaceEmbeddings();
        $bestUser   = null;
        $bestDist   = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            $stored = $this->decodeEmbedding($user->getFaceEmbeddings());
            if (!$stored) continue;

            $dist = $this->euclideanDistance($descriptor, $stored);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $bestUser = $user;
            }
        }

        $threshold = 0.55;   // face-api.js default is 0.6; 0.55 is slightly stricter

        if ($bestUser && $bestDist < $threshold) {
            if ($bestUser->getStatus() === 'blocked') {
                return new JsonResponse(['error' => 'Your account has been blocked.'], 403);
            }
            $security->login($bestUser, 'form_login', 'main');
            return new JsonResponse(['success' => true, 'redirect' => '/dashboard']);
        }

        return new JsonResponse(['error' => 'Face not recognised. Please try again or use your password.'], 401);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Decode a stored face embedding (JSON → base64 → big-endian float32 array).
     * Compatible with both the JavaFX format and web-enrolled embeddings.
     */
    private function decodeEmbedding(?string $json): ?array
    {
        if (!$json) return null;
        $data = json_decode($json, true);
        if (!isset($data[0]['embedding'])) return null;

        $bytes = base64_decode($data[0]['embedding']);
        if (strlen($bytes) !== 512) return null;   // 128 × 4 bytes

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
