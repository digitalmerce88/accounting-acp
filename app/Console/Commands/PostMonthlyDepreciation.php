<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AssetDepreciationService;
use Carbon\Carbon;

class PostMonthlyDepreciation extends Command
{
    protected $signature = 'assets:post-depreciation {--year=} {--month=}';
    protected $description = 'สร้างรายการค่าเสื่อมประจำเดือน (SLM) สำหรับสินทรัพย์ทั้งหมดที่ยัง Active';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: Carbon::now()->year);
        $month = (int) ($this->option('month') ?: Carbon::now()->month);
        $count = AssetDepreciationService::generateForAllActive($year, $month);
        $this->info("สร้างรายการค่าเสื่อมแล้ว {$count} รายการ สำหรับ {$year}-{$month}");
        return Command::SUCCESS;
    }
}
