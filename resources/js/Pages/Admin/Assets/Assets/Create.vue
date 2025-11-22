<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">เพิ่มทรัพย์สิน</h1>
    <form @submit.prevent="submit" class="space-y-4 text-sm max-w-2xl">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-gray-600 mb-1">รหัส</label>
          <input v-model="form.asset_code" type="text" class="w-full border rounded p-2" required />
        </div>
        <div class="md:col-span-2">
          <label class="block text-gray-600 mb-1">ชื่อทรัพย์สิน</label>
          <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">หมวดหมู่</label>
          <select v-model="form.category_id" class="w-full border rounded p-2">
            <option :value="null">-</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-600 mb-1">วันที่ซื้อ</label>
          <input v-model="form.purchase_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">มูลค่าซื้อ</label>
          <input v-model.number="form.purchase_cost_decimal" type="number" min="0" step="0.01" class="w-full border rounded p-2 text-right" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">มูลค่าเศษ (Salvage)</label>
          <input v-model.number="form.salvage_value_decimal" type="number" min="0" step="0.01" class="w-full border rounded p-2 text-right" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">อายุการใช้งาน (เดือน)</label>
          <input v-model.number="form.useful_life_months" type="number" min="1" class="w-full border rounded p-2 text-right" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">เริ่มคิดค่าเสื่อม</label>
          <input v-model="form.start_depreciation_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">ค่าเสื่อม / เดือน (SLM)</label>
          <div class="p-2 border rounded bg-gray-50 text-right">{{ fmt(monthlyAmount) }}</div>
        </div>
      </div>
      <div class="flex gap-2">
        <button type="submit" class="px-3 py-1 bg-blue-700 text-white rounded" :disabled="processing">บันทึก</button>
        <a href="/admin/assets/assets" class="px-3 py-1 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { reactive, ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
const page = usePage()
const categories = page.props.categories || []
const today = new Date().toISOString().slice(0,10)
const form = reactive({
  asset_code:'', name:'', category_id:null, purchase_date:today, purchase_cost_decimal:0, salvage_value_decimal:0, useful_life_months:12, start_depreciation_date:today
})
const processing = ref(false)
const monthlyAmount = computed(()=>{
  const cost = Number(form.purchase_cost_decimal||0)
  const salvage = Number(form.salvage_value_decimal||0)
  const life = Number(form.useful_life_months||0)
  if(life<=0) return 0
  const base = Math.max(cost - salvage,0)
  return Math.round((base / life)*100)/100
})
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function submit(){
  processing.value = true
  router.post('/admin/assets/assets', form, { onFinish(){ processing.value=false } })
}
</script>
