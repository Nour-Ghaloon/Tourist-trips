<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFavoriteRequest;
use App\Http\Requests\UpdateFavoriteRequest;
use App\Models\City;
use App\Models\Driver;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\Room;
use App\Models\Tourguide;
use App\Models\Trip;
use App\Models\Trip_place;
use App\Models\Vehicle;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getAllFavorites()
    {
        // $favorites = Auth::user()->favorites()->with('favoritable')->get();
        $userId = Auth::id();

        // مصفوفة بكل الموديلات القابلة للمفضلة
        $favoriteModels = [
            City::class,
            Hotel::class,
            Trip::class,
            Vehicle::class,
            Place::class,
            Restaurant::class,
            Room::class,
            Driver::class,
            Tourguide::class,
            Trip_place::class,
        ];

        $allFavorites = collect();

        foreach ($favoriteModels as $modelClass) {
            $items = $modelClass::withIsFavorite()
                ->whereExists(function ($query) use ($userId, $modelClass) {
                    $table = (new $modelClass)->getTable();
                    $query->select('*')
                        ->from('favorites')
                        ->whereColumn('favoritable_id', "$table.id")
                        ->where('favoritable_type', $modelClass)
                        ->where('user_id', $userId);
                })
                ->with('is_favorit')->get();

            $allFavorites = $allFavorites->merge($items);
        }

        return response()->json($allFavorites);
    }

    public function index()
    {
        $typeNames = [
            'City' => 'مدينة',
            'Hotel' => 'فندق',
            'Room' => 'غرفة',
            'Restaurant' => 'مطعم',
            'Trip' => 'رحلة',
            'Vehicle' => 'سيارة',
            'TourGuide' => 'دليل سياحي',
            'Place' => 'مكان',
            'TripPlace' => 'مكان رحلة',
            'Driver' => 'سائق'
        ];

        $favorites = Auth::user()
            ->favorites()
            ->with('favoritable')
            ->get()
            ->map(function ($favorite) use ($typeNames) {

                $type = class_basename($favorite->favoritable_type);
                $item = $favorite->favoritable;
                if ($item) {
                    $item->is_favorite = 1;
                }
                return [
                    'id' => $favorite->id,
                    'type' => $typeNames[$type] ?? $type,
                    'data' => $favorite->favoritable
                ];
            });

        return response()->json($favorites);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFavoriteRequest $request)
    {
        $favorite = Favorite::create($request->validated());
        return response()->json($favorite, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $favorite = Favorite::FindOrFail($id);
        return response()->json($favorite, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFavoriteRequest $request, string $id)
    {
        $favorite = Favorite::findOrFail($id);
        $favorite->update($request->validated());
        return response()->json($favorite, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $favorite = Favorite::FindOrFail($id);
        $favorite->delete();
        return response()->json('deleted', 200);
    }
}
