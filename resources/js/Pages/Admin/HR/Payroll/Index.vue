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
            <td class="p-2 border">{{ statusLabel(r.status) }}</td>
            <td class="p-2 border">
              <div class="flex gap-2">
                <button @click="openView(r.id)" class="px-2 py-0.5 text-xs bg-gray-100 border rounded">ดู</button>
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
  <!-- View Modal -->
  <Modal :show="showModal" @close="closeModal">
    <div class="p-4 text-sm min-w-[320px]">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">รายละเอียดรอบเงินเดือน</h3>
        <button class="text-gray-500" @click="closeModal">✕</button>
      </div>
      <div v-if="run" class="flex flex-wrap gap-2 mb-3">
        <a :href="`/admin/hr/payroll/${run.id}/summary.pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์สรุป</a>
        <a :href="`/admin/hr/payroll/${run.id}/payslips.pdf`" target="_blank" rel="noopener" class="px-2 py-0.5 border rounded">พิมพ์สลิปทั้งหมด</a>
      </div>
      <div v-if="loading" class="py-6 text-center text-gray-500">กำลังโหลด...</div>
      <div v-else-if="run">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
          <div><div class="text-gray-500">งวด</div><div class="font-medium">{{ run.period_year }}-{{ String(run.period_month).padStart(2,'0') }}</div></div>
          <div><div class="text-gray-500">สถานะ</div><div class="font-medium">{{ statusLabel(run.status) }}</div></div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full border text-xs">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 border text-left">พนักงาน</th>
                <th class="p-2 border text-right">เงินเดือน</th>
                <th class="p-2 border text-right">หัก</th>
                <th class="p-2 border text-right">สุทธิ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="it in items" :key="it.id">
                <td class="p-2 border">{{ it.employee?.name || '-' }}</td>
                <td class="p-2 border text-right">{{ fmt(Number(it.earning_basic_decimal ?? 0) + Number(it.earning_other_decimal ?? 0)) }}</td>
                <td class="p-2 border text-right">{{ fmt(Number(it.sso_employee_decimal ?? 0) + Number(it.wht_decimal ?? 0)) }}</td>
                <td class="p-2 border text-right">{{ fmt(Number(it.net_pay_decimal ?? 0)) }}</td>
              </tr>
              <tr v-if="!items || items.length===0"><td colspan="4" class="p-3 text-center text-gray-500">-</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { statusLabel } from '@/utils/statusLabels'
import { alertError } from '@/utils/swal'
import { confirmDialog } from '@/utils/swal'
import Modal from '@/Components/Modal.vue'
const rows = computed(()=> usePage().props.rows || {data:[]})
const today = usePage().props.today
const d = new Date(today)
const year = ref(d.getFullYear())
const month = ref(d.getMonth()+1)
function createRun(){
  router.post('/admin/hr/payroll', { year: year.value, month: month.value })
}
async function lock(id){ if(await confirmDialog('ล็อกรอบนี้?')) router.post(`/admin/hr/payroll/${id}/lock`) }
async function pay(id){ if(await confirmDialog('จ่ายเงินเดือนรอบนี้?')) router.post(`/admin/hr/payroll/${id}/pay`, { date: today }) }
async function unlock(id){ if(await confirmDialog('ปลดล็อครอบนี้?')) router.post(`/admin/hr/payroll/${id}/unlock`) }
async function del(id){ if(await confirmDialog('ลบรอบนี้? จะลบรายการพนักงานในรอบด้วย')) router.delete(`/admin/hr/payroll/${id}`) }

function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }

// modal state
const showModal = ref(false)
const loading = ref(false)
const run = ref(null)
const items = ref([])
function closeModal(){ showModal.value = false; run.value = null; items.value = [] }
async function openView(id){
  showModal.value = true
  loading.value = true
  try{
    const res = await fetch(`/admin/hr/payroll/${id}`, { headers: { 'Accept':'application/json' } })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()
    run.value = data.run
    items.value = data.items || []
  } finally {
    loading.value = false
  }
}
</script>
