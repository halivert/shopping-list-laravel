<?php

namespace App\Shopping\Requests;

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
            'items' => 'array|required|min:2',
            'items.*' => [
                'required',
                'string',
                Rule::exists('shopping_day_items', 'id')
                    ->where('shopping_day_id', $this->shoppingDay->id),
            ]
        ];
    }
}
