<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHotelRequest;
use App\Http\Requests\UpdateHotelRequest;
use App\Models\Hotel;
use App\Models\Media;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hotel = Hotel::with('media')->withIsFavorite()->get();
        return response()->json($hotel, 200);
    }
    public function AllHotels()
    {
        $hotel = Hotel::with('media')->get();
        return response()->json($hotel, 200);
    }

    public function CityNameFromHotel($hotelId)
    {
        $hotel = Hotel::find($hotelId)->city->name;
        return response()->json($hotel, 200);
    }

    public function NameHotel(String $nameHotel)
    {
        return Hotel::where('name', 'LIKE', "%$nameHotel%")->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHotelRequest $request)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (!Auth::check() || Auth::user()->role !== 'hotel' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $hotel = Hotel::create($validattedData);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $hotel->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json(
            [
                'message' => 'تم إضافة مطعم بنجاح',
                'hotel' => $hotel
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $hotel  = Hotel::FindOrFail($id)->where('id', $id)->with('media')->withIsFavorite()->first();

        return response()->json($hotel, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHotelRequest $request, string $id)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (!Auth::check() || Auth::user()->role !== 'hotel' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $hotel = Hotel::findOrFail($id);
        $hotel->update($validattedData);
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Hotel::class)
                ->where('mediable_id', $hotel->id)
                ->first();

            if ($media) {
                // حذف الصورة القديمة من Cloudinary إذا كان عندك public_id محفوظ (اختياري)
                Cloudinary::destroy($media->public_id);

                $uploaded = Cloudinary::upload($request->file('image')->getRealPath());
                $url = $uploaded->getSecurePath();

                $media->update([
                    'file_path' => $url,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }

        //  إضافة صور جديدة
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $hotel->media()->create([
                    'file_path' => $url,
                ]);
            }
        }
        return response()->json([
            'message' => 'تم تعديل المطعم بنجاح',
            'hotel' => $hotel->load('media')
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        if (!Auth::check() || Auth::user()->role !== 'hotel' && Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $hotel  = Hotel::FindOrFail($id);
        $hotel->delete();
        return response()->json(
            [
                'message' => 'تم حذف المطعم بنجاح'
            ],
            200
        );
    }

    // دالة إضافة تقييم
    public function addReviewHotel(Request $request, $hotelId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $hotel = Hotel::findOrFail($hotelId);

        $hotel->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showHotelWithRate($id)
    {
        $hotel = Hotel::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'hotel' => $hotel,
            'average_rating' => round($hotel->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $hotel->reviews_count,
            'reviews_details' => $hotel->reviews->map(function ($review) {
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
        $hotel = Hotel::findOrFail($id);
        Auth::user()->favoriteHotels()->syncWithoutDetaching($hotel->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $hotel = Hotel::findOrFail($id);
        Auth::user()->favoriteHotels()->detach($hotel->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
