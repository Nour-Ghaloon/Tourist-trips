<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
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
            'discountable_type' => 'required|string|in:App\\Models\\Trip',
            'discountable_id'   => 'required|integer|exists:trips,id',
            'type'              => 'required|string|in:general,child,early_bird',
            'percentage'        => 'nullable|numeric|min:0|max:100',
            'amount'            => 'nullable|numeric|min:0',
            'max_uses'          => 'nullable|integer|min:1',
            'valid_until'       => 'nullable|date|after_or_equal:today',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            // تحقق أن هناك إما percentage أو amount
            if (empty($data['percentage']) && empty($data['amount'])) {
                $validator->errors()->add('discount', 'يجب تحديد قيمة الحسم: نسبة مئوية أو مبلغ ثابت.');
            }

            // تحقق ألا يكون كلاهما معاً
            if (!empty($data['percentage']) && !empty($data['amount'])) {
                $validator->errors()->add('discount', 'يرجى اختيار إما نسبة مئوية أو مبلغ ثابت، وليس كلاهما.');
            }

            // تحقق من max_uses إذا كان early_bird
            if (($data['type'] ?? '') === 'early_bird' && empty($data['max_uses'])) {
                $validator->errors()->add('max_uses', 'يجب تحديد عدد المستفيدين للأوائل (max_uses).');
            }
        });
    }
}
