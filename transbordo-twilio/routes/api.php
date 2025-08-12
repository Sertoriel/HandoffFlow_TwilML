<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwilioWebhookController;

// Exemplo de rota API
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando!']);
});
// Rotas do Twilio
Route::prefix('twilio')->group(function () {
    Route::post('/handoff', [TwilioWebhookController::class, 'handoff']);
    Route::post('/webhook', [TwilioWebhookController::class, 'webhook']);
    Route::post('/close', [TwilioWebhookController::class, 'closeChat']);
});