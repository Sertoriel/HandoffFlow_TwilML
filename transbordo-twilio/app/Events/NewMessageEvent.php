<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $message;
    public $direction;

    public function __construct($sessionId, $message, $direction)
    {
        $this->sessionId = $sessionId;
        $this->message = $message;
        $this->direction = $direction;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("chat.{$this->sessionId}");
    }

    // Adicione para compatibilidade com Reverb
    public function broadcastAs(): string
    {
        return 'NewMessageEvent';
    }
}