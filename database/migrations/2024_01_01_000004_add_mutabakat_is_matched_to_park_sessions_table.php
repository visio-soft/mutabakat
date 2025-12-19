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
        Schema::table('park_sessions', function (Blueprint $table) {
            $table->boolean('mutabakat_is_matched')->default(false)->after('processing_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('park_sessions', function (Blueprint $table) {
            $table->dropColumn('mutabakat_is_matched');
        });
    }
};
