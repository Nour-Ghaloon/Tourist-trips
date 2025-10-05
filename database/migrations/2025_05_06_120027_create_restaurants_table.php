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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('menu')->nullable();
            $table->string('menu_public_id')->nullable();
            $table->text('address');
            $table->text('description');
            $table->text('contact_info');
            $table->text('opening_hours');
            $table->integer('capacity');
            $table->double('rate')->nullable();
            $table->ForeignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->ForeignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
