<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
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
            'comment' => 'nullable|string',
            'rate' => 'sometimes|between:0,5',
            'reviewable_id' => 'sometimes|integer',
            'reviewable_type' => 'sometimes|string',
            'user_id' => 'sometimes|exists:users,id'
        ];
    }
}
