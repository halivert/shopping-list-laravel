<?php

namespace App\Shopping\Resources;

use App\Http\Resources\ProductResource;
use App\Shopping\Resources\ShoppingDayResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingDayItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shoppingDay' => ShoppingDayResource::make(
                $this->whenLoaded('shoppingDay')
            ),
            'product' => ProductResource::make($this->product),
            'index' => $this->index,
            'unitPrice' => $this->unit_price,
            'quantity' => $this->quantity,
        ];
    }
}
