<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">แก้ไขรายรับ</h1>
    <form class="mt-4 space-y-3" method="post" :action="`/admin/accounting/income/${item.id}`" enctype="multipart/form-data" @submit.prevent="submit">
      <input type="hidden" name="_method" value="put" />
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
        <input type="text" v-model="form.memo" class="mt-1 border rounded px-2 py-1 w-full" />
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
          <select v-model.number="form.customer_id" class="mt-1 border rounded px-2 py-1 w-full">
            <option :value="null">-</option>
            <option :value="c.id" v-for="c in customers" :key="c.id">{{ c.name }}</option>
          </select>
        </div>
      </div>
      <div>
        <FileDropzone v-model:modelValue="form.files" />
      </div>
      <div class="pt-2">
        <button type="submit" :disabled="busy" class="px-4 py-2 bg-blue-700 text-white rounded">{{ busy ? 'กำลังบันทึก...' : 'อัปเดต' }}</button>
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
const categories = computed(()=> p.categories || [])
const customers = computed(()=> p.customers || [])
const item = computed(()=> p.item)
const form = reactive({ ...item.value })
const vatApplicable = ref(form.vat_applicable ? 1 : 0)
watch(()=> form.category_id, (id)=>{ const c = categories.value.find(x=>x.id===id); vatApplicable.value = c && c.vat_applicable ? 1 : 0 })
watch(vatApplicable, v=>{ form.vat_applicable = !!v })
const whtPercent = ref(Number(form.wht_rate||0)*100)
watch(whtPercent, v=>{ form.wht_rate = Math.max(0, Number(v||0))/100 })
const busy = ref(false)
function submit(){
  busy.value = true
  const fd = new FormData()
  Object.entries(form).forEach(([k,v])=>{ if(k==='files') return; if(typeof v==='boolean'){ fd.append(k, v?'1':'0') } else { fd.append(k, v==null?'':v) } })
  ;(form.files||[]).forEach(f=> fd.append('files[]', f))
  fd.append('_method','put')
  const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  if (csrf) fd.append('_token', csrf)
  ;(form.files||[]).forEach(f=>{
    const file = (f && f.raw instanceof File) ? f.raw : (f instanceof File ? f : null)
    if (file) fd.append('files[]', file)
  })
  router.post(`/admin/accounting/income/${item.value.id}`, fd, {
    forceFormData:true,
    onSuccess: (page) => {
      const msg = page.props?.flash?.success || 'อัปเดตสำเร็จ'
      alertSuccess(msg)
      // controller redirects to show; Inertia will navigate accordingly
    },
    onError: (resp) => {
      const errs = resp && (resp.errors || resp)
      if (errs && typeof errs === 'object') {
        Object.assign(errors, errs)
        const msg = Object.entries(errs).map(([k,v])=> `${k}: ${Array.isArray(v)?v.join(', '):v}`).join('\n')
        alertError(msg)
      } else {
        alertError('เกิดข้อผิดพลาด ไม่สามารถอัปเดตได้')
      }
    },
    onFinish:()=> busy.value=false
  })
}
</script>
