<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Media;
use App\Models\Room;
use App\Models\Roomtype;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::with('media', 'roomtype')->withIsFavorite()->get();
        return response()->json($rooms, 200);
    }
    public function AllRooms()
    {
        $rooms = Room::with('media', 'roomtype', 'hotel')->get();
        return response()->json($rooms, 200);
    }
    public function availableRooms(Request $request)
    {
        Auth::user()->id;
        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        $rooms = Room::whereDoesntHave('reservation', function ($query) use ($start, $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            });
        })->with('media', 'roomtype')->withIsFavorite()->get();

        return response()->json($rooms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        // if (!Auth::check() || Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $room = Room::create($request->validated());
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $uploaded = Cloudinary::upload($image->getRealPath());
                $url = $uploaded->getSecurePath();

                $room->media()->create([
                    'file_path' => $url,
                    'caption' => null,
                    'alt_text' => null,
                    'public_id' => $uploaded->getPublicId()
                ]);
            }
        }
        return response()->json([
            'message' => 'تم إضافة غرفة  بنجاح',
            'room' => $room->load('media')
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room  = Room::FindOrFail($id)->where('id', $id)->with('media', 'roomtype', 'hotel')->withIsFavorite()->first();
        return response()->json($room, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, string $id)
    {
        // if (!Auth::check() || Auth::user()->role !== 'admin') {
        //     return response()->json([
        //         'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
        //     ], 403,);
        // }
        $room  = Room::findOrFail($id);
        $room->update($request->validated());
        if ($request->hasFile('image') && $request->has('media_id')) {
            $media = Media::where('id', $request->media_id)
                ->where('mediable_type', Room::class)
                ->where('mediable_id', $room->id)
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

                $room->media()->create([
                    'file_path' => $url,
                ]);
            }
        }
        return response()->json([
            'message' => 'تم تعديل الغرفة  بنجاح',
            'room' => $room->load('media')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $room  = Room::FindOrFail($id);
        $room->delete();
        return response()->json('deleted', 200);
    }

    // دالة إضافة تقييم
    public function addReviewRoom(Request $request, $roomId)
    {
        $data = $request->validate([
            'rate' => 'required|between:0,5',
            'comment' => 'nullable|string',
        ]);

        $room = Room::findOrFail($roomId);

        $room->reviews()->create([
            'user_id' => Auth::id(),
            'rate' => $data['rate'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['message' => 'تم إضافة التقييم بنجاح']);
    }

    // دالة عرض المدينة مع التقييمات، المتوسط، وعدد التقييمات والتعليقات مع بيانات المستخدم
    public function showRoomWithRate($id)
    {
        $room = Room::with(['reviews.user'])      // جلب التقييمات مع بيانات المستخدمين
            ->withCount('reviews')       // عدد التقييمات
            ->withAvg('reviews', 'rate') // متوسط التقييم
            ->findOrFail($id);

        return response()->json([
            'room' => $room,
            'average_rating' => round($room->reviews_avg_rate ?? 0, 2),
            'reviews_count' => $room->reviews_count,
            'reviews_details' => $room->reviews->map(function ($review) {
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
        $room = Room::findOrFail($id);
        Auth::user()->favoriteRooms()->syncWithoutDetaching($room->id);
        return response()->json(['message' => 'تم الإضافة للمفضلة'], 200);
    }

    public function removeFromFavorites($id)
    {
        $room = Room::findOrFail($id);
        Auth::user()->favoriteRooms()->detach($room->id);
        return response()->json(['message' => 'تم الإزالة من المفضلة'], 200);
    }
}
