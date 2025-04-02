<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PlaceController;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/places', [PlaceController::class, 'index']);

// 搜索相關路由
Route::get('/popular-searches', [PlaceController::class, 'getPopularSearches']);
Route::get('/related-searches', [PlaceController::class, 'getRelatedSearches']);

// 評分相關路由
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/places/{id}/stats', [PlaceController::class, 'getPlaceStats']);
    Route::post('/places/{id}/ratings', [PlaceController::class, 'submitRating']);
    Route::get('/ratings', [PlaceController::class, 'getUserRatings']);
    Route::post('/ratings/{id}/verify', [PlaceController::class, 'verifyRating']);
    
    // 評分篩選相關路由
    Route::get('/places/{id}/ratings', [PlaceController::class, 'getPlaceRatings']);
    Route::get('/places/{id}/rating-tags', [PlaceController::class, 'getPlaceRatingTags']);
    Route::get('/places/{id}/rating-time-distribution', [PlaceController::class, 'getPlaceRatingTimeDistribution']);
    
    // 評分展示優化相關路由
    Route::get('/places/{id}/rating-summary', [PlaceController::class, 'getPlaceRatingSummary']);
    Route::get('/places/{id}/enhanced-ratings', [PlaceController::class, 'getEnhancedPlaceRatings']);
});

// 搜索歷史相關路由
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/search-history', [PlaceController::class, 'getSearchHistory']);
    Route::delete('/search-history', [PlaceController::class, 'clearSearchHistory']);
}); 