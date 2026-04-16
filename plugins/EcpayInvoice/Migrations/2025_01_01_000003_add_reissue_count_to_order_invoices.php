<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_invoices', function (Blueprint $table) {
            $table->unsignedInteger('reissue_count')->default(0)->after('voided_at')->comment('補開次數，用於產生唯一 RelateNumber');
        });
    }

    public function down(): void
    {
        Schema::table('order_invoices', function (Blueprint $table) {
            $table->dropColumn('reissue_count');
        });
    }
};
