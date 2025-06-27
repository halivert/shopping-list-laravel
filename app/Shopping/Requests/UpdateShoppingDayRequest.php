<?php

namespace App\Shopping\Requests;

use App\Models\Product;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateShoppingDayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->shoppingDay);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'touch' => 'sometimes|boolean',
            'items' => 'array|required',
            'items.*.id' => [
                'required',
                'string',
                Rule::exists('shopping_day_items', 'id')
                    ->where('shopping_day_id', $this->shoppingDay->id),
            ],
            'items.*.index' => 'sometimes|integer',
            'items.*.quantity' => 'sometimes|numeric',
            'items.*.unitPrice' => 'sometimes|numeric',

            'products' => 'array',
            'products.*.id' => [
                'required',
                'string',
                Rule::exists('products', 'id')
                    ->where('owner_id', $this->shoppingDay->owner_id),
            ],
            'products.*.index' => 'required|integer'
        ];
    }
}
