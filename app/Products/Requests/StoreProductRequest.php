<?php

namespace App\Products\Requests;

use App\Models\Product;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('create', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required_without:products|string|max:100',
            'user_id' => 'sometimes|string|exists:users,id',
            'products' => 'required_without:name|array',
            'products.*' => 'string|max:100',
        ];
    }
}
