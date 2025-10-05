<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Trip;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function tripmedia()
    {
        $media = Media::where('mediable_type', 'App\Models\Trip')->get();
        return response()->json($media, 200);
    }

    public function hotelmedia()
    {
        $media = Media::where('mediable_type', 'App\Models\Hotel')->get();
        return response()->json($media, 200);
    }

    public function restaurantmedia()
    {
        $media = Media::where('mediable_type', 'App\Models\Restaurant')->get();
        return response()->json($media, 200);
    }


    public function destroy(string $id)
    {
        $media = Media::where('id', $id)
           // ->where('mediable_type', Trip::class) // تأكد أنها صورة مرتبطة برحلة
            ->firstOrFail();

        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'ليس لديك صلاحيات لحذف الصورة'], 403);
        }

        // حذف من Cloudinary باستخدام public_id
        if ($media->public_id) {
            Cloudinary::destroy($media->public_id);
        }

        // حذف من قاعدة البيانات
        $media->delete();

        return response()->json(['message' => 'تم حذف الصورة بنجاح'], 200);
    }
}
