<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('ecpay_love_codes')) {
            Schema::create('ecpay_love_codes', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('受捐贈機關全名');
                $table->string('short_name')->nullable()->comment('簡稱');
                $table->string('love_code')->index()->comment('愛心碼');
                $table->string('tax_id', 8)->comment('統一編號');
                $table->string('city')->comment('縣市');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ecpay_love_codes');
    }
};
