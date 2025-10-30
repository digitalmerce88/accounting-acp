<template>
  <div>
    <h1>Ledger</h1>
    <label>Account ID: <input v-model.number="account_id"/></label>
    <button @click="load">Load</button>
    <table v-if="rows.length" class="mt-4 border">
      <thead><tr><th>Date</th><th>Entry</th><th>Memo</th><th>Dr</th><th>Cr</th><th>Bal</th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r[1]"><td>{{ r[0] }}</td><td>{{ r[1] }}</td><td>{{ r[2] }}</td><td>{{ r[3] }}</td><td>{{ r[4] }}</td><td>{{ r[5] }}</td></tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
const account_id = ref(null)
const rows = ref([])
const load = async ()=>{
  const res = await axios.get('/admin/accounting/reports/ledger', { params: { account_id: account_id.value }, headers:{ Accept: 'application/json' } })
  rows.value = res.data.data || res.data
}
</script>
