<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">สร้างบิล/ใบค่าใช้จ่าย</h1>
    <form @submit.prevent="submit" class="space-y-4 text-sm">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-gray-600 mb-1">วันที่บิล</label>
          <input v-model="form.bill_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">ครบกำหนด</label>
          <input v-model="form.due_date" type="date" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">เลขที่</label>
          <input v-model="form.number" type="text" class="w-full border rounded p-2" placeholder="เว้นว่างเพื่อรันภายหลัง" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">WHT %</label>
          <input v-model.number="form.wht_rate_decimal" type="number" step="0.01" min="0" class="w-full border rounded p-2 text-right" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between">
          <div class="font-medium">รายการ</div>
          <button type="button" @click="addItem" class="px-2 py-1 text-xs bg-gray-100 border rounded">+ เพิ่มรายการ</button>
        </div>
        <div class="overflow-x-auto mt-2">
          <table class="min-w-full border">
            <thead class="bg-gray-50 text-xs">
              <tr>
                <th class="border p-2 text-left">ชื่อรายการ</th>
                <th class="border p-2 w-24">จำนวน</th>
                <th class="border p-2 w-28">ราคาต่อหน่วย</th>
                <th class="border p-2 w-24">VAT %</th>
                <th class="border p-2 w-28 text-right">เป็นเงิน</th>
                <th class="border p-2 w-16"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(it,idx) in form.items" :key="idx" class="text-sm">
                <td class="border p-1"><input v-model="it.name" class="w-full p-1 border rounded" placeholder="ระบุชื่อ" /></td>
                <td class="border p-1"><input v-model.number="it.qty_decimal" type="number" step="0.01" min="0" class="w-full p-1 border rounded text-right" /></td>
                <td class="border p-1"><input v-model.number="it.unit_price_decimal" type="number" step="0.01" min="0" class="w-full p-1 border rounded text-right" /></td>
                <td class="border p-1"><input v-model.number="it.vat_rate_decimal" type="number" step="0.01" min="0" class="w-full p-1 border rounded text-right" /></td>
                <td class="border p-1 text-right">{{ fmt(it.qty_decimal*it.unit_price_decimal) }}</td>
                <td class="border p-1 text-center"><button type="button" @click="removeItem(idx)" class="text-red-600">ลบ</button></td>
              </tr>
              <tr v-if="form.items.length===0"><td colspan="6" class="p-3 text-center text-gray-500">ไม่มีรายการ</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex flex-col items-end gap-1">
        <div>Subtotal: <span class="font-medium">{{ fmt(subtotal) }}</span></div>
        <div>VAT: <span class="font-medium">{{ fmt(vat) }}</span></div>
        <div>Total: <span class="font-semibold">{{ fmt(total) }}</span></div>
        <div v-if="form.wht_rate_decimal>0">WHT (ประมาณ): <span class="font-medium">{{ fmt(whtEstimate) }}</span></div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="px-3 py-1 bg-green-700 text-white rounded" :disabled="processing">บันทึก</button>
        <a href="/admin/documents/bills" class="px-3 py-1 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { reactive, computed, ref } from 'vue'

const form = reactive({
  bill_date: new Date().toISOString().slice(0,10),
  due_date: '',
  number: '',
  wht_rate_decimal: 0,
  items: [ { name: '', qty_decimal: 1, unit_price_decimal: 0, vat_rate_decimal: 0 } ],
})
const processing = ref(false)

const subtotal = computed(()=> form.items.reduce((s,it)=> s + (Number(it.qty_decimal||0)*Number(it.unit_price_decimal||0)), 0))
const vat = computed(()=> form.items.reduce((s,it)=> s + (Number(it.qty_decimal||0)*Number(it.unit_price_decimal||0)) * (Number(it.vat_rate_decimal||0)/100), 0))
const total = computed(()=> subtotal.value + vat.value)
const whtEstimate = computed(()=> subtotal.value * (Number(form.wht_rate_decimal||0)/100))

function addItem(){ form.items.push({ name: '', qty_decimal: 1, unit_price_decimal: 0, vat_rate_decimal: 0 }) }
function removeItem(i){ form.items.splice(i,1) }
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }

function submit(){
  processing.value = true
  router.post('/admin/documents/bills', form, { onFinish(){ processing.value = false } })
}
</script>
