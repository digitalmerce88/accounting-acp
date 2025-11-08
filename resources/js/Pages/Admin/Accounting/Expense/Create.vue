<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">บันทึกรายจ่าย</h1>
    <form
      class="mt-4 space-y-3"
      method="post"
      action="/admin/accounting/expense"
      enctype="multipart/form-data"
      @submit.prevent="submit"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">วันที่</label>
          <input type="date" v-model="form.date" class="mt-1 border rounded px-2 py-1 w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">วิธีจ่าย</label>
          <select v-model="form.payment_method" class="mt-1 border rounded px-2 py-1 w-full">
            <option value="bank">โอน/บัญชีธนาคาร</option>
            <option value="cash">เงินสด</option>
          </select>
        </div>
      </div>

      <div>
        <label class="text-sm text-gray-600">จ่ายค่าอะไร (บันทึก)</label>
        <input type="text" v-model="form.memo" class="mt-1 border rounded px-2 py-1 w-full" placeholder="เช่น ค่าโฆษณา/ค่าไฟฟ้า" />
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">หมวดค่าใช้จ่าย</label>
          <select v-model.number="form.category_id" class="mt-1 border rounded px-2 py-1 w-full">
            <option :value="c.id" v-for="c in categories" :key="c.id">{{ c.name }}</option>
          </select>
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
          <label class="text-sm text-gray-600">หมวดมี VAT?</label>
          <select v-model.number="vatApplicable" class="mt-1 border rounded px-2 py-1 w-full">
            <option :value="1">ใช่</option>
            <option :value="0">ไม่ใช่</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">WHT (%)</label>
          <input type="number" step="0.01" v-model.number="whtPercent" class="mt-1 border rounded px-2 py-1 w-full" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">ผู้ขาย (ถ้ามี)</label>
          <select v-model.number="form.vendor_id" class="mt-1 border rounded px-2 py-1 w-full">
            <option :value="null">-</option>
            <option :value="v.id" v-for="v in vendors" :key="v.id">{{ v.name }}</option>
          </select>
        </div>
      </div>

      <div>
        <FileDropzone v-model:modelValue="form.files" />
      </div>

      <div class="pt-2">
        <button type="submit" :disabled="busy" class="px-4 py-2 bg-red-700 text-white rounded">
          {{ busy ? 'กำลังบันทึก...' : 'บันทึกรายจ่าย' }}
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

const p = usePage().props
const categories = computed(()=> p.categories || [])
const vendors = computed(()=> p.vendors || [])
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
  vendor_id: null,
  files: [],
})

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
function submit(){
  busy.value = true
  console.log('Expense.submit clicked', toRaw(form))
  // basic client-side checks
  errors.date = null; errors.amount = null; errors.category_id = null
  if (!form.category_id) {
    errors.category_id = 'กรุณาเลือกหมวดค่าใช้จ่าย'
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
  ;(form.files||[]).forEach(f=> fd.append('files[]', f))
  router.post('/admin/accounting/expense', fd, {
    forceFormData: true,
    onSuccess: (page)=>{
      console.log('Expense.post onSuccess', page)
    },
    onError: (resp)=>{
      console.error('Expense.post onError', resp)
      // Inertia returns validation errors keyed by field
      if (resp && resp.errors) {
        Object.assign(errors, resp.errors)
      }
    },
    onFinish: ()=> busy.value=false,
  })
}
</script>

<style scoped>
.field-error { color: #b91c1c; font-size: .9rem; margin-top: .25rem }
</style>
