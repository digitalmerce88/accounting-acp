<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-semibold">หมวดหมู่ทรัพย์สิน</h1>
      <a href="/admin/assets/categories/create" class="px-3 py-1 bg-blue-700 text-white rounded text-sm">+ เพิ่มหมวดหมู่</a>
    </div>
    <div class="overflow-x-auto text-sm">
      <table class="min-w-full border">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border text-left">ชื่อ</th>
            <th class="p-2 border text-right">อายุการใช้งาน (เดือน)</th>
            <th class="p-2 border">วิธีค่าเสื่อม</th>
            <th class="p-2 border w-32"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in rows.data" :key="r.id">
            <td class="border p-2">{{ r.name }}</td>
            <td class="border p-2 text-right">{{ r.useful_life_months }}</td>
            <td class="border p-2 text-center uppercase">{{ r.depreciation_method }}</td>
            <td class="border p-2 text-center">
              <a :href="`/admin/assets/categories/${r.id}/edit`" class="text-blue-700">แก้ไข</a>
            </td>
          </tr>
          <tr v-if="rows.data.length===0"><td colspan="4" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
const page = usePage()
const rows = page.props.rows
</script>
