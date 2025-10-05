<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Trip_place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripPlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index()
    {
        $tripplace =Trip_place::withIsFavorite()->get();
        return response()->json($tripplace, 200);
    }
    public function AllTripPlace()
    {
        $tripplace =Trip_place::all();
        return response()->json($tripplace, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $tripplace=Trip_place::create([
            'trip_id'=>$request->trip_id,
            'place_id'=>$request->place_id,
            'hotel_id'=>$request->hotel_id,
            'restaurant_id'=>$request->restaurant_id
        ]);
           return response()->json($tripplace, 201);
           return response()->json($tripplace, 201);
           return response()->json($tripplace, 201);
           return response()->json($tripplace, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tripplace=Trip_place::FindOrFail($id);
       return response()->json($tripplace, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tripplace=Trip_place::findOrFail($id);
        $tripplace-> update([
            'trip_id'=>$request->trip_id,
            'place_id'=>$request->place_id,
            'hotel_id'=>$request->hotel_id,
            'restaurant_id'=>$request->restaurant_id
        ]);
           return response()->json($tripplace, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tripplace=Trip_place::FindOrFail($id);
        $tripplace->delete();
        return response()->json('deleted', 200);
    }

    // دالة إضافة تقييم
    public function addReviewTrip_place(Request $request, $Id)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $tripplace = Trip_place::findOrFail($Id);

        $tripplace->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showTrip_placeWithRate($id)
    {
        $tripplace = Trip_place::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'tripplace' => $tripplace,
            'average_rating' => round($tripplace->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $tripplace->reviews_count,
            'reviews_details' => $tripplace->reviews->map(function ($review) {
                return [
                    'rate' => $review->rate,
                    'comment' => $review->comment,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                    ],
                    'created_at' => $review->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    public function addToFavorites($id)
    {
        $tripplace = Trip_place::findOrFail($id);
        Auth::user()->favoriteTripPlaces()->syncWithoutDetaching($tripplace->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $tripplace = Trip_place::findOrFail($id);
        Auth::user()->favoriteTripPlaces()->detach($tripplace->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }

}
