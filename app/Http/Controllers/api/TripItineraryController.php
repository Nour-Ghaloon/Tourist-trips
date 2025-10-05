<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripItineraryRequest;
use App\Http\Requests\UpdateTripItineraryRequest;
use App\Http\Resources\TripItineraryResource;
use App\Models\Hotel;
use App\Models\Trip;
use App\Models\TripItinerary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TripItineraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Trip $trip)
    {
        $itineraries = $trip->itineraries()->with('place.media')->orderBy('day_number')->get();

        return TripItineraryResource::collection($itineraries);
    }

    public function AllTripeItineraries()
    {
        return TripItinerary::with(['hotel', 'restaurant', 'place.media'])->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripItineraryRequest $request, Trip $trip)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(
                [
                    'message' => 'مسموح فقط للمشرفين إضافة وصف للرحلة ',
                ],
                403
            );
        }

        abort_if(
            !Auth::check() || Auth::user()->role !== 'admin',
            403,
            'مسموح فقط للمشرفين إضافة وصف للرحلة'
        );

        $data = $request->validated();
        $data['trip_id'] = $trip->id;

        // إنشاء السجل بدون distance أولاً
        $itinerary = TripItinerary::create($data);

        // حساب المسافة وتحديثها
        $itinerary->distance = $this->calculateDistance($itinerary);
        $itinerary->save();

        return new TripItineraryResource($itinerary->load('place'));
    }

    /**
     * Display the specified resource.
     */
    public function show(TripItinerary $itinerary)
    {
        return new TripItineraryResource($itinerary->load('place'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripItineraryRequest $request, TripItinerary $itinerary)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(
                [
                    'message' => 'مسموح فقط للمشرفين تعديل الوصف',
                ],
                403
            );
        }
        $itinerary->update($request->validated());
        // حساب المسافة بعد التحديث
        $itinerary->distance = $this->calculateDistance($itinerary);
        $itinerary->save();
        return new TripItineraryResource($itinerary->load('place'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TripItinerary $itinerary)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(
                [
                    'message' => 'مسموح فقط للمشرفين حذف الوصف',
                ],
                403
            );
        }
        $itinerary->delete();
        return response()->json(['message' => 'تم حذف وصف اليوم بنجاح.']);
    }

    //  دالة لحساب المسافة
    private function calculateDistance(TripItinerary $itinerary)
    {
        $trip = $itinerary->trip;
        $city = $trip->city;
        $place = $itinerary->place;

        if ($itinerary->day_number == 1) {
            $lat1 = $city->latitude;
            $lon1 = $city->longitude;
            $lat2 = $place->latitude;
            $lon2 = $place->longitude;
        } else {
            $previousItinerary = TripItinerary::where('trip_id', $trip->id)
                ->where('day_number', $itinerary->day_number - 1)
                ->first();

            if (!$previousItinerary) {
                return 0;
            }

            $prevPlace = $previousItinerary->place;
            $lat1 = $prevPlace->latitude;
            $lon1 = $prevPlace->longitude;
            $lat2 = $place->latitude;
            $lon2 = $place->longitude;
        }

        return $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * دالة حساب المسافة باستخدام معادلة هافيرسين (Haversine)
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // كيلومتر

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
