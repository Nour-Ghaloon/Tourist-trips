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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->integer('number_people');
            $table->integer('number_children')->nullable();
            $table->morphs('reservable');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status',['pending','confirmed','cancelled']);
            $table->text('comment')->nullable();
            $table->integer('guest_count')->nullable();
            $table->ForeignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->ForeignId('trip_id')->constrained('trips')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
