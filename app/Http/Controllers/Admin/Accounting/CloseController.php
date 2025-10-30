<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domain\Accounting\ClosingService;

class CloseController extends Controller
{
    public function month(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2000',
            'month' => 'required|integer|min:1|max:12',
        ]);
        $bizId = (int) ($request->user()->business_id ?? 1); // basic single business default
        $entry = (new ClosingService())->closeMonth($bizId, (int)$data['year'], (int)$data['month']);
        if ($request->wantsJson()) return response()->json(['ok'=>true, 'entry_id'=>$entry->id]);
        return back()->with('success', 'ปิดงวดเรียบร้อย');
    }
}
