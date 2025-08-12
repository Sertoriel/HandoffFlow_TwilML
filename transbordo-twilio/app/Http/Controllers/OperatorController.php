<?php

namespace App\Http\Controllers;

use App\Models\OP;
use App\Models\ChatSession;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class OperatorController extends Controller
{
    public function dashboard()
    {
        $activeSessions = ChatSession::where('status', 'active')->get();
        return view('operator', compact('activeSessions'));
    }

    public function sendMessage(Request $request)
    {
        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $twilio->messages->create(
            $request->customer,
            [
                'from' => env('TWILIO_WHATSAPP_NUMBER'),
                'body' => $request->message
            ]
        );
    }

    public function closeSession(Request $request)
    {
        Http::post(env('APP_URL') . '/api/twilio/close', [
            'session_id' => $request->session_id
        ]);
    }
}
