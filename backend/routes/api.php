<?php

use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::post('/check', [VoucherController::class, 'check']);
Route::post('/generate', [VoucherController::class, 'generate']);
