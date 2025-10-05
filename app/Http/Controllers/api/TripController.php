<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTripRequest;
use App\Http\Requests\UpdateTripRequest;
use App\Models\Media;
use App\Models\Reservation;
use App\Models\Trip;
use Carbon\Carbon;
use Cloudinary\Cloudinary as CloudinaryCloudinary;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
//use Cloudinary\Cloudinary;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function AlltripsWithLogin()
    {
        return Trip::with('media')->withIsFavorite()->get();
    }
    public function Alltrips()
    {
        return Trip::with('media')->get();
    }

    public function AllTripsForUser()
    {
        return Trip::where('created_by', auth()->id())->get();
    }

    public function CityNameFromTrip($tripId)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $trip = Trip::find($tripId)->city->name;
        return response()->json($trip, 200);
    }

    public function publicTrips()
    {
        return Trip::where('type', 'group')->count();
    }

    public function privateTrips()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        return Trip::where('type', 'solo')->count();
    }

    public function AllpublicTrips()
    {
        return Trip::where('type', 'group')->with('media', 'discounts')->get();
    }

    public function AllprivateTrips()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        return Trip::where('type', 'solo')->with('media')->get();
    }

    public function InvoicesTrip($tripId)
    {
        $trip = Trip::with([
            'reservations.invoice',
            'reservations.reservable'
        ])->findOrFail($tripId);

        $invoices = $trip->reservations->map(function ($reservation) {
            return [
                'reservation_type' => class_basename($reservation->reservable_type),
                'reservation_details' => $reservation->reservable,
                'invoice' => $reservation->invoice ?? 'لا توجد فاتورة'
            ];
        });
        return response()->json([
            'trip' => $trip->only(['id', 'name', 'start_date', 'end_date']),
            'invoices' => $invoices
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTripRequest $request)
    {
        $created_by = Auth::user()->id;
        $validated = $request->validated();
        $validated['created_by'] = $created_by;
        if ($validated['type'] === 'solo') {
            $validated['user_id'] = $created_by;
        } else {
            if (Auth::user()->role !== 'admin') {
                return response()->json(
                    [
                        'message' => 'مسموح فقط للمشرفين إضافة الرحلات العامة',
                    ],
                    403
                );
            }
            $validated['user_id'] = null;
        }
        $trip = Trip::create($validated);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $trip->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json([
            'message' => 'تم إنشاء الرحلة',
            'trip' => $trip->load('media')
        ], 201);
    }

    /**
     * Display the specified resource.
     */

    public function show(string $id)
    {
        $trip = Trip::FindOrFail($id);
        return response()->json($trip, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTripRequest $request, string $id)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $trip = Trip::findOrFail($id);
        $trip->update($validattedData);
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Trip::class)
                ->where('mediable_id', $trip->id)
                ->first();

            if ($media) {
                // حذف الصورة القديمة من Cloudinary إذا كان عندك public_id محفوظ (اختياري)
                Cloudinary::destroy($media->public_id);

                $uploaded = Cloudinary::upload($request->file('image')->getRealPath());
                $url = $uploaded->getSecurePath();

                $media->update([
                    'file_path' => $url,
                ]);
            }
        }

        //  إضافة صور جديدة
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $trip->media()->create([
                    'file_path' => $url,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }

        return response()->json(['message' => 'تم تحديث الرحلة', 'trip' => $trip->load('media')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $trip = Trip::FindOrFail($id);
        $trip->delete();
        return response()->json('deleted', 200);
    }

    // دالة إضافة تقييم
    public function addReviewTrip(Request $request, $tripId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $trip = Trip::findOrFail($tripId);
        // التحقق أن الرحلة منتهية
        if (Carbon::parse($trip->end_date)->gt(Carbon::now())) {
            return response()->json([
                'message' => 'لا يمكنك تقييم الرحلة إلا بعد انتهائها'
            ], 403);
        }
        // التحقق أن المستخدم لديه حجز للرحلة
        $hasReservation = Reservation::where('user_id', Auth::id())
            ->where('trip_id', $tripId)
            ->exists();

        if (!$hasReservation) {
            return response()->json([
                'message' => 'لا يمكنك تقييم الرحلة إلا إذا كان لديك حجز سابق'
            ], 403);
        }

        // إذا الشروط متحققة يضيف التقييم
        $trip->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showTripWithRate($id)
    {
        $trip = Trip::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'trip' => $trip,
            'average_rating' => round($trip->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $trip->reviews_count,
            'reviews_details' => $trip->reviews->map(function ($review) {
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
        $trip = Trip::findOrFail($id);
        Auth::user()->favoriteTrips()->syncWithoutDetaching($trip->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }


    public function removeFromFavorites($id)
    {
        $trip = Trip::findOrFail($id);
        Auth::user()->favoriteTrips()->detach($trip->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
