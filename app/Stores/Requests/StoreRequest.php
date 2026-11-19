<?php

namespace App\Stores\Requests;

use App\Stores\Store;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Creates a store for the route owner, or renames the route store.
 */
class StoreRequest extends FormRequest
{
    public function authorize(): Response
    {
        $store = $this->route('store');

        return $store instanceof Store
            ? Gate::inspect('update', $store)
            : Gate::inspect('create', [Store::class, $this->route('owner')]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }
}
