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
        Schema::create('access', function (Blueprint $table) {
            $table->foreignUuid('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->uuidMorphs('accessible');

            $table->foreignUuid('added_by')
                ->constrained('users', 'id')
                ->cascadeOnDelete();

            $table->index(['user_id', 'accessible_id', 'accessible_type']);

            $table->timestamps(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access');
    }
};
