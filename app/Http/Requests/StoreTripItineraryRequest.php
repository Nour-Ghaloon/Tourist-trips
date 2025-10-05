<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTripItineraryRequest extends FormRequest
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
            'day_number' => 'required|integer|min:1',
            'title' => 'required|string',
            'short_title' => 'nullable|string',
            'description' => 'nullable|string',
            'full_description' => 'nullable|string',
            'place_id' => 'nullable|exists:places,id',
            'hotel_id' => 'nullable|exists:hotels,id',
            'restaurant_id' => 'nullable|exists:restaurants,id',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'map_location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
