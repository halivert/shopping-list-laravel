<?php

namespace App\Stores\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SortStoreProductsRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('store'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'products' => 'present|array',
            'products.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('products', 'id')
                    ->where('owner_id', $this->route('store')->owner_id),
            ],
        ];
    }
}
