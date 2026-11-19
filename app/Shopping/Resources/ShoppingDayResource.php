<?php

namespace App\Shopping\Resources;

use App\Http\Resources\UserResource;
use App\Stores\Resources\StoreResource;
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
            'store' => $this->whenNotNull(
                $this->store ? StoreResource::make($this->store) : null
            ),
            'learnOrder' => $this->learn_order ?? true,
            // Already learned (when the next day in its store started)
            'learned' => $this->learned_at !== null,
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
