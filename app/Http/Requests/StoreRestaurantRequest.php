<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
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
            'name' => 'required|string',
            'menu' => 'nullable|:png,jpg,jpeg,gif,webp|max:4096',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'contact_info' => 'required|string',
            'opening_hours' => 'required|string',
            'rate' => 'nullable|between:0,5',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'city_id' => 'required|exists:cities,id',
            'capacity' => 'required|integer|min:1',
        ];
    }
}
