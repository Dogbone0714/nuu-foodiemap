<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PlaceController;

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/places', [PlaceController::class, 'index']); 