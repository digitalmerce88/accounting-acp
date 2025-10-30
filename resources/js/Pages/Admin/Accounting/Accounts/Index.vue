<template>
  <div>
    <h1 class="text-2xl font-bold">Accounts</h1>
    <button @click="refresh" class="mt-4 px-3 py-1 bg-blue-600 text-white rounded">Refresh</button>
    <table class="min-w-full mt-4 border">
      <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>NB</th></tr></thead>
      <tbody>
        <tr v-for="acc in accounts" :key="acc.id">
          <td>{{ acc.code }}</td>
          <td>{{ acc.name }}</td>
          <td>{{ acc.type }}</td>
          <td>{{ acc.normal_balance }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const accounts = ref([])
const refresh = async () => {
  const res = await axios.get('/admin/accounting/accounts', { headers: { Accept: 'application/json' } })
  accounts.value = res.data.data || res.data.data?.data || res.data
}
onMounted(refresh)
</script>
