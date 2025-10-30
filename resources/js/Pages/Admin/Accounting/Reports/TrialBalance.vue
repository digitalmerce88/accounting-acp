<template>
  <div>
    <h1>Trial Balance</h1>
    <button @click="load">Load</button>
    <table v-if="rows.length" class="mt-4 border">
      <thead><tr><th>Code</th><th>Name</th><th>DR</th><th>CR</th></tr></thead>
      <tbody>
        <tr v-for="r in rows" :key="r[0]"><td>{{ r[0] }}</td><td>{{ r[1] }}</td><td>{{ r[3] }}</td><td>{{ r[4] }}</td></tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
const rows = ref([])
const load = async ()=>{
  const res = await axios.get('/admin/accounting/reports/trial-balance', { headers:{ Accept: 'application/json' } })
  rows.value = res.data.data || res.data
}
</script>
