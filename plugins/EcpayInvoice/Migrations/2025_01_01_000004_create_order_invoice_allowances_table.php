<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('order_invoice_allowances')) {
            Schema::create('order_invoice_allowances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_invoice_id')->index()->comment('發票 ID');
                $table->string('allowance_number')->nullable()->comment('綠界折讓單號');
                $table->string('desc', 100)->comment('折讓原因');
                $table->integer('amount')->comment('折讓金額');
                $table->string('status')->default('issued')->comment('issued/void');
                $table->timestamp('voided_at')->nullable();
                $table->json('response_log')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_invoice_allowances');
    }
};
