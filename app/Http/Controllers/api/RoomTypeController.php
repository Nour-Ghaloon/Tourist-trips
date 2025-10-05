<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomtypeRequest;
use App\Http\Requests\UpdateRoomtypeRequest;
use App\Models\Roomtype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function AllRoomTypes()
    {
        $roomtype = Roomtype::all();
        return response()->json($roomtype, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomtypeRequest $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $roomtype = Roomtype::create($request->validated());
        return response()->json($roomtype, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $roomtype  = Roomtype::FindOrFail($id);
        return response()->json($roomtype, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomtypeRequest $request, string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $roomtype  = Roomtype::findOrFail($id);
        $roomtype->update($request->validated());
        return response()->json($roomtype, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $roomtype  = Roomtype::FindOrFail($id);
        $roomtype->delete();
        return response()->json('deleted', 200);
    }
}
