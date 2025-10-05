<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Models\Discount;
use App\Models\Reservation;
use App\Models\Trip;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $discount = Discount::all();
        return response()->json($discount, 200);
    }

    public function discountForTrip($TripId)
    {
        $trip = Trip::With('discounts')->findOrFail($TripId);
        return response()->json($trip, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDiscountRequest $request)
    {
        $data = $request->validated();

        // نجبر أن يكون الحسم على الرحلة فقط
        if (($data['discountable_type'] ?? null) !== Trip::class) {
            return response()->json([
                'message' => 'الحسم متاح فقط على الرحلات.'
            ], 422);
        }

        // تحقق من الرحلة
        $trip = Trip::findOrFail($data['discountable_id']);

        // تحقق من أن الرحلة من نوع group
        if ($trip->type !== 'group') {
            return response()->json([
                'message' => 'يمكن إضافة الحسم فقط على الرحلات العامة (group).'
            ], 422);
        }

        // التحقق من وجود قيمة إما amount أو percentage
        $hasPct = !empty($data['percentage']);
        $hasAmt = !empty($data['amount']);
        if ($hasPct && $hasAmt) {
            return response()->json([
                'message' => 'يرجى اختيار إما نسبة مئوية أو مبلغ ثابت، وليس كلاهما.'
            ], 422);
        }
        if (!$hasPct && !$hasAmt) {
            return response()->json([
                'message' => 'يجب تحديد نسبة مئوية أو مبلغ ثابت للحسم.'
            ], 422);
        }

        // التحقق من نوع الحسم (string)
        $validTypes = ['general', 'child', 'early_bird'];
        if (empty($data['type']) || !in_array($data['type'], $validTypes)) {
            return response()->json([
                'message' => 'نوع الحسم غير صحيح. القيم المسموحة: general, child, early_bird.'
            ], 422);
        }

        // التحقق من max_uses فقط إذا كان early_bird
        if ($data['type'] === 'early_bird') {
            if (empty($data['max_uses']) || (int)$data['max_uses'] <= 0) {
                return response()->json([
                    'message' => 'يجب تحديد max_uses لحسم الأوائل (عدد المستفيدين الأوائل).'
                ], 422);
            }
        }

        // إنشاء الحسم
        $discount = Discount::create([
            'discountable_id'   => $trip->id,
            'discountable_type' => Trip::class,
            'type'              => $data['type'],       // string
            'percentage'        => $data['percentage'] ?? null,
            'amount'            => $data['amount'] ?? null,
            'max_uses'          => $data['max_uses'] ?? null,
            'valid_until'       => $data['valid_until'] ?? null,
        ]);

        return response()->json([
            'message' => 'تمت إضافة الحسم بنجاح.',
            'data'    => $discount,
        ], 201);




        // $validated = Discount::create($request->validated());
        // // جلب الحجز المرتبط بالحسم
        // $reservation = Reservation::with('trip')->findOrFail($validated['discountable_id']);

        // // تحقق من أن العلاقة هي مع الحجز وليس شيء آخر
        // if ($validated['discountable_type'] !== Reservation::class) {
        //     return response()->json([
        //         'message' => 'الحسم متاح فقط لحجوزات الرحلات العامة.'
        //     ], 422);
        // }

        // // تحقق من أن الرحلة المرتبطة بالحجز هي رحلة عامة فقط
        // if (!$reservation->trip || $reservation->trip->type !== 'group') {
        //     return response()->json([
        //         'message' => 'يمكن إضافة الحسم فقط على الرحلات العامة.'
        //     ], 422);
        // }

        // // إنشاء الحسم
        // $discount = Discount::create([
        //     'amount'             => $validated['amount'],
        //     'percentage'         => $validated['percentage'],
        //     'valid_until'        => $validated['valid_until'],
        //     'discountable_id'    => $validated['discountable_id'],
        //     'discountable_type'  => $validated['discountable_type'],
        // ]);

        // return response()->json([
        //     'message' => 'تمت إضافة الحسم بنجاح.',
        //     'data'    => $discount
        // ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $discount = Discount::FindOrFail($id);
        return response()->json($discount, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDiscountRequest $request, string $id)
    {
        $discount = Discount::findOrFail($id);

        // التحقق من أن الحسم مرتبط بحجز
        if ($discount->discountable_type !== Reservation::class) {
            return response()->json([
                'message' => 'الحسم متاح فقط لحجوزات الرحلات العامة.'
            ], 422);
        }

        $reservation = Reservation::with('trip')->findOrFail($discount->discountable_id);

        // التحقق أنه حجز رحلة عامة
        if (!$reservation->trip || $reservation->trip->type !== 'group') {
            return response()->json([
                'message' => 'يمكن تعديل الحسم فقط على الرحلات العامة.'
            ], 422);
        }

        $discount->update($request->validated());

        return response()->json([
            'message' => 'تم تعديل الحسم بنجاح.',
            'data'    => $discount,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $discount = Discount::findOrFail($id);

        // التحقق من أن الحسم مرتبط بحجز
        if ($discount->discountable_type !== Reservation::class) {
            return response()->json([
                'message' => 'الحذف مسموح فقط لحسومات الرحلات العامة'
            ], 422);
        }

        $reservation = Reservation::with('trip')->findOrFail($discount->discountable_id);

        // التحقق أنه مرتبط برحلة عامة
        if (!$reservation->trip || $reservation->trip->type !== 'group') {
            return response()->json([
                'message' => 'لا يمكن حذف الحسم إلا من الرحلات العامة.'
            ], 422);
        }

        $discount->delete();

        return response()->json([
            'message' => 'تم حذف الحسم بنجاح.'
        ]);
    }
}
