<?php
namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;

class CategoriesController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:income,expense',
            'vat_applicable' => 'nullable|boolean',
        ]);

        $bizId = $request->user()->business_id ?? 1;
        $cat = Category::create([
            'business_id' => $bizId,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'expense',
            'vat_applicable' => isset($data['vat_applicable']) ? (bool)$data['vat_applicable'] : false,
        ]);

        return response()->json(['ok' => true, 'item' => $cat], 201);
    }
}
