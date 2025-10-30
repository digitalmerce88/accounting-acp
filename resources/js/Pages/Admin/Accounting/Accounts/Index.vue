<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Accounts</h1>
      <div class="flex items-center gap-2">
        <input v-model="q" @keyup.enter="refresh(1)" class="border rounded px-2 py-1 text-sm" placeholder="Search code or name" />
        <button @click="refresh(1)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Search</button>
        <button @click="openCreate()" class="px-3 py-1 border rounded text-sm">New</button>
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
            <th class="p-2 border">-</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="acc in accounts" :key="acc.id" class="border-b hover:bg-gray-50">
            <td class="p-2 border font-mono">{{ acc.code }}</td>
            <td class="p-2 border">{{ acc.name }}</td>
            <td class="p-2 border capitalize">{{ acc.type }}</td>
            <td class="p-2 border capitalize">{{ acc.normal_balance }}</td>
            <td class="p-2 border text-center">
              <button class="text-blue-600 hover:underline" @click="openEdit(acc)">Edit</button>
            </td>
          </tr>
          <tr v-if="!loading && accounts.length === 0">
            <td colspan="5" class="p-4 text-center text-gray-500">No records</td>
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

    <!-- Create/Edit Modal -->
    <Modal :show="showModal" @close="closeModal">
      <div class="p-4">
        <h2 class="text-lg font-semibold mb-3">{{ isEdit ? 'Edit Account' : 'New Account' }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Code</label>
            <input v-model="form.code" :disabled="isEdit" class="w-full border rounded px-2 py-1" placeholder="e.g. 130" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Type</label>
            <select v-model="form.type" class="w-full border rounded px-2 py-1">
              <option value="asset">asset</option>
              <option value="liability">liability</option>
              <option value="equity">equity</option>
              <option value="revenue">revenue</option>
              <option value="expense">expense</option>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-1">Name</label>
            <input v-model="form.name" class="w-full border rounded px-2 py-1" placeholder="Account name" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">Normal balance</label>
            <select v-model="form.normal_balance" class="w-full border rounded px-2 py-1">
              <option value="debit">debit</option>
              <option value="credit">credit</option>
            </select>
          </div>
        </div>
        <div class="mt-4 flex items-center gap-2">
          <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
          <button class="px-3 py-1 border rounded" @click="closeModal">Cancel</button>
          <span v-if="error" class="text-red-600 text-sm">{{ error }}</span>
        </div>
      </div>
    </Modal>
  </AdminLayout>
  
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import { ref, onMounted } from 'vue'
import axios from 'axios'

const accounts = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const q = ref('')
const loading = ref(false)

const showModal = ref(false)
const isEdit = ref(false)
const saving = ref(false)
const error = ref('')
const editingId = ref(null)
const form = ref({ code: '', name: '', type: 'asset', normal_balance: 'debit' })

function openCreate(){
  isEdit.value = false
  editingId.value = null
  error.value = ''
  form.value = { code: '', name: '', type: 'asset', normal_balance: 'debit' }
  showModal.value = true
}
function openEdit(acc){
  isEdit.value = true
  editingId.value = acc.id
  error.value = ''
  form.value = { code: acc.code, name: acc.name, type: acc.type, normal_balance: acc.normal_balance }
  showModal.value = true
}
function closeModal(){
  showModal.value = false
}

const refresh = async (page = 1) => {
  loading.value = true
  try {
    const res = await axios.get('/admin/accounting/accounts', { params: { page, q: q.value }, headers: { Accept: 'application/json' } })
    accounts.value = res.data.data || []
    meta.value = res.data.meta || { current_page: 1, last_page: 1 }
  } finally { loading.value = false }
}

async function submit(){
  saving.value = true
  error.value = ''
  try {
    if (isEdit.value) {
      await axios.put(`/admin/accounting/accounts/${editingId.value}`, {
        name: form.value.name,
        type: form.value.type,
        normal_balance: form.value.normal_balance,
      })
    } else {
      await axios.post('/admin/accounting/accounts', form.value)
    }
    showModal.value = false
    await refresh(meta.value.current_page)
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to save'
  } finally { saving.value = false }
}

onMounted(() => refresh(1))
</script>
