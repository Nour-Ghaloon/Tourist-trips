<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
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
            'number_people' => 'sometimes|integer|min:1',
            'reservable_id' => 'sometimes|integer',
            'reservable_type' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'comment' => 'sometimes|string',
            'user_id' => 'sometimes|exists:users,id',
            'trip_id' => 'sometimes|exists:trips,id',
            'number_children' => [
                'nullable',
                'integer',
                'min:0',
                // function ($attribute, $value, $fail) {
                //     $trip = Trip::find($this->trip_id);
                //     if (!$trip || $trip->role !== 'group') {
                //         $fail('."group" هذا الخيار متاح فقط للرحلات العامة ');
                //     }
                // }
            ]
        ];
    }
}
