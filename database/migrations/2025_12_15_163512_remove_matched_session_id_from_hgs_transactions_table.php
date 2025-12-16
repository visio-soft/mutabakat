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
        // Kolonlar zaten mevcut değil, işlem yapılmayacak
        if (Schema::hasColumn('hgs_park_transactions', 'matched_session_id')) {
            Schema::table('hgs_park_transactions', function (Blueprint $table) {
                $table->dropColumn('matched_session_id');
            });
        }
        
        if (Schema::hasColumn('hgs_park_transactions', 'is_matched')) {
            Schema::table('hgs_park_transactions', function (Blueprint $table) {
                $table->dropColumn('is_matched');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hgs_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('matched_session_id')->nullable()->after('park_id');
            $table->boolean('is_matched')->default(false)->after('row_hash');
        });
    }
};
