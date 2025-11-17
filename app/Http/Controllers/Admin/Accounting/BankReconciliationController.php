<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Domain\Banking\BankReconciliationService;
use App\Http\Controllers\Controller;
use App\Models\{BankAccount, BankTransaction, Reconciliation, ReconciliationMatch};
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankReconciliationController extends Controller
{
    public function accounts(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rows = BankAccount::where('business_id',$bizId)->orderBy('id','desc')->paginate(12);
        return Inertia::render('Admin/Accounting/Bank/Accounts', ['rows'=>$rows]);
    }

    public function transactions(Request $request, int $accountId)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $acc = BankAccount::where('business_id',$bizId)->findOrFail($accountId);
        $rows = BankTransaction::where('business_id',$bizId)->where('bank_account_id',$acc->id)->latest('date')->paginate(20);
        if ($request->wantsJson()) { return response()->json(['account'=>$acc,'rows'=>$rows]); }
        return Inertia::render('Admin/Accounting/Bank/Transactions', ['account'=>$acc,'rows'=>$rows]);
    }

    public function import(Request $request, int $accountId, BankReconciliationService $svc)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $acc = BankAccount::where('business_id',$bizId)->findOrFail($accountId);
        $data = $request->validate([
            'file' => ['required','file','mimes:csv,txt'],
            'mapping' => ['nullable','array']
        ]);
        $path = $data['file']->getRealPath();
        $count = $svc->importCsv($bizId, $acc->id, $path, (array)($data['mapping'] ?? []));
        return back()->with('success', "นำเข้ารายการธนาคารแล้ว {$count} รายการ");
    }

    public function start(Request $request)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $data = $request->validate([
            'bank_account_id' => ['required','integer'],
            'period_start' => ['required','date'],
            'period_end' => ['required','date','after_or_equal:period_start'],
            'statement_balance_decimal' => ['nullable','numeric']
        ]);
        $acc = BankAccount::where('business_id',$bizId)->findOrFail($data['bank_account_id']);
        $rec = Reconciliation::create([
            'business_id' => $bizId,
            'bank_account_id' => $acc->id,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'statement_balance_decimal' => (float)($data['statement_balance_decimal'] ?? 0),
            'status' => 'in_progress',
        ]);
        return redirect()->route('admin.accounting.bank.reconcile.show', $rec->id)->with('success','เริ่มกระทบยอดแล้ว');
    }

    public function autoMatch(Request $request, int $id, BankReconciliationService $svc)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rec = Reconciliation::where('business_id',$bizId)->findOrFail($id);
        $count = $svc->autoMatch($bizId, $rec->bank_account_id, $rec->period_start, $rec->period_end, $rec->id);
        return back()->with('success', "จับคู่ให้อัตโนมัติแล้ว {$count} รายการ");
    }

    public function show(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rec = Reconciliation::where('business_id',$bizId)->with(['matches.bankTransaction','account'])->findOrFail($id);
        if ($request->wantsJson()) {
            return response()->json(['item'=>$rec]);
        }
        return Inertia::render('Admin/Accounting/Bank/Reconcile', ['item'=>$rec]);
    }

    public function manualMatch(Request $request, int $id)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rec = Reconciliation::where('business_id',$bizId)->findOrFail($id);
        $data = $request->validate([
            'bank_transaction_id' => ['required','integer'],
            'transaction_id' => ['required','integer'],
            'amount' => ['required','numeric']
        ]);
        $bt = BankTransaction::where('business_id',$bizId)->where('bank_account_id',$rec->bank_account_id)->findOrFail($data['bank_transaction_id']);
        ReconciliationMatch::create([
            'reconciliation_id'=>$rec->id,
            'bank_transaction_id'=>$bt->id,
            'transaction_id'=>$data['transaction_id'],
            'matched_amount_decimal'=>round((float)$data['amount'],2),
            'method'=>'manual',
        ]);
        $bt->matched = true; $bt->save();
        return back()->with('success','จับคู่แบบแมนนวลแล้ว');
    }

    public function unmatch(Request $request, int $id, int $matchId)
    {
        $bizId = (int)($request->user()->business_id ?? 1);
        $rec = Reconciliation::where('business_id',$bizId)->findOrFail($id);
        $match = ReconciliationMatch::where('reconciliation_id',$rec->id)->findOrFail($matchId);
        $bt = BankTransaction::find($match->bank_transaction_id);
        if ($bt) { $bt->matched = false; $bt->save(); }
        $match->delete();
        return back()->with('success','ยกเลิกการจับคู่แล้ว');
    }
}
