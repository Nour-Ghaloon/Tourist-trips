<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlaceRequest;
use App\Http\Requests\UpdatePlaceRequest;
use App\Models\Hotel;
use App\Models\Media;
use App\Models\Place;
use App\Models\Restaurant;
use App\Models\Tourguide;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $place = Place::with('media')->withIsFavorite()->get();
        return response()->json($place, 200);
    }

    public function allPlaces()
    {
        $place = Place::with('media')->get();

        return response()->json($place, 200);
    }

    public function MostPlacesVisit()
    {
        return Place::withCount('trips')->orderBy('trips_count', 'desc')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePlaceRequest $request)
    {
        $place = Place::create($request->validated());
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $place->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json(
            [
                'message' => 'تم إضافة المكان بنجاح',
                'hotel' => $place
            ],
            201
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $place = Place::FindOrFail($id)->where('id', $id)->with('media')->withIsFavorite()->first();
        return response()->json($place, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePlaceRequest $request, string $id)
    {
        $place = Place::findOrFail($id);
        $place->update($request->validated());
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Place::class)
                ->where('mediable_id', $place->id)
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

                $place->media()->create([
                    'file_path' => $url,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json([
            'message' => 'تم تعديل المكان بنجاح',
            'place' => $place->load('media')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $place = Place::FindOrFail($id);
        $place->delete();
        return response()->json('deleted', 200);
    }

    // دالة إضافة تقييم
    public function addReviewPlace(Request $request, $placeId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $place = Place::findOrFail($placeId);

        $place->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showPlaceWithRate($id)
    {
        $place = Place::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'place' => $place,
            'average_rating' => round($place->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $place->reviews_count,
            'reviews_details' => $place->reviews->map(function ($review) {
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
        $place = Place::findOrFail($id);
        Auth::user()->favoritePlaces()->syncWithoutDetaching($place->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $place = Place::findOrFail($id);
        Auth::user()->favoritePlaces()->detach($place->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
