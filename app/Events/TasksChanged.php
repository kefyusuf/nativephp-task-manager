<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TasksChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * Live PHP → webview event bridge over the NativePHP channel.
     */
    public function __construct(
        public string $action, // created | updated | deleted
        public string $title,
    ) {}

    /**
     * @return array<int, string>
     */
    public function broadcastOn(): array
    {
        return ['nativephp'];
    }
}
