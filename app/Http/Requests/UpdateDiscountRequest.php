<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
           'discountable_type' => 'sometimes|string|in:App\\Models\\Trip',
            'discountable_id'   => 'sometimes|integer|exists:trips,id',
            'type'              => 'sometimes|string|in:general,child,early_bird',
            'percentage'        => 'nullable|numeric|min:0|max:100',
            'amount'            => 'nullable|numeric|min:0',
            'max_uses'          => 'nullable|integer|min:1',
            'valid_until'       => 'nullable|date|after_or_equal:today',
        ];
    }
}
