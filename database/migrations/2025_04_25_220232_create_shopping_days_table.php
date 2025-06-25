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
        Schema::create('shopping_days', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamp('date');

            $table->timestamps(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_days');
    }
};
