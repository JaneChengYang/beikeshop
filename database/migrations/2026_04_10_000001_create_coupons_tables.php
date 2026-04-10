<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique()->comment('優惠碼');
            $table->enum('type', ['fixed', 'percent'])->comment('折扣類型：固定金額/百分比');
            $table->decimal('value', 10, 2)->comment('折扣值');
            $table->decimal('min_order', 10, 2)->default(0)->comment('最低消費門檻，0=無限制');
            $table->unsignedInteger('usage_limit')->default(0)->comment('總使用次數上限，0=無限制');
            $table->unsignedInteger('usage_limit_per_user')->default(0)->comment('每人限用次數，0=無限制');
            $table->unsignedInteger('used_count')->default(0)->comment('已使用次數');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('指定客人，null=公開折扣碼');
            $table->timestamp('starts_at')->nullable()->comment('開始時間');
            $table->timestamp('expires_at')->nullable()->comment('到期時間');
            $table->boolean('active')->default(true)->comment('是否啟用');
            $table->timestamps();
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('coupon_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();

            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
