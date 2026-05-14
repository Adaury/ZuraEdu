<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GradePublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'notifications';

    public function __construct(
        public int    $grupoId,
        public string $periodo,
        public string $asignatura,
        public ?int   $estudianteUserId = null, // null = publicar a todo el grupo
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("private-grupo.{$this->grupoId}")];

        if ($this->estudianteUserId) {
            $channels[] = new PrivateChannel("private-user.{$this->estudianteUserId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'grade.published';
    }

    public function broadcastWith(): array
    {
        return [
            'grupo_id'   => $this->grupoId,
            'periodo'    => $this->periodo,
            'asignatura' => $this->asignatura,
            'mensaje'    => "Se publicaron las calificaciones de {$this->asignatura} — {$this->periodo}",
        ];
    }
}
