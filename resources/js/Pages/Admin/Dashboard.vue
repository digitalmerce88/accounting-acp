<template>
  <AdminLayout>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="border rounded p-4 bg-white">
        <div class="text-xs text-gray-500">ผังบัญชี</div>
        <div class="text-2xl font-semibold">{{ metrics.accounts_count }}</div>
      </div>
      <div class="border rounded p-4 bg-white">
        <div class="text-xs text-gray-500">บันทึกบัญชี</div>
        <div class="text-2xl font-semibold">{{ metrics.journals_count }}</div>
      </div>
      <div class="border rounded p-4 bg-white">
        <div class="text-xs text-gray-500">งบทดลอง (เดบิต)</div>
        <div class="text-2xl font-semibold">{{ money(metrics.tb_total_dr) }}</div>
      </div>
      <div class="border rounded p-4 bg-white">
        <div class="text-xs text-gray-500">งบทดลอง (เครดิต)</div>
        <div class="text-2xl font-semibold">{{ money(metrics.tb_total_cr) }}</div>
      </div>
    </div>

    <div class="mt-6 bg-white border rounded p-4">
      <div class="flex items-center justify-between">
  <h2 class="font-semibold">บันทึกบัญชีล่าสุด</h2>
  <Link href="/admin/accounting/journals" class="text-blue-600 text-sm">ดูทั้งหมด</Link>
      </div>
      <div class="mt-3 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="text-left p-2 border">วันที่</th>
              <th class="text-left p-2 border">คำอธิบาย</th>
              <th class="text-left p-2 border">สถานะ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="j in recent_journals" :key="j.id" class="border-b">
              <td class="p-2 border">{{ j.date }}</td>
              <td class="p-2 border">{{ j.memo }}</td>
              <td class="p-2 border capitalize">{{ statusLabel(j.status) }}</td>
            </tr>
            <tr v-if="recent_journals.length===0"><td colspan="3" class="p-3 text-center text-gray-500">ยังไม่มีรายการ</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white border rounded p-4">
        <h2 class="font-semibold mb-3">Aging ลูกหนี้ (AR)</h2>
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="(v,k) in aging.ar" :key="k" class="border-b last:border-b-0">
              <td class="py-1">{{ label(k) }}</td>
              <td class="py-1 text-right font-medium">{{ money(v) }}</td>
              <td class="py-1 text-right text-xs text-gray-500">Base {{ money(aging.ar_base[k]) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="bg-white border rounded p-4">
        <h2 class="font-semibold mb-3">Aging เจ้าหนี้ (AP)</h2>
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="(v,k) in aging.ap" :key="k" class="border-b last:border-b-0">
              <td class="py-1">{{ label(k) }}</td>
              <td class="py-1 text-right font-medium">{{ money(v) }}</td>
              <td class="py-1 text-right text-xs text-gray-500">Base {{ money(aging.ap_base[k]) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6 bg-white border rounded p-4">
      <h2 class="font-semibold mb-3">Cashflow 6 เดือนย้อนหลัง</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-2 border text-left">เดือน</th>
              <th class="p-2 border text-right">รายรับ</th>
              <th class="p-2 border text-right">รายจ่าย</th>
              <th class="p-2 border text-right">สุทธิ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in cashflow" :key="r.month">
              <td class="p-2 border">{{ r.month }}</td>
              <td class="p-2 border text-right text-green-700">{{ money(r.income) }}</td>
              <td class="p-2 border text-right text-red-700">{{ money(r.expense) }}</td>
              <td class="p-2 border text-right" :class="r.net>=0 ? 'text-green-700' : 'text-red-700'">{{ money(r.net) }}</td>
            </tr>
            <tr v-if="cashflow.length===0"><td colspan="4" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6 bg-white border rounded p-4">
      <h2 class="font-semibold mb-3">ค่าใช้จ่ายแยกหมวด (30 วันย้อนหลัง)</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-2 border text-left">บัญชี</th>
              <th class="p-2 border text-left">ชื่อ</th>
              <th class="p-2 border text-right">ยอดรวม</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in expenses_by_category" :key="e.code">
              <td class="p-2 border">{{ e.code }}</td>
              <td class="p-2 border">{{ e.name }}</td>
              <td class="p-2 border text-right text-red-700">{{ money(e.total) }}</td>
            </tr>
            <tr v-if="expenses_by_category.length===0"><td colspan="3" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-6 bg-white border rounded p-4">
      <h2 class="font-semibold mb-3">แนวโน้มรายได้และกำไร (12 เดือนย้อนหลัง)</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-2 border text-left">เดือน</th>
              <th class="p-2 border text-right">รายได้</th>
              <th class="p-2 border text-right">ค่าใช้จ่าย</th>
              <th class="p-2 border text-right">กำไรสุทธิ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="t in trends" :key="t.month">
              <td class="p-2 border">{{ t.month }}</td>
              <td class="p-2 border text-right text-green-700">{{ money(t.revenue) }}</td>
              <td class="p-2 border text-right text-red-700">{{ money(t.expense) }}</td>
              <td class="p-2 border text-right font-medium" :class="t.profit>=0 ? 'text-green-700' : 'text-red-700'">{{ money(t.profit) }}</td>
            </tr>
            <tr v-if="trends.length===0"><td colspan="4" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { statusLabel } from '@/utils/statusLabels'

const page = usePage()
const metrics = computed(() => page.props.metrics || { accounts_count: 0, journals_count: 0, tb_total_dr: 0, tb_total_cr: 0 })
const recent_journals = computed(() => page.props.recent_journals || [])
const aging = computed(() => page.props.aging || { ar:{}, ar_base:{}, ap:{}, ap_base:{} })
const cashflow = computed(() => page.props.cashflow || [])
const expenses_by_category = computed(() => page.props.expenses_by_category || [])
const trends = computed(() => page.props.trends || [])

function money(n){
  return Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function label(k){
  return {
    current: 'ยังไม่ถึงกำหนด',
    '1_30': 'ค้าง 1-30 วัน',
    '31_60': 'ค้าง 31-60 วัน',
    '61_90': 'ค้าง 61-90 วัน',
    '90_plus': 'เกิน 90 วัน'
  }[k] || k
}
</script>
