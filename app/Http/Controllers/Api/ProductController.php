<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ExternalApiException;
use App\Http\Controllers\Controller;
use App\Services\FatherShopsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductController extends Controller
{
    public function __construct(
        private readonly FatherShopsService $fatherShopsService
    ) {
    }

    /**
     * Get details of a specific product.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->fatherShopsService
                ->getProductDetails($id);

            return ApiResponse::success(
                'Product details fetched successfully.',
                $product
            );
        } catch (ExternalApiException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->statusCode
            );
        } catch (Throwable $exception) {
            Log::error('Product details API failed.', [
                'product_id' => $id,
                'exception' => $exception->getMessage(),
            ]);

            return ApiResponse::error(
                'An unexpected error occurred while fetching product details.',
                500
            );
        }
    }
}
