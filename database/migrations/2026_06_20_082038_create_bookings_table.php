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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique()->comment('Format: BK-YYYYMMDD-XXXXXX');
            $table->string('flight_id')->comment('Stable base64url ID: base64(flightNumber|departureTime)');
            $table->string('flight_number', 10);
            $table->char('origin', 3);
            $table->char('destination', 3);
            $table->string('departure_time');       // stored as ISO-8601 string
            $table->decimal('price_per_passenger', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->decimal('total_price', 10, 2);
            $table->jsonb('passengers');
            $table->string('status', 20)->default('confirmed')->comment('confirmed | cancelled');
            $table->timestamps();
            $table->index('flight_id');
            $table->index('flight_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
