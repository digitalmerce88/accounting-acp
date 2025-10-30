<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Journals</h1>
      <Link href="/admin/accounting/journals/create" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Create</Link>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-2 border">Date</th>
            <th class="text-left p-2 border">Memo</th>
            <th class="text-left p-2 border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="j in journals" :key="j.id" class="border-b hover:bg-gray-50">
            <td class="p-2 border">{{ j.date }}</td>
            <td class="p-2 border">{{ j.memo }}</td>
            <td class="p-2 border">
              <button class="text-red-600 hover:underline" @click="del(j)">Delete</button>
            </td>
          </tr>
          <tr v-if="!loading && journals.length===0">
            <td colspan="3" class="p-4 text-center text-gray-500">No records</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center justify-between text-sm">
      <div class="text-gray-600">Page {{ meta.current_page }} of {{ meta.last_page }}</div>
      <div class="space-x-2">
        <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="meta.current_page<=1" @click="load(meta.current_page-1)">Prev</button>
        <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="meta.current_page>=meta.last_page" @click="load(meta.current_page+1)">Next</button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import axios from 'axios'
const journals = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const loading = ref(false)
const load = async (page = 1)=>{
  loading.value = true
  try {
    const res = await axios.get('/admin/accounting/journals', { params: { page }, headers: { Accept: 'application/json' } })
    journals.value = res.data.data || []
    meta.value = res.data.meta || { current_page: 1, last_page: 1 }
  } finally { loading.value = false }
}
async function del(j) {
  if (!confirm('Delete this journal?')) return
  await axios.delete(`/admin/accounting/journals/${j.id}`)
  load(meta.value.current_page)
}
onMounted(() => load(1))
</script>
