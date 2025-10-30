<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\{Transaction, Category, Vendor, Customer};
use App\Domain\Accounting\PostingService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransactionsController extends Controller
{
    public function index(Request $request, string $kind)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $rows = Transaction::where('business_id', $bizId)->where('kind', $kind)
            ->latest('date')->paginate(10);
        return Inertia::render('Admin/Accounting/'.ucfirst($kind).'/Index', [
            'rows' => $rows,
        ]);
    }

    public function create(Request $request, string $kind)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);
        $categories = Category::where('business_id', $bizId)->where('type', $kind === 'income' ? 'income' : 'expense')
            ->orderBy('name')->get(['id','name','type','vat_applicable']);
        $vendors = Vendor::where('business_id', $bizId)->orderBy('name')->get(['id','name','wht_type']);
        $customers = Customer::where('business_id', $bizId)->orderBy('name')->get(['id','name']);
        return Inertia::render('Admin/Accounting/'.ucfirst($kind).'/Create', [
            'categories' => $categories,
            'vendors' => $vendors,
            'customers' => $customers,
            'kind' => $kind,
            'today' => now()->toDateString(),
        ]);
    }

    public function store(Request $request, string $kind)
    {
        $bizId = (int) ($request->user()->business_id ?? 1);

        $data = $request->validate([
            'date' => ['required','date'],
            'memo' => ['nullable','string','max:255'],
            'amount' => ['required','numeric','min:0.01'],
            'price_input_mode' => ['required','in:gross,net,novat'],
            'vat_applicable' => ['required','boolean'],
            'wht_rate' => ['nullable','numeric','min:0','max:1'],
            'payment_method' => ['required','in:cash,bank'],
            'category_id' => ['required','integer','exists:categories,id'],
            'vendor_id' => ['nullable','integer','exists:vendors,id'],
            'customer_id' => ['nullable','integer','exists:customers,id'],
            'files.*' => ['sometimes','file','max:4096'],
        ]);

        // Log incoming request data (sanitized)
        Log::info('TransactionsController.store request', [
            'user_id' => $request->user()?->id,
            'business_id' => $bizId,
            'kind' => $kind,
            'payload' => [
                'date' => $data['date'] ?? null,
                'memo' => $data['memo'] ?? null,
                'amount' => $data['amount'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'vendor_id' => $data['vendor_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'price_input_mode' => $data['price_input_mode'] ?? null,
                'vat_applicable' => $data['vat_applicable'] ?? null,
                'wht_rate' => $data['wht_rate'] ?? null,
            ],
        ]);

        $svc = new PostingService();
        $payload = array_merge($data, [
            'business_id' => $bizId,
        ]);
        if ($kind === 'income') {
            $entry = $svc->postIncome($payload);
            Log::info('PostingService.postIncome result', ['entry_id' => $entry->id ?? null]);
        } else {
            $entry = $svc->postExpense($payload);
            Log::info('PostingService.postExpense result', ['entry_id' => $entry->id ?? null]);
        }

        $tx = Transaction::create([
            'business_id' => $bizId,
            'kind' => $kind,
            'date' => $data['date'],
            'memo' => $data['memo'] ?? null,
            'amount' => $data['amount'],
            'vat' => 0, // derived in posting; optional to compute here later
            'wht' => 0,
            'category_id' => $data['category_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'vendor_id' => $data['vendor_id'] ?? null,
            'payment_method' => $data['payment_method'],
            'bank_account_id' => null,
            'price_input_mode' => $data['price_input_mode'],
            'vat_applicable' => (bool)$data['vat_applicable'],
            'wht_rate' => $data['wht_rate'] ?? 0,
            'status' => 'posted',
            'journal_entry_id' => $entry->id,
        ]);

        Log::info('Transaction created', ['transaction_id' => $tx->id, 'journal_entry_id' => $tx->journal_entry_id, 'kind' => $tx->kind]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('attachments', 'public');
                $tx->attachments()->create([
                    'business_id' => $bizId,
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
                Log::info('Transaction attachment saved', ['transaction_id' => $tx->id, 'path' => $path]);
            }
        }

        return redirect()->to('/admin/accounting/'.($kind === 'income' ? 'income' : 'expense'))
            ->with('success', $kind === 'income' ? 'บันทึกรายรับเรียบร้อย' : 'บันทึกรายจ่ายเรียบร้อย');
    }
}
