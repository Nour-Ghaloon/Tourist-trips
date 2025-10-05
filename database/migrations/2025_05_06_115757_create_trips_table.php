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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type',['solo','group']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('base_price',10,2)->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('duration_days')->nullable();
            $table->string('duration_time');
            $table->string('meeting_point');
            $table->ForeignId('created_by')->constrained('users')->nullable()->cascadeOnDelete();
            $table->ForeignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->ForeignId('vehicle_id')->nullable()->constrained('vehicles')->cascadeOnDelete();
           // $table->ForeignId('tourguide_id')->constrained('tourguides')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
