<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccessResource extends JsonResource
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
            'userEmail' => $this->user_email,
            'user' => UserResource::make($this->whenLoaded('user')),
            'accessible' => $this->whenLoaded('accessible', function () {
                if ($this->accessible instanceof \App\Models\User) {
                    return UserResource::make($this->accessible);
                }
            }),
            'approvedAt' => $this->whenNotNull($this->approved_at),
        ];
    }
}
