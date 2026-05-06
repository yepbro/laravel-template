<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\IslandsController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class);
Route::get('/islands', IslandsController::class);
Route::get('/spa/{path?}', SpaController::class)->where('path', '.*');
