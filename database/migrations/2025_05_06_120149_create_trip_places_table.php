<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trip_places', function (Blueprint $table) {
            $table->id();
            $table->ForeignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->ForeignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->ForeignId('hotel_id')->constrained('hotels')->nullable()->cascadeOnDelete();
            $table->ForeignId('restaurant_id')->constrained('restaurants')->nullable()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_places');
    }
};
