<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find and clean up duplicate attendance entries
        $duplicates = DB::table('attendance')
            ->select('user_id', 'date', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'date')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all entries for this user and date
            $entries = Attendance::where('user_id', $duplicate->user_id)
                ->where('date', $duplicate->date)
                ->orderBy('created_at', 'desc')
                ->get();

            // Keep the first (most recent) entry and delete the rest
            $keepEntry = $entries->first();
            $deleteEntries = $entries->skip(1);

            foreach ($deleteEntries as $entry) {
                $entry->delete();
            }

            echo "Cleaned up duplicates for user {$duplicate->user_id} on {$duplicate->date}. Kept entry ID: {$keepEntry->id}\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be easily reversed as we're deleting data
        // In a real scenario, you might want to backup the data before running this
    }
};