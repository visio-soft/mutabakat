<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hgs_transactions', function (Blueprint $table) {
            $table->boolean('is_matched')->default(false)->after('row_hash');
        });
    }

    public function down(): void
    {
        Schema::table('hgs_transactions', function (Blueprint $table) {
            $table->dropColumn('is_matched');
        });
    }
};
