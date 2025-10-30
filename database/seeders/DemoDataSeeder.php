<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Business,Account,JournalEntry,JournalLine};
class DemoDataSeeder extends Seeder {
    public function run(): void {
        $biz = Business::first();
        $cash = Account::where('code','101')->first();
        $equity = Account::where('code','301')->first();
        $service = Account::where('code','402')->first();
        $expense = Account::where('code','502')->first();
        $e = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-01'),'memo'=>'ทุนเปิดกิจการ','status'=>'draft']);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$cash->id,'debit'=>50000,'credit'=>0]);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$equity->id,'debit'=>0,'credit'=>50000]); $e->update(['status'=>'posted']);
        $e = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-05'),'memo'=>'ขายบริการ','status'=>'draft']);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$cash->id,'debit'=>8000,'credit'=>0]);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$service->id,'debit'=>0,'credit'=>8000]); $e->update(['status'=>'posted']);
        $e = JournalEntry::create(['business_id'=>$biz->id,'date'=>date('Y-m-10'),'memo'=>'ค่าสาธารณูปโภค','status'=>'draft']);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$expense->id,'debit'=>1500,'credit'=>0]);
        JournalLine::create(['entry_id'=>$e->id,'account_id'=>$cash->id,'debit'=>0,'credit'=>1500]); $e->update(['status'=>'posted']);
    }
}
