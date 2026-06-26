<?php

namespace App\Products\Events;

use App\Http\Resources\ProductResource;
use App\Products\Product;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProductResource $product;

    public function __construct(
        private Product $internalProduct
    ) {
        $this->product = new ProductResource($internalProduct);
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('product-list.' . $this->internalProduct->owner_id),
        ];
    }
}
