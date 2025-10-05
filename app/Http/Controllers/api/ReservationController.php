<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\CustomNotification;
use App\Models\Hotel;
use App\Models\Invoice;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Restaurant;
use App\Models\Room;
use App\Models\Tourguide;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function AllReservation()
    {
        return Reservation::all();
    }
    public function NumberReservations()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $reservation = Reservation::count();
        return response()->json($reservation, 200);
    }

    public function NumberReservationsForTrip($tripId)
    {
        $reservation = Reservation::Where('trip_id', $tripId)->with('user')->get();
        return response()->json($reservation, 200);
    }

    public function AllReservationForUser()
    {
        return Reservation::where('user_id', auth()->id())->where('status', '!=', 'cancelled')
            ->get();
    }

    public function AllTripPublicForUser()
    {
        return Reservation::where('user_id', auth()->id())->where('status', '!=', 'cancelled')
            ->where('reservable_type', 'App\Models\Trip')->with('trip.media', 'trip')
            ->get();
    }

    public function availableSeats($tripId)
    {
        $trip = Trip::findOrFail($tripId);

        // جمع عدد المقاعد للحجوزات المؤكدة فقط
        $reservedAdults = Reservation::where('trip_id', $trip->id)
            ->where('status', 'confirmed')
            ->sum('number_people');

        $reservedChildren = Reservation::where('trip_id', $trip->id)
            ->where('status', 'confirmed')
            ->sum('number_children');

        $reservedSeats = $reservedAdults + $reservedChildren;

        $availableSeats = max(0, $trip->capacity - $reservedSeats);



        return response()->json([
            'اسم الرحلة' => $trip->name,
            'السعة' => $trip->capacity,
            'المقاعد المحجوزة' => $reservedSeats,
            'المقاعد المتاحة' => $availableSeats
        ]);
    }

    public function AllUserForTrip($tripId)
    {
        $reservation = Reservation::with('user')->where('trip_id', $tripId)->get();
        return response()->json($reservation, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        $user_id = Auth::user()->id;
        $validated = $request->validated();
        $validated['user_id'] = $user_id;
        $user = auth()->user();
        $trip = Trip::findOrFail($request->trip_id);

        Wallet::firstOrCreate(
            ['user_id' => $user_id],  // إذا ما لقى محفظة لهالمستخدم بيعمل وحدة جديدة
            ['balance' => 0]         // الرصيد الافتراضي
        );
        if ($trip->type === 'group') {
            return $this->handleGroupReservation($request, $trip, $user);
        }

        if ($trip->type === 'solo') {
            // نحول للـ preview أولاً بدلاً من الحجز المباشر
            //return $this->previewSoloReservation($request, $trip, $user);

            return $this->handleSoloReservation($request, $trip, $user);
        }

        return response()->json(['error' => 'نوع الرحلة غير مدعوم'], 422);
    }

    private function handleGroupReservation($request, $trip, $user)
    {
        if (Carbon::parse($trip->start_date)->lte(Carbon::now())) {
            return response()->json([
                'message' => 'لا يمكنك الحجز في الرحلة لأنها بدأت بالفعل'
            ], 403);
        }
        $numberAdults   = (int) $request->number_people;
        $numberChildren = (int) ($request->number_children ?? 0);
        $totalPeople    = $numberAdults + $numberChildren;

        // تحقق من السعة مع استثناء الحجوزات الملغاة أو غير المؤكدة
        $currentReservation = $trip->reservation()
            ->where('status', 'confirmed')
            ->sum('number_people');
        if ($currentReservation + $totalPeople > $trip->capacity) {
            return response()->json([
                'error' => 'عذرًا، لا يوجد عدد كافٍ من الأماكن المتاحة في هذه الرحلة',
                'المقاعد المتاحة' => $trip->capacity - $currentReservation
            ], 422);
        }

        $basePricePerPerson = $trip->base_price;
        $totalBasePrice = $basePricePerPerson * $totalPeople;
        $totalDiscount = 0.0;

        // تحميل جميع الحسومات المرتبطة بالرحلة
        $trip->load('discounts');

        foreach ($trip->discounts as $discount) {

            // تحقق من انتهاء الحسم
            if ($discount->valid_until && now()->gt($discount->valid_until)) {
                continue;
            }

            // حسم للأطفال
            if ($discount->type === 'child' && $numberChildren > 0) {
                if (!empty($discount->percentage)) {
                    $totalDiscount += $basePricePerPerson * ($discount->percentage / 100) * $numberChildren;
                } elseif (!empty($discount->amount)) {
                    $totalDiscount += $discount->amount * $numberChildren;
                }
            }

            // حسم الأوائل (early_bird)
            if ($discount->type === 'early_bird') {
                $alreadyReserved = $trip->reservation()->sum('number_people');
                $maxUses = (int) ($discount->max_uses ?? 0);
                $remainingEligible = max(0, $maxUses - $alreadyReserved);

                if ($remainingEligible > 0) {
                    $eligibleNow = min($remainingEligible, $totalPeople);

                    if (!empty($discount->percentage)) {
                        $totalDiscount += $basePricePerPerson * ($discount->percentage / 100) * $eligibleNow;
                    } elseif (!empty($discount->amount)) {
                        $totalDiscount += $discount->amount * $eligibleNow;
                    }
                }
            }

            // الحسم العام (general)
            if ($discount->type === 'general') {
                if (!empty($discount->percentage)) {
                    $totalDiscount += $basePricePerPerson * ($discount->percentage / 100) * $totalPeople;
                } elseif (!empty($discount->amount)) {
                    $totalDiscount += $discount->amount * $totalPeople;
                }
            }
        }

        // المجموع النهائي بعد الحسم
        $total = max(0, $totalBasePrice - $totalDiscount);

        // تحقق من الرصيد
        $wallet = $user->wallet;
        if ($wallet->balance < $total) {
            return response()->json(['error' => 'الرصيد غير كافٍ'], 422);
        }

        // خصم الرصيد وتسجيل العملية
        $wallet->balance -= $total;
        $wallet->save();

        $wallet->transactions()->create([
            'type' => 'withdrawal',
            'amount' => $total,
            'description' => 'حجز رحلة عامة',
        ]);

        // إنشاء الحجز
        $reservation = $trip->reservation()->create([
            'user_id' => $user->id,
            'trip_id' => $trip->id,
            'number_people' => $numberAdults,
            'number_children' => $numberChildren,
            'status' => 'confirmed',
        ]);

        // إنشاء الفاتورة
        Invoice::create([
            'reservation_id' => $reservation->id,
            'total_amount' => $total,
            'payment_status' => 'paid',
        ]);

        return response()->json([
            'message' => 'تم حجز الرحلة العامة بنجاح',
            'reservation' => $reservation,
            'total_price' => $total
        ]);
    }

    private function handleSoloReservation($request, $trip, $user)
    {
        $start = Carbon::parse($request['start_date']);
        $end   = Carbon::parse($request['end_date']);

        // تحقق أن النهاية بعد البداية
        if ($end->lt($start)) {
            return response()->json([
                'error' => "تاريخ نهاية الحجز للعنصر  يجب أن يكون بعد تاريخ البداية"
            ], 422);
        }
        // --- تحقق أن التواريخ ضمن فترة الرحلة ---
        $tripStart = Carbon::parse($trip->start_date);
        $tripEnd   = Carbon::parse($trip->end_date);

        if ($tripStart->lt($tripStart) || $tripEnd->gt($tripEnd)) {
            return response()->json([
                'error' => 'يجب أن يكون الحجز ضمن مدة الرحلة من '
                    . $tripStart->toDateString() . ' إلى ' . $tripEnd->toDateString()
            ], 422);
        }

        $days = $start->diffInDays($end) ?: 1;

        // تحقق أن الطلب يحتوي على حجوزات متعددة أو عنصر واحد
        $reservationsData = $request->has('reservations') ? $request->reservations : [
            [
                'reservable_type' => $request->reservable_type,
                'reservable_id'   => $request->reservable_id,
                'start_date'      => $request->start_date,
                'end_date'        => $request->end_date,
                'start_time'      => $request->start_time ?? null,
            ]
        ];
        // --- تحقق كل الحجوزات أولاً ---
        $validatedReservations = [];
        foreach ($reservationsData as $resv) {
            $start = Carbon::parse($resv['start_date']);
            $end   = Carbon::parse($resv['end_date']);

            if ($end->lt($start)) {
                return response()->json([
                    'error' => "تاريخ نهاية الحجز للعنصر يجب أن يكون بعد تاريخ البداية"
                ], 422);
            }

            if ($start->lt($tripStart) || $end->gt($tripEnd)) {
                return response()->json([
                    'error' => "الحجز للعنصر ID {$resv['reservable_id']} يجب أن يكون ضمن مدة الرحلة من {$tripStart->toDateString()} إلى {$tripEnd->toDateString()}"
                ], 422);
            }

            $reservableType = $resv['reservable_type'] ?? null;
            $reservableId   = $resv['reservable_id'] ?? null;

            $allowed = [
                'App\Models\Room',
                'App\Models\Restaurant',
                'App\Models\Tourguide',
                'App\Models\Vehicle',
                'App\Models\Place',
            ];
            if (!in_array($reservableType, $allowed)) {
                return response()->json(['error' => "نوع الحجز {$reservableType} غير مدعوم"], 422);
            }

            $reservable = $reservableType::findOrFail($reservableId);

            // --- تحقق أن المستخدم ما يحجز أكثر من سيارة أو مرشد ---
            if ($reservable instanceof Vehicle) {
                $alreadyVehicle = Reservation::where('user_id', $user->id)
                    ->where('trip_id', $trip->id)
                    ->where('reservable_type', Vehicle::class)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();
                if ($alreadyVehicle) {
                    return response()->json([
                        'error' => 'لا يمكنك حجز أكثر من سيارة لنفس الرحلة'
                    ], 422);
                }
            }

            if ($reservable instanceof Tourguide) {
                $alreadyGuide = Reservation::where('user_id', $user->id)
                    ->where('trip_id', $trip->id)
                    ->where('reservable_type', Tourguide::class)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();
                if ($alreadyGuide) {
                    return response()->json([
                        'error' => 'لا يمكنك حجز أكثر من مرشد سياحي لنفس الرحلة'
                    ], 422);
                }
            }

            // --- تحقق حجز غرف في أكثر من فندق ---
            if ($reservable instanceof Room) {
                $hotelId = $reservable->hotel_id;
                $hasOtherHotel = Reservation::where('user_id', $user->id)
                    ->where('trip_id', $trip->id)
                    ->where('reservable_type', \App\Models\Room::class)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->whereHasMorph(
                        'reservable',
                        [\App\Models\Room::class],
                        function ($q) use ($hotelId) {
                            $q->where('hotel_id', '!=', $hotelId);
                        }
                    )->exists();
                if ($hasOtherHotel) {
                    return response()->json([
                        'message' => 'لا يمكنك حجز غرف في أكثر من فندق واحد لنفس الرحلة.'
                    ], 422);
                }
            }

            // --- تحقق التعارض / السعة ---
            if ($reservable instanceof Room) {
                $hasConflict = Reservation::where('reservable_type', Room::class)
                    ->where('reservable_id', $reservable->id)
                    ->where(function ($query) use ($start, $end) {
                        $query->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($q) use ($start, $end) {
                                $q->where('start_date', '<=', $start)
                                    ->where('end_date', '>=', $end);
                            });
                    })->exists();
                if ($hasConflict) {
                    return response()->json(['message' => "الغرفة ID {$reservable->id} محجوزة خلال الفترة المطلوبة."], 409);
                }
            } elseif ($reservable instanceof Restaurant) {
                $existingGuests = Reservation::where('reservable_type', Restaurant::class)
                    ->where('reservable_id', $reservable->id)
                    ->whereDate('start_date', $start)
                    ->whereTime('start_date', $resv['start_time'] ?? '00:00')
                    ->sum('guest_count');
                if ($existingGuests + ($resv['guest_count'] ?? 1) > $reservable->capacity) {
                    return response()->json(['message' => "المطعم ID {$reservable->id} محجوز بالكامل في هذا الوقت"], 400);
                }
            } elseif ($reservable instanceof Tourguide || $reservable instanceof Vehicle) {
                $hasConflict = Reservation::where('reservable_type', get_class($reservable))
                    ->where('reservable_id', $reservable->id)
                    ->where(function ($query) use ($start, $end) {
                        $query->where('start_date', '<', $end)
                            ->where('end_date', '>', $start);
                    })->exists();
                if ($hasConflict) {
                    return response()->json(['message' => "العنصر ID {$reservable->id} محجوز في نفس الفترة."], 409);
                }
            }

            // --- حساب السعر للتأكد ---
            $reservablePrice = 0;
            if ($reservable instanceof Room) {
                $days = $start->diffInDays($end) ?: 1;
                $reservablePrice = $reservable->price_per_night * $days;
            } elseif ($reservable instanceof Place) {
                $reservablePrice = $reservable->entry_free ?? 0;
            } elseif ($reservable instanceof Tourguide || $reservable instanceof Vehicle) {
                $days = $start->diffInDays($end) ?: 1;
                $reservablePrice = $reservable->price * $days;
            } elseif ($reservable instanceof Restaurant) {
                $reservablePrice = 0;
            }

            $resv['total_price'] = $reservablePrice;
            $validatedReservations[] = $resv; // نحتفظ بالبيانات بعد التحقق
        }

        // --- إذا كل شيء صحيح، ننشئ الحجوزات دفعة واحدة ---
        return DB::transaction(function () use ($validatedReservations, $trip, $user) {
            $createdReservations = [];

            foreach ($validatedReservations as $resv) {
                $reservable = $resv['reservable_type']::findOrFail($resv['reservable_id']);
                $start = Carbon::parse($resv['start_date']);
                $end   = Carbon::parse($resv['end_date']);

                // إنشاء الحجز
                $reservation = Reservation::create([
                    'user_id' => $user->id,
                    'trip_id' => $trip->id,
                    'start_date' => $start->format('Y-m-d H:i:s'),
                    'end_date'   => $end->format('Y-m-d H:i:s'),
                    'status' => 'pending',
                    'number_people' => $resv['number_people'] ?? ($resv['guest_count'] ?? 1),
                    'reservable_id' => $reservable->id,
                    'reservable_type' => get_class($reservable),
                ]);

                // --- إنشاء الفاتورة ---
                Invoice::create([
                    'reservation_id' => $reservation->id,
                    'reservabletype' => get_class($reservable),
                    'reservable_id' => $reservable->id,
                    'total_amount' => $resv['total_price'],
                    'payment_status' => 'pending',
                    'number_people' => $reservation->guest_count,
                ]);

                // --- إشعارات ---
                CustomNotification::create([
                    'user_id' => $user->id,
                    'title' => 'تم إنشاء الحجز',
                    'body' => "تم حجز العنصر ID {$reservable->id} بانتظار الدفع.",
                    'reservation_id' => $reservation->id
                ]);

                if ($reservable instanceof Room && $reservable->hotel && $reservable->hotel->user) {
                    CustomNotification::create([
                        'user_id' => $reservable->hotel->user->id,
                        'title' => 'تم حجز غرفتك',
                        'body' => "تم حجز الغرفة ID {$reservable->id} من {$reservation->start_date} إلى {$reservation->end_date}.",
                        'reservation_id' => $reservation->id
                    ]);
                } elseif ($reservable instanceof Restaurant && $reservable->user) {
                    CustomNotification::create([
                        'user_id' => $reservable->user->id,
                        'title' => 'تم حجز مطعمك',
                        'body' => "تم حجز المطعم ID {$reservable->id} بتاريخ {$reservation->start_date}.",
                        'reservation_id' => $reservation->id
                    ]);
                }

                $createdReservations[] = $reservation;
            }

            return response()->json([
                'message' => 'تم إنشاء جميع الحجوزات وبانتظار الدفع',
                'reservations' => $createdReservations,
                'reservation_id' => $reservation->id
            ]);
        });
    }


    /**
     * Display the specified resource.
     */
    public function showForUser()
    {
        $reservation = Auth::user()->reservations;
        return response()->json($reservation, 200);
    }
    public function numberUserForTrip($tripId)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json([
                'error' => 'غير مصر لك بتنفيذ هذا الإجراء'
            ], 403,);
        }
        $trip = Trip::findOrFail($tripId);
        $totalPeople = $trip->reservation()->sum('number_people');
        $totalChildren = $trip->reservation()->sum('number_children');
        $total = $totalPeople + $totalChildren;
        return response()->json($total, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservationRequest $request, string $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->update($request->validated());
        return response()->json([
            'message' => 'تم تعديل حجز الرحلة بنجاح',
            'reservation' => $reservation
        ], 200);
    }
    public function updatePrivateReservation(UpdateReservationRequest $request, $reservationId)
    {

        // جلب الحجز مع المستخدم والمحفظة
        $reservation = Reservation::with('user.wallet', 'trip', 'invoice')->find($reservationId);
        $reservation->update($request->validated());

        if (!$reservation) {
            return response()->json(['error' => 'الحجز غير موجود'], 404);
        }

        $user = $reservation->user;
        if (!$user) {
            return response()->json(['error' => 'المستخدم المرتبط بالحجز غير موجود'], 422);
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            return response()->json(['error' => 'لا توجد محفظة مرتبطة بهذا المستخدم'], 422);
        }

        $trip = $reservation->trip;

        // منع تعديل الحجز بعد بدء الرحلة
        if (Carbon::parse($trip->start_date)->lte(Carbon::now())) {
            return response()->json(['message' => 'لا يمكنك تعديل الحجز لأن الرحلة بدأت بالفعل'], 403);
        }

        // البيانات القديمة
        $oldTotal = $reservation->invoice->total_amount ?? 0;

        // بيانات جديدة
        $adults   = (int) $request->number_people;
        $children = (int) ($request->number_children ?? 0);
        $totalPeople = $adults + $children;

        $newTotal = 0;
        $basePrice = $trip->base_price;

        // حساب الأشخاص
        $newTotal += $basePrice * $totalPeople;

        // تعديل السيارة
        if ($request->has('vehicle_id')) {
            $car = Vehicle::find($request->car_id);
            if (!$car) return response()->json(['error' => 'السيارة غير موجودة'], 404);
            $reservation->update([
                'vehicle_id' => $car->id,
                'reservable_type' => Vehicle::class,
                'reservable_id' => $car->id
            ]);
            $newTotal += $car->price;
        }

        // تعديل الفندق
        if ($request->has('room_id')) {
            $room = Room::find($request->room_id);
            if (!$room) return response()->json(['error' => 'الغرفة غير موجودة'], 404);
            $reservation->update([
                'room_id' => $room->id,
                'reservable_type' => Hotel::class,
                'reservable_id' => $room->id
            ]);
            $newTotal += $room->price_per_night * $trip->days;
        }

        // تعديل الدليل السياحي
        if ($request->has('tourguide_id')) {
            $guide = Tourguide::find($request->tourguide_id);
            if (!$guide) return response()->json(['error' => 'الدليل السياحي غير موجود'], 404);
            $reservation->update([
                'tourguide_id' => $guide->id,
                'reservable_type' => Tourguide::class,
                'reservable_id' => $guide->id
            ]);
            $newTotal += $guide->price * $trip->days;
        }

        // تطبيق أي حسم خاص بالرحلة (نفس منطق handleGroupReservation)
        $trip->load('discounts');
        $totalDiscount = 0.0;
        foreach ($trip->discounts as $discount) {
            if ($discount->valid_until && now()->gt($discount->valid_until)) continue;

            // حسم للأطفال
            if ($discount->type === 'child' && $children > 0) {
                if (!empty($discount->percentage)) $totalDiscount += $basePrice * ($discount->percentage / 100) * $children;
                elseif (!empty($discount->amount)) $totalDiscount += $discount->amount * $children;
            }

            // حسم الأوائل
            if ($discount->type === 'early_bird') {
                $alreadyReserved = $trip->reservation()->sum('number_people');
                $maxUses = (int) ($discount->max_uses ?? 0);
                $remainingEligible = max(0, $maxUses - $alreadyReserved);
                if ($remainingEligible > 0) {
                    $eligibleNow = min($remainingEligible, $totalPeople);
                    if (!empty($discount->percentage)) $totalDiscount += $basePrice * ($discount->percentage / 100) * $eligibleNow;
                    elseif (!empty($discount->amount)) $totalDiscount += $discount->amount * $eligibleNow;
                }
            }

            // الحسم العام
            if ($discount->type === 'general') {
                if (!empty($discount->percentage)) $totalDiscount += $basePrice * ($discount->percentage / 100) * $totalPeople;
                elseif (!empty($discount->amount)) $totalDiscount += $discount->amount * $totalPeople;
            }
        }

        $newTotal = max(0, $newTotal - $totalDiscount);
        // حساب الفرق مع القديم
        $difference = $newTotal - $oldTotal;

        if ($difference > 0) {
            if ($wallet->balance < $difference) return response()->json(['error' => 'الرصيد غير كافٍ لتعديل الحجز'], 422);
            $wallet->decrement('balance', $difference);
            $wallet->transactions()->create([
                'type' => 'withdrawal',
                'amount' => $difference,
                'description' => 'تعديل حجز خاص - خصم فرق السعر',
            ]);
        } elseif ($difference < 0) {
            $wallet->increment('balance', abs($difference));
            $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => abs($difference),
                'description' => 'تعديل حجز خاص - استرجاع فرق السعر',
            ]);
        }

        // تحديث بيانات الحجز
        $reservation->update([
            'number_people' => $adults,
            'number_children' => $children,
        ]);

        // تحديث الفاتورة
        if ($reservation->invoice) {
            $reservation->invoice->update(['total_amount' => $newTotal]);
        } else {
            // إنشاء فاتورة إذا لم تكن موجودة
            Invoice::create([
                'reservation_id' => $reservation->id,
                'total_amount' => $newTotal,
                'payment_status' => 'paid',
            ]);
        }

        return response()->json([
            'message' => 'تم تعديل الحجز الخاص بنجاح',
            'reservation' => $reservation,
            'total_price' => $newTotal
        ]);
    }


    public function cancelWithPenalty($id)
    {
        $user = auth()->user();
        $reservation = Reservation::where('user_id', $user->id)->findOrFail($id);

        if ($reservation->status !== 'confirmed') {
            return response()->json(['error' => 'لا يمكن إلغاء هذا الحجز'], 422);
        }

        $invoice = $reservation->invoice;
        if (!$invoice || $invoice->payment_status !== 'paid') {
            return response()->json(['error' => 'لا يوجد مبلغ مدفوع لهذا الحجز'], 422);
        }
        if (!$reservation->trip) {
            return response()->json(['error' => 'لا توجد رحلة مرتبطة بهذا الحجز'], 422);
        }

        // تحقق إذا كان وقت انطلاق الرحلة قد بدأ أو انتهى
        if (now()->gte($reservation->trip->start_date)) {
            return response()->json(['message' => 'لا يمكن إلغاء الحجز بعد بدء وقت الرحلة.'], 400);
        }

        $wallet = $user->wallet;
        $total = $invoice->total_amount;

        // حساب الأيام المتبقية حتى موعد الرحلة
        $daysLeft = now()->diffInDays($reservation->trip->start_date);

        // تحديد نسبة الخصم بناءً على المدة المتبقية
        if ($daysLeft >= 30) {
            $refundRate = 0.80; // استرداد 80%
        } elseif ($daysLeft >= 14) {
            $refundRate = 0.70; // استرداد 70%
        } elseif ($daysLeft >= 3) {
            $refundRate = 0.50; // استرداد 50%
        } else {
            $refundRate = 0.00; // لا استرداد
        }

        $refundAmount = round($total * $refundRate, 2);
        $penaltyAmount = round($total - $refundAmount, 2);

        // إرجاع المبلغ (إذا كان أكبر من 0)
        if ($refundAmount > 0) {
            $wallet->balance += $refundAmount;
            $wallet->save();

            // تسجيل معاملة الاسترداد
            $wallet->transactions()->create([
                'type' => 'refund',
                'amount' => $refundAmount,
                'description' => "استرداد بعد إلغاء الحجز رقم {$reservation->id} (استرداد " . ($refundRate * 100) . "%)"
            ]);
        }

        // تسجيل غرامة (للتوثيق فقط)
        if ($penaltyAmount > 0) {
            $wallet->transactions()->create([
                'type' => 'penalty',
                'amount' => $penaltyAmount,
                'description' => "رسوم إلغاء الحجز رقم {$reservation->id}"
            ]);
        }

        // تحديث حالة الحجز والفاتورة
        $reservation->status = 'cancelled';
        $reservation->save();

        $invoice->update([
            'payment_status' => $refundRate > 0 ? 'refunded' : 'penalty',
        ]);

        return response()->json([
            'message' => "تم إلغاء الحجز. تم استرجاع " . ($refundRate * 100) . "% من المبلغ وخصم " . (100 - $refundRate * 100) . "% كرسوم.",
            'refunded' => $refundAmount,
            'penalty' => $penaltyAmount,
        ]);
    }
}
