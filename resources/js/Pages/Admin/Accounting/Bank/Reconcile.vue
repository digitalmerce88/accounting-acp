<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ item: Object })
function autoMatch(){ router.post(route('admin.accounting.bank.reconcile.auto', props.item.id)) }
</script>
<template>
  <AdminLayout>
    <Head title="Reconciliation" />
    <div class="p-6 space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Reconciliation #{{ item.id }}</h1>
        <Link :href="route('admin.accounting.bank.accounts')" class="text-indigo-600">Back</Link>
      </div>

    <div class="bg-white p-4 rounded shadow grid grid-cols-1 md:grid-cols-4 gap-3">
      <div>
        <div class="text-sm text-gray-500">Account</div>
        <div class="font-medium">#{{ item.bank_account_id }}</div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Period</div>
        <div class="font-medium">{{ item.period_start }} → {{ item.period_end }}</div>
      </div>
      <div>
        <div class="text-sm text-gray-500">Statement</div>
        <div class="font-medium">{{ Number(item.statement_balance_decimal || 0).toFixed(2) }}</div>
      </div>
      <div class="flex items-center justify-end">
        <button @click="autoMatch" class="px-4 py-2 bg-indigo-600 text-white rounded">Auto-match</button>
      </div>
    </div>

    <div class="bg-white shadow rounded overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="bg-gray-50 text-left text-sm">
            <th class="p-3">Bank Tx</th>
            <th class="p-3">Date</th>
            <th class="p-3">Amount</th>
            <th class="p-3">Ref/Desc</th>
            <th class="p-3">Internal Tx</th>
            <th class="p-3">Matched Amount</th>
            <th class="p-3">Method</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in item.matches" :key="m.id" class="border-t">
            <td class="p-3">#{{ m.bank_transaction_id }}</td>
            <td class="p-3">{{ m.bank_transaction?.date }}</td>
            <td class="p-3 text-right">{{ Number(m.bank_transaction?.amount_decimal || 0).toFixed(2) }}</td>
            <td class="p-3">{{ m.bank_transaction?.reference || m.bank_transaction?.description }}</td>
            <td class="p-3">Tx #{{ m.transaction_id || '-' }}</td>
            <td class="p-3 text-right">{{ Number(m.matched_amount_decimal).toFixed(2) }}</td>
            <td class="p-3">{{ m.method }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    </div>
  </AdminLayout>
</template>
