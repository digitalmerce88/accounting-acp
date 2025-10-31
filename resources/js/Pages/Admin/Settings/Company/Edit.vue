<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">ข้อมูลบริษัท (สำหรับหัวเอกสาร/PDF)</h1>
  <form @submit.prevent="submit" class="space-y-4 text-sm max-w-3xl" enctype="multipart/form-data">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-600 mb-1">ชื่อบริษัท</label>
          <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">เลขผู้เสียภาษี</label>
          <input v-model="form.tax_id" type="text" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">โทรศัพท์</label>
          <input v-model="form.phone" type="text" class="w-full border rounded p-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-gray-600 mb-1">อีเมล</label>
          <input v-model="form.email" type="email" class="w-full border rounded p-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-gray-600 mb-1">โลโก้บริษัท (PNG/JPG/WEBP)</label>
          <div class="flex items-center gap-3">
            <input type="file" @change="onLogoChange" accept="image/png,image/jpeg,image/webp" />
            <button v-if="logoPreview || item.logo_path" type="button" @click="removeLogo" class="px-2 py-1 text-xs border rounded">ลบโลโก้</button>
          </div>
          <div v-if="logoPreview || item.logo_path" class="mt-2">
            <img :src="logoPreview || storageUrl(item.logo_path)" alt="Company Logo" class="h-14 object-contain bg-white border p-1" />
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-gray-600 mb-1">ที่อยู่ (บรรทัดที่ 1)</label>
          <input v-model="form.address_line1" type="text" class="w-full border rounded p-2" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-gray-600 mb-1">ที่อยู่ (บรรทัดที่ 2)</label>
          <input v-model="form.address_line2" type="text" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">จังหวัด</label>
          <input v-model="form.province" type="text" class="w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-gray-600 mb-1">รหัสไปรษณีย์</label>
          <input v-model="form.postcode" type="text" class="w-full border rounded p-2" />
        </div>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="px-3 py-1 bg-blue-700 text-white rounded" :disabled="processing">บันทึก</button>
        <a href="/admin" class="px-3 py-1 border rounded">กลับ</a>
      </div>
    </form>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'
const item = usePage().props.item || {}
const form = reactive({
  name: item.name || '',
  tax_id: item.tax_id || '',
  phone: item.phone || '',
  email: item.email || '',
  address_line1: item.address_line1 || '',
  address_line2: item.address_line2 || '',
  province: item.province || '',
  postcode: item.postcode || '',
  logo: null,
  remove_logo: false,
})
const processing = ref(false)
const logoPreview = ref('')
function onLogoChange(e){ const f=e.target.files[0]; form.logo=f||null; form.remove_logo=false; if(f){ const r=new FileReader(); r.onload=()=>{logoPreview.value=r.result}; r.readAsDataURL(f)} }
function removeLogo(){ form.logo=null; logoPreview.value=''; form.remove_logo=true }
function storageUrl(p){ return p ? `/storage/${p}` : '' }
function submit(){ processing.value = true; router.post('/admin/settings/company', {...form, _method:'PUT'}, { onFinish(){ processing.value = false }, forceFormData: true }) }
</script>
