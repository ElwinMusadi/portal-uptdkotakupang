<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: one clock-in per officer and activity, at the
     * server's time, with the point, its accuracy, the distance to the
     * activity and the selfie in the private bucket.
     */
    public function up(): void
    {
        Schema::create('field_attendances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('field_activity_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('waktu');
            $table->decimal('lat', 9, 6);
            $table->decimal('lng', 9, 6);
            $table->unsignedSmallInteger('akurasi');
            $table->unsignedInteger('jarak');
            $table->string('foto', 500);
            $table->timestamps();
            $table->unique(['field_activity_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_attendances');
    }
};
