<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountsController extends Controller
{
    public function index(Request $request)
    {
        // API: return JSON for XHR or API calls; otherwise render Inertia page
        if ($request->wantsJson()) {
            $q = Account::query();
            if ($s = $request->query('q')) {
                $q->where(fn($w)=>$w->where('code','like',"%$s%")->orWhere('name','like',"%$s%"));
            }
            return response()->json($q->orderBy('code')->paginate(20));
        }
        return Inertia::render('Admin/Accounting/Accounts/Index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => ['nullable','integer'],
            'code' => ['required','string','max:50'],
            'name' => ['required','string','max:150'],
            'type' => ['required','in:asset,liability,equity,revenue,expense'],
            'normal_balance' => ['required','in:debit,credit'],
        ]);
        // Unique per business
        abort_if(Account::where('business_id',$data['business_id']??null)->where('code',$data['code'])->exists(), 422, 'Code already exists.');
        $acc = Account::create($data);
        return response()->json($acc, 201);
    }

    public function edit(int $id)
    {
        if (request()->wantsJson()) {
            return response()->json(Account::findOrFail($id));
        }
        return Inertia::render('Admin/Accounting/Accounts/Edit', ['id'=>$id]);
    }

    public function update(Request $request, int $id)
    {
        $acc = Account::findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes','string','max:150'],
            'type' => ['sometimes','in:asset,liability,equity,revenue,expense'],
            'normal_balance' => ['sometimes','in:debit,credit'],
        ]);
        $acc->update($data);
        return response()->json($acc);
    }

    public function destroy(int $id)
    {
        $acc = Account::findOrFail($id);
        $acc->delete();
        return response()->noContent();
    }
}
