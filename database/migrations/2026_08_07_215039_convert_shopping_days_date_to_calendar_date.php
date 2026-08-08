<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A shopping day's `date` is a calendar date ("the day I went shopping"),
     * not an instant, but the column was a `timestamp`. Every row so far was
     * created in America/Mexico_City (no per-user timezone field exists), so
     * we reinterpret each stored UTC instant as its Mexico City calendar date
     * BEFORE narrowing the column — narrowing first would truncate to the UTC
     * date and silently shift any row created near midnight to the wrong day.
     */
    public function up(): void
    {
        foreach (DB::table('shopping_days')->get(['id', 'date']) as $row) {
            DB::table('shopping_days')->where('id', $row->id)->update([
                'date' => Carbon::parse($row->date, 'UTC')
                    ->setTimezone('America/Mexico_City')
                    ->toDateString(),
            ]);
        }

        Schema::table('shopping_days', function (Blueprint $table) {
            $table->date('date')->change();
        });
    }

    /**
     * Reverses the migration. The original time-of-day (the moment the
     * "start shopping day" button was tapped) is not recoverable — each date
     * is written back as America/Mexico_City midnight, expressed in UTC, so
     * the old display path (an instant rendered in the viewer's local
     * timezone) still shows the correct day for a Mexico City viewer.
     */
    public function down(): void
    {
        Schema::table('shopping_days', function (Blueprint $table) {
            $table->timestamp('date')->change();
        });

        foreach (DB::table('shopping_days')->get(['id', 'date']) as $row) {
            DB::table('shopping_days')->where('id', $row->id)->update([
                'date' => Carbon::parse($row->date, 'America/Mexico_City')
                    ->startOfDay()
                    ->setTimezone('UTC'),
            ]);
        }
    }
};
