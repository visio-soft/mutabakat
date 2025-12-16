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
        Schema::table('parks', function (Blueprint $table) {
            // Eğer eski kolon varsa rename yap, yoksa yeni oluştur
            if (Schema::hasColumn('parks', 'reconciliation_park_name')) {
                $table->renameColumn('reconciliation_park_name', 'mutabakat_park_name');
            } elseif (!Schema::hasColumn('parks', 'mutabakat_park_name')) {
                $table->string('mutabakat_park_name')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parks', function (Blueprint $table) {
            $table->dropColumn('mutabakat_park_name');
        });
    }
};
