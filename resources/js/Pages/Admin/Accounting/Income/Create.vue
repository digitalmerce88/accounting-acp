<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">บันทึกรายรับ</h1>
    <form
      class="mt-4 space-y-3"
      method="post"
      action="/admin/accounting/income"
      enctype="multipart/form-data"
      @submit.prevent="submit"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">วันที่</label>
          <input type="date" v-model="form.date" class="mt-1 border rounded px-2 py-1 w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">วิธีรับเงิน</label>
          <select v-model="form.payment_method" class="mt-1 border rounded px-2 py-1 w-full">
            <option value="bank">โอน/บัญชีธนาคาร</option>
            <option value="cash">เงินสด</option>
          </select>
        </div>
      </div>

      <div>
        <label class="text-sm text-gray-600">ได้จากอะไร (บันทึก)</label>
        <input type="text" v-model="form.memo" class="mt-1 border rounded px-2 py-1 w-full" placeholder="เช่น ขายสินค้า/บริการ" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">หมวดรายได้</label>
          <div class="mt-1 flex items-center gap-2">
            <select v-model.number="form.category_id" class="border rounded px-2 py-1 w-full">
              <option :value="c.id" v-for="c in categories" :key="c.id">{{ c.name }}</option>
            </select>
            <button type="button" @click="categoryModalOpen = true" class="p-2 bg-gray-100 rounded text-gray-600 hover:bg-gray-200" title="เพิ่มหมวด">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
          </div>
        </div>
        <div>
          <label class="text-sm text-gray-600">จำนวนเงิน</label>
          <input type="number" step="0.01" v-model.number="form.amount" class="mt-1 border rounded px-2 py-1 w-full" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="text-sm text-gray-600">โหมดราคา</label>
          <select v-model="form.price_input_mode" class="mt-1 border rounded px-2 py-1 w-full">
            <option value="gross">รวม VAT</option>
            <option value="net">ไม่รวม VAT</option>
            <option value="novat">ไม่เสีย VAT</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">ลงบัญชี VAT?</label>
          <select v-model.number="vatApplicable" class="mt-1 border rounded px-2 py-1 w-full">
            <option :value="1">ใช่</option>
            <option :value="0">ไม่ใช่</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">หัก ณ ที่จ่าย (%)</label>
          <input type="number" step="0.01" v-model.number="whtPercent" class="mt-1 border rounded px-2 py-1 w-full" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">ลูกค้า (ถ้ามี)</label>
          <div class="mt-1 flex items-center gap-2">
            <select v-model.number="form.customer_id" class="border rounded px-2 py-1 w-full">
              <option :value="null">-</option>
              <option :value="c.id" v-for="c in customers" :key="c.id">{{ c.name }}</option>
            </select>
            <button type="button" @click="customerModalOpen = true" class="p-2 bg-gray-100 rounded text-gray-600 hover:bg-gray-200" title="เพิ่มลูกค้า">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
          </div>
        </div>
      </div>

      <div>
        <FileDropzone v-model:modelValue="form.files" />
      </div>

      <!-- Category modal -->
      <div v-if="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-40" @click="categoryModalOpen=false"></div>
        <div class="bg-white p-4 rounded shadow z-10 w-full max-w-md">
          <h3 class="font-semibold mb-2">สร้างหมวดรายได้</h3>
          <input v-model="newCategoryName" placeholder="ชื่อหมวด" class="w-full border rounded px-2 py-1 mb-2" />
          <div class="flex justify-end gap-2">
            <button class="px-3 py-1" @click="categoryModalOpen=false">ยกเลิก</button>
            <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="(async()=>{ await createCategory({name:newCategoryName.value, type:'income'}); })()">สร้าง</button>
          </div>
        </div>
      </div>

      <!-- Customer modal -->
      <div v-if="customerModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-40" @click="customerModalOpen=false"></div>
        <div class="bg-white p-4 rounded shadow z-10 w-full max-w-md">
          <h3 class="font-semibold mb-2">สร้างลูกค้า</h3>
          <input v-model="newCustomerName" placeholder="ชื่อลูกค้า" class="w-full border rounded px-2 py-1 mb-2" />
          <input v-model="newCustomerPhone" placeholder="โทรศัพท์ (ไม่บังคับ)" class="w-full border rounded px-2 py-1 mb-2" />
          <div class="flex justify-end gap-2">
            <button class="px-3 py-1" @click="customerModalOpen=false">ยกเลิก</button>
            <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="(async()=>{ await createCustomer({name:newCustomerName.value, phone:newCustomerPhone.value}); })()">สร้าง</button>
          </div>
        </div>
      </div>

      <div class="pt-2">
        <button type="submit" :disabled="busy" class="px-4 py-2 bg-green-700 text-white rounded">
          {{ busy ? 'กำลังบันทึก...' : 'บันทึกรายรับ' }}
        </button>
      </div>
    </form>
  </AdminLayout>

</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import FileDropzone from '@/Components/FileDropzone.vue'
import { computed, reactive, ref, watch, toRaw } from 'vue'
import { alertError, alertSuccess } from '@/utils/swal'

const p = usePage().props
const categories = ref([...(p.categories || [])])
const customers = ref([...(p.customers || [])])
const categoryModalOpen = ref(false)
const customerModalOpen = ref(false)
const newCategoryName = ref('')
const newCustomerName = ref('')
const newCustomerPhone = ref('')
const today = computed(()=> p.today)

const form = reactive({
  date: today.value,
  memo: '',
  amount: 0,
  price_input_mode: 'gross',
  vat_applicable: true,
  wht_rate: 0,
  payment_method: 'bank',
  category_id: categories.value[0]?.id || null,
  customer_id: null,
  files: [],
})

// VAT applicable follows selected category default
const vatApplicable = ref(1)
watch(()=> form.category_id, (id)=>{
  const c = categories.value.find(x=>x.id===id)
  vatApplicable.value = c && c.vat_applicable ? 1 : 0
})
watch(vatApplicable, v=>{ form.vat_applicable = !!v })

const whtPercent = ref(0)
watch(whtPercent, v=>{ form.wht_rate = Math.max(0, Number(v||0))/100 })

const busy = ref(false)
const errors = reactive({})
// functions to create category/customer inline
async function createCategory(payload) {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch('/admin/accounting/categories', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    })
    if (!res.ok) throw res
    const body = await res.json()
    if (body && body.item) {
      categories.value.unshift(body.item)
      form.category_id = body.item.id
      categoryModalOpen.value = false
      alertSuccess('สร้างหมวดเรียบร้อย')
    }
  } catch (e) {
    console.error('createCategory error', e)
    alertError('ไม่สามารถสร้างหมวดได้')
  }
}

async function createCustomer(payload) {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch('/admin/documents/customers', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    })
    if (!res.ok) throw res
    const body = await res.json()
    if (body && body.item) {
      customers.value.unshift(body.item)
      form.customer_id = body.item.id
      customerModalOpen.value = false
      alertSuccess('สร้างลูกค้าเรียบร้อย')
    }
  } catch (e) {
    console.error('createCustomer error', e)
    alertError('ไม่สามารถสร้างลูกค้าได้')
  }
}
function submit(){
  busy.value = true
  console.log('Income.submit clicked', toRaw(form))
  // basic client-side checks
  errors.date = null; errors.amount = null; errors.category_id = null
  if (!form.category_id) {
    errors.category_id = 'กรุณาเลือกหมวดรายได้'
    busy.value = false
    return
  }
  if (!form.amount || Number(form.amount) <= 0) {
    errors.amount = 'จำนวนเงินต้องมากกว่า 0'
    busy.value = false
    return
  }
  const fd = new FormData()
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
  Object.entries(form).forEach(([k,v])=>{
    if(k==='files') return
    // Coerce boolean to '1'/'0' for Laravel boolean validation
    if (typeof v === 'boolean') {
      fd.append(k, v ? '1' : '0')
    } else {
      fd.append(k, v==null ? '' : v)
    }
  })
  if (csrf) fd.append('_token', csrf)
  ;(form.files||[]).forEach(f=>{
    const file = (f && f.raw instanceof File) ? f.raw : (f instanceof File ? f : null)
    if (file) fd.append('files[]', file)
  })
  router.post('/admin/accounting/income', fd, {
    forceFormData: true,
    onSuccess: (page)=>{
      // show success message (flash from server will also be present)
      const msg = page.props?.flash?.success || 'บันทึกสำเร็จ'
      alertSuccess(msg)
      // Inertia will already have redirected to the show page based on controller
    },
    onError: (resp)=>{
      console.error('Income.post onError', resp)
      // Inertia passes validation errors object directly; sometimes it's {errors: {...}}
      const errs = resp && (resp.errors || resp)
      if (errs && typeof errs === 'object') {
        Object.assign(errors, errs)
        const msg = Object.entries(errs).map(([k,v])=> `${k}: ${Array.isArray(v)?v.join(', '):v}`).join('\n')
        alertError(msg)
      } else {
        alertError('เกิดข้อผิดพลาด ไม่สามารถบันทึกได้')
      }
    },
    onFinish: ()=> busy.value=false,
  })
}
</script>

<style scoped>
.field-error { color: #065f46; font-size: .9rem; margin-top: .25rem }
</style>
