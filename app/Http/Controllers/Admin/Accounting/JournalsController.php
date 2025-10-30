<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Domain\Accounting\Services\JournalService;
use App\Http\Controllers\Controller;
use App\Models\{JournalEntry,JournalLine};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalsController extends Controller
{
    public function index(Request $request)
    {
        $q = JournalEntry::query()->orderByDesc('date')->orderByDesc('id');
        return response()->json($q->paginate(20));
    }

    public function create()
    {
        return response()->json(['message'=>'Provide date, memo, and lines on POST /admin/accounting/journals']);
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
            $entry = JournalEntry::with('id')->find($posted->id);
            return response()->json($entry, 201);
        });
    }

    public function show(int $id)
    {
        $e = JournalEntry::with(['id'])->findOrFail($id);
        $lines = JournalLine::where('entry_id',$id)->orderBy('id')->get();
        return response()->json(['entry'=>$e,'lines'=>$lines]);
    }

    public function destroy(int $id)
    {
        $e = JournalEntry::findOrFail($id);
        $e->delete();
        return response()->noContent();
    }
}
