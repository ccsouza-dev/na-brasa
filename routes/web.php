<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ApiController;

Route::get('/', [HomeController::class, 'index'])->name('dashboard');
Route::get('/stock', [HomeController::class, 'stock'])->name('stock');
Route::get('/financial', [HomeController::class, 'financial'])->name('financial');
Route::get('/mobile', [HomeController::class, 'mobile'])->name('mobile');
Route::get('/caderneta', [HomeController::class, 'caderneta'])->name('caderneta');

Route::match(['get', 'post'], '/api.php', [ApiController::class, 'handle'])->name('api.legacy');
