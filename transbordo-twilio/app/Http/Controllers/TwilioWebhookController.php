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
        Log::debug('Handoff Request Received', $request->all());

        // Validar dados recebidos, incluindo os SIDs do Twilio Studio
        $validated = $request->validate([
            'From' => 'required|string',
            'Body' => 'required|string',
            'FlowSid' => 'nullable|string', // Twilio Flow SID
            'ExecutionSid' => 'nullable|string', // Twilio Execution SID
        ]);

        // Criar sessão de atendimento, armazenando os SIDs
        $session = ChatSession::create([
            'customer_id' => $validated['From'],
            'initial_message' => $validated['Body'],
            'status' => 'active',
            'twilio_flow_sid' => $validated['FlowSid'] ?? null,
            'twilio_execution_sid' => $validated['ExecutionSid'] ?? null,
        ]);

        // Notificar operador (exemplo via webhook - idealmente assíncrono)
        // Http::post('https://seu-sistema.com/notify', [
        //     'customer' => $validated['From'],
        //     'session_id' => $session->id
        // ]);
        Log::info('New chat session created and handoff initiated', ['session_id' => $session->id, 'customer_id' => $validated['From'], 'flow_sid' => $session->twilio_flow_sid, 'execution_sid' => $session->twilio_execution_sid]);

        // Disparar evento para atualizar a lista de sessões no painel do operador
        broadcast(new \App\Events\NewChatSessionEvent($session));

        // Responder ao Twilio com TwiML vazio ou de transferência
        return response()->view('twiml.transfer', [], 200)
            ->header('Content-Type', 'text/xml');
    }



    public function webhook(Request $request)
    {
        Log::debug('Inbound Webhook Received', $request->all());

        $customer = $request->input('From');
        $messageBody = $request->input('Body');
        $messageSid = $request->input('MessageSid'); // Capturar o Message SID

        $session = ChatSession::where('customer_id', $customer)
            ->where('status', 'active')
            ->first();

        if ($session) {
            // Salvar a mensagem no banco de dados com o SID do Twilio
            $message = $session->messages()->create([
                'direction' => 'inbound',
                'body' => $messageBody,
                'twilio_message_sid' => $messageSid, // Salvar o SID da mensagem
            ]);

            // Disparar evento para o operador com a mensagem completa
            broadcast(new NewMessageEvent($session->id, $message->body, 'inbound'));
            Log::info('Inbound message processed and broadcasted', ['session_id' => $session->id, 'message_body' => $messageBody, 'message_sid' => $messageSid]);
        } else {
            Log::info('No active session found for customer, message ignored or handled by bot', ['customer_id' => $customer, 'message_body' => $messageBody]);
            // Opcional: Aqui você pode adicionar lógica para enviar a mensagem de volta para o bot
            // ou criar uma nova sessão se for o caso (se o handoff não tiver ocorrido ainda).
        }

        return response('', 200);
    }



    public function closeChat(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:chat_sessions,id',
            ]);

            $session = ChatSession::find($request->session_id);

            if (!$session) {
                Log::warning('Attempted to close non-existent session', ['session_id' => $request->session_id]);
                return response()->json(['status' => 'error', 'message' => 'Session not found'], 404);
            }

            $session->update(['status' => 'completed']);
            Log::info('Chat session status updated to completed', ['session_id' => $session->id]);

            // Enviar mensagem final
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->messages->create(
                $session->customer_id,
                [
                    'from' => env('TWILIO_WHATSAPP_NUMBER'),
                    'body' => 'Atendimento finalizado! Você será redirecionado ao nosso chatbot.'
                ]
            );
            Log::info('Final message sent to customer', ['session_id' => $session->id, 'customer_id' => $session->customer_id]);

            // Redirecionar para o Flow original usando updateExecution
            if ($session->twilio_flow_sid && $session->twilio_execution_sid) {
                try {
                    $twilio->studio->v2->flows($session->twilio_flow_sid)
                        ->executions($session->twilio_execution_sid)
                        ->update('ended'); // Finaliza a execução do Flow
                    Log::info('Twilio Flow execution ended via updateExecution', [
                        'session_id' => $session->id,
                        'flow_sid' => $session->twilio_flow_sid,
                        'execution_sid' => $session->twilio_execution_sid
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to update Twilio Flow execution', [
                        'session_id' => $session->id,
                        'flow_sid' => $session->twilio_flow_sid,
                        'execution_sid' => $session->twilio_execution_sid,  
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]);
                    // Fallback para TwiML Redirect se updateExecution falhar
                    return response()->view('twiml.redirect', [
                        'flow_sid' => env('TWILIO_FLOW_SID')
                    ], 200)->header('Content-Type', 'text/xml');
                }
            } else {
                Log::warning('Missing Flow SID or Execution SID for updateExecution, falling back to TwiML Redirect', ['session_id' => $session->id]);
                // Fallback para TwiML Redirect se os SIDs não estiverem disponíveis
                return response()->view('twiml.redirect', [
                    'flow_sid' => env('TWILIO_FLOW_SID')
                ], 200)->header('Content-Type', 'text/xml');
            }

            return response()->json(['status' => 'success', 'message' => 'Chat closed and Flow updated']);
        } catch (\Exception $e) {
            Log::error('Error closing chat in TwilioWebhookController', [
                'session_id' => $request->session_id ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Failed to close chat'], 500);
        }
    }
}
