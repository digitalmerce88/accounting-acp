<template>
  <AdminLayout>
    <div class="flex items-end justify-between gap-4">
  <h1 class="text-xl font-semibold">งบทดลอง</h1>
      <div class="flex items-center gap-2 text-sm">
        <div>
          <label class="block text-xs text-gray-600">ตั้งแต่วันที่</label>
          <input v-model="from" type="date" class="border rounded px-2 py-1" />
        </div>
        <div>
          <label class="block text-xs text-gray-600">ถึงวันที่</label>
          <input v-model="to" type="date" class="border rounded px-2 py-1" />
        </div>
  <button @click="load" class="px-3 py-1 bg-blue-600 text-white rounded">ใช้งานตัวกรอง</button>
  <a :href="csvHref" class="px-3 py-1 border rounded">ส่งออก CSV</a>
      </div>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr><th class="p-2 border text-left">เลขที่บัญชี</th><th class="p-2 border text-left">ชื่อบัญชี</th><th class="p-2 border text-right">เดบิต</th><th class="p-2 border text-right">เครดิต</th></tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r[0]">
            <td class="p-2 border font-mono">{{ r[0] }}</td>
            <td class="p-2 border">{{ r[1] }}</td>
            <td class="p-2 border text-right">{{ money(r[3]) }}</td>
            <td class="p-2 border text-right">{{ money(r[4]) }}</td>
          </tr>
          <tr v-if="rows.length===0"><td colspan="4" class="p-4 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'
import axios from 'axios'
const rows = ref([])
const from = ref('')
const to = ref('')
const csvHref = computed(() => `/admin/accounting/reports/trial-balance.csv?from=${from.value||''}&to=${to.value||''}`)
const load = async ()=>{
  const res = await axios.get('/admin/accounting/reports/trial-balance', { params: { from: from.value||undefined, to: to.value||undefined }, headers:{ Accept: 'application/json' } })
  rows.value = res.data.data || res.data
}
const money = (n)=> Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
</script>
