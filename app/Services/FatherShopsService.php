<?php

namespace App\Services;

use App\Exceptions\ExternalApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FatherShopsService
{
    public function getCategories(): array
    {
        return $this->fetch('fathershops:categories','/category/hot-category.json',fn (array $data): bool =>
                isset($data['response']) &&
                is_array($data['response'])
        );
    }

    public function getCategoryProducts(string $categoryId): array
    {
        return $this->fetch("fathershops:category:{$categoryId}:products","/category/{$categoryId}.json",fn (array $data): bool=>
                isset($data['data']['products']) &&
                is_array($data['data']['products'])
        );
    }

    public function getProductDetails(string $productId): array
    {
        return $this->fetch("fathershops:product:{$productId}","/cache/{$productId}-f1.json",fn (array $data): bool =>
                isset($data['product_id']) &&
                (string) $data['product_id'] === $productId &&
                isset($data['descriptions']) &&
                is_array($data['descriptions'])
        );
    }

    private function fetch( string $cacheKey, string $path, callable $isValid): array {
        return Cache::remember($cacheKey, now()->addSeconds((int) config('fathershops.cache_ttl', 600)),
        
            function () use ($path, $isValid): array {
                $url = rtrim(
                    config('fathershops.base_url'),
                    '/'
                ) . '/' . ltrim($path, '/');

                try {
                    $response = Http::acceptJson()
                        ->timeout(
                            (int) config('fathershops.timeout', 15)
                        )
                        ->get($url);
                } catch (ConnectionException $exception) {
                    throw new ExternalApiException(
                        'Unable to connect to the FatherShops service.',
                        502
                    );
                }

                if ($response->status() === 404) {
                    throw new ExternalApiException(
                        'The requested resource was not found.',
                        404
                    );
                }

                if (!$response->successful()) {
                    throw new ExternalApiException(
                        'FatherShops service returned an unsuccessful response.',
                        502
                    );
                }

                $data = $response->json();

                if (!is_array($data) || !$isValid($data)) {
                    $upstreamData = is_array($data)
                        ? ($data['data'] ?? null)
                        : null;
                    $products = is_array($upstreamData)
                        ? ($upstreamData['products'] ?? null)
                        : null;

                    Log::warning('FatherShops service returned an invalid response.', [
                        'url' => $url,
                        'status' => $response->status(),
                        'content_type' => $response->header('Content-Type'),
                        'top_level_keys' => is_array($data)
                            ? array_keys($data)
                            : [],
                        'data_type' => gettype($upstreamData),
                        'data_keys' => is_array($upstreamData)
                            ? array_keys($upstreamData)
                            : [],
                        'products_type' => gettype($products),
                    ]);

                    throw new ExternalApiException(
                        'FatherShops service returned an invalid response.',
                        502
                    );
                }

                return $data;
            }
        );
    }
}
