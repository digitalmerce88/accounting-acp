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
          <label class="text-sm text-gray-600">ลูกค้า (ถ้ามี)</label>
          <select v-model.number="form.customer_id" class="mt-1 border rounded px-2 py-1 w-full">
            <option :value="null">-</option>
            <option :value="c.id" v-for="c in customers" :key="c.id">{{ c.name }}</option>
          </select>
        </div>
      </div>

      <div>
        <label class="text-sm text-gray-600">แนบไฟล์ (สลิป/ใบเสร็จ)</label>
        <input type="file" multiple @change="onFiles" class="mt-1" />
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
import { computed, reactive, ref, watch, toRaw } from 'vue'

const p = usePage().props
const categories = computed(()=> p.categories || [])
const customers = computed(()=> p.customers || [])
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
function onFiles(e){ form.files = Array.from(e.target.files||[]) }
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
  ;(form.files||[]).forEach(f=> fd.append('files[]', f))
  router.post('/admin/accounting/income', fd, {
    forceFormData: true,
    onSuccess: (page)=>{
      console.log('Income.post onSuccess', page)
    },
    onError: (resp)=>{
      console.error('Income.post onError', resp)
      if (resp && resp.errors) {
        Object.assign(errors, resp.errors)
      }
    },
    onFinish: ()=> busy.value=false,
  })
}
</script>

<style scoped>
.field-error { color: #065f46; font-size: .9rem; margin-top: .25rem }
</style>
