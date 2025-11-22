<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExchangeRatesController extends Controller
{
    public function index(Request $request)
    {
        $rows = ExchangeRate::query()
            ->orderByDesc('rate_date')
            ->orderBy('base_currency')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json(['rows' => $rows]);
        }

        return Inertia::render('Admin/Settings/ExchangeRates/Index', ['rows' => $rows]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Admin/Settings/ExchangeRates/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'currency_code' => ['required', 'string', 'max:3'],
            'rate_decimal' => ['required', 'numeric', 'min:0'],
        ]);

        // Check duplicate
        $existing = ExchangeRate::where('rate_date', $data['date'])
            ->where('base_currency', strtoupper($data['currency_code']))
            ->where('quote_currency', 'THB')
            ->first();

        if ($existing) {
            return back()->withErrors(['currency_code' => 'อัตราสำหรับวันและสกุลเงินนี้มีอยู่แล้ว']);
        }

        ExchangeRate::create([
            'rate_date' => $data['date'],
            'base_currency' => strtoupper($data['currency_code']),
            'quote_currency' => 'THB',
            'rate_decimal' => $data['rate_decimal'],
        ]);

        return redirect()->route('admin.settings.exchange-rates.index')
            ->with('success', 'เพิ่มอัตราแลกเปลี่ยนแล้ว');
    }

    public function edit(Request $request, int $id)
    {
        $item = ExchangeRate::findOrFail($id);

        // map to UI expected keys
        return Inertia::render('Admin/Settings/ExchangeRates/Edit', [
            'item' => [
                'id' => $item->id,
                'date' => $item->rate_date,
                'currency_code' => $item->base_currency,
                'rate_decimal' => $item->rate_decimal,
            ]
        ]);
    }

    public function update(Request $request, int $id)
    {
        $item = ExchangeRate::findOrFail($id);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'currency_code' => ['required', 'string', 'max:3'],
            'rate_decimal' => ['required', 'numeric', 'min:0'],
        ]);

        // Check duplicate excluding current
        $existing = ExchangeRate::where('id', '!=', $id)
            ->where('rate_date', $data['date'])
            ->where('base_currency', strtoupper($data['currency_code']))
            ->where('quote_currency', 'THB')
            ->first();

        if ($existing) {
            return back()->withErrors(['currency_code' => 'อัตราสำหรับวันและสกุลเงินนี้มีอยู่แล้ว']);
        }

        $item->update([
            'rate_date' => $data['date'],
            'base_currency' => strtoupper($data['currency_code']),
            'quote_currency' => 'THB',
            'rate_decimal' => $data['rate_decimal'],
        ]);

        return redirect()->route('admin.settings.exchange-rates.index')
            ->with('success', 'อัปเดตอัตราแลกเปลี่ยนแล้ว');
    }

    public function destroy(Request $request, int $id)
    {
        $item = ExchangeRate::findOrFail($id);
        $item->delete();

        return back()->with('success', 'ลบอัตราแลกเปลี่ยนแล้ว');
    }
}
