<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDisposal;
use Illuminate\Support\Facades\DB;

class AssetDisposalService
{
    public static function dispose(Asset $asset, \DateTimeInterface $date, float $proceed): AssetDisposal
    {
        if ($asset->status === 'disposed') {
            throw new \RuntimeException('Asset already disposed');
        }
        return DB::transaction(function() use ($asset, $date, $proceed) {
            $cost = (float)$asset->purchase_cost_decimal;
            $salvage = (float)$asset->salvage_value_decimal;
            $life = (int)$asset->useful_life_months;
            $depreciatedMonths = $asset->depreciationEntries()->count();
            $base = max($cost - $salvage,0);
            $accumDep = round($base * min($depreciatedMonths,$life) / $life, 2);
            $bookValue = round($cost - $accumDep,2);
            $gainLoss = round($proceed - $bookValue,2);
            $disposal = AssetDisposal::create([
                'business_id' => $asset->business_id,
                'asset_id' => $asset->id,
                'disposal_date' => $date->format('Y-m-d'),
                'proceed_amount_decimal' => $proceed,
                'gain_loss_decimal' => $gainLoss,
            ]);
            $asset->status = 'disposed';
            $asset->disposal_date = $date->format('Y-m-d');
            $asset->save();

            // Post journal for disposal: Dr Cash, Dr AccumDep, Cr Asset Cost, Gain or Loss
            self::postDisposalJournal($asset, $disposal, $cost, $accumDep, $proceed, $gainLoss, $date->format('Y-m-d'));
            return $disposal;
        });
    }

    protected static function postDisposalJournal(Asset $asset, AssetDisposal $disposal, float $cost, float $accumDep, float $proceed, float $gainLoss, string $date): void
    {
        $d = config('accounting.defaults');
        $bizId = (int)$asset->business_id;
        $cashId = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['bank_code']])->value('id');
        $assetCostId = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['asset_cost_code']])->value('id');
        $accDepId = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['accumulated_depreciation_code']])->value('id');
        $gainId = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['asset_disposal_gain_code']])->value('id');
        $lossId = \App\Models\Account::where(['business_id'=>$bizId,'code'=>$d['asset_disposal_loss_code']])->value('id');
        if (!$cashId || !$assetCostId || !$accDepId || (!$gainId && !$lossId)) { return; }
        $je = \App\Models\JournalEntry::create([
            'business_id' => $bizId,
            'date' => $date,
            'memo' => 'Disposal '.$asset->asset_code,
            'status' => 'posted',
        ]);
        // Cash proceeds
        if ($proceed > 0) { \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$cashId,'debit'=>round($proceed,2),'credit'=>0]); }
        // Accumulated depreciation
        if ($accumDep > 0) { \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$accDepId,'debit'=>round($accumDep,2),'credit'=>0]); }
        // Remove asset cost
        if ($cost > 0) { \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$assetCostId,'debit'=>0,'credit'=>round($cost,2)]); }
        // Gain or Loss line
        if ($gainLoss > 0 && $gainId) { \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$gainId,'debit'=>0,'credit'=>round($gainLoss,2)]); }
        if ($gainLoss < 0 && $lossId) { \App\Models\JournalLine::create(['entry_id'=>$je->id,'account_id'=>$lossId,'debit'=>round(abs($gainLoss),2),'credit'=>0]); }
        $disposal->journal_entry_id = $je->id; $disposal->save();
    }
}
