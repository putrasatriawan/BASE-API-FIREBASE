<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PublicException;

class BiteshipService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.biteship.api_key');
        $this->baseUrl = config('services.biteship.base_url', 'https://api.biteship.com');
    }

    /**
     * Check rates by area ID (primary method)
     *
     * @param int $userId
     * @param string $originAreaId
     * @param object $address
     * @param array $items
     * @return array
     */
    public function checkRatesByAreaId(int $userId, string $originAreaId, $address, array $items): array
    {
        try {
            if (!$this->apiKey) {
                throw new PublicException('Biteship API key belum dikonfigurasi');
            }

            $payload = [
                'origin_area_id' => $originAreaId,
                'destination_area_id' => $address->biteship_id,
                'couriers' => 'paxel,jne,sicepat,jnt,anteraja,ninja,lion,idexpress',
                'items' => $items,
            ];

            Log::info('Biteship check rates by area ID', [
                'user_id' => $userId,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/rates/couriers', $payload);

            if (!$response->successful()) {
                Log::warning('Biteship API by area ID failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                // Throw exception to trigger fallback
                throw new \Exception('Biteship API by area ID failed: ' . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'method' => 'area_id',
                'data' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error('Check rates by area ID failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'method' => 'area_id',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check rates by postal code and coordinates (fallback method)
     *
     * @param int $userId
     * @param string $originPostalCode
     * @param object $address
     * @param array $items
     * @return array
     */
    public function checkRatesByPostalCode(int $userId, string $originPostalCode, $address, array $items): array
    {
        try {
            if (!$this->apiKey) {
                throw new PublicException('Biteship API key belum dikonfigurasi');
            }

            // Validate address has lat/lon
            if (!$address->lat || !$address->lon) {
                throw new PublicException('Alamat tidak memiliki koordinat latitude/longitude');
            }

            $payload = [
                'origin_postal_code' => (int) $originPostalCode,
                'destination_postal_code' => (int) $address->postal_code,
                'couriers' => 'paxel,jne,sicepat,jnt,anteraja,ninja,lion,idexpress',
                'items' => $items,
            ];

            Log::info('Biteship check rates by postal code', [
                'user_id' => $userId,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v1/rates/couriers', $payload);

            if (!$response->successful()) {
                Log::error('Biteship API by postal code failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                throw new PublicException('Gagal mengambil tarif pengiriman: ' . $response->body());
            }

            $result = $response->json();

            return [
                'success' => true,
                'method' => 'postal_code',
                'data' => $result,
            ];
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Check rates by postal code failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal mengecek tarif pengiriman: ' . $e->getMessage());
        }
    }

    /**
     * Check rates with automatic fallback
     * Try area ID first, if fails then try postal code + coordinates
     *
     * @param int $userId
     * @param string $originAreaId
     * @param string $originPostalCode
     * @param object $address
     * @param array $items
     * @return array
     */
    public function checkRatesWithFallback(
        int $userId,
        string $originAreaId,
        string $originPostalCode,
        $address,
        array $items
    ): array {
        // Try primary method: area ID
        $resultByAreaId = $this->checkRatesByAreaId($userId, $originAreaId, $address, $items);

        if ($resultByAreaId['success']) {
            return $resultByAreaId['data'];
        }

        // Fallback to postal code + coordinates
        Log::info('Falling back to postal code method', [
            'user_id' => $userId,
            'reason' => $resultByAreaId['error'],
        ]);

        $resultByPostalCode = $this->checkRatesByPostalCode($userId, $originPostalCode, $address, $items);

        if ($resultByPostalCode['success']) {
            return $resultByPostalCode['data'];
        }

        // Both methods failed
        throw new PublicException('Gagal mengambil tarif pengiriman dari semua metode');
    }
}
