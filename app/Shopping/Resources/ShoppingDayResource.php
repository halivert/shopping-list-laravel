<?php

namespace App\Shopping\Resources;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShoppingDayResource extends JsonResource
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
            'date' => $this->date->format('Y-m-d'),
            'owner' => UserResource::make($this->owner),
            'items' => ShoppingDayItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}
