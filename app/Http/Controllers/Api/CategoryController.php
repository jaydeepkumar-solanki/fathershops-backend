<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ExternalApiException;
use App\Http\Controllers\Controller;
use App\Services\FatherShopsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(
        private readonly FatherShopsService $fatherShopsService
    ) {
    }

    /**
     * Get all categories.
     */
    public function index(): JsonResponse
    {
        try {
            $response = $this->fatherShopsService->getCategories();

            return ApiResponse::success(
                'Categories fetched successfully.',
                $response['response']
            );
        } catch (ExternalApiException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->statusCode
            );
        } catch (Throwable $exception) {
            Log::error('Categories API failed.', [
                'exception' => $exception->getMessage(),
            ]);

            return ApiResponse::error(
                'An unexpected error occurred while fetching categories.',
                500
            );
        }
    }

    /**
     * Get products for a specific category.
     */
    public function products(string $id): JsonResponse
    {
        try {
            $response = $this->fatherShopsService
                ->getCategoryProducts($id);

            return ApiResponse::success(
                'Category products fetched successfully.',
                $response['data']['products']
            );
        } catch (ExternalApiException $exception) {
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->statusCode
            );
        } catch (Throwable $exception) {
            Log::error('Category products API failed.', [
                'category_id' => $id,
                'exception' => $exception->getMessage(),
            ]);

            return ApiResponse::error(
                'An unexpected error occurred while fetching category products.',
                500
            );
        }
    }
}
