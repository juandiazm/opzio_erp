<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class pusherEvents implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $SERVICE_CHANNEL;
    public $SERVICE_EVENT;
    public $message;
    
    public function __construct($SERVICE_CHANNEL, $SERVICE_EVENT, $message)
    {
        $this->SERVICE_CHANNEL = 'opzio-channel-'.$SERVICE_CHANNEL;
        $this->SERVICE_EVENT = 'opzio-event-'.$SERVICE_EVENT;
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return [$this->SERVICE_CHANNEL];
    }
    public function broadcastAs() {

        return $this->SERVICE_EVENT;
    }
}
