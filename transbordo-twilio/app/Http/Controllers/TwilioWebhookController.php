<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use App\Models\ChatSession;
use App\Models\Message;
use Illuminate\Support\Facades\Http;
use App\Events\NewMessageEvent;
use Twilio\Rest\Chat;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    public function handoff(Request $request)
    {
        // Validar dados recebidos
        $validated = $request->validate([
            'From' => 'required|string',
            'Body' => 'required|string',
        ]);

        // Criar sessão de atendimento
        $session = ChatSession::create([
            'customer_id' => $validated['From'],
            'initial_message' => $validated['Body'],
            'status' => 'active',
        ]);

        // Notificar operador (exemplo via webhook)
        Http::post('https://seu-sistema.com/notify', [
            'customer' => $validated['From'],
            'session_id' => $session->id
        ]);

        // Responder ao Twilio
        return response()->view('twiml.transfer', [], 200)
            ->header('Content-Type', 'text/xml');



        Log::debug('Handoff Request', $request->all());
    }

    public function webhook(Request $request)
    {
        // Processar mensagens do cliente
        $customer = $request->input('From');
        $message = $request->input('Body');

        // Buscar sessão ativa
        $session = ChatSession::where('customer_id', $customer)
            ->where('status', 'active')
            ->first();

        if ($session) {
            // Repassar para operador (exemplo via WebSocket)
            $session->messages()->create([
                'direction' => 'inbound',
                'body' => $message
            ]);

            broadcast(new NewMessageEvent($session->id, $message, 'inbound'));
        }

        return response('', 200);
    }

    public function closeChat(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:chat_sessions,id',
            ]);

            // Buscar e finalizar sessão
            $session = ChatSession::find($request->session_id);
            $session->update(['status' => 'completed']);

            // Enviar mensagem final
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->messages->create(
                $session->customer_id,
                [
                    'from' => env('TWILIO_WHATSAPP_NUMBER'),
                    'body' => 'Atendimento finalizado! Você será redirecionado ao nosso chatbot.'
                ]
            );

            // Redirecionar para o Flow original
            return response()->view('twiml.redirect', [
                'flow_sid' => env('TWILIO_FLOW_SID')
            ], 200)->header('Content-Type', 'text/xml');
        } catch (\Exception $e) {
            dd($e);
            Log::error('Error closing chat', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to close chat'], 500);
        }
    }
}
