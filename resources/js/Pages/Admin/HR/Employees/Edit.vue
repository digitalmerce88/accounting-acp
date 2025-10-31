<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">แก้ไขพนักงาน</h1>
    <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-gray-600">รหัสพนักงาน</label>
        <input v-model="f.emp_code" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">ชื่อ-นามสกุล</label>
        <input v-model="f.name" required class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">ตำแหน่ง</label>
        <input v-model="f.position" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">วันที่เริ่มงาน</label>
        <input type="date" v-model="f.start_date" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">เงินเดือน (บาท)</label>
        <input type="number" step="0.01" v-model.number="f.base_salary_decimal" required class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">อีเมล</label>
        <input type="email" v-model="f.email" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">เบอร์โทร</label>
        <input v-model="f.phone" class="w-full border rounded px-2 py-1" />
      </div>
      <div class="flex items-center gap-2">
        <input id="sso" type="checkbox" v-model="f.sso_enabled" />
        <label for="sso" class="text-sm text-gray-600">เข้าประกันสังคม</label>
      </div>
      <div class="md:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600">ธนาคาร</label>
            <input v-model="f.bank.name" class="w-full border rounded px-2 py-1" />
          </div>
          <div>
            <label class="block text-sm text-gray-600">เลขที่บัญชี</label>
            <input v-model="f.bank.number" class="w-full border rounded px-2 py-1" />
          </div>
        </div>
      </div>
      <div class="md:col-span-2 flex gap-2">
        <button class="px-4 py-2 bg-blue-700 text-white rounded">บันทึก</button>
        <a :href="`/admin/hr/employees/${item.id}`" class="px-4 py-2 bg-gray-100 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { reactive, computed } from 'vue'
const item = computed(()=> usePage().props.item)
const f = reactive({
  emp_code: item.value.emp_code || '',
  name: item.value.name || '',
  position: item.value.position || '',
  start_date: item.value.start_date || '',
  base_salary_decimal: item.value.base_salary_decimal || 0,
  email: item.value.email || '',
  phone: item.value.phone || '',
  sso_enabled: !!item.value.sso_enabled,
  bank: item.value.bank_account_json || { name:'', number:'' },
})
function submit(){
  const payload = { ...f, sso_enabled: !!f.sso_enabled }
  router.post(`/admin/hr/employees/${item.value.id}`, { ...payload, _method: 'PUT' })
}
</script>
