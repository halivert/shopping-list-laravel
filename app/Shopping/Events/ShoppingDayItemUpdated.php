<?php

namespace App\Shopping\Events;

use App\Shopping\Resources\ShoppingDayItemResource;
use App\Shopping\ShoppingDayItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShoppingDayItemUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ShoppingDayItemResource $shoppingDayItem;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private ShoppingDayItem $internalShoppingDayItem
    ) {
        $this->shoppingDayItem = new ShoppingDayItemResource(
            $internalShoppingDayItem
        );
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $shoppingDayId = $this->internalShoppingDayItem->shopping_day_id;

        return [
            new PrivateChannel('shopping-day-updated.' . $shoppingDayId),
        ];
    }
}
