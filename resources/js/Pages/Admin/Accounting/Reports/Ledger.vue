<template>
  <AdminLayout>
    <div class="flex items-end justify-between gap-4">
      <h1 class="text-xl font-semibold">Ledger</h1>
      <div class="flex items-center gap-2 text-sm">
        <div>
          <label class="block text-xs text-gray-600">Account</label>
          <select v-model.number="account_id" class="border rounded px-2 py-1 min-w-64">
            <option :value="null">Select account</option>
            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs text-gray-600">From</label>
          <input v-model="from" type="date" class="border rounded px-2 py-1" />
        </div>
        <div>
          <label class="block text-xs text-gray-600">To</label>
          <input v-model="to" type="date" class="border rounded px-2 py-1" />
        </div>
        <button @click="load" class="px-3 py-1 bg-blue-600 text-white rounded">Apply</button>
        <a :href="csvHref" class="px-3 py-1 border rounded" :class="!account_id ? 'pointer-events-none opacity-50' : ''">Export CSV</a>
      </div>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr><th class="p-2 border text-left">Date</th><th class="p-2 border text-left">Entry</th><th class="p-2 border text-left">Memo</th><th class="p-2 border text-right">Dr</th><th class="p-2 border text-right">Cr</th><th class="p-2 border text-right">Balance</th></tr>
        </thead>
        <tbody>
          <tr v-for="r in rows" :key="r[1]">
            <td class="p-2 border">{{ r[0] }}</td>
            <td class="p-2 border">{{ r[1] }}</td>
            <td class="p-2 border">{{ r[2] }}</td>
            <td class="p-2 border text-right">{{ money(r[3]) }}</td>
            <td class="p-2 border text-right">{{ money(r[4]) }}</td>
            <td class="p-2 border text-right">{{ money(r[5]) }}</td>
          </tr>
          <tr v-if="rows.length===0"><td colspan="6" class="p-4 text-center text-gray-500">No data</td></tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
const account_id = ref(null)
const from = ref('')
const to = ref('')
const rows = ref([])
const accounts = ref([])
const csvHref = computed(() => `/admin/accounting/reports/ledger.csv?account_id=${account_id.value||''}&from=${from.value||''}&to=${to.value||''}`)

async function loadAccounts(){
  const res = await axios.get('/admin/accounting/accounts', { params: { page: 1 }, headers: { Accept: 'application/json' } })
  accounts.value = res.data.data || []
}
const load = async ()=>{
  const res = await axios.get('/admin/accounting/reports/ledger', { params: { account_id: account_id.value, from: from.value||undefined, to: to.value||undefined }, headers:{ Accept: 'application/json' } })
  rows.value = res.data.data || res.data
}
const money = (n)=> Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
onMounted(loadAccounts)
</script>
