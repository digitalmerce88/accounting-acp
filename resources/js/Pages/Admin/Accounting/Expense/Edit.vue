<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">แก้ไขรายจ่าย</h1>
    <form class="mt-4 space-y-3" method="post" :action="`/admin/accounting/expense/${item.id}`" enctype="multipart/form-data" @submit.prevent="submit">
      <input type="hidden" name="_method" value="put" />
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
        <input type="text" v-model="form.memo" class="mt-1 border rounded px-2 py-1 w-full" />
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm text-gray-600">หมวดค่าใช้จ่าย</label>
          <div class="mt-1 flex items-center gap-2">
            <select v-model.number="form.category_id" class="mt-0 border rounded px-2 py-1 w-full">
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
          <label class="text-sm text-gray-600">ผู้ขาย (ถ้ามี)</label>
          <div class="mt-1 flex items-center gap-2">
            <select v-model.number="form.vendor_id" class="mt-0 border rounded px-2 py-1 w-full">
              <option :value="null">-</option>
              <option :value="v.id" v-for="v in vendors" :key="v.id">{{ v.name }}</option>
            </select>
            <button type="button" @click="vendorModalOpen = true" class="p-2 bg-gray-100 rounded text-gray-600 hover:bg-gray-200" title="เพิ่มผู้ขาย">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            </button>
          </div>
        </div>
      </div>
      <div>
        <FileDropzone v-model:modelValue="form.files" />
      </div>
      <div class="pt-2">
        <button type="submit" :disabled="busy" class="px-4 py-2 bg-blue-700 text-white rounded">{{ busy ? 'กำลังบันทึก...' : 'อัปเดต' }}</button>
      </div>
    </form>

    <!-- Category modal -->
    <div v-if="categoryModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black opacity-40" @click="categoryModalOpen=false"></div>
      <div class="bg-white p-4 rounded shadow z-10 w-full max-w-md">
        <h3 class="font-semibold mb-2">สร้างหมวดค่าใช้จ่าย</h3>
        <input v-model="newCategoryName" placeholder="ชื่อหมวด" class="w-full border rounded px-2 py-1 mb-2" />
        <div class="flex justify-end gap-2">
          <button class="px-3 py-1" @click="categoryModalOpen=false">ยกเลิก</button>
          <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="createCategory({name:newCategoryName, type:'expense'})">สร้าง</button>
        </div>
      </div>
    </div>

    <!-- Vendor modal -->
    <div v-if="vendorModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black opacity-40" @click="vendorModalOpen=false"></div>
      <div class="bg-white p-4 rounded shadow z-10 w-full max-w-md">
        <h3 class="font-semibold mb-2">สร้างผู้ขาย</h3>
        <input v-model="newVendorName" placeholder="ชื่อผู้ขาย" class="w-full border rounded px-2 py-1 mb-2" />
        <input v-model="newVendorPhone" placeholder="โทรศัพท์ (ไม่บังคับ)" class="w-full border rounded px-2 py-1 mb-2" />
        <div class="flex justify-end gap-2">
          <button class="px-3 py-1" @click="vendorModalOpen=false">ยกเลิก</button>
          <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="createVendor({name:newVendorName, phone:newVendorPhone})">สร้าง</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import FileDropzone from '@/Components/FileDropzone.vue'
import { computed, reactive, ref, watch } from 'vue'
import { alertError, alertSuccess } from '@/utils/swal'
const p = usePage().props
const categories = ref([...(p.categories || [])])
const vendors = ref([...(p.vendors || [])])
const item = computed(()=> p.item)
const form = reactive({ ...item.value })
const vatApplicable = ref(form.vat_applicable ? 1 : 0)
watch(()=> form.category_id, (id)=>{ const c = categories.value.find(x=>x.id===id); vatApplicable.value = c && c.vat_applicable ? 1 : 0 })
watch(vatApplicable, v=>{ form.vat_applicable = !!v })
const whtPercent = ref(Number(form.wht_rate||0)*100)
watch(whtPercent, v=>{ form.wht_rate = Math.max(0, Number(v||0))/100 })
const busy = ref(false)
const errors = reactive({})
const categoryModalOpen = ref(false)
const vendorModalOpen = ref(false)
const newCategoryName = ref('')
const newVendorName = ref('')
const newVendorPhone = ref('')

async function createCategory(payload) {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch('/admin/accounting/categories', {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(payload)
    })
    if (!res.ok) throw res
    const body = await res.json()
    if (body && body.item) {
      categories.value.unshift(body.item)
      form.category_id = body.item.id
      categoryModalOpen.value = false
      alertSuccess('สร้างหมวดเรียบร้อย')
    }
  } catch (e) { console.error('createCategory error', e); alertError('ไม่สามารถสร้างหมวดได้') }
}

async function createVendor(payload) {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch('/admin/documents/vendors', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify(payload) })
    if (!res.ok) throw res
    const body = await res.json()
    if (body && body.item) {
      vendors.value.unshift(body.item)
      form.vendor_id = body.item.id
      vendorModalOpen.value = false
      alertSuccess('สร้างผู้ขายเรียบร้อย')
    }
  } catch (e) { console.error('createVendor error', e); alertError('ไม่สามารถสร้างผู้ขายได้') }
}
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
  router.post(`/admin/accounting/expense/${item.value.id}`, fd, {
    forceFormData:true,
    onSuccess: (page) => {
      const msg = page.props?.flash?.success || 'อัปเดตสำเร็จ'
      alertSuccess(msg)
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
