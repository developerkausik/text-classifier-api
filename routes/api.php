<?php

use App\Http\Controllers\ClassificationController;
use Illuminate\Support\Facades\Route;

Route::post('/classify', ClassificationController::class)
    ->middleware('throttle:30,1')
    ->name('classify');
