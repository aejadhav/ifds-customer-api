<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerNotificationPushed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $customerId,       // ifds_customer_id
        public readonly string $notificationId,   // BFF notification UUID
        public readonly string $type,
        public readonly string $title,
        public readonly ?string $body,
        public readonly array  $data,
        public readonly string $createdAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("customer.{$this->customerId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.pushed';
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notificationId,
            'type'            => $this->type,
            'title'           => $this->title,
            'body'            => $this->body,
            'data'            => $this->data,
            'created_at'      => $this->createdAt,
        ];
    }
}
