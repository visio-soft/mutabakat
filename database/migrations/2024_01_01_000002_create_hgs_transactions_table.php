<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hgs_park_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('park_id')->nullable();
            $table->datetime('provision_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->datetime('entry_date')->nullable();
            $table->datetime('exit_date')->nullable();
            $table->string('plate', 20)->nullable();
            $table->string('hgs_product_number', 50)->nullable();
            $table->string('institution_name')->nullable();
            $table->string('parking_name')->nullable();
            $table->string('lane_info', 10)->nullable();
            $table->text('description')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);
            $table->decimal('net_transfer_amount', 10, 2)->default(0);
            $table->string('row_hash', 32)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['park_id', 'provision_date']);
            $table->index(['plate', 'entry_date']);
            $table->index('reference_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hgs_park_transactions');
    }
};
