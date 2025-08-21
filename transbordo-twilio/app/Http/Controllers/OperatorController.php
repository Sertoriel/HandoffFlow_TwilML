<?php

namespace App\Http\Controllers;

use App\Models\OP;
use App\Models\ChatSession;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Twilio\Rest\Chat;
use Illuminate\Support\Facades\Log;
use App\Jobs\CloseTwilioSession;

class OperatorController extends Controller
{
    public function dashboard()
    {
        $activeSessions = ChatSession::with('messages')
            ->where('status', 'active')
            ->get();

        return view('operator', compact('activeSessions'));
    }


    public function getMessages(ChatSession $session)
    {
        return response()->json($session->messages()->orderBy('created_at')->get());
    }


    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:chat_sessions,id',
            'message' => 'required|string',
        ]);

        $session = ChatSession::find($request->session_id);
        // $session->update(['status' => 'answered']);
        //Salva a mensagem no banco de dados
        $session->messages()->create([
            'direction' => 'outbound',
            'body' => $request->message
        ]);

        //Envia Via Twilio
        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $twilio->messages->create(
            $session->customer_id,
            [
                'from' => env('TWILIO_WHATSAPP_NUMBER'),
                'body' => $request->message
            ]
        );

        return response()->json(['status' => 'success']);
    }

    public function closeSession(Request $request)
    {
        try {
            $request->validate([
                'session_id' => 'required|exists:chat_sessions,id',
            ]);

            $session = ChatSession::find($request->session_id);

            // 1. Atualizar status da sessão para 'completed' imediatamente
            $session->update(['status' => 'completed']);
            Log::info('Session status updated to completed', ['session_id' => $session->id]);

            // 2. Despachar job para fechar a sessão no Twilio de forma assíncrona
            // Isso evita bloquear a requisição do operador
            CloseTwilioSession::dispatch($session->id);

            return response()->json(['status' => 'success', 'message' => 'Session close initiated']);
        } catch (\Exception $e) {
            // 3. Logging adequado em produção
            Log::error('Error closing session from OperatorController', [
                'session_id' => $request->session_id ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Failed to initiate session close'], 500);
        }
    }
}
