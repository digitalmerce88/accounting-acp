<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">แก้ไขใบแจ้งหนี้ {{ form.number || form.id }}</h1>
    <div class="mt-2 mb-4 flex gap-2">
      <button v-if="item.approval_status==='draft'" @click="openModal('submit')" class="px-3 py-1 bg-yellow-600 text-white rounded text-sm">ส่งอนุมัติ</button>
      <button v-if="auth.is_admin && item.approval_status==='submitted'" @click="openModal('approve')" class="px-3 py-1 bg-green-700 text-white rounded text-sm">อนุมัติ</button>
      <button v-if="auth.is_admin && item.approval_status==='approved'" @click="openModal('lock')" class="px-3 py-1 bg-gray-800 text-white rounded text-sm">ล็อก</button>
      <button v-if="auth.is_admin && item.approval_status==='locked'" @click="openModal('unlock')" class="px-3 py-1 bg-gray-500 text-white rounded text-sm">ปลดล็อก</button>
    </div>
    <form @submit.prevent="submit" class="space-y-4 text-sm">
      <div class="p-3 border rounded">
        <div class="font-medium mb-2">ข้อมูลผู้รับเอกสาร (ลูกค้า)</div>
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
          <input v-model="form.number" type="text" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">สกุลเงิน</label>
          <input v-model="form.currency_code" @change="onCurrencyChange" list="currency-list" type="text" maxlength="3" class="w-full border rounded p-2 uppercase" />
          <datalist id="currency-list">
            <option v-for="c in currencies" :key="c.code" :value="c.code">{{ c.code }} - {{ c.name }}</option>
          </datalist>
        </div>
        <div>
          <label class="block text-gray-600 mb-1">อัตราแลกเปลี่ยน</label>
          <input v-model.number="form.fx_rate_decimal" type="number" step="0.00000001" min="0" class="w-full border rounded p-2 text-right" />
        </div>
        <div class="flex items-center gap-2">
          <input id="is_tax" v-model="form.is_tax_invoice" type="checkbox" class="h-4 w-4" />
          <label for="is_tax" class="text-gray-700">ออกใบกำกับภาษี</label>
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
                <td class="border p-1"><input v-model="it.name" class="w-full p-1 border rounded" /></td>
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
        <a :href="`/admin/documents/invoices/${form.id}`" class="px-3 py-1 border rounded">ยกเลิก</a>
      </div>
    </form>

    <ApprovalCommentModal
      :show="showModal"
      :title="modalTitle"
      v-model="comment"
      :submitting="submitting"
      @submit="doAction"
      @cancel="closeModal"
    />

  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ApprovalCommentModal from '@/Components/ApprovalCommentModal.vue'
import { usePage, router } from '@inertiajs/vue3'
import { reactive, ref, computed, onMounted } from 'vue'
import { alertInfo } from '@/utils/swal'
import { useDocumentCalculator } from '@/composables/useDocumentCalculator'
const item = usePage().props.item
const auth = usePage().props.auth || { is_admin: false }
const form = reactive({
  id: item.id,
  issue_date: item.issue_date,
  due_date: item.due_date,
  number: item.number,
  currency_code: item.currency_code || 'THB',
  fx_rate_decimal: Number(item.fx_rate_decimal || 1),
  is_tax_invoice: !!item.is_tax_invoice,
  customer: { name: item.customer?.name||'', tax_id: item.customer?.tax_id||'', phone: item.customer?.phone||'', address: item.customer?.address||'' },
  items: item.items?.map(it=>({ name: it.name, qty_decimal: Number(it.qty_decimal), unit_price_decimal: Number(it.unit_price_decimal), vat_rate_decimal: Number(it.vat_rate_decimal)})) || [],
  discount_type: item.discount_type || 'none',
  discount_value_decimal: item.discount_value_decimal ?? 0,
  deposit_type: item.deposit_type || 'none',
  deposit_value_decimal: item.deposit_value_decimal ?? 0,
})
const customerQuery = ref('')
const processing = ref(false)
const currencies = ref([])
const baseCurrency = ref('THB')
// national_id no longer used
const { subtotal, discount_amount, adjusted_subtotal, vat, total, deposit_amount, amount_due } = useDocumentCalculator(form)
function addItem(){ form.items.push({ name: '', qty_decimal: 1, unit_price_decimal: 0, vat_rate_decimal: 0 }) }
function removeItem(i){ form.items.splice(i,1) }
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
async function loadCurrencies(){
  try{
    const res = await fetch('/api/currencies')
    const data = await res.json()
    currencies.value = data.list || []
    baseCurrency.value = data.base || 'THB'
  }catch(e){ console.error(e) }
}
async function onCurrencyChange(){
  const code = (form.currency_code || '').toUpperCase()
  form.currency_code = code
  if(!code){ return }
  try{
    const res = await fetch(`/api/currency-rate?code=${encodeURIComponent(code)}`)
    if(res.ok){
      const data = await res.json()
      form.fx_rate_decimal = Number(data.rate || 1)
    }else{
      form.fx_rate_decimal = (code === baseCurrency.value) ? 1 : form.fx_rate_decimal
    }
  }catch(e){ console.error(e) }
}
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
  alertInfo('พบข้อมูลและกรอกให้แล้ว')
    }else{
  alertInfo('ไม่พบข้อมูล สามารถกรอกสร้างใหม่ได้')
    }
  }catch(e){ console.error(e) }
}
function submit(){
  processing.value = true
  router.put(`/admin/documents/invoices/${form.id}`, form, { onFinish(){ processing.value = false } })
}

onMounted(() => { loadCurrencies() })

// Approval modal logic
const showModal = ref(false)
const action = ref('submit')
const comment = ref('')
const submitting = ref(false)
const modalTitle = computed(()=> action.value==='submit' ? 'ส่งอนุมัติเอกสาร' : action.value==='approve' ? 'อนุมัติเอกสาร' : action.value==='lock' ? 'ล็อกเอกสาร' : 'ปลดล็อกเอกสาร')
function openModal(act){ action.value = act; comment.value=''; showModal.value=true }
function closeModal(){ showModal.value=false }
async function doAction(){
  submitting.value = true
  const id = form.id
  const payload = { comment: comment.value }
  const url = action.value==='submit' ? `/admin/documents/invoices/${id}/submit` :
              action.value==='approve' ? `/admin/documents/invoices/${id}/approve` :
              action.value==='lock' ? `/admin/documents/invoices/${id}/lock` :
              `/admin/documents/invoices/${id}/unlock`
  router.post(url, payload, { onFinish: () => { submitting.value=false; showModal.value=false } })
}
</script>
