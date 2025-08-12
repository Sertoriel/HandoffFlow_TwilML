<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Http;

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
        return response()->xml([
            'Response' => [
                'Message' => 'Estamos transferindo você para um atendente. Aguarde um momento.'
            ]
        ], 200, ['Content-Type' => 'application/xml']);
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
            broadcast(new NewMessageEvent($session->id, $message));
        }

        return response('', 200);
    }

    public function closeChat(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
        ]);

        // Buscar e finalizar sessão
        $session = ChatSession::findOrFail($request->session_id);
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
        return response()->xml([
            'Response' => [
                'Redirect' => 'https://studio.twilio.com/v2/Flows/FWXXXXXX/Executions'
            ]
        ], 200, ['Content-Type' => 'application/xml']);
    }
}