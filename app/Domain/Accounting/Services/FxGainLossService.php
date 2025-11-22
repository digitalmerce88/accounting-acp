<?php

namespace App\Domain\Accounting\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\{Invoice, Bill, Account, JournalEntry, JournalLine};

/**
 * FX Gain/Loss Service
 * คำนวณและโพสต์กำไร/ขาดทุนจากอัตราแลกเปลี่ยนเมื่อรับ/จ่ายเงินในสกุลต่างประเทศ
 */
class FxGainLossService
{
    /**
     * คำนวณและโพสต์ FX gain/loss สำหรับ Invoice เมื่อรับชำระ
     *
     * @param Invoice $invoice
     * @param float $settlementRate อัตราแลกเปลี่ยน ณ วันรับชำระ
     * @param string $settlementDate วันที่รับชำระ
     * @return JournalEntry|null
     */
    public function postInvoiceSettlement(Invoice $invoice, float $settlementRate, string $settlementDate): ?JournalEntry
    {
        // ถ้าไม่มี currency_code หรือเป็น THB = ไม่มี FX
        if (empty($invoice->currency_code) || strtoupper($invoice->currency_code) === 'THB') {
            return null;
        }

        $originalRate = (float) ($invoice->fx_rate_decimal ?? 1);
        $total = (float) $invoice->total;

        // คำนวณ base total ตามอัตราต้นทาง vs ณ วันชำระ
        $originalBase = $total / $originalRate; // base ตอนบันทึก
        $settlementBase = $total / $settlementRate; // base ตอนจ่ายจริง

        $difference = round($settlementBase - $originalBase, 2);

        // ถ้าไม่มีส่วนต่าง ไม่ต้องโพสต์
        if (abs($difference) < 0.01) {
            return null;
        }

        $bizId = $invoice->business_id;

        // หา account codes จาก config
        $gainCode = config('accounting.defaults.fx_gain_code', '561'); // รายได้อื่น - กำไรจากอัตราแลกเปลี่ยน
        $lossCode = config('accounting.defaults.fx_loss_code', '751'); // ค่าใช้จ่ายอื่น - ขาดทุนจากอัตราแลกเปลี่ยน
        $arCode = config('accounting.defaults.accounts_receivable_code', '121');

        $gainAccount = Account::where('business_id', $bizId)->where('code', $gainCode)->first();
        $lossAccount = Account::where('business_id', $bizId)->where('code', $lossCode)->first();
        $arAccount = Account::where('business_id', $bizId)->where('code', $arCode)->first();

        if (!$arAccount) {
            \Log::warning('FxGainLossService: AR account not found');
            return null;
        }

        $entry = new JournalEntry();
        $entry->business_id = $bizId;
        $entry->date = Carbon::parse($settlementDate)->toDateString();
        $entry->memo = "FX Gain/Loss: Invoice #{$invoice->number} settlement";
        $entry->status = 'posted';
        $entry->save();

        if ($difference > 0) {
            // Gain: AR ได้รับมากกว่า → Cr AR, Dr Gain
            if ($gainAccount) {
                JournalLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $gainAccount->id,
                    'debit' => abs($difference),
                    'credit' => 0,
                ]);
            }
            JournalLine::create([
                'entry_id' => $entry->id,
                'account_id' => $arAccount->id,
                'debit' => 0,
                'credit' => abs($difference),
            ]);
        } else {
            // Loss: AR ได้รับน้อยกว่า → Dr AR, Cr Loss
            JournalLine::create([
                'entry_id' => $entry->id,
                'account_id' => $arAccount->id,
                'debit' => abs($difference),
                'credit' => 0,
            ]);
            if ($lossAccount) {
                JournalLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $lossAccount->id,
                    'debit' => 0,
                    'credit' => abs($difference),
                ]);
            }
        }

        return $entry;
    }

    /**
     * คำนวณและโพสต์ FX gain/loss สำหรับ Bill เมื่อจ่ายชำระ
     *
     * @param Bill $bill
     * @param float $settlementRate อัตราแลกเปลี่ยน ณ วันจ่ายชำระ
     * @param string $settlementDate วันที่จ่ายชำระ
     * @return JournalEntry|null
     */
    public function postBillSettlement(Bill $bill, float $settlementRate, string $settlementDate): ?JournalEntry
    {
        if (empty($bill->currency_code) || strtoupper($bill->currency_code) === 'THB') {
            return null;
        }

        $originalRate = (float) ($bill->fx_rate_decimal ?? 1);
        $total = (float) $bill->total;

        $originalBase = $total / $originalRate;
        $settlementBase = $total / $settlementRate;

        $difference = round($settlementBase - $originalBase, 2);

        if (abs($difference) < 0.01) {
            return null;
        }

        $bizId = $bill->business_id;

        $gainCode = config('accounting.defaults.fx_gain_code', '561');
        $lossCode = config('accounting.defaults.fx_loss_code', '751');
        $apCode = config('accounting.defaults.accounts_payable_code', '211');

        $gainAccount = Account::where('business_id', $bizId)->where('code', $gainCode)->first();
        $lossAccount = Account::where('business_id', $bizId)->where('code', $lossCode)->first();
        $apAccount = Account::where('business_id', $bizId)->where('code', $apCode)->first();

        if (!$apAccount) {
            Log::warning('FxGainLossService: AP account not found');
            return null;
        }

        $entry = new JournalEntry();
        $entry->business_id = $bizId;
        $entry->date = Carbon::parse($settlementDate)->toDateString();
        $entry->memo = "FX Gain/Loss: Bill #{$bill->number} settlement";
        $entry->status = 'posted';
        $entry->save();

        if ($difference > 0) {
            // Loss: ต้องจ่ายมากกว่า → Dr Loss, Cr AP
            if ($lossAccount) {
                JournalLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $lossAccount->id,
                    'debit' => abs($difference),
                    'credit' => 0,
                ]);
            }
            JournalLine::create([
                'entry_id' => $entry->id,
                'account_id' => $apAccount->id,
                'debit' => 0,
                'credit' => abs($difference),
            ]);
        } else {
            // Gain: ต้องจ่ายน้อยกว่า → Dr AP, Cr Gain
            JournalLine::create([
                'entry_id' => $entry->id,
                'account_id' => $apAccount->id,
                'debit' => abs($difference),
                'credit' => 0,
            ]);
            if ($gainAccount) {
                JournalLine::create([
                    'entry_id' => $entry->id,
                    'account_id' => $gainAccount->id,
                    'debit' => 0,
                    'credit' => abs($difference),
                ]);
            }
        }

        return $entry;
    }
}
