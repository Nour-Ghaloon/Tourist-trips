<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Models\Media;
use App\Models\Reservation;
use App\Models\Restaurant;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $restaurant = Restaurant::with('media')->withIsFavorite()->get();
        return response()->json($restaurant, 200);
    }
    public function AllRestaurant()
    {
        $restaurant = Restaurant::with('media')->get();
        return response()->json($restaurant, 200);
    }
    public function CityNameFromRestaurant($restaurantId)
    {
        $restaurant = Restaurant::find($restaurantId)->city->name;
        return response()->json($restaurant, 200);
    }
    public function NameRestaurant(String $nameRestaurant)
    {
        return Restaurant::where('name', 'LIKE', "%$nameRestaurant%")->get();
    }

    public function availableRestaurants(Request $request)
    {
        $date = $request->start_date;
        $time = $request->start_time;
        $people = $request->guest_count ?? 1;

        $restaurants = Restaurant::with('media')->withIsFavorite()->get()->filter(function ($restaurant) use ($date, $time, $people) {
            $existingGuests = Reservation::where('reservable_type', Restaurant::class)
                ->where('reservable_id', $restaurant->id)
                ->whereDate('start_date', $date)
                ->whereTime('start_date', $time)
                ->sum('guest_count');

            return ($existingGuests + $people) <= $restaurant->capacity;
        });

        return response()->json($restaurants->values());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRestaurantRequest $request)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        if (!Auth::check() || Auth::user()->role !== 'restaurant' && Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $restaurant = Restaurant::create($validattedData);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $restaurant->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        // رفع صورة القائمة
        if ($request->hasFile('menu')) {

            $request->validate([
                'menu' => 'mimes:jpg,jpeg,png|max:5120' // حجم أقصى 5MB
            ]);

            $uploadedImage = Cloudinary::upload(
                $request->file('menu')->getRealPath(),
                [
                    'folder' => 'restaurant_menus',
                    'resource_type' => 'image'
                ]
            );
            $restaurant->menu = $uploadedImage->getSecurePath();        // رابط الصورة
            $restaurant->menu_public_id = $uploadedImage->getPublicId(); // ID لحذف الصورة لاحقاً
            $restaurant->save();
        }
        return response()->json([
            'message' => 'تم إضافة المطعم بنجاح',
            'restaurant' => $restaurant->load('media'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $restaurant  = Restaurant::FindOrFail($id)->where('id', $id)->with('media')->withIsFavorite()->first();
        return response()->json($restaurant, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRestaurantRequest $request, string $id)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        if (!Auth::check() || Auth::user()->role !== 'restaurant' && Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $restaurant  = Restaurant::findOrFail($id);
        //اضافة قائمة الطعام كصورة
        $restaurant->fill($request->except('menu'));

        if ($request->hasFile('menu')) {

            // حذف القديم إذا موجود
            if ($restaurant->menu_public_id) {
                Cloudinary::destroy($restaurant->menu_public_id, ['resource_type' => 'image']);
            }

            $uploadedPdf = Cloudinary::uploadFile(
                $request->file('menu')->getRealPath(),
                [
                    'resource_type' => 'image',
                    'folder' => 'restaurant_menus',
                    'overwrite' => true
                ]
            );

            $restaurant->menu = $uploadedPdf->getSecurePath();
            $restaurant->menu_public_id = $uploadedPdf->getPublicId();
            $restaurant->save();
        }
        //اضافة صورة
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Restaurant::class)
                ->where('mediable_id', $restaurant->id)
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

                $restaurant->media()->create([
                    'file_path' => $url,
                ]);
            }
        }
        return response()->json([
            'message' => 'تم تعديل المطعم بنجاح',
            'restaurant' => $restaurant->load('media'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'restaurant' && Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $restaurant  = Restaurant::FindOrFail($id);

        // حذف قائمة الطعام من Cloudinary إذا موجودة
        if ($restaurant->menu_public_id) {
            Cloudinary::destroy($restaurant->menu_public_id, [
                'resource_type' => 'raw'
            ]);
        }

        // حذف الصور من Cloudinary إذا موجودة
        foreach ($restaurant->media as $media) {
            if ($media->public_id) {
                Cloudinary::destroy($media->public_id);
            }
            $media->delete(); // حذف السجلات من قاعدة البيانات
        }

        $restaurant->delete();
        return response()->json('deleted', 200);
    }

    public function deleteMenu($id)
    {
        $restaurant = Restaurant::findOrFail($id);

        if ($restaurant->menu_public_id) {
            Cloudinary::destroy($restaurant->menu_public_id, ['resource_type' => 'image']);
            $restaurant->menu = null;
            $restaurant->menu_public_id = null;
            $restaurant->save();
        }

        return response()->json(['message' => 'تم حذف قائمة الطعام بنجاح']);
    }

    // دالة إضافة تقييم
    public function addReviewRestaurant(Request $request, $restaurantId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $restaurant = Restaurant::findOrFail($restaurantId);

        $restaurant->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }
    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showRestaurantWithRate($id)
    {
        $restaurant = Restaurant::with(['reviews.user'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rate')
            ->findOrFail($id);

        return response()->json([
            'restaurant' => $restaurant,
            'average_rating' => round($restaurant->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $restaurant->reviews_count,
            'reviews_details' => $restaurant->reviews->map(function ($review) {
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
        $restaurant = Restaurant::findOrFail($id);
        Auth::user()->favoriteRestaurants()->syncWithoutDetaching($restaurant->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $restaurant = Restaurant::findOrFail($id);
        Auth::user()->favoriteRestaurants()->detach($restaurant->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
