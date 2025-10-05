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
        Schema::create('trip_itineraries', function (Blueprint $table) {
            $table->id();
            $table->integer('day_number');
            $table->string('day_number');
            $table->decimal('distance',10,3)->default(0);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('map_location')->nullable();
            $table->text('notes')->nullable();
            $table->text('description')->nullable();
            $table->text('full_description')->nullable();
            $table->string('short_title')->nullable();
            $table->ForeignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->ForeignId('place_id')->nullable()->constrained('places')->cascadeOnDelete();
            $table->ForeignId('hotel_id')->nullable()->constrained('hotels')->cascadeOnDelete();
            $table->ForeignId('restaurant_id')->nullable()->constrained('restaurants')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trip_itineraries');
    }
};
