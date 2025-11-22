<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciationEntry;
use Carbon\Carbon;

class AssetDepreciationService
{
    // Straight-Line Method depreciation amount per month
    public static function monthlyAmount(Asset $asset): float
    {
        $cost = (float)$asset->purchase_cost_decimal;
        $salvage = (float)$asset->salvage_value_decimal;
        $life = (int)$asset->useful_life_months;
        if ($life <= 0) { return 0.0; }
        $base = max($cost - $salvage, 0);
        return round($base / $life, 2);
    }

    public static function generateForPeriod(Asset $asset, int $year, int $month): ?AssetDepreciationEntry
    {
        if ($asset->status !== 'active') { return null; }
        $start = Carbon::parse($asset->start_depreciation_date)->startOfDay();
        $periodDate = Carbon::create($year, $month, 1)->startOfDay();
        // if period is before start depreciation date skip
        if ($periodDate->lt($start)) { return null; }
        // if already exists skip
        if (AssetDepreciationEntry::where('asset_id',$asset->id)->where('period_year',$year)->where('period_month',$month)->exists()) { return null; }
        // if exceeded useful life skip
        $monthsDiff = $start->diffInMonths($periodDate) + 1; // counting current month
        if ($monthsDiff > $asset->useful_life_months) { return null; }
        $amount = self::monthlyAmount($asset);
        return AssetDepreciationEntry::create([
            'business_id' => $asset->business_id,
            'asset_id' => $asset->id,
            'period_year' => $year,
            'period_month' => $month,
            'amount_decimal' => $amount,
        ]);
    }

    public static function generateForAllActive(int $year, int $month): int
    {
        $count = 0;
        Asset::where('status','active')->chunk(200, function($chunk) use (&$count, $year, $month) {
            foreach ($chunk as $asset) {
                $entry = self::generateForPeriod($asset, $year, $month);
                if ($entry) {
                    // Attempt auto-post to GL
                    try { self::postJournal($entry); } catch (\Throwable $e) { /* log later if needed */ }
                    $count++;
                }
            }
        });
        return $count;
    }

    public static function postJournal(AssetDepreciationEntry $entry): ?\App\Models\JournalEntry
    {
        if ($entry->posted_journal_entry_id) { return null; }
        $asset = $entry->asset; if (!$asset) { return null; }
        $bizId = (int)$asset->business_id;
        $amount = (float)$entry->amount_decimal;
        if ($amount <= 0) { return null; }
        $date = sprintf('%04d-%02d-01', $entry->period_year, $entry->period_month);
        $d = config('accounting.defaults');
        $depExp = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['depreciation_expense_code']])->value('id');
        $accDep = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['accumulated_depreciation_code']])->value('id');
        if (!$depExp || !$accDep) { return null; }
        $je = \App\Models\JournalEntry::create([
            'business_id' => $bizId,
            'date' => $date,
            'memo' => 'Depreciation '.$asset->asset_code,
            'status' => 'posted',
        ]);
        \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$depExp,'debit'=>$amount,'credit'=>0]);
        \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$accDep,'debit'=>0,'credit'=>$amount]);
        $entry->posted_journal_entry_id = $je->id; $entry->save();
        return $je;
    }
}
