<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurrencyController extends Controller
{
    public function list(Request $request)
    {
        $base = DB::table('currencies')->where('is_base', true)->value('code') ?? 'THB';
        $list = DB::table('currencies')->orderBy('code')->get(['code','name','minor_unit','is_base']);
        return response()->json(['base' => $base, 'list' => $list]);
    }

    public function latestRate(Request $request)
    {
        $code = strtoupper((string)$request->query('code', 'THB'));
        $base = DB::table('currencies')->where('is_base', true)->value('code') ?? 'THB';
        if ($code === $base) {
            return response()->json(['base' => $base, 'code' => $code, 'rate' => 1.0]);
        }
        $row = DB::table('exchange_rates')
            ->where('base_currency', $base)
            ->where('quote_currency', $code)
            ->orderByDesc('rate_date')
            ->orderByDesc('id')
            ->first();
        if (!$row) {
            return response()->json(['base' => $base, 'code' => $code, 'rate' => null], 404);
        }
        return response()->json([
            'base' => $base,
            'code' => $code,
            'rate' => (float)$row->rate_decimal,
            'rate_date' => (string)$row->rate_date,
        ]);
    }
}
