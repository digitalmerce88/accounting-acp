<template>
  <div>
    <h1>Create Journal</h1>
    <form @submit.prevent="submit">
      <label>Date: <input v-model="date" type="date"/></label>
      <label>Memo: <input v-model="memo"/></label>
      <div>
        <h3>Lines</h3>
        <div v-for="(ln, idx) in lines" :key="idx">
          <input v-model.number="ln.account_id" placeholder="account_id"/>
          <input v-model.number="ln.debit" placeholder="debit"/>
          <input v-model.number="ln.credit" placeholder="credit"/>
        </div>
        <button @click.prevent="addLine">Add line</button>
      </div>
      <button type="submit">Create</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
const date = ref(new Date().toISOString().slice(0,10))
const memo = ref('')
const lines = ref([{account_id:null,debit:0,credit:0},{account_id:null,debit:0,credit:0}])
const addLine = ()=> lines.value.push({account_id:null,debit:0,credit:0})
const submit = async ()=>{
  await axios.post('/admin/accounting/journals', { date: date.value, memo: memo.value, lines: lines.value })
  window.location.href = '/admin/accounting/journals'
}
</script>
