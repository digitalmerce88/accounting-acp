# รายละเอียดฟังก์ชันการแสดงผลรายงาน (Report Functions)

เอกสารนี้สรุปการทำงานของระบบรายงานทางบัญชีทั้งหมดในโปรเจกต์ โดยแบ่งตามประเภทของรายงานและ Logic เบื้องหลัง

## 1. ภาพรวม (Overview Dashboard)
แสดงสรุปรายรับ รายจ่าย และกำไรสุทธิ

*   **Controller:** `App\Http\Controllers\Admin\Accounting\ReportsController::overview`
*   **Service:** `App\Domain\Accounting\Services\SummaryReportService::overview`
*   **Logic การคำนวณ:**
    *   **Income (รายรับ):** ผลรวมฝั่ง **Credit** ของบัญชีประเภท `revenue` จากตาราง `journal_lines`
    *   **Expense (รายจ่าย):** ผลรวมฝั่ง **Debit** ของบัญชีประเภท `expense` จากตาราง `journal_lines`
    *   **Net (กำไรสุทธิ):** `Income - Expense`
    *   **Base Currency Totals (ยอดเงินสกุลหลัก):**
        *   คำนวณจากเอกสาร `Invoice` (ฝั่งรายรับ) และ `Bill` (ฝั่งรายจ่าย) โดยตรง
        *   ใช้ฟังก์ชัน `sumDocumentBase` ที่มีความยืดหยุ่น (Robust Fallback):
            1.  พยายามหาผลรวมจากคอลัมน์ `base_total_decimal` (ถ้ามี)
            2.  ถ้าไม่มี ให้คำนวณจาก `total * fx_rate_decimal` (ถ้ามี)
            3.  ถ้าไม่มีทั้งคู่ ให้ใช้ `total` ปกติ
*   **การแสดงผล:** `resources/js/Pages/Admin/Accounting/Reports/Overview.vue`

## 2. งบกำไรขาดทุน (Profit & Loss)
แสดงรายละเอียดรายได้และค่าใช้จ่ายแยกตามประเภทบัญชี

*   **Controller:** `App\Http\Controllers\Admin\Accounting\ReportsController::profitAndLoss`
*   **Service:** `App\Domain\Accounting\Services\ProfitAndLossService::run`
*   **Logic การคำนวณ:**
    *   คล้ายกับ Overview แต่แยกโครงสร้างข้อมูลเพื่อการแสดงผลที่ละเอียดกว่า
    *   ดึงข้อมูลจาก `JournalLine` ที่เชื่อมกับ `JournalEntry` (สถานะ `posted`)
    *   มีการคำนวณยอด Base Currency แยกต่างหากเพื่อรองรับ Multi-currency
*   **การแสดงผล:** `resources/js/Pages/Admin/Accounting/Reports/ProfitAndLoss.vue`

## 3. งบทดลอง (Trial Balance)
แสดงยอดคงเหลือ Debit/Credit ของทุกบัญชี ณ ช่วงเวลาที่กำหนด

*   **Controller:** `App\Http\Controllers\Admin\Accounting\ReportsController::trialBalance`
*   **Service:** `App\Domain\Accounting\Services\TrialBalanceService` -> `App\Domain\Accounting\Reports\TrialBalance`
*   **Logic การคำนวณ:**
    *   Query ตาราง `journal_lines` join กับ `accounts`
    *   Group by `account_id`
    *   Sum `debit` และ `credit` ของแต่ละบัญชี
    *   กรองเฉพาะรายการที่ `status = posted`
*   **การแสดงผล:** `resources/js/Pages/Admin/Accounting/Reports/TrialBalance.vue`
*   **Export:** รองรับ CSV (`trialBalanceCsv`) และ PDF (`trialBalancePdf`)

## 4. สมุดบัญชีแยกประเภท (General Ledger)
แสดงความเคลื่อนไหว (Transaction) ของบัญชีใดบัญชีหนึ่ง

*   **Controller:** `App\Http\Controllers\Admin\Accounting\ReportsController::ledger`
*   **Service:** `App\Domain\Accounting\Services\LedgerService` -> `App\Domain\Accounting\Reports\Ledger`
*   **Logic การคำนวณ:**
    *   รับค่า `account_id`
    *   ดึงรายการ `journal_lines` ทั้งหมดของบัญชีนั้น เรียงตามวันที่
    *   วนลูปคำนวณยอดคงเหลือสะสม (Running Balance) ทีละบรรทัด (`Balance += Debit - Credit`)
*   **การแสดงผล:** `resources/js/Pages/Admin/Accounting/Reports/Ledger.vue`
*   **Export:** รองรับ CSV และ PDF

## 5. รายงานแยกตามหมวดหมู่ (By Category)
แสดงยอดรวมรายได้และค่าใช้จ่าย แยกตามชื่อบัญชี

*   **Controller:** `App\Http\Controllers\Admin\Accounting\ReportsController::byCategory`
*   **Service:** `App\Domain\Accounting\Services\SummaryReportService::byCategory`
*   **Logic การคำนวณ:**
    *   ดึงข้อมูลจาก `journal_lines` เฉพาะบัญชีประเภท `revenue` และ `expense`
    *   Group by `account_id`
    *   Sum ยอดตามประเภทบัญชี
*   **การแสดงผล:** `resources/js/Pages/Admin/Accounting/Reports/Category.vue`

## 6. รายงานภาษี (Tax Reports)
### ภาษีซื้อ (Purchase VAT)
*   **Controller:** `ReportsController::taxPurchaseVat`
*   **Service:** `SummaryReportService::taxPurchaseVat`
*   **Logic:** ดึงผลรวมฝั่ง **Debit** ของบัญชีรหัส `511` (ภาษีซื้อ)

### ภาษีขาย (Sales VAT)
*   **Controller:** `ReportsController::taxSalesVat`
*   **Service:** `SummaryReportService::taxSalesVat`
*   **Logic:** ดึงผลรวมฝั่ง **Credit** ของบัญชีรหัส `411` (ภาษีขาย)

## 7. สรุปหัก ณ ที่จ่าย (WHT Summary)
แสดงยอดภาษีหัก ณ ที่จ่ายที่ได้รับและที่ต้องนำส่ง

*   **Controller:** `ReportsController::whtSummary`
*   **Service:** `SummaryReportService::whtSummary`
*   **Logic:**
    *   **WHT Received (ถูกหัก):** ผลรวม **Debit** ของบัญชีรหัส `153`
    *   **WHT Payable (หักเขา):** ผลรวม **Credit** ของบัญชีรหัส `231`

## หมายเหตุเพิ่มเติม (Technical Notes)
*   **Robust Base Currency Calculation:** ใน `ProfitAndLossService` และ `SummaryReportService` มีการใช้ฟังก์ชัน `sumDocumentBase` ที่ถูกปรับปรุงให้รองรับกรณีที่ Database Schema อาจยังไม่มีคอลัมน์ `base_total_decimal` โดยจะ Fallback ไปคำนวณจาก `total * fx_rate` หรือใช้ `total` ธรรมดาแทน เพื่อป้องกัน Error
*   **Inertia & Vue:** รายงานทั้งหมดใช้ Inertia.js ในการส่งข้อมูลจาก Controller ไปยัง Vue Component เพื่อแสดงผล
*   **Export:** เกือบทุกรายงานรองรับการ Export เป็น CSV ผ่าน StreamedResponse เพื่อประสิทธิภาพในการโหลดข้อมูลขนาดใหญ่
