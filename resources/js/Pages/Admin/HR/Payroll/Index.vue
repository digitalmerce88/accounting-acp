<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">รอบเงินเดือน</h1>
      <form @submit.prevent="createRun" class="flex gap-2 items-center">
        <input type="number" v-model="year" class="border rounded px-2 py-1 w-24" />
        <input type="number" v-model="month" class="border rounded px-2 py-1 w-20" min="1" max="12" />
        <button class="px-3 py-1 bg-green-700 text-white rounded">+ สร้างรอบ</button>
      </form>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border">งวด</th>
            <th class="p-2 border">สถานะ</th>
            <th class="p-2 border">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="p-2 border">{{ r.period_year }}-{{ String(r.period_month).padStart(2,'0') }}</td>
            <td class="p-2 border">{{ r.status }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <a :href="`/admin/hr/payroll/${r.id}`" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</a>
                <button v-if="r.status==='draft'" @click="lock(r.id)" class="px-2 py-0.5 text-xs bg-yellow-600 text-white rounded">ล็อก</button>
                <button v-if="r.status==='draft'" @click="del(r.id)" class="px-2 py-0.5 text-xs bg-red-700 text-white rounded">ลบ</button>
                <button v-if="r.status==='locked'" @click="pay(r.id)" class="px-2 py-0.5 text-xs bg-blue-700 text-white rounded">จ่าย</button>
                <button v-if="r.status==='locked'" @click="unlock(r.id)" class="px-2 py-0.5 text-xs bg-orange-600 text-white rounded">ปลดล็อค</button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.data || rows.data.length===0">
            <td colspan="3" class="p-3 text-center text-gray-500">ยังไม่มีรอบเงินเดือน</td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
const today = usePage().props.today
const d = new Date(today)
const year = ref(d.getFullYear())
const month = ref(d.getMonth()+1)
function createRun(){
  router.post('/admin/hr/payroll', { year: year.value, month: month.value })
}
function lock(id){ if(confirm('ล็อกรอบนี้?')) router.post(`/admin/hr/payroll/${id}/lock`) }
function pay(id){ if(confirm('จ่ายเงินเดือนรอบนี้?')) router.post(`/admin/hr/payroll/${id}/pay`, { date: today }) }
function unlock(id){ if(confirm('ปลดล็อครอบนี้?')) router.post(`/admin/hr/payroll/${id}/unlock`) }
function del(id){ if(confirm('ลบรอบนี้? จะลบรายการพนักงานในรอบด้วย')) router.delete(`/admin/hr/payroll/${id}`) }
</script>
