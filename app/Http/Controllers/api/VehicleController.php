<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Media;
use App\Models\User;
use App\Models\Vehicle;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vehicle = Vehicle::with('media')->withIsFavorite()->get();
        return response()->json($vehicle, 200);
    }
    public function AllVehicle()
    {
        $vehicle = Vehicle::with('media', 'driver.media')->withIsFavorite()->get();
        return response()->json($vehicle, 200);
    }

    public function availableVehicles(Request $request)
    {
        Auth::user()->id;
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $vehicles = Vehicle::whereDoesntHave('reservation', function ($query) use ($start, $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            });
        })->with('media')->withIsFavorite()->get();

        return response()->json($vehicles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVehicleRequest $request)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (!Auth::check() || Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $vehicle = Vehicle::create($validattedData);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $vehicle->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }

        return response()->json([
            'message' => 'تم إضافة مركبة  بنجاح',
            'room' => $vehicle->load('media')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $vehicle = Vehicle::FindOrFail($id)->where('id', $id)->with('media')->withIsFavorite()->first();
        return response()->json($vehicle, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, $id)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update($validattedData);
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Vehicle::class)
                ->where('mediable_id', $vehicle->id)
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

                $vehicle->media()->create([
                    'file_path' => $url,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }

        return response()->json(['message' => 'تم تحديث المركبة', 'trip' => $vehicle->load('media')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vehicle  = Vehicle::FindOrFail($id);
        $vehicle->delete();
        return response()->json('deleted', 200);
    }

    // دالة إضافة تقييم
    public function addReviewVehicle(Request $request, $vehicleId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $vehicle = Vehicle::findOrFail($vehicleId);

        $vehicle->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showVehicleWithRate($id)
    {
        $vehicle = Vehicle::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'vehicle' => $vehicle,
            'average_rating' => round($vehicle->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $vehicle->reviews_count,
            'reviews_details' => $vehicle->reviews->map(function ($review) {
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
        $vehicle = Vehicle::findOrFail($id);
        Auth::user()->favoriteVehicles()->syncWithoutDetaching($vehicle->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        Auth::user()->favoriteVehicles()->detach($vehicle->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
