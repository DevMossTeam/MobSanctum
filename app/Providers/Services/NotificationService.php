<?php

namespace App\Providers\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DeviceToken;

class NotificationService
{
    protected array $serviceAccount;
    protected string $tokenUri;
    protected string $projectId;
    protected string $clientEmail;
    protected string $privateKey;

    public function __construct()
    {
        // ✏️ Cek dua kemungkinan lokasi file JSON:
        //  - storage/app/fcm_service_account.json
        //  - storage/fcm_service_account.json
        $pathsToTry = [
            storage_path('app/fcm_service_account.json'),
            storage_path('fcm_service_account.json'),
        ];

        $jsonKeyPath = null;
        foreach ($pathsToTry as $path) {
            if (file_exists($path)) {
                $jsonKeyPath = $path;
                break;
            }
        }

        if ($jsonKeyPath === null) {
            Log::error("FCM service account file not found. Dicari di: " . implode(' , ', $pathsToTry));
            throw new \Exception("FCM service account file not found. Pastikan JSON terletak di storage/app atau storage root.");
        }

        $raw = file_get_contents($jsonKeyPath);
        $this->serviceAccount = json_decode($raw, true);

        if (!is_array($this->serviceAccount)) {
            Log::error("Failed to parse FCM service account JSON: {$jsonKeyPath}");
            throw new \Exception("FCM service account JSON tidak valid.");
        }

        // ✏️ Ambil token_uri, tetapi kalau memang tidak di-set di file JSON, gunakan default Google OAuth2
        $this->tokenUri = $this->serviceAccount['token_uri']
            ?? 'https://oauth2.googleapis.com/token';

        // Key lain wajib ada: project_id, client_email, private_key
        foreach (['project_id', 'client_email', 'private_key'] as $key) {
            if (empty($this->serviceAccount[$key])) {
                Log::error("Key '{$key}' is missing or empty in FCM service account JSON.");
                throw new \Exception("Key '{$key}' is missing or empty in FCM service account JSON.");
            }
        }

        $this->projectId   = $this->serviceAccount['project_id'];
        $this->clientEmail = $this->serviceAccount['client_email'];
        $this->privateKey  = $this->serviceAccount['private_key'];
    }

    /**
     * Buat JWT assertion untuk OAuth2
     */
    protected function createJwtAssertion(): string
    {
        $now = time();
        $exp = $now + 3600; // 1 jam valid

        $payload = [
            'iss'   => $this->clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => $this->tokenUri,
            'iat'   => $now,
            'exp'   => $exp,
        ];

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    /**
     * Mendapatkan access token (dengan cache)
     */
    protected function getAccessToken(): string
    {
        // Simpan di cache selama ~55 menit
        return Cache::remember('fcm_access_token', 55 * 60, function () {
            $jwtAssertion = $this->createJwtAssertion();

            $response = Http::asForm()->post($this->tokenUri, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwtAssertion,
            ])->throw();

            $json = $response->json();
            if (empty($json['access_token'])) {
                throw new \Exception('Failed to retrieve access_token from Google OAuth2 response.');
            }

            return $json['access_token'];
        });
    }

    /**
     * Kirim notifikasi ke semua device
     *
     * @param  string  $title
     * @param  string  $body
     * @param  array   $data
     * @return array
     */
    public function send(string $title, string $body, array $data = []): array
{
    $tokens = DeviceToken::pluck('device_token')->toArray();

    if (empty($tokens)) {
        return [
            'success' => false,
            'message' => 'No device tokens found.',
        ];
    }

    $accessToken = $this->getAccessToken();
    $endpoint    = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    $results     = [];

    foreach (array_chunk($tokens, 100) as $chunk) {
        foreach ($chunk as $token) {
            $payload = [
                'message' => [
                    'token'        => $token,
                    'notification' => [
                        'title' => $title,
                        'body'  => $body,
                    ],
                    'data' => $data,
                ],
            ];

            try {
                $resp = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type'  => 'application/json',
                ])->post($endpoint, $payload)
                  ->throw()
                  ->json();

                $results[] = [
                    'token'    => $token,
                    'response' => $resp,
                ];
            } catch (\Illuminate\Http\Client\RequestException $e) {
                $statusCode = $e->response->status();
                $errorBody  = $e->response->json();

                // Jika error 404 (token tidak ditemukan), hapus dari DB
                if ($statusCode === 404 && isset($errorBody['error']['status'])
                    && $errorBody['error']['status'] === 'NOT_FOUND')
                {
                    // Hapus token yang invalid
                    DeviceToken::where('device_token', $token)->delete();

                    $results[] = [
                        'token' => $token,
                        'error' => 'Token not found on FCM (removed from DB).',
                    ];
                } else {
                    // Error lain → catat saja
                    Log::error('FCM send error', [
                        'token'   => $token,
                        'status'  => $statusCode,
                        'message' => $errorBody,
                    ]);

                    $results[] = [
                        'token' => $token,
                        'error' => $e->getMessage(),
                    ];
                }
            } catch (\Throwable $e) {
                // Untuk error‐error tak terduga
                Log::error('FCM unexpected error', [
                    'token'   => $token,
                    'message' => $e->getMessage(),
                ]);

                $results[] = [
                    'token' => $token,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }
        return $results;
    }
}
