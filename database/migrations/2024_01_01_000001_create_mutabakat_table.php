<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutabakat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('park_id')->nullable();
            $table->string('row_hash')->nullable()->index();
            $table->date('provision_date')->nullable();
            $table->string('company')->nullable();
            $table->string('parking_name')->nullable();
            $table->string('parent_parking_name')->nullable();
            $table->string('transaction_name')->nullable();
            $table->integer('transaction_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('net_transfer_amount', 15, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutabakat');
    }
};
