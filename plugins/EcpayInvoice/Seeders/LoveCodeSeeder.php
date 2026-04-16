<?php

namespace Plugin\EcpayInvoice\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LoveCodeSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('g_受捐贈機關或團體捐贈碼清單.csv');

        if (! file_exists($csvPath)) {
            return;
        }

        // 若已有資料則跳過，避免重複匯入
        if (DB::table('ecpay_love_codes')->count() > 0) {
            return;
        }

        $handle = fopen($csvPath, 'r');
        // 跳過標題列
        fgetcsv($handle);

        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 6) {
                continue;
            }
            // 序號, 名稱, 捐贈碼, 簡稱, 統編, 縣市
            $batch[] = [
                'name'       => trim($row[1]),
                'love_code'  => trim($row[2]),
                'short_name' => trim($row[3]) ?: null,
                'tax_id'     => trim($row[4]),
                'city'       => trim($row[5]),
            ];

            if (count($batch) >= 200) {
                DB::table('ecpay_love_codes')->insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('ecpay_love_codes')->insert($batch);
        }

        fclose($handle);
    }
}
