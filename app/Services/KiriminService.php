<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Exceptions\PublicException;

class KiriminService
{
    protected ?string $baseUrl;
    protected ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.kirimin.base_url');
        $this->apiKey = config('services.kirimin.api_key');
    }

    /**
     * Get subdistricts (kelurahan) by district (kecamatan) ID
     *
     * @param int $kecamatanId
     * @return array
     */
    public function getSubdistricts(int $kecamatanId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->get($this->baseUrl . 'api/mitra/kelurahan', [
                'kecamatan_id' => $kecamatanId,
            ]);

            if (!$response->successful()) {
                Log::error('Kirimin API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PublicException('Gagal mengambil data kelurahan dari Kirimin');
            }

            $data = $response->json();
            if (!isset($data['status']) || !$data['status']) {
                throw new PublicException('Gagal mengambil data kelurahan: ' . ($data['text'] ?? 'Unknown error'));
            }

            return $data['result'] ?? [];
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Get subdistricts from Kirimin failed', [
                'kecamatan_id' => $kecamatanId,
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal mengambil data kelurahan: ' . $e->getMessage());
        }
    }

    /**
     * Get shipping rates from Kirimin API
     *
     * @param array $params
     * @return array
     */
    public function getShippingRates(array $params): array
    {
        try {
            $payload = [
                'origin' => $params['origin'],
                'subdistrict_origin' => $params['subdistrict_origin'],
                'destination' => $params['destination'],
                'subdistrict_destination' => $params['subdistrict_destination'],
                'weight' => $params['weight'],
                'length' => $params['length'] ?? 1,
                'width' => $params['width'] ?? 1,
                'height' => $params['height'] ?? 1,
                'item_value' => (string) $params['item_value'],
                'insurance' => $params['insurance'] ?? 1,
                'courier' => $params['courier'] ?? ['jne'],
            ];

            Log::info('Kirimin shipping rates request', [
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/mitra/v6.1/shipping_price', $payload);

            if (!$response->successful()) {
                Log::error('Kirimin shipping rates API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                throw new PublicException('Gagal mengambil tarif pengiriman dari Kirimin');
            }

            $data = $response->json();

            Log::info('Kirimin shipping rates response', [
                'data' => $data,
            ]);

            if (!isset($data['status']) || !$data['status']) {
                throw new PublicException('Gagal mengambil tarif pengiriman: ' . ($data['text'] ?? 'Unknown error'));
            }

            return $data['results'] ?? [];
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Get shipping rates from Kirimin failed', [
                'params' => $params,
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal mengambil tarif pengiriman: ' . $e->getMessage());
        }
    }

    /**
     * Get pickup schedules from Kirimin API
     *
     * @return array
     */
    public function getSchedules(): array
    {
        try {
            Log::info('Fetching schedules from Kirimin', [
                'url' => $this->baseUrl . 'api/mitra/v2/schedules',
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'api/mitra/v2/schedules');

            if (!$response->successful()) {
                Log::error('Kirimin schedules API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new PublicException('Gagal mengambil jadwal pickup dari Kirimin');
            }

            $data = $response->json();

            Log::info('Kirimin schedules response', [
                'data' => $data,
            ]);

            return $data;
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Get schedules from Kirimin failed', [
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal mengambil jadwal pickup: ' . $e->getMessage());
        }
    }

    /**
     * Create order to Kirimin API
     *
     * @param array $orderData
     * @return array
     */
    public function createOrder(array $orderData): array
    {
        try {
            Log::info('Creating order to Kirimin', [
                'url' => $this->baseUrl . '/api/mitra/v6.1/request_pickup',
                'order_data' => $orderData,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/mitra/v6.1/request_pickup', $orderData);

            if (!$response->successful()) {
                Log::error('Kirimin create order API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'order_data' => $orderData,
                ]);

                throw new PublicException('Gagal membuat order ke Kirimin: ' . $response->body());
            }

            $data = $response->json();

            Log::info('Kirimin create order response', [
                'data' => $data,
            ]);

            if (!isset($data['status']) || !$data['status']) {
                throw new PublicException('Gagal membuat order: ' . ($data['text'] ?? 'Unknown error'));
            }

            return $data;
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Create order to Kirimin failed', [
                'order_data' => $orderData,
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal membuat order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel shipment to Kirimin API
     *
     * @param string $awb
     * @param string $reason
     * @return array
     */
    public function cancelShipment(string $awb, string $reason): array
    {
        try {
            $payload = [
                'awb' => $awb,
                'reason' => $reason,
            ];

            Log::info('Cancelling shipment to Kirimin', [
                'url' => $this->baseUrl . 'api/mitra/v3/cancel_shipment',
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'api/mitra/v3/cancel_shipment', $payload);

            if (!$response->successful()) {
                Log::error('Kirimin cancel shipment API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                throw new PublicException('Gagal membatalkan shipment ke Kirimin: ' . $response->body());
            }

            $data = $response->json();

            Log::info('Kirimin cancel shipment response', [
                'data' => $data,
            ]);

            if (!isset($data['status']) || !$data['status']) {
                throw new PublicException('Gagal membatalkan shipment: ' . ($data['text'] ?? 'Unknown error'));
            }

            return $data;
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Cancel shipment to Kirimin failed', [
                'awb' => $awb,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal membatalkan shipment: ' . $e->getMessage());
        }
    }

    /**
     * Get tracking information from Kirimin API
     *
     * @param string $orderId
     * @return array
     */
    public function getTracking(string $orderId): array
    {
        try {
            $payload = [
                'order_id' => $orderId,
            ];

            Log::info('Getting tracking from Kirimin', [
                'url' => $this->baseUrl . 'api/mitra/tracking',
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'api/mitra/tracking', $payload);

            if (!$response->successful()) {
                Log::error('Kirimin tracking API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);

                throw new PublicException('Gagal mengambil tracking dari Kirimin: ' . $response->body());
            }

            $data = $response->json();
            // DD($data);
            Log::info('Kirimin tracking response', [
                'data' => $data,
            ]);

            if (!isset($data['status']) || !$data['status']) {
                throw new PublicException('Gagal mengambil tracking: ' . ($data['text'] ?? 'Unknown error'));
            }

            return $data;
        } catch (PublicException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Get tracking from Kirimin failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw new PublicException('Gagal mengambil tracking: ' . $e->getMessage());
        }
    }
}
