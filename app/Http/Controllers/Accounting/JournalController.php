<?php
namespace App\Http\Controllers\Accounting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domain\Accounting\Services\JournalService;

class JournalController extends Controller {
    public function store(Request $r) {
        $validated = $r->validate([
            'business_id' => 'nullable|integer',
            'date' => 'required|date',
            'memo' => 'nullable|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'numeric',
            'lines.*.credit' => 'numeric',
        ]);
        $svc = app(JournalService::class);
        $entry = $svc->createDraft($validated['business_id'] ?? null, $validated['date'], $validated['memo'] ?? null);
        foreach ($validated['lines'] as $ln) {
            $svc->upsertLine($entry->id, $ln['account_id'], $ln['debit'] ?? 0, $ln['credit'] ?? 0);
        }
        $svc->post($entry->id);
        return response()->json(['ok'=>true, 'entry_id'=>$entry->id]);
    }
}
