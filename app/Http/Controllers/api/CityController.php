<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Models\City;
use App\Models\Hotel;
use App\Models\Media;
use App\Models\Restaurant;
use App\Models\Tourguide;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $city = City::with('media')->withIsFavorite()->get();
        return response()->json($city, 200);
    }
    public function getAllCity()
    {
        $city = City::with('media')->get();
        return response()->json($city, 200);
    }

    public function AllHotelFromCity(String $city)
    {
        $hotelNames = Hotel::whereHas('city', function ($query) use ($city) {
            $query->where('name', $city);
        })->get();
        return response()->json($hotelNames, 200);
    }

    public function AllRestaurantFromCity(String $city)
    {
        $RestaurantlNames = Restaurant::whereHas('city', function ($query) use ($city) {
            $query->where('name', $city);
        })->get();
        return response()->json($RestaurantlNames, 200);
    }

    public function AllGuidFromCity(String $city)
    {
        $GuidNames = User::where('role', 'guide')->whereHas(
            'tourguide.place.city',
            function ($query) use ($city) {
                $query->where('name', $city);
            }

        )->with(['tourguide' => function ($query) {
            $query->select('user_id', 'language', 'price');
        }])->select('id', 'name', 'email')->get();
        return response()->json($GuidNames, 200);
    }

    public function store(StoreCityRequest $request)
    {
        // if (!Auth::check() || Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $city = City::create($request->validated());
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $city->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json([
            'message' => 'تم إضافة مدينة بنجاح',
            'city' => $city->load('media')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $city = City::FindOrFail($id);
        return response()->json($city, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, string $id)
    {
        // if (!Auth::check() || Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $city = City::findOrFail($id);
        $city->update($request->validated());
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', City::class)
                ->where('mediable_id', $city->id)
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

                $city->media()->create([
                    'file_path' => $url,
                ]);
            }
        }

        return response()->json([
            'message' => 'تم تحديث المدينة',
            'city' => $city->load('media')
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $city = City::FindOrFail($id);
        $city->delete();
        return response()->json('deleted', 200);
    }


    // دالة إضافة تقييم لمدينة
    public function addReviewCity(Request $request, $cityId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $city = City::findOrFail($cityId);

        $city->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showCityWithRate($id)
    {
        $city = City::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'city' => $city,
            'average_rating' => round($city->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $city->reviews_count,
            'reviews_details' => $city->reviews->map(function ($review) {
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
        $city = City::findOrFail($id);
        Auth::user()->favoriteCities()->syncWithoutDetaching($city->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $city = City::findOrFail($id);
        Auth::user()->favoriteCities()->detach($city->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
