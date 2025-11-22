<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">สร้างใบเสนอราคา</h1>
    <form @submit.prevent="submit" class="space-y-4 text-sm">
      <div class="p-3 border rounded">
        <div class="font-medium mb-2">ข้อมูลลูกค้า</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div class="md:col-span-2">
            <label class="block text-gray-600 mb-1">ค้นหาจาก เลขผู้เสียภาษี / เบอร์โทร</label>
            <div class="flex gap-2">
              <input v-model="customerQuery" type="text" class="w-full border rounded p-2" placeholder="ระบุค่าหนึ่งค่า แล้วกดค้นหา" />
              <button type="button" @click="searchCustomer" class="px-3 py-1 border rounded">ค้นหา</button>
            </div>
          </div>
          <div>
            <label class="block text-gray-600 mb-1">ชื่อ</label>
            <input v-model="form.customer.name" type="text" class="w-full border rounded p-2" placeholder="ชื่อบริษัท/บุคคล" />
          </div>

          <div>
            <label class="block text-gray-600 mb-1">เลขผู้เสียภาษี</label>
            <input v-model="form.customer.tax_id" type="text" class="w-full border rounded p-2" />
          </div>
          <div>
            <label class="block text-gray-600 mb-1">โทรศัพท์</label>
            <input v-model="form.customer.phone" type="text" class="w-full border rounded p-2" />
          </div>
          <div class="md:col-span-3">
            <label class="block text-gray-600 mb-1">ที่อยู่</label>
            <textarea v-model="form.customer.address" rows="2" class="w-full border rounded p-2"></textarea>
          </div>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-gray-600 mb-1">วันที่ออก</label>
          <input v-model="form.issue_date" type="date" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">ครบกำหนด</label>
          <input v-model="form.due_date" type="date" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">เลขที่</label>
          <input v-model="form.number" type="text" class="w-full border rounded p-2" placeholder="เว้นว่างเพื่อรันภายหลัง" />
        </div>
        <div class="md:col-span-3">
          <label class="block text-gray-600 mb-1">เรื่อง/หัวข้อ</label>
          <input v-model="form.subject" type="text" class="w-full border rounded p-2" placeholder="เช่น ใบเสนอราคาโครงการ X" />
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
        <div class="flex items-center gap-2">
          <label class="text-sm">ส่วนลด</label>
          <select v-model="form.discount_type" class="border rounded p-1 text-sm">
            <option value="none">ไม่มี</option>
            <option value="amount">จำนวนเงิน</option>
            <option value="percent">เปอร์เซนต์</option>
          </select>
          <input v-model.number="form.discount_value_decimal" type="number" step="0.01" min="0" class="w-32 border rounded p-1 text-sm text-right" />
        </div>
        <div>Discount: <span class="font-medium">{{ fmt(discount_amount) }}</span></div>
        <div>Subtotal after discount: <span class="font-medium">{{ fmt(adjusted_subtotal) }}</span></div>
        <div>VAT: <span class="font-medium">{{ fmt(vat) }}</span></div>
        <div class="flex items-center gap-2">
          <label class="text-sm">มัดจำ</label>
          <select v-model="form.deposit_type" class="border rounded p-1 text-sm">
            <option value="none">ไม่มี</option>
            <option value="amount">จำนวนเงิน</option>
            <option value="percent">เปอร์เซนต์</option>
          </select>
          <input v-model.number="form.deposit_value_decimal" type="number" step="0.01" min="0" class="w-32 border rounded p-1 text-sm text-right" />
        </div>
        <div>Deposit: <span class="font-medium">{{ fmt(deposit_amount) }}</span></div>
        <div>Total: <span class="font-semibold">{{ fmt(total) }}</span></div>
        <div>Amount due: <span class="font-semibold">{{ fmt(amount_due) }}</span></div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="px-3 py-1 bg-blue-700 text-white rounded" :disabled="processing">บันทึก</button>
        <a href="/admin/documents/quotes" class="px-3 py-1 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
import { alertInfo } from '@/utils/swal'
import { useDocumentCalculator } from '@/composables/useDocumentCalculator'

const form = reactive({
  issue_date: new Date().toISOString().slice(0,10),
  due_date: '',
  number: '',
  subject: '',
  customer: { name: '', tax_id: '', phone: '', address: '' },
  items: [ { name: '', qty_decimal: 1, unit_price_decimal: 0, vat_rate_decimal: 0 } ],
  discount_type: 'none',
  discount_value_decimal: 0,
  deposit_type: 'none',
  deposit_value_decimal: 0,
})
const customerQuery = ref('')
const processing = ref(false)

const { subtotal, discount_amount, adjusted_subtotal, vat, total, deposit_amount, amount_due } = useDocumentCalculator(form)

function addItem(){ form.items.push({ name: '', qty_decimal: 1, unit_price_decimal: 0, vat_rate_decimal: 0 }) }
function removeItem(i){ form.items.splice(i,1) }
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }

async function searchCustomer(){
  if(!customerQuery.value) return
  try{
    const res = await fetch(`/admin/documents/customers/search?q=${encodeURIComponent(customerQuery.value)}`)
    const data = await res.json()
    if(data && data.found){
      const c = data.item
      form.customer.name = c.name || ''
      form.customer.tax_id = c.tax_id || ''
      form.customer.phone = c.phone || ''
      form.customer.address = c.address || ''
      await alertInfo('พบข้อมูลและกรอกให้แล้ว')
    }else{
      await alertInfo('ไม่พบข้อมูล สามารถกรอกสร้างใหม่ได้')
    }
  }catch(e){ console.error(e) }
}

function submit(){
  processing.value = true
  router.post('/admin/documents/quotes', form, { onFinish(){ processing.value = false } })
}
</script>
