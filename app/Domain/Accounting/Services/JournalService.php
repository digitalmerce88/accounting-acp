<?php
namespace App\Domain\Accounting\Services;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Period;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalService {
    public function createDraft($businessId, $date, $memo=null): JournalEntry {
        return JournalEntry::create([ 'business_id'=>$businessId, 'date'=>$date, 'memo'=>$memo, 'status'=>'draft' ]);
    }
    public function upsertLine($entryId, $accountId, $debit=0, $credit=0): JournalLine {
        $entry = JournalEntry::findOrFail($entryId);
        $this->assertPeriodOpen($entry->date, $entry->business_id);
        if ($entry->status === 'posted') { throw new Exception('Entry already posted.'); }
        return JournalLine::create([ 'entry_id'=>$entryId, 'account_id'=>$accountId, 'debit'=>$debit or 0, 'credit'=>$credit or 0 ]);
    }
    public function post($entryId): JournalEntry {
        return DB::transaction(function() use ($entryId) {
            $entry = JournalEntry::lockForUpdate()->findOrFail($entryId);
            $this->assertPeriodOpen($entry->date, $entry->business_id);
            $sum = JournalLine::where('entry_id',$entry->id)
                ->selectRaw('ROUND(SUM(debit),2) as dr, ROUND(SUM(credit),2) as cr')->first();
            if (round($sum->dr ?? 0,2) !== round($sum->cr ?? 0,2)) throw new Exception('Entry not balanced.');
            $entry->status = 'posted'; $entry->save(); return $entry;
        });
    }
    protected function assertPeriodOpen($date, $businessId=null): void {
        $month = (int)date('n', strtotime($date));
        $year = (int)date('Y', strtotime($date));
        $p = Period::where('business_id',$businessId)->where('month',$month)->where('year',$year)->first();
        if ($p && $p->status === 'locked') throw new Exception('Period is locked.');
    }
}
