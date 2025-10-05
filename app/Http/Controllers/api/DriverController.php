<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\Driver;
use App\Models\Media;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $driver = Driver::with('media')->withIsFavorite()->get();
        return response()->json($driver, 200);
    }
    public function AllDrivers()
    {
        $driver = Driver::with('media')->get();
        return response()->json($driver, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDriverRequest $request)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        if (!Auth::check() || Auth::user()->role !== 'driver' && Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $driver = Driver::create($validattedData);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $driver->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json([
            'message' => 'تم إضافة سائق بنجاح',
            'driver' => $driver->load('media')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $driver = Driver::FindOrFail($id)->where('id', $id)->withIsFavorite()->first();;
        return response()->json($driver, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDriverRequest $request, $id)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (!Auth::check() || Auth::user()->role !== 'driver' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $driver = Driver::findOrFail($id);
        $driver->update($validattedData);
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Driver::class)
                ->where('mediable_id', $driver->id)
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

                $driver->media()->create([
                    'file_path' => $url,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }

        return response()->json([
            'message' => 'تم تعديل السائق بنجاح',
            'driver' => $driver->load('media')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // if (!Auth::check() || Auth::user()->role !== 'driver' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $driver = Driver::FindOrFail($id);
        $driver->delete();
        return response()->json('deleted', 200);
    }
    // دالة إضافة تقييم
    public function addReviewDriver(Request $request, $driverId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $driver = Driver::findOrFail($driverId);

        $driver->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showDriverWithRate($id)
    {
        $driver = Driver::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'driver' => $driver,
            'average_rating' => round($driver->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $driver->reviews_count,
            'reviews_details' => $driver->reviews->map(function ($review) {
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
        $driver = Driver::findOrFail($id);
        Auth::user()->favoriteDrivers()->syncWithoutDetaching($driver->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $driver = Driver::findOrFail($id);
        Auth::user()->favoriteDrivers()->detach($driver->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
