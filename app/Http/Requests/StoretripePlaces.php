<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoretripePlaces extends FormRequest
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
            'trip_id' => 'required|exists:trips,id',
            'place_id' => 'required|exists:places,id',
            'restaurant_id' => 'nullable|exists:restaurants,id',
            'hotel_id'=>[
                'nullable',
                'exists:hotels,id',
                Rule::unique('trip_places')->where(fn($q)=>
                $q->where('trip_id',$this->trip_id))->where('hotel_id',$this->hotel_id)
            ]

        ];
    }
}
