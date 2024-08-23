<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class SaveTextFilterResponseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $request = [];
    public array $response = [];

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Request $request, $response = [])
    {
        $this->response = $response;
        $this->request = $request->all();
        $this->user = $request->user();
    }
}
