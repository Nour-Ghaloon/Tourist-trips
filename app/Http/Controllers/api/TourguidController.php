<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTourguideRequest;
use App\Http\Requests\UpdateTourguideRequest;
use App\Models\Media;
use App\Models\Tourguide;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TourguidController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tourguid = Tourguide::with('media', 'user')->withIsFavorite()->get();
        return response()->json($tourguid, 200);
    }
    public function AllTourguide()
    {
        $tourguid = Tourguide::with('media', 'user')->get();
        return response()->json($tourguid, 200);
    }
    public function PlaceNameFromTourguide($tourguidId)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin' || Auth::user()->role !== 'guide') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $tourguid = Tourguide::find($tourguidId)->place->name;
        return response()->json($tourguid, 200);
    }

    public function availableTourguides(Request $request)
    {
        Auth::user()->id;
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $tourguides = Tourguide::whereDoesntHave('reservation', function ($query) use ($start, $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            });
        })->with('media','user')->withIsFavorite()->get();

        return response()->json($tourguides);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTourguideRequest $request)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (!Auth::check() || Auth::user()->role !== 'guide' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $tourguid = Tourguide::create($validattedData);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $tourguid->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json(
            [
                'message' => 'تم إضافة دليل سياحي بنجاح',
                'tourguid' => $tourguid
            ],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tourguid  = Tourguide::FindOrFail($id)->where('id', $id)->with('media','user')->withIsFavorite()->first();
        return response()->json($tourguid, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTourguideRequest $request, string $id)
    {
        $user_id = Auth::user()->id;
        $validattedData = $request->validated();
        $validattedData['user_id'] = $user_id;
        // if (!Auth::check() || Auth::user()->role !== 'guide' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $tourguid  = Tourguide::findOrFail($id);
        $tourguid->update($validattedData);
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Tourguide::class)
                ->where('mediable_id', $tourguid->id)
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

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $tourguid->media()->create([
                    'file_path' => $url,
                    'public_id' => $uploaded->getPublicId()

                ]);
            }
        }
        return response()->json([
            'message' => 'تم تعديل الدليل السياحي بنجاح',
            'tourguid' => $tourguid->load('media')
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // if (!Auth::check() || Auth::user()->role !== 'guide' && Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $tourguid  = Tourguide::FindOrFail($id);
        $tourguid->delete();
        return response()->json(['message' => 'تم حذف الدليل السياحي بنجاح'], 200);
    }

    // دالة إضافة تقييم
    public function addReviewTourguide(Request $request, $tourguidId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $tourguid = Tourguide::findOrFail($tourguidId);

        $tourguid->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showTourguideWithRate($id)
    {
        $tourguid = Tourguide::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'tourguid' => $tourguid,
            'average_rating' => round($tourguid->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $tourguid->reviews_count,
            'reviews_details' => $tourguid->reviews->map(function ($review) {
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
        $tourguid = Tourguide::findOrFail($id);
        Auth::user()->favoriteTourGuides()->syncWithoutDetaching($tourguid->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $tourguid = Tourguide::findOrFail($id);
        Auth::user()->favoriteTourGuides()->detach($tourguid->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
