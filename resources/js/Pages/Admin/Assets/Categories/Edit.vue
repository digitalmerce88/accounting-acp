<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">แก้ไขหมวดหมู่ทรัพย์สิน {{ form.name }}</h1>
    <form @submit.prevent="submit" class="space-y-4 text-sm max-w-md">
      <div>
        <label class="block text-gray-600 mb-1">ชื่อ</label>
        <input v-model="form.name" type="text" class="w-full border rounded p-2" required />
      </div>
      <div>
        <label class="block text-gray-600 mb-1">อายุการใช้งาน (เดือน)</label>
        <input v-model.number="form.useful_life_months" type="number" min="1" class="w-full border rounded p-2 text-right" required />
      </div>
      <div>
        <label class="block text-gray-600 mb-1">วิธีค่าเสื่อม</label>
        <select v-model="form.depreciation_method" class="w-full border rounded p-2">
          <option value="slm">SLM</option>
        </select>
      </div>
      <div class="flex gap-2">
        <button type="submit" class="px-3 py-1 bg-blue-700 text-white rounded" :disabled="processing">บันทึก</button>
        <a href="/admin/assets/categories" class="px-3 py-1 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { reactive, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
const page = usePage()
const item = page.props.item
const form = reactive({ id:item.id, name:item.name, useful_life_months:item.useful_life_months, depreciation_method:item.depreciation_method })
const processing = ref(false)
function submit(){
  processing.value = true
  router.put(`/admin/assets/categories/${form.id}`, form, { onFinish(){ processing.value=false } })
}
</script>
