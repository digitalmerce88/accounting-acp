<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
const props = defineProps({ account: Object, rows: Object })
const form = useForm({ file: null, mapping: {} })
function onFile(e){ form.file = e.target.files[0] }
function submit(){ form.post(route('admin.accounting.bank.import', props.account.id)) }
</script>
<template>
  <Head :title="`Bank Tx: ${account.account_name}`" />
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Transactions — {{ account.account_name }}</h1>
      <Link :href="route('admin.accounting.bank.accounts')" class="text-indigo-600">Back</Link>
    </div>
    <div class="bg-white p-4 rounded shadow">
      <form @submit.prevent="submit" class="flex items-center gap-3">
        <input type="file" accept=".csv,.txt" @change="onFile" class="border rounded p-2" />
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded" :disabled="form.processing || !form.file">Import CSV</button>
      </form>
    </div>
    <div class="bg-white shadow rounded overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="bg-gray-50 text-left text-sm">
            <th class="p-3">Date</th>
            <th class="p-3">Amount</th>
            <th class="p-3">Reference</th>
            <th class="p-3">Description</th>
            <th class="p-3">Matched</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows.data" :key="row.id" class="border-t">
            <td class="p-3">{{ row.date }}</td>
            <td class="p-3 text-right">{{ Number(row.amount_decimal).toFixed(2) }}</td>
            <td class="p-3">{{ row.reference }}</td>
            <td class="p-3">{{ row.description }}</td>
            <td class="p-3">{{ row.matched ? 'Yes' : 'No' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="bg-white p-4 rounded shadow">
      <h2 class="font-semibold mb-3">Start Reconciliation</h2>
      <form method="post" :action="route('admin.accounting.bank.reconcile.start')" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="hidden" name="bank_account_id" :value="account.id" />
        <div>
          <label class="text-sm text-gray-600">Period Start</label>
          <input type="date" name="period_start" class="border rounded p-2 w-full" required />
        </div>
        <div>
          <label class="text-sm text-gray-600">Period End</label>
          <input type="date" name="period_end" class="border rounded p-2 w-full" required />
        </div>
        <div>
          <label class="text-sm text-gray-600">Statement Balance</label>
          <input type="number" step="0.01" name="statement_balance_decimal" class="border rounded p-2 w-full" />
        </div>
        <div class="flex items-end">
          <button class="px-4 py-2 bg-emerald-600 text-white rounded">Start</button>
        </div>
      </form>
    </div>
  </div>
</template>
