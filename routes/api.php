<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;

Route::prefix('categories')->group(function () {

    // Get all categories
    Route::get('/', [CategoryController::class, 'index']);

    // Get products by category ID
    Route::get('/{id}/products', [
        CategoryController::class,
        'products'
    ])->whereNumber('id');

});

// Get product details by product ID
Route::get('/products/{id}', [
    ProductController::class,
    'show'
])->whereNumber('id');
