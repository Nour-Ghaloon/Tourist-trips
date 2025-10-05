<?php

namespace App\Http\Requests;

use App\Models\Trip;
use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
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
            'description' => 'nullable|string',
            'type' => 'required|in:solo,group',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'base_price' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:png,jpg,jpeg,gif,webp|max:4096',
            'caption' => 'nullable|string',
            'alt_text' => 'nullable|string',
            'duration_days' => 'nullable|integer|min:1',
            'duration_time' => 'required|date_format:H:i',
            'meeting_point' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'tourguide_id' => 'nullable|exists:tourguides,id',
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
                if (!$this->filled('name')) {
                    $validator->errors()->add('name', 'الاسم مطلوب في الرحلة العامة.');
                }

                if (!$this->filled('type')) {
                    $validator->errors()->add('type', 'نوع الرحلة مطلوب في الرحلة العامة.');
                }

                if (!$this->filled('start_date')) {
                    $validator->errors()->add('start_date', 'تاريخ البداية مطلوب في الرحلة العامة.');
                }

                if (!$this->filled('end_date')) {
                    $validator->errors()->add('end_date', 'تاريخ النهاية مطلوب في الرحلة العامة.');
                }
                if (!$this->filled('base_price')) {
                    $validator->errors()->add('base_price', ' السعر مطلوب في الرحلة العامة.');
                }
                if (!$this->filled('capacity')) {
                    $validator->errors()->add('capacity', ' السعة مطلوبة في الرحلة العامة.');
                }
                if (!$this->filled('duration_days')) {
                    $validator->errors()->add('duration_days', 'عدد الايام مطلوب في الرحلة العامة.');
                }
                if (!$this->filled('duration_time')) {
                    $validator->errors()->add('duration_time', 'التوقيت  مطلوب في الرحلة العامة.');
                }
                if (!$this->filled('meeting_point')) {
                    $validator->errors()->add('meeting_point', 'نقطة التجمع  مطلوبة في الرحلة العامة.');
                }
                if (!$this->filled('city_id')) {
                    $validator->errors()->add('city_id', 'المدينة مطلوبة في الرحلة العامة.');
                }
                if (!$this->filled('tourguide_id')) {
                    $validator->errors()->add('tourguide_id', 'الدليل السياحي مطلوب في الرحلة العامة.');
                }
            }

            if ($trip->type === 'solo') {
                // تحقق الحقول المطلوبة في الرحلة الخاصة
                if (!$this->filled('name')) {
                    $validator->errors()->add('name', 'الاسم مطلوب في الرحلة الخاصة.');
                }

                if (!$this->filled('start_date')) {
                    $validator->errors()->add('start_date', 'تاريخ البداية مطلوب في الرحلة الخاصة.');
                }
                if (!$this->filled('end_date')) {
                    $validator->errors()->add('end_date', 'تاريخ النهاية مطلوب في الرحلة الخاصة.');
                }
                if (!$this->filled('duration_time')) {
                    $validator->errors()->add('duration_time', 'التوقيت مطلوب في الرحلة الخاصة.');
                }
                if (!$this->filled('meeting_point')) {
                    $validator->errors()->add('meeting_point', 'نقطة التجمع مطلوبة في الرحلة الخاصة.');
                }
                if (!$this->filled('city_id')) {
                    $validator->errors()->add('city_id', ' المدينة مطلوبة في الرحلة الخاصة.');
                }
            }
        });
    }
    public function messages()
    {
        return [
            'name.string' => 'يرجى ادخال الاسم بشكل صحيح',


        ];
    }
}
