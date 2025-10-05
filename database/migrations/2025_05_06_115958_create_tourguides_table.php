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
        Schema::create('tourguides', function (Blueprint $table) {
            $table->id();
            $table->json('language');
            $table->decimal('price',10,2);
            $table->ForeignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->ForeignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tourguides');
    }
};
