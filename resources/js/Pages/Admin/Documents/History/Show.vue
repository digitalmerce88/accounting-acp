<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">ประวัติเอกสาร {{ doc.number || doc.id }} ({{ doc.type }})</h1>
      <a :href="backUrl" class="px-3 py-1 border rounded">ย้อนกลับ</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
      <div>
        <div class="font-medium mb-2">Approval History</div>
        <div class="border rounded overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left border">เวลา</th>
                <th class="p-2 text-left border">ผู้ใช้</th>
                <th class="p-2 text-left border">การกระทำ</th>
                <th class="p-2 text-left border">หมายเหตุ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in approvalLogs" :key="row.id">
                <td class="p-2 border">{{ row.created_at }}</td>
                <td class="p-2 border">{{ row.user_name || '-' }}</td>
                <td class="p-2 border">{{ row.action }}</td>
                <td class="p-2 border">{{ row.comment || '-' }}</td>
              </tr>
              <tr v-if="approvalLogs.length===0"><td colspan="4" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="font-medium mb-2">Audit Trail</div>
        <div class="border rounded overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left border">เวลา</th>
                <th class="p-2 text-left border">ผู้ใช้</th>
                <th class="p-2 text-left border">การกระทำ</th>
                <th class="p-2 text-left border">รายละเอียด</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in auditLogs" :key="row.id">
                <td class="p-2 border">{{ row.created_at }}</td>
                <td class="p-2 border">{{ row.user_name || '-' }}</td>
                <td class="p-2 border">{{ row.action }}</td>
                <td class="p-2 border">
                  <pre class="whitespace-pre-wrap text-xs">{{ summarize(row) }}</pre>
                </td>
              </tr>
              <tr v-if="auditLogs.length===0"><td colspan="4" class="p-3 text-center text-gray-500">ไม่มีข้อมูล</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const doc = computed(()=> page.props.doc)
const approvalLogs = computed(()=> page.props.approvalLogs || [])
const auditLogs = computed(()=> page.props.auditLogs || [])

const backUrl = computed(()=>{
  const t = doc.value.type
  const id = doc.value.id
  if(t==='invoices') return `/admin/documents/invoices/${id}`
  if(t==='quotes') return `/admin/documents/quotes/${id}`
  if(t==='po') return `/admin/documents/po/${id}`
  if(t==='bills') return `/admin/documents/bills/${id}`
  return '/admin'
})

function summarize(row){
  try{
    const oldK = Object.keys(row.old_values||{})
    const newK = Object.keys(row.new_values||{})
    if(oldK.length===0 && newK.length===0) return '-'
    const keys = Array.from(new Set([...oldK, ...newK])).slice(0,8)
    return keys.map(k=> `${k}: ${JSON.stringify(row.old_values?.[k])} -> ${JSON.stringify(row.new_values?.[k])}`).join('\n')
  }catch(e){ return JSON.stringify(row.new_values||{}, null, 2) }
}
</script>
