<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'owner' => UserResource::make($this->whenLoaded('owner')),
            'searchIndex' => $this->whenNotNull('search_index'),
            'shoppingIndex' => $this->whenNotNull('shopping_index'),
        ];
    }
}
