<template>
  <AdminLayout>
    <div class="flex items-center justify-between mb-3">
      <h1 class="text-xl font-semibold">รายละเอียดรายจ่าย</h1>
      <div class="flex gap-2">
        <a :href="`/admin/accounting/expense/${item.id}/edit`" class="px-3 py-1 text-sm bg-blue-600 text-white rounded">แก้ไข</a>
        <button @click="del()" class="px-3 py-1 text-sm bg-red-600 text-white rounded">ลบ</button>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
      <div>
        <div class="text-gray-500">วันที่</div>
  <div class="font-medium">{{ fmtDMY(item.date) }}</div>
      </div>
      <div>
        <div class="text-gray-500">จำนวน</div>
        <div class="font-medium">{{ fmt(item.amount) }}</div>
      </div>
      <div>
        <div class="text-gray-500">บันทึก</div>
        <div class="font-medium">{{ item.memo || '-' }}</div>
      </div>
      <div>
        <div class="text-gray-500">วิธีจ่าย</div>
        <div class="font-medium">{{ item.payment_method }}</div>
      </div>
    </div>

    <div class="mt-6">
      <h3 class="font-semibold">รายการบัญชี (Journal)</h3>
      <table class="min-w-full border text-sm mt-2">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 border text-left">บัญชี</th>
            <th class="p-2 border text-right">เดบิต</th>
            <th class="p-2 border text-right">เครดิต</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ln in lines" :key="ln.id">
            <td class="p-2 border">{{ ln.account_name || ln.account_id }}</td>
            <td class="p-2 border text-right">{{ fmt(ln.debit) }}</td>
            <td class="p-2 border text-right">{{ fmt(ln.credit) }}</td>
          </tr>
          <tr v-if="!lines || lines.length===0">
            <td colspan="3" class="p-3 text-center text-gray-500">-</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-6">
      <h3 class="font-semibold">ไฟล์แนบ</h3>
      <ul class="list-disc pl-5 text-sm">
        <li v-for="(a,idx) in attachmentsList" :key="a.id || idx">
          <a :href="`/storage/${a.path}`" target="_blank" class="text-blue-700 underline">{{ a.name || a.path }}</a>
        </li>
        <li v-if="!attachmentsList || attachmentsList.length===0" class="text-gray-500">-</li>
      </ul>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { fmtDMY } from '@/utils/format'
import { computed } from 'vue'
import { confirmDialog } from '@/utils/swal'
const p = usePage().props
const item = computed(()=> p.item)
const lines = computed(()=> p.lines || [])
const attachments = computed(()=> p.attachments || [])
const attachmentsJson = computed(()=> p.attachments_json || [])
const attachmentsList = computed(()=> (attachmentsJson.value && attachmentsJson.value.length>0) ? attachmentsJson.value : attachments.value)
function fmt(n){ return Number(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}) }
async function del(){ if(await confirmDialog('ยืนยันลบรายการนี้?')) router.delete(`/admin/accounting/expense/${item.value.id}`) }
</script>
