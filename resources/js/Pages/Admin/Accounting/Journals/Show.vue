<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold">Journal #{{ id }}</h1>
    <div v-if="loading" class="mt-2 text-sm text-gray-500">Loading...</div>
    <div v-else class="mt-4 space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <div class="text-xs text-gray-500">Date</div>
          <div class="font-medium">{{ entry.date }}</div>
        </div>
        <div class="md:col-span-2">
          <div class="text-xs text-gray-500">Memo</div>
          <div class="font-medium">{{ entry.memo }}</div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full border text-sm">
          <thead class="bg-gray-50">
            <tr><th class="p-2 border text-left">Account</th><th class="p-2 border text-right">Dr</th><th class="p-2 border text-right">Cr</th></tr>
          </thead>
          <tbody>
            <tr v-for="ln in lines" :key="ln.id">
              <td class="p-2 border">{{ ln.account_id }}</td>
              <td class="p-2 border text-right">{{ money(ln.debit) }}</td>
              <td class="p-2 border text-right">{{ money(ln.credit) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { onMounted, ref } from 'vue'
import axios from 'axios'

const props = defineProps({ id: Number })
const entry = ref({})
const lines = ref([])
const loading = ref(true)

async function load(){
  const res = await axios.get(`/admin/accounting/journals/${props.id}`, { headers: { Accept: 'application/json' } })
  entry.value = res.data.entry || {}
  lines.value = res.data.lines || []
  loading.value = false
}
function money(n){ return Number(n||0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }

onMounted(load)
</script>
