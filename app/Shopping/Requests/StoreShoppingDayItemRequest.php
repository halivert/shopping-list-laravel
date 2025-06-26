<?php

namespace App\Shopping\Requests;

use App\Shopping\ShoppingDayItem;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreShoppingDayItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect(
            'create',
            [ShoppingDayItem::class, $this->shoppingDay]
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'string',
                'required',
                Rule::exists('products', 'id')
                    ->where('owner_id', $this->shoppingDay->owner_id)
            ],
            'index' => 'integer|required',
            'items' => 'array|sometimes',
            'items.*' => [
                'string',
                'required',
                Rule::exists('shopping_day_items', 'id')
                    ->where('shopping_day_id', $this->shoppingDay->id)
            ]
        ];
    }
}
