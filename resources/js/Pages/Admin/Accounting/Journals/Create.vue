<template>
  <AdminLayout>
  <h1 class="text-xl font-semibold">สร้างบันทึกรายการ</h1>
    <form class="mt-4 space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm text-gray-700 mb-1">วันที่</label>
          <input v-model="date" type="date" class="w-full border rounded px-2 py-1" />
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm text-gray-700 mb-1">บันทึก</label>
          <input v-model="memo" class="w-full border rounded px-2 py-1" placeholder="บันทึก" />
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">รายการบันทึก</h3>
          <button class="px-3 py-1 border rounded text-sm" @click.prevent="addLine">เพิ่มแถว</button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full border text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="text-left p-2 border">บัญชี</th>
                <th class="text-left p-2 border">เดบิต</th>
                <th class="text-left p-2 border">เครดิต</th>
                <th class="p-2 border w-24">-</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(ln, idx) in lines" :key="idx" class="border-b">
                <td class="p-2 border">
                  <select v-model.number="ln.account_id" class="w-full border rounded px-2 py-1">
                    <option :value="null">เลือกบัญชี</option>
                    <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option>
                  </select>
                </td>
                <td class="p-2 border"><input type="number" step="0.01" v-model.number="ln.debit" class="w-full border rounded px-2 py-1 text-right" /></td>
                <td class="p-2 border"><input type="number" step="0.01" v-model.number="ln.credit" class="w-full border rounded px-2 py-1 text-right" /></td>
                <td class="p-2 border text-center">
                  <button class="text-red-600" @click.prevent="removeLine(idx)">ลบ</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="flex items-center gap-3">
  <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">สร้าง</button>
        <span v-if="error" class="text-red-600 text-sm">{{ error }}</span>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import axios from 'axios'
const date = ref(new Date().toISOString().slice(0,10))
const memo = ref('')
const lines = ref([{account_id:null,debit:0,credit:0},{account_id:null,debit:0,credit:0}])
const accounts = ref([])
const error = ref('')

const addLine = ()=> lines.value.push({account_id:null,debit:0,credit:0})
const removeLine = (idx)=> lines.value.splice(idx,1)

async function loadAccounts() {
  // Load first page large enough to list typical accounts
  const res = await axios.get('/admin/accounting/accounts', { params: { page: 1, q: '' }, headers: { Accept: 'application/json' } })
  accounts.value = res.data.data || []
}

const submit = async ()=>{
  error.value = ''
  try {
    await axios.post('/admin/accounting/journals', { date: date.value, memo: memo.value, lines: lines.value })
    window.location.href = '/admin/accounting/journals'
  } catch (e) {
  error.value = e?.response?.data?.message || 'ไม่สามารถสร้างบันทึกรายการได้'
  }
}
onMounted(loadAccounts)
</script>
