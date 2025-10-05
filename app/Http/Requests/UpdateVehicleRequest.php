<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
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
            'type' => 'sometimes|string',
            'name' => 'sometimes|string',
            'capacity' => 'sometimes|integer|min:1',
            'price' => 'sometimes|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'driver_id' => 'sometimes|exists:drivers,id'
        ];
    }
}
