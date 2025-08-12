<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OperatorController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/operator', [OperatorController::class, 'dashboard']);