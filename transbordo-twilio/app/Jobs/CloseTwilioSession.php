<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloseTwilioSession implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $sessionId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $session = ChatSession::find($this->sessionId);

            if (!$session) {
                Log::warning('Attempted to close non-existent session via job', ['session_id' => $this->sessionId]);
                return;
            }

            // Chamada ao webhook de fechamento (agora assíncrona)
            $response = Http::post('http://127.0.0.1:8000/api/twilio/close', [
                'session_id' => $session->id
            ]);

            if ($response->successful()) {
                Log::info('Webhook for session close called successfully via job', [
                    'session_id' => $session->id,
                    'response' => $response->json()
                ]);
            } else {
                Log::error('Webhook for session close failed via job', [
                    'session_id' => $session->id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing CloseTwilioSession job', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}
