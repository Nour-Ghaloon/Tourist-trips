<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
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
            'name' => 'sometimes|string',
            'menu' => 'nullable||mimes:png,jpg,jpeg,gif,webp|max:4096',
            'description' => 'nullable|string',
            'address' => 'sometimes|string',
            'contact_info' => 'sometimes|string',
            'opening_hours' => 'sometimes|string',
            'rate' => 'nullable|between:0,5',
            'city_id' => 'sometimes|exists:cities,id',
            'capacity' => 'sometimes|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
        ];
    }
}
