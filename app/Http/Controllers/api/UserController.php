<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\CustomNotification;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function AllUser()
    {
        return User::all();
    }

    public function numberUser()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        return User::count();
    }

    // جلب إشعارات المستخدم
    public function notifications(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'notifications' => CustomNotification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
        ]);
    }

    // تحديد إشعار كمقروء
    public function markAsRead($id, Request $request)
    {
        $notification = CustomNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);
        $notification->read_at = now();
        $notification->save();

        return response()->json(['message' => 'تم تحديد الإشعار كمقروء']);
    }

    // تحديد كل الإشعارات كمقروءة
    public function markAllAsRead(Request $request)
    {
        CustomNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'تم تحديد كل الإشعارات كمقروءة']);
    }


    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user,driver,guide,restaurant,hotel',
        ]);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        return response()->json([
            'message' => 'تم انشاء حساب بنجاح',
            'User' => $user
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        if (!Auth::attempt($request->only('email', 'password')))
            return response()->json([
                'message' => 'invalid email or password'
            ], 401);
        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_Token')->plainTextToken;
        return response()->json([
            'message' => 'تم تسجيل الدحول بنجاح',
            'User' => $user,
            'Token' => $token
        ], 201);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح'
        ]);
    }
}
