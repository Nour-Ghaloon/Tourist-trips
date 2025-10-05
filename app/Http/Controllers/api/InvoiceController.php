<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\CustomNotification;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoice = Invoice::all();
        return response()->json($invoice, 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function confirmPayment($invoiceId)
    {
        $user = auth()->user();
        $invoice = Invoice::with('reservation')->findOrFail($invoiceId);
        $reservation = $invoice->reservation;

        // تحقق أن الفاتورة بانتظار الدفع
        if ($invoice->payment_status !== 'pending') {
            return response()->json(['error' => 'لا يمكن تأكيد الدفع، حالة الفاتورة ليست pending'], 422);
        }

        $wallet = $user->wallet;
        $amount = floatval($invoice->total_amount);

        // تحقق الرصيد
        if ($wallet->balance < $amount) {
            return response()->json(['error' => 'الرصيد غير كافٍ لإتمام الدفع'], 422);
        }

        DB::transaction(function () use ($wallet, $amount, $invoice, $reservation, $user) {

            // خصم المبلغ من المحفظة
            $wallet->decrement('balance', $amount);
            $wallet->transactions()->create([
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => 'تأكيد دفع حجز #' . $reservation->id,
            ]);

            // تحديث حالة الفاتورة إلى paid
            $invoice->update([
                'payment_status' => 'paid'
            ]);

            // تحديث حالة الحجز إلى confirmed
            $reservation->update([
                'status' => 'confirmed'
            ]);

            // إشعار المستخدم بالدفع
            CustomNotification::create([
                'user_id' => $user->id,
                'title' => 'تم الدفع بنجاح',
                'body' => "تم خصم {$amount} من محفظتك لتأكيد الحجز رقم {$reservation->id}.",
                'reservation_id' => $reservation->id
            ]);

            // إشعار صاحب الـ reservable
            if ($reservation->reservable_type && $reservation->reservable_id) {
                $reservable = $reservation->reservable;
                if ($reservable instanceof \App\Models\Room && $reservable->hotel->user) {
                    CustomNotification::create([
                        'user_id' => $reservable->hotel->user->id,
                        'title' => 'تم حجز غرفتك',
                        'body' => "تم تأكيد الحجز للغرفة رقم {$reservable->id} من {$reservation->start_date} إلى {$reservation->end_date}.",
                        'reservation_id' => $reservation->id
                    ]);
                } elseif ($reservable instanceof \App\Models\Restaurant && $reservable->user) {
                    CustomNotification::create([
                        'user_id' => $reservable->user->id,
                        'title' => 'تم حجز مطعمك',
                        'body' => "تم تأكيد الحجز للمطعم رقم {$reservable->id} بتاريخ {$reservation->start_date}.",
                        'reservation_id' => $reservation->id
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'تم تأكيد الدفع والحجز بنجاح',
            'invoice_id' => $invoice->id,
            'reservation_id' => $reservation->id,
            'amount_paid' => $amount
        ]);
    }

    public function payTripInvoices($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        // جلب الفواتير المعلقة
        $invoices = Invoice::whereHas('reservation', function ($q) use ($tripId) {
            $q->where('trip_id', $tripId);
        })->where('payment_status', 'pending')->get();

        if ($invoices->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد فواتير معلقة لهذه الرحلة.'
            ], 404);
        }

        $totalAmount = $invoices->sum('total_amount');

        $user = auth()->user();
        $wallet = $user->wallet;

        if ($wallet->balance < $totalAmount) {
            return response()->json([
                'message' => 'الرصيد غير كافٍ في المحفظة.'
            ], 422);
        }

        DB::transaction(function () use ($invoices, $wallet, $totalAmount) {
            $wallet->decrement('balance', $totalAmount);
            $wallet->transactions()->create([
                'type' => 'withdrawal',
                'amount' => $totalAmount,
                'description' => 'دفع جميع فواتير رحلة',
            ]);

            // تحديث الفواتير والحجوزات
            foreach ($invoices as $invoice) {
                $invoice->update(['payment_status' => 'paid']);
                $reservation = $invoice->reservation;
                if ($reservation) {
                    $reservation->update(['status' => 'confirmed']);
                }
            }
            // إشعار صاحب الـ reservable
            if ($reservation->reservable_type && $reservation->reservable_id) {
                $reservable = $reservation->reservable;
                if ($reservable instanceof \App\Models\Room && $reservable->hotel->user) {
                    CustomNotification::create([
                        'user_id' => $reservable->hotel->user->id,
                        'title' => 'تم حجز غرفتك',
                        'body' => "تم تأكيد الحجز للغرفة رقم {$reservable->id} من {$reservation->start_date} إلى {$reservation->end_date}.",
                        'reservation_id' => $reservation->id
                    ]);
                } elseif ($reservable instanceof \App\Models\Restaurant && $reservable->user) {
                    CustomNotification::create([
                        'user_id' => $reservable->user->id,
                        'title' => 'تم حجز مطعمك',
                        'body' => "تم تأكيد الحجز للمطعم رقم {$reservable->id} بتاريخ {$reservation->start_date}.",
                        'reservation_id' => $reservation->id
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'تم دفع جميع الفواتير الخاصة بالرحلة بنجاح.',
            'total_paid' => $totalAmount,
            'invoices_paid' => $invoices // هنا ترجع تفاصيل الفواتير المدفوعة
        ]);
    }

    public function InvoicForTrip($tripId)
    {
        $trip = Trip::findOrFail($tripId);
        // جلب الفواتير المعلقة
        $invoices = Invoice::whereHas('reservation', function ($q) use ($tripId) {
            $q->where('trip_id', $tripId);
        })->where('payment_status', 'pending')->with('reservation')->get();

        if ($invoices->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد فواتير معلقة لهذه الرحلة.'
            ], 404);
        }

        $totalAmount = $invoices->sum('total_amount');
        $user = auth()->user();
        return response()->json([
            'message' => '  جميع الفواتير الخاصة بالرحلة .',
            'total_paid' => $totalAmount,
            'invoices_paid' => $invoices // هنا ترجع تفاصيل الفواتير المدفوعة
        ]);
    }

    public function store(Request $request)
    {
        $invoice = Invoice::create([
            'total_amount' => $request->total_amount,
            'payment_status' => $request->payment_status,
            'reservation_id' => $request->reservation_id
        ]);
        return response()->json($invoice, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice  = Invoice::FindOrFail($id);
        return response()->json($invoice, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice  = Invoice::findOrFail($id);
        $invoice->update([
            'total_amount' => $request->total_amount,
            'payment_status' => $request->payment_status,
            'reservation_id' => $request->reservation_id
        ]);
        return response()->json($invoice, 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice  = Invoice::FindOrFail($id);
        $invoice->delete();
        return response()->json('deleted', 200);
    }
}
