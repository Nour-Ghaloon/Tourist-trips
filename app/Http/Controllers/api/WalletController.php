<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        // إنشاء المحفظة إذا غير موجودة
        $wallet = auth()->user()->wallet;
        if (!$wallet) {
            $wallet = auth()->user()->wallet()->create([
                'balance' => 0
            ]);
        }

        // تحديث الرصيد
        $wallet->balance += $request->amount;
        $wallet->save();

        // تسجيل عملية الإيداع
        $wallet->transactions()->create([
            'type' => 'deposit',
            'amount' => $request->amount,
            'description' => 'إضافة رصيد عبر النظام',

        ]);

        return response()->json([
            'message' => 'تم إضافة الرصيد بنجاح',
            'balance' => $wallet->balance,
        ], 201);
    }


    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'invoice_id' => 'nullable|exists:invoices,id',
            'description' => 'nullable|string'
        ]);
        $wallet = Auth::user()->wallet;
        if ($wallet->balance < $request->amount)
            return response()->json([
                'error' => 'الرصيد غير كافي',
            ], 422);

        $wallet->balance -= $request->amount;
        $wallet->save();

        $wallet->transactions()->create([
            'type' => 'withdrawal',
            'amount' => $request->amount,
            'invoice_id' => $request->invoice_id,
            'description' => 'خصم رصيد من المحفظة ',
        ]);
        if ($request->invoice_id) {
            $invoice = Invoice::find($request->invoice_id);
            $invoice->update([
                'payment_status' => 'paid',
            ]);
            return response()->json([
                'message' => 'تم الحصم من المحفظة بنجاح',
                'balance' => $wallet->balance,
            ], 201);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show()
    {
        $userId = Auth::id(); // أو أي طريقة بتحدد فيها المستخدم الحالي

        $wallet = Wallet::firstOrCreate(
            ['user_id' => $userId],  // إذا ما لقى محفظة لهالمستخدم بيعمل وحدة جديدة
            ['balance' => 0]         // الرصيد الافتراضي
        );

        return response()->json([
            'balance' => $wallet->balance,
        ]);
    }
}
