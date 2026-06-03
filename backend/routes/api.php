<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArticleController;
use Illuminate\Support\Facades\Route;

// Routes publiques (tout le monde peut lire)
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::post('/login', [AuthController::class, 'login']);

// Routes admin (pour le développement, pas d'auth)
Route::prefix('admin')->group(function () {
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
});
