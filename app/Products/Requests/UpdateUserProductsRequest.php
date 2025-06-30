<?php

namespace App\Products\Requests;

use App\Products\Product;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUserProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', [Product::class, $this->owner]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'list' => 'required|string|in:search_index,shopping_index',
            'products' => 'required|array',
            'products.*' => [
                'required',
                'string',
                Rule::exists('products', 'id')
                    ->where('owner_id', $this->owner->id)
            ],
        ];
    }
}
