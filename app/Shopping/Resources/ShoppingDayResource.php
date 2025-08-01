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
            'date' => $this->date,
            'owner' => UserResource::make($this->owner),
            'items' => ShoppingDayItemResource::collection(
                $this->whenLoaded('items')
            ),
            'updatedAt' => $this->when(
                !$this->created_at->equalTo($this->updated_at),
                $this->updated_at
            )
        ];
    }
}
