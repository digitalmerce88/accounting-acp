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
              <td class="p-2 border capitalize">{{ j.status }}</td>
            </tr>
            <tr v-if="recent_journals.length===0"><td colspan="3" class="p-3 text-center text-gray-500">ยังไม่มีรายการ</td></tr>
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

const page = usePage()
const metrics = computed(() => page.props.metrics || { accounts_count: 0, journals_count: 0, tb_total_dr: 0, tb_total_cr: 0 })
const recent_journals = computed(() => page.props.recent_journals || [])

function money(n){
  return Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
