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
        Schema::create('wallet_transctions', function (Blueprint $table) {
            $table->id();
            $table->enum('type',['deposit','withdrawal','refund','penalty']);
            $table->decimal('amount',10,2);
            $table->text('description')->nullable();
            $table->ForeignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->ForeignId('invoice_id')->constrained('wallets')->nullable()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transctions');
    }
};
