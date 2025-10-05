<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
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
            'type' => 'required|string',
            'name' => 'required|string',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'driver_id' => 'required|exists:drivers,id'
        ];
    }
}
