<?php

namespace App\Http\Controllers;

use App\Models\OP;
use App\Models\ChatSession;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Twilio\Rest\Chat;

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
        $request->validate([
            'session_id' => 'required|exists:chat_sessions,id',
        ]);

        $session = ChatSession::find($request->session_id);
        // $session->update(['status' => 'closed']);

        //chamado de Webhook de fechamento
        Http::post(env('APP_URL') . '/api/twilio/close', [
            'session_id' => $session->id
        ]);

        return response()->json(['status' => 'success']);
    }
}
