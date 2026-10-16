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
        Schema::create('service_board_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->date('tanggal')->unique();
            // The entry IDs as a JSON list kept as text, like settings.value
            $table->text('urutan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_board_orders');
    }
};
