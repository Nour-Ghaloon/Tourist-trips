<?php

use App\Http\Controllers\api\CityController;
use App\Http\Controllers\api\DiscountController;
use App\Http\Controllers\api\DriverController;
use App\Http\Controllers\api\FavoriteController;
use App\Http\Controllers\api\HotelController;
use App\Http\Controllers\api\InvoiceController;
use App\Http\Controllers\api\MediaController;
use App\Http\Controllers\api\PaymentController;
use App\Http\Controllers\api\PlaceController;
use App\Http\Controllers\api\ReservationController;
use App\Http\Controllers\api\RestaurantController;
use App\Http\Controllers\api\RestaurantReservationController;
use App\Http\Controllers\api\ReviewController;
use App\Http\Controllers\api\RoomController;
use App\Http\Controllers\api\RoomReservationController;
use App\Http\Controllers\api\RoomTypeController;
use App\Http\Controllers\api\TourguidController;
use App\Http\Controllers\api\TripController;
use App\Http\Controllers\api\TripItineraryController;
use App\Http\Controllers\api\TripPlaceController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\VehicleController;
use App\Http\Controllers\api\WalletController;
use App\Http\Controllers\api\WalletTransctionController;
use App\Models\Hotel;
use App\Models\TripItinerary;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('payments', PaymentController::class);
Route::apiResource('reviews', ReviewController::class);

//********Discount*******
Route::apiResource('discounts', DiscountController::class);
Route::get('discountForTrip/{tripId}', [DiscountController::class, 'discountForTrip']);


//********Media*******
Route::apiResource('destroymedia', MediaController::class)->middleware('auth:sanctum');
Route::get('TripPohto', [MediaController::class, 'tripmedia'])->middleware('auth:sanctum');
Route::get('TripPohto', [MediaController::class, 'tripmedia'])->middleware('auth:sanctum');
Route::get('HotelPohto', [MediaController::class, 'hotelmedia'])->middleware('auth:sanctum');
Route::get('RestaurantPohto', [MediaController::class, 'restaurantmedia'])->middleware('auth:sanctum');

//********Invoice*******
Route::apiResource('invoices', InvoiceController::class);
Route::post('/trips/{tripId}/pay-invoices', [InvoiceController::class, 'payTripInvoices'])->middleware('auth:sanctum');
Route::post('InvoicForTrip/{tripId}', [InvoiceController::class, 'InvoicForTrip'])->middleware('auth:sanctum');

//********Trips*******
Route::get('public-trips', [TripController::class, 'publicTrips']);
Route::get('private-trips', [TripController::class, 'privateTrips'])->middleware('auth:sanctum');
Route::get('AllpublicTrips', [TripController::class, 'AllpublicTrips']);
Route::get('/trips/{trip}/invoices', [TripController::class, 'InvoicesTrip'])->middleware('auth:sanctum');
Route::get('AllprivateTrips', [TripController::class, 'AllprivateTrips'])->middleware('auth:sanctum');
Route::post('trips', [TripController::class, 'store'])->middleware('auth:sanctum');
Route::get('Alltrips', [TripController::class, 'Alltrips']);
Route::get('AlltripsWithLogin', [TripController::class, 'AlltripsWithLogin'])->middleware('auth:sanctum');
Route::put('trips/{id}', [TripController::class, 'update'])->middleware('auth:sanctum');
Route::delete('trips/{id}', [TripController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('AllTripsForUser', [TripController::class, 'AllTripsForUser'])->middleware('auth:sanctum');
Route::get('CityNameFromTrip/{tripId}', [TripController::class, 'CityNameFromTrip']);
Route::post('AddTripsToFavorites/{id}', [TripController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('RemoveTripsFromFavorites/{id}', [TripController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewTrip/{id}', [TripController::class, 'addReviewTrip'])->middleware('auth:sanctum');
Route::post('showTripWithRate/{id}', [TripController::class, 'showTripWithRate'])->middleware('auth:sanctum');
Route::get('showTripWithRate/{id}', [TripController::class, 'showTripWithRate']);


//********TripPlace*******
Route::apiResource('tripPlaces', TripPlaceController::class)->middleware('auth:sanctum');
Route::get('AllTripPlace', [TripPlaceController::class, 'AllTripPlace']);
Route::post('addTripPlaceToFavorites/{id}', [TripPlaceController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeTripPlaceFromFavorites/{id}', [TripPlaceController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewTrip_place/{id}', [TripPlaceController::class, 'addReviewTrip_place'])->middleware('auth:sanctum');
Route::post('showTrip_placeWithRate/{id}', [TripPlaceController::class, 'showTrip_placeWithRate'])->middleware('auth:sanctum');

//********TripItinerary*******
Route::prefix('trips/{trip}/itineraries')->group(function () {
    Route::get('/', [TripItineraryController::class, 'index']);
    Route::post('/', [TripItineraryController::class, 'store'])->middleware('auth:sanctum');
});

Route::prefix('itineraries')->group(function () {
    Route::get('{itinerary}', [TripItineraryController::class, 'show']);
    Route::put('{itinerary}', [TripItineraryController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('{itinerary}', [TripItineraryController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::get('AllTripeItineraries', [TripItineraryController::class, 'AllTripeItineraries'])->middleware('auth:sanctum');

//********City*******
Route::apiResource('cities', CityController::class)->middleware('auth:sanctum');
Route::get('getAllCity', [CityController::class, 'getAllCity']);
Route::get('AllHotelFromCity/{name}', [CityController::class, 'AllHotelFromCity']);
Route::get('AllRestaurantFromCity/{name}', [CityController::class, 'AllRestaurantFromCity']);
Route::get('AllGuidFromCity/{name}', [CityController::class, 'AllGuidFromCity']);
Route::post('addCityToFavorites/{id}', [CityController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeCityFromFavorites/{id}', [CityController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewCity/{id}', [CityController::class, 'addReviewCity'])->middleware('auth:sanctum');
Route::post('showCityWithRate/{id}', [CityController::class, 'showCityWithRate'])->middleware('auth:sanctum');
Route::get('showCityWithRate/{id}', [CityController::class, 'showCityWithRate']);


//********Place*******
Route::apiResource('places', PlaceController::class)->middleware('auth:sanctum');
Route::get('allPlaces', [PlaceController::class, 'allPlaces']);
Route::get('MostPlacesVisit', [PlaceController::class, 'MostPlacesVisit']);
Route::post('addPlaceToFavorites/{id}', [PlaceController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removePlaceFromFavorites/{id}', [PlaceController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewPlace/{id}', [PlaceController::class, 'addReviewPlace'])->middleware('auth:sanctum');
Route::post('showPlaceWithRate/{id}', [PlaceController::class, 'showPlaceWithRate'])->middleware('auth:sanctum');
Route::get('showPlaceWithRate/{id}', [PlaceController::class, 'showPlaceWithRate']);

//********Driver*******
Route::apiResource('drivers', DriverController::class)->middleware('auth:sanctum');
Route::get('AllDrivers', [DriverController::class, 'AllDrivers']);
Route::post('addDriverToFavorites/{id}', [DriverController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeDriverFromFavorites/{id}', [DriverController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewDriver/{id}', [DriverController::class, 'addReviewDriver'])->middleware('auth:sanctum');
Route::post('showDriverWithRate/{id}', [DriverController::class, 'showDriverWithRate'])->middleware('auth:sanctum');
Route::get('showDriverWithRate/{id}', [DriverController::class, 'showDriverWithRate']);


//********Wallet*******
Route::get('ShowWallet', [WalletController::class, 'show'])->middleware('auth:sanctum');
Route::post('deposit', [WalletController::class, 'deposit'])->middleware('auth:sanctum');
Route::post('withdraw', [WalletController::class, 'withdraw'])->middleware('auth:sanctum');

//********Reservations*******
Route::put('updateReservation/{id}', [ReservationController::class, 'update']);
Route::get('AllReservation', [ReservationController::class, 'AllReservation']);
Route::get('AllReservationForUser', [ReservationController::class, 'AllReservationForUser'])->middleware('auth:sanctum');
Route::get('AllTripPublicForUser', [ReservationController::class, 'AllTripPublicForUser'])->middleware('auth:sanctum');
Route::get('NumberReservations', [ReservationController::class, 'NumberReservations'])->middleware('auth:sanctum');
Route::put('updatePrivateReservation/{id}', [ReservationController::class, 'updatePrivateReservation'])->middleware('auth:sanctum');
Route::get('NumberReservationsForTrip/{id}', [ReservationController::class, 'NumberReservationsForTrip']);
Route::get('availableSeats/{id}', [ReservationController::class, 'availableSeats']);
Route::get('AllUserForTrip/{id}', [ReservationController::class, 'AllUserForTrip']);
Route::get('NumberUserForTrip/{id}', [ReservationController::class, 'NumberUserForTrip'])->middleware('auth:sanctum');
Route::get('ShowReservationsForUser', [ReservationController::class, 'showForUser'])->middleware('auth:sanctum');
Route::post('StoreReservations', [ReservationController::class, 'store'])->middleware('auth:sanctum');
Route::post('cancelWithPenalty/{id}', [ReservationController::class, 'cancelWithPenalty'])->middleware('auth:sanctum');

//********Room*******
Route::apiResource('rooms', RoomController::class)->middleware('auth:sanctum');
Route::get('AllRooms', [RoomController::class, 'AllRooms']);
Route::get('availableRooms', [RoomController::class, 'availableRooms'])->middleware('auth:sanctum');
Route::post('addRoomToFavorites/{id}', [RoomController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeRoomFromFavorites/{id}', [RoomController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewRoom/{id}', [RoomController::class, 'addReviewRoom'])->middleware('auth:sanctum');
Route::post('showRoomWithRate/{id}', [RoomController::class, 'showRoomWithRate'])->middleware('auth:sanctum');
Route::get('showRoomWithRate/{id}', [RoomController::class, 'showRoomWithRate']);

//********RoomType*******
Route::apiResource('roomtypes', RoomTypeController::class)->middleware('auth:sanctum');
Route::get('AllRoomTypes', [RoomTypeController::class, 'AllRoomTypes']);

//********Restaurant*******
Route::apiResource('restaurants', RestaurantController::class)->middleware('auth:sanctum');
Route::get('AllRestaurant', [RestaurantController::class, 'AllRestaurant']);
Route::get('availableRestaurants', [RestaurantController::class, 'availableRestaurants']);
Route::delete('restaurants/{id}/menu', [RestaurantController::class, 'deleteMenu']);
Route::get('CityNameFromRestaurant/{restaurantId}', [RestaurantController::class, 'CityNameFromRestaurant']);
Route::get('NameRestaurant/{nameRestaurant}', [RestaurantController::class, 'NameRestaurant']);
Route::post('addRestaurantToFavorites/{id}', [RestaurantController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeRestaurantFromFavorites/{id}', [RestaurantController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewRestaurant/{id}', [RestaurantController::class, 'addReviewRestaurant'])->middleware('auth:sanctum');
Route::post('showRestaurantWithRate/{id}', [RestaurantController::class, 'showRestaurantWithRate'])->middleware('auth:sanctum');
Route::get('showRestaurantWithRate/{id}', [RestaurantController::class, 'showRestaurantWithRate']);


//********Hotel*******
Route::apiResource('hotels', HotelController::class)->middleware('auth:sanctum');
Route::get('AllHotels', [HotelController::class, 'AllHotels']);
Route::get('CityNameFromHotel/{hotelId}', [HotelController::class, 'CityNameFromHotel']);
Route::get('NameHotel/{hotel}', [HotelController::class, 'NameHotel']);
Route::post('addHotelToFavorites/{id}', [HotelController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeHotelFromFavorites/{id}', [HotelController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewHotel/{id}', [HotelController::class, 'addReviewHotel'])->middleware('auth:sanctum');
Route::post('showHotelWithRate/{id}', [HotelController::class, 'showHotelWithRate'])->middleware('auth:sanctum');
Route::get('showHotelWithRate/{id}', [HotelController::class, 'showHotelWithRate']);

//********Tourguide*******
Route::apiResource('tourguides', TourguidController::class)->middleware('auth:sanctum');
Route::get('AllTourguide', [TourguidController::class, 'AllTourguide']);
Route::get('availableTourguides', [TourguidController::class, 'availableTourguides'])->middleware('auth:sanctum');
Route::get('PlaceNameFromTourguide/{TourguideId}', [TourguidController::class, 'PlaceNameFromTourguide'])->middleware('auth:sanctum');
Route::post('addTourguideToFavorites/{id}', [TourguidController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeTourguideFromFavorites/{id}', [TourguidController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewTourguide/{id}', [TourguidController::class, 'addReviewTourguide'])->middleware('auth:sanctum');
Route::post('showTourguideWithRate/{id}', [TourguidController::class, 'showTourguideWithRate'])->middleware('auth:sanctum');
Route::get('showTourguideWithRate/{id}', [TourguidController::class, 'showTourguideWithRate']);

//********Vehicle*******
Route::apiResource('vehicles', VehicleController::class)->middleware('auth:sanctum');
Route::get('AllVehicle', [VehicleController::class, 'AllVehicle']);
Route::get('availableVehicles', [VehicleController::class, 'availableVehicles'])->middleware('auth:sanctum');
Route::post('addVehicleToFavorites/{id}', [VehicleController::class, 'addToFavorites'])->middleware('auth:sanctum');
Route::post('removeVehicleFromFavorites/{id}', [VehicleController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::post('addReviewVehicle/{id}', [VehicleController::class, 'addReviewVehicle'])->middleware('auth:sanctum');
Route::post('showVehicleWithRate/{id}', [VehicleController::class, 'showVehicleWithRate'])->middleware('auth:sanctum');
Route::get('showVehicleWithRate/{id}', [VehicleController::class, 'showVehicleWithRate']);

//********Favorite*******
Route::apiResource('favorites', FavoriteController::class)->middleware('auth:sanctum');
Route::get('getAllFavorites', [FavoriteController::class, 'getAllFavorites'])->middleware('auth:sanctum');


//********User*******
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::get('numberUser', [UserController::class, 'numberUser']);
Route::get('AllUser', [UserController::class, 'AllUser']);
Route::get('notifications', [UserController::class, 'notifications'])->middleware('auth:sanctum');
Route::post('markAsRead/{id}', [UserController::class, 'markAsRead'])->middleware('auth:sanctum');
Route::patch('/notifications/read-all', [UserController::class, 'markAllAsRead'])->middleware('auth:sanctum');
