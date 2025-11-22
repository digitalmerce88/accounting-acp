<?php

namespace App\Http\Controllers\Admin\Documents;

use App\Http\Controllers\Controller;
use App\Models\{ApprovalLog, AuditLog, Invoice, Quote, PurchaseOrder, Bill, User};
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistoryController extends Controller
{
    public function show(string $type, int $id)
    {
        $map = [
            'invoices' => Invoice::class,
            'quotes' => Quote::class,
            'po' => PurchaseOrder::class,
            'bills' => Bill::class,
        ];
        if (! isset($map[$type])) {
            abort(404);
        }
        $modelClass = $map[$type];
        $doc = $modelClass::findOrFail($id);

        $approval = ApprovalLog::where('model_type', $modelClass)
            ->where('model_id', $doc->id)
            ->orderByDesc('id')
            ->get();
        $audit = AuditLog::where('model_type', $modelClass)
            ->where('model_id', $doc->id)
            ->orderByDesc('id')
            ->get();

        $userIds = $approval->pluck('user_id')->merge($audit->pluck('user_id'))->filter()->unique()->values();
        $users = $userIds->isNotEmpty() ? User::whereIn('id', $userIds)->pluck('name', 'id') : collect();

        $approvalData = $approval->map(function ($l) use ($users) {
            return [
                'id' => $l->id,
                'action' => $l->action,
                'comment' => $l->comment,
                'user_id' => $l->user_id,
                'user_name' => $l->user_id ? ($users[$l->user_id] ?? null) : null,
                'created_at' => optional($l->created_at)->toDateTimeString(),
            ];
        });

        $auditData = $audit->map(function ($l) use ($users) {
            return [
                'id' => $l->id,
                'action' => $l->action,
                'user_id' => $l->user_id,
                'user_name' => $l->user_id ? ($users[$l->user_id] ?? null) : null,
                'old_values' => $l->old_values,
                'new_values' => $l->new_values,
                'created_at' => optional($l->created_at)->toDateTimeString(),
            ];
        });

        return Inertia::render('Admin/Documents/History/Show', [
            'doc' => [
                'id' => $doc->id,
                'number' => $doc->number ?? null,
                'approval_status' => $doc->approval_status ?? null,
                'type' => $type,
            ],
            'approvalLogs' => $approvalData,
            'auditLogs' => $auditData,
        ]);
    }
}
