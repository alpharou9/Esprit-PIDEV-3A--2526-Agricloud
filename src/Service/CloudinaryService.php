<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CloudinaryService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SluggerInterface $slugger,
        private readonly string $cloudName,
        private readonly string $apiKey,
        private readonly string $apiSecret,
        private readonly string $localUploadDir,
    ) {
    }

    public function storeProductImage(UploadedFile $imageFile): array
    {
        if ($this->isConfigured()) {
            $cloudinaryUpload = $this->uploadToCloudinary($imageFile);
            if ($cloudinaryUpload !== null) {
                return $cloudinaryUpload;
            }
        }

        return $this->uploadLocally($imageFile);
    }

    private function uploadToCloudinary(UploadedFile $imageFile): ?array
    {
        $baseName = (string) $this->slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
        $publicId = sprintf('agricloud/products/%s-%s', $baseName !== '' ? $baseName : 'product', uniqid());
        $timestamp = time();
        $signature = sha1(sprintf('folder=agricloud/products&public_id=%s&timestamp=%d%s', $publicId, $timestamp, $this->apiSecret));

        $formData = new FormDataPart([
            'file' => DataPart::fromPath(
                $imageFile->getPathname(),
                $imageFile->getClientOriginalName(),
                $imageFile->getMimeType() ?: 'application/octet-stream'
            ),
            'folder' => 'agricloud/products',
            'public_id' => $publicId,
            'timestamp' => (string) $timestamp,
            'api_key' => $this->apiKey,
            'signature' => $signature,
        ]);

        try {
            $response = $this->httpClient->request('POST', sprintf('https://api.cloudinary.com/v1_1/%s/image/upload', $this->cloudName), [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
                'timeout' => 25,
            ]);
        } catch (TransportExceptionInterface) {
            return null;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return null;
        }

        $payload = json_decode($response->getContent(false), true);
        if (!is_array($payload) || ($payload['secure_url'] ?? null) === null) {
            return null;
        }

        $optimizedUrl = preg_replace('#/upload/#', '/upload/f_auto,q_auto,c_fill,w_1200/', (string) $payload['secure_url'], 1);

        return [
            'image' => $optimizedUrl ?: (string) $payload['secure_url'],
            'provider' => 'cloudinary',
            'warning' => null,
        ];
    }

    private function uploadLocally(UploadedFile $imageFile): array
    {
        if (!is_dir($this->localUploadDir)) {
            mkdir($this->localUploadDir, 0777, true);
        }

        $safeName = (string) $this->slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = sprintf(
            '%s-%s.%s',
            $safeName !== '' ? $safeName : 'product',
            uniqid(),
            $imageFile->guessExtension() ?: 'jpg'
        );

        $imageFile->move($this->localUploadDir, $fileName);

        return [
            'image' => $fileName,
            'provider' => 'local',
            'warning' => $this->isConfigured()
                ? 'Cloudinary upload was unavailable, so the product image was stored locally.'
                : 'Cloudinary is not configured yet, so the product image was stored locally.',
        ];
    }

    private function isConfigured(): bool
    {
        return $this->cloudName !== '' && $this->apiKey !== '' && $this->apiSecret !== '';
    }
}
