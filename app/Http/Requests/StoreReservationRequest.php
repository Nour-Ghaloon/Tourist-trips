<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;


class StoreReservationRequest extends FormRequest
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
            'trip_id' => ['required', 'exists:trips,id'],
            'comment' => 'nullable|string',
            'number_people' => ['nullable', 'integer', 'min:1'],
            //'number_children' => ['nullable', 'integer', 'min:0'],
            //'status' => ['nullable', 'in:pending,confirmed,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            // 'guest_count' => ['required','integer','min:1'],
            'reservable_id' => ['nullable', 'array'],
            'reservable_type' => [
                'nullable',
                'string',
                'in:App\\Models\\Room,App\\Models\\Restaurant,App\\Models\\Tourguide,App\\Models\\Vehicle,App\\Models\\Place'
            ],
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $trip = Trip::find($this->trip_id);
            if (!$trip) {
                return;
            }

            if ($trip->type === 'group') {
                // تحقق الحقول المطلوبة في الرحلة العامة
                if (!$this->filled('number_people')) {
                    $validator->errors()->add('number_people', 'عدد الأشخاص مطلوب في الرحلة العامة.');
                }

                if (!$this->filled('number_children')) {
                    $validator->errors()->add('number_children', 'عدد الأطفال مطلوب في الرحلة العامة.');
                }

                if (!$this->filled('status')) {
                    $validator->errors()->add('status', 'حالة الحجز مطلوبة في الرحلة العامة.');
                }

                if ($this->filled('reservable_id') || $this->filled('reservable_type')) {
                    $validator->errors()->add('reservable_id', 'لا يجب إرسال reservable_id أو reservable_type في الرحلة العامة.');
                }
            }

            if ($trip->type === 'solo') {

                // if (!$this->filled('start_date')) {
                //     $validator->errors()->add('start_date', 'تاريخ البداية مطلوب في الرحلة الخاصة.');
                // }

                // if ($this->filled('reservable_type') && $this->reservable_type !== 'App\\Models\\Restaurant'&& $this->reservable_type !== 'App\\Models\\Plce') {
                //     if (!$this->filled('end_date')) {
                //         $validator->errors()->add('end_date', 'تاريخ النهاية مطلوب في الرحلة الخاصة.');
                //     }
                // }

                // if (!$this->filled('reservable_id')) {
                //     $validator->errors()->add('reservable_id', 'الكيان المحجوز مطلوب في الرحلة الخاصة.');
                // }

                // if (!$this->filled('reservable_type')) {
                //     $validator->errors()->add('reservable_type', 'نوع الكيان المحجوز مطلوب في الرحلة الخاصة.');
                // }

                if ($this->filled('number_children') || $this->filled('status')) {
                    $validator->errors()->add('number_children', 'لا يجب إرسال عدد الأطفال أو حالة الحجز في الرحلة الخاصة.');
                }
            }
        });
    }


    public function message()
    {
        return [
            'trip_id.required' => 'يرجى اختيار رحلة',
            'start_date.after_or_equal' => 'يجب ان يكون تاريخ البدء من اليوم أو بعده',
            'end_date.after' => 'يجب ان يكون تاريخ الانتهاء بعد تاريخ البدء',
        ];
    }
}
