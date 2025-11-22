<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
const props = defineProps({ rows: Object })
</script>
<template>
  <AdminLayout>
    <Head title="Bank Accounts" />
    <div class="p-6">
      <h1 class="text-xl font-semibold mb-4">Bank Accounts</h1>
      <div class="overflow-x-auto bg-white shadow rounded">
        <table class="min-w-full">
        <thead>
          <tr class="bg-gray-50 text-left text-sm">
            <th class="p-3">ID</th>
            <th class="p-3">Bank Code</th>
            <th class="p-3">Account No</th>
            <th class="p-3">Account Name</th>
            <th class="p-3">Default</th>
            <th class="p-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows.data" :key="row.id" class="border-t">
            <td class="p-3">{{ row.id }}</td>
            <td class="p-3">{{ row.bank_code }}</td>
            <td class="p-3">{{ row.account_no }}</td>
            <td class="p-3">{{ row.account_name }}</td>
            <td class="p-3">{{ row.is_default ? 'Yes' : 'No' }}</td>
            <td class="p-3">
              <Link :href="route('admin.accounting.bank.transactions', row.id)"
                class="px-3 py-1 bg-indigo-600 text-white rounded">Transactions</Link>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
