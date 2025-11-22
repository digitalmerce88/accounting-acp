<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">แก้ไขอัตราแลกเปลี่ยน</h1>
    <form @submit.prevent="submit" class="space-y-4 text-sm max-w-lg">
      <div>
        <label class="block text-gray-600 mb-1">วันที่</label>
        <input v-model="form.date" type="date" class="w-full border rounded p-2" required />
      </div>
      <div>
        <label class="block text-gray-600 mb-1">สกุลเงิน</label>
        <input v-model="form.currency_code" type="text" maxlength="3" class="w-full border rounded p-2 uppercase" required />
      </div>
      <div>
        <label class="block text-gray-600 mb-1">อัตรา (ต่อ THB)</label>
        <input v-model.number="form.rate_decimal" type="number" min="0" step="0.0001" class="w-full border rounded p-2 text-right" required />
        <div class="text-xs text-gray-500 mt-1">ระบุว่า 1 THB = ? สกุลเงินนี้</div>
      </div>
      <div class="flex gap-2">
        <button type="submit" class="px-3 py-1 bg-blue-700 text-white rounded" :disabled="processing">บันทึก</button>
        <a href="/admin/settings/exchange-rates" class="px-3 py-1 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { reactive, ref } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
const item = usePage().props.item
const form = reactive({
  date: item.date,
  currency_code: item.currency_code,
  rate_decimal: item.rate_decimal,
})
const processing = ref(false)
function submit() {
  processing.value = true
  router.put(`/admin/settings/exchange-rates/${item.id}`, form, {
    onFinish() { processing.value = false }
  })
}
</script>
