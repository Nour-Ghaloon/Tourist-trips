<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreTourguideRequest extends FormRequest
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
            'language' => 'required|array',
            'price' => 'required|integer|min:0',
            'city_id' => 'required|exists:cities,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
        ];
    }
}
