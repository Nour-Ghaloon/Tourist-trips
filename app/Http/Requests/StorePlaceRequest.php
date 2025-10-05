<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceRequest extends FormRequest
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
            'type' => 'required|string',
            'icon' => 'required|string',
            'description' => 'nullable|string',
            'entry_free' => 'nullable|numeric|min:0',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'city_id' => 'required|exists:cities,id'

        ];
    }
}
