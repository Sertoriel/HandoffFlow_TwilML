<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OperatorController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/operator', [OperatorController::class, 'dashboard']);

// routes/web.php
Route::get('/operator/session/{session}/messages', [OperatorController::class, 'getMessages']);

Route::post('/operator/send', [OperatorController::class, 'sendMessage'])->name('operator.send');

Route::post('/operator/close', [OperatorController::class, 'closeSession'])->name('operator.close');