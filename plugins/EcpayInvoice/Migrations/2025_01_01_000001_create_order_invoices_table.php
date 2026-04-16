<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('order_invoices')) {
            Schema::create('order_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->index();
                $table->string('carrier_type')->default('personal')->comment('personal/mobile/love/company');
                $table->string('carrier_number')->nullable()->comment('手機條碼 /XXXXXXX');
                $table->string('tax_id', 8)->nullable()->comment('統一編號');
                $table->string('company_title')->nullable()->comment('公司抬頭');
                $table->string('love_code')->nullable()->comment('捐贈愛心碼');
                $table->string('invoice_number')->nullable()->comment('綠界回傳發票號碼');
                $table->string('random_number', 4)->nullable()->comment('綠界回傳隨機碼');
                $table->string('status')->default('pending')->comment('pending/issued/failed/void');
                $table->text('failed_reason')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('voided_at')->nullable();
                $table->json('issue_log')->nullable()->comment('送出的 request');
                $table->json('response_log')->nullable()->comment('綠界回傳的 response');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_invoices');
    }
};
