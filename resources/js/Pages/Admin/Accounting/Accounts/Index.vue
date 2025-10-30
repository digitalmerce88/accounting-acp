<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Accounts</h1>
      <div class="flex items-center gap-2">
        <input v-model="q" @keyup.enter="refresh(1)" class="border rounded px-2 py-1 text-sm" placeholder="Search code or name" />
        <button @click="refresh(1)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Search</button>
      </div>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="text-left p-2 border">Code</th>
            <th class="text-left p-2 border">Name</th>
            <th class="text-left p-2 border">Type</th>
            <th class="text-left p-2 border">Normal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="acc in accounts" :key="acc.id" class="border-b hover:bg-gray-50">
            <td class="p-2 border font-mono">{{ acc.code }}</td>
            <td class="p-2 border">{{ acc.name }}</td>
            <td class="p-2 border capitalize">{{ acc.type }}</td>
            <td class="p-2 border capitalize">{{ acc.normal_balance }}</td>
          </tr>
          <tr v-if="!loading && accounts.length === 0">
            <td colspan="4" class="p-4 text-center text-gray-500">No records</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center justify-between text-sm">
      <div class="text-gray-600">Page {{ meta.current_page }} of {{ meta.last_page }}</div>
      <div class="space-x-2">
        <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="meta.current_page<=1" @click="refresh(meta.current_page-1)">Prev</button>
        <button class="px-3 py-1 border rounded disabled:opacity-50" :disabled="meta.current_page>=meta.last_page" @click="refresh(meta.current_page+1)">Next</button>
      </div>
    </div>
  </AdminLayout>

</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const accounts = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const q = ref('')
const loading = ref(false)

const refresh = async (page = 1) => {
  loading.value = true
  try {
    const res = await axios.get('/admin/accounting/accounts', { params: { page, q: q.value }, headers: { Accept: 'application/json' } })
    accounts.value = res.data.data || []
    meta.value = res.data.meta || { current_page: 1, last_page: 1 }
  } finally { loading.value = false }
}
onMounted(() => refresh(1))
</script>
