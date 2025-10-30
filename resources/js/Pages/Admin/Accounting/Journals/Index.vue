<template>
  <div>
    <h1 class="text-2xl font-bold">Journals</h1>
    <div class="mt-4">
      <a href="/admin/accounting/journals/create" class="text-blue-600">Create journal</a>
    </div>
    <ul class="mt-4">
      <li v-for="j in journals" :key="j.id">{{ j.date }} - {{ j.memo }}</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
const journals = ref([])
const load = async ()=>{
  const res = await axios.get('/admin/accounting/journals', { headers: { Accept: 'application/json' } })
  journals.value = res.data.data || res.data
}
onMounted(load)
</script>
