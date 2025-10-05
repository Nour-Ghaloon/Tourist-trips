<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
      public function index()
    {
        $payment = Payment::all();
        return response()->json($payment, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $payment = Payment::create([
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => $request->status,
            'invoice_id' => $request->invoice_id
        ]);
        return response()->json($payment, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment  = Payment::FindOrFail($id);
        return response()->json($payment, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment  = Payment::findOrFail($id);
        $payment->update([
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => $request->status,
            'invoice_id' => $request->invoice_id
        ]);
        return response()->json($payment, 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment  = Payment::FindOrFail($id);
        $payment->delete();
        return response()->json('deleted', 200);
    }
}
