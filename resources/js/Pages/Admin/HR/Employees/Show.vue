<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">ข้อมูลพนักงาน</h1>
      <div class="flex gap-2">
        <a :href="`/admin/hr/employees/${item.id}/edit`" class="px-3 py-1 bg-blue-700 text-white rounded">แก้ไข</a>
        <button v-if="item.active" @click="deactivate" class="px-3 py-1 bg-red-700 text-white rounded">ปิดใช้งาน</button>
        <button v-else @click="restore" class="px-3 py-1 bg-green-700 text-white rounded">เปิดใช้งาน</button>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div>
        <div class="text-gray-500">รหัส</div>
        <div class="font-medium">{{ item.emp_code || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">ชื่อ</div>
        <div class="font-medium">{{ item.name }}</div>
      </div>
      <div>
        <div class="text-gray-500">ตำแหน่ง</div>
        <div class="font-medium">{{ item.position || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">วันที่เริ่มงาน</div>
        <div class="font-medium">{{ item.start_date || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">เงินเดือน</div>
        <div class="font-medium">{{ fmt(item.base_salary_decimal) }}</div>
      </div>
      <div>
        <div class="text-gray-500">สถานะ</div>
        <div class="font-medium" :class="item.active ? 'text-green-700' : 'text-gray-400'">{{ item.active ? 'ใช้งาน' : 'ปิด' }}</div>
      </div>
      <div>
        <div class="text-gray-500">อีเมล</div>
        <div class="font-medium">{{ item.email || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">เบอร์โทร</div>
        <div class="font-medium">{{ item.phone || '-' }}</div>
      </div>
      <div class="md:col-span-2">
        <div class="text-gray-500">บัญชีธนาคาร</div>
        <div class="font-medium">{{ item.bank_account_json?.name || '-' }} {{ item.bank_account_json?.number || '' }}</div>
      </div>
      <div>
        <div class="text-gray-500">หัก ณ ที่จ่าย (กำหนดเอง)</div>
        <div class="font-medium">{{ item.tax_profile_json?.wht_fixed_decimal ? fmt(item.tax_profile_json.wht_fixed_decimal) + ' บาท/เดือน' : '-' }}</div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'
const item = computed(()=> usePage().props.item)
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
function deactivate(){ if(confirm('ปิดใช้งานพนักงานนี้?')) router.delete(`/admin/hr/employees/${item.value.id}`) }
function restore(){ if(confirm('เปิดใช้งานพนักงานนี้?')) router.post(`/admin/hr/employees/${item.value.id}/restore`) }
</script>
