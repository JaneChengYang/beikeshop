<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
        });
    }
};
