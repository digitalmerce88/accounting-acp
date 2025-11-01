<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Domain\Accounting\Services\JournalService;
use App\Http\Controllers\Controller;
use App\Models\{JournalEntry,JournalLine,Account};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $q = JournalEntry::query()->orderByDesc('date')->orderByDesc('id');
            return response()->json($q->paginate(20));
        }
        return Inertia::render('Admin/Accounting/Journals/Index');
    }

    public function create()
    {
        if (request()->wantsJson()) {
            return response()->json(['message'=>'Provide date, memo, and lines on POST /admin/accounting/journals']);
        }
        return Inertia::render('Admin/Accounting/Journals/Create');
    }

    public function edit(int $id)
    {
        if (request()->wantsJson()) {
            $e = JournalEntry::findOrFail($id);
            $lines = JournalLine::where('entry_id',$id)->orderBy('id')->get();
            // attach account name for each line to make frontend rendering simpler
            $acctNames = Account::whereIn('id', $lines->pluck('account_id')->unique())->pluck('name','id')->toArray();
            $lines = $lines->map(function($ln) use ($acctNames) {
                $ln->account_name = $acctNames[$ln->account_id] ?? null;
                return $ln;
            });
            return response()->json(['entry'=>$e,'lines'=>$lines]);
        }
        return Inertia::render('Admin/Accounting/Journals/Edit', ['id'=>$id]);
    }

    public function store(Request $request, JournalService $svc)
    {
        $data = $request->validate([
            'business_id' => ['nullable','integer'],
            'date' => ['required','date'],
            'memo' => ['nullable','string'],
            'lines' => ['required','array','min:2'],
            'lines.*.account_id' => ['required','integer'],
            'lines.*.debit' => ['nullable','numeric','min:0'],
            'lines.*.credit' => ['nullable','numeric','min:0'],
        ]);
        return DB::transaction(function() use ($svc, $data) {
            $e = $svc->createDraft($data['business_id'] ?? null, $data['date'], $data['memo'] ?? null);
            foreach ($data['lines'] as $ln) {
                $svc->upsertLine($e->id, $ln['account_id'], $ln['debit'] ?? 0, $ln['credit'] ?? 0);
            }
            try {
                $posted = $svc->post($e->id);
            } catch (Exception $ex) {
                throw $ex;
            }
            // Return the posted entry; no need to eager-load a non-existent relation
            $entry = JournalEntry::find($posted->id);
            return response()->json($entry, 201);
        });
    }

    public function update(int $id, Request $request, JournalService $svc)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'memo' => ['nullable','string'],
            'lines' => ['required','array','min:2'],
            'lines.*.account_id' => ['required','integer'],
            'lines.*.debit' => ['nullable','numeric','min:0'],
            'lines.*.credit' => ['nullable','numeric','min:0'],
        ]);
        return DB::transaction(function() use ($svc, $id, $data) {
            $e = JournalEntry::lockForUpdate()->findOrFail($id);
            // reset to draft and replace lines
            $e->update(['date'=>$data['date'], 'memo'=>$data['memo'] ?? null, 'status'=>'draft']);
            JournalLine::where('entry_id',$e->id)->delete();
            foreach ($data['lines'] as $ln) {
                $svc->upsertLine($e->id, $ln['account_id'], $ln['debit'] ?? 0, $ln['credit'] ?? 0);
            }
            $posted = $svc->post($e->id);
            return response()->json(['id'=>$posted->id], 200);
        });
    }

    public function show(int $id)
    {
        if (request()->wantsJson()) {
            // Load entry only; lines are fetched separately below
            $e = JournalEntry::findOrFail($id);
            $lines = JournalLine::where('entry_id',$id)->orderBy('id')->get();
            $acctNames = Account::whereIn('id', $lines->pluck('account_id')->unique())->pluck('name','id')->toArray();
            $lines = $lines->map(function($ln) use ($acctNames) {
                $ln->account_name = $acctNames[$ln->account_id] ?? null;
                return $ln;
            });
            return response()->json(['entry'=>$e,'lines'=>$lines]);
        }
        return Inertia::render('Admin/Accounting/Journals/Show', ['id'=>$id]);
    }

    public function destroy(int $id)
    {
        $e = JournalEntry::findOrFail($id);
        $e->delete();
        return response()->noContent();
    }
}
