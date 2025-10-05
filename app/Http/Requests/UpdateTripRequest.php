<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
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
            'description' => 'nullable|string',
            'type' => 'sometimes|in:solo,group',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'base_price' => 'sometimes|numeric|min:0',
            'is_custom' => 'sometimes|boolean',
            'number_people' => 'sometimes|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'caption' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string',
            'duration_days' => 'sometimes|integer|min:1',
            'duration_time' => 'sometimes|date_format:H:i',
            'meeting_point' => 'sometimes|string|max:255',
            'city_id' => 'sometimes|nullable|exists:cities,id',
            'vehicle_id' => 'sometimes|nullable|exists:vehicles,id',
            'tourguide_id' => 'sometimes|exists:tourguides,id'
        ];
    }
}
