<template>
  <div class="min-h-screen flex">
    <aside class="w-64 bg-gray-900 text-white p-4">
      <h1 class="font-bold mb-4">Admin</h1>
      <nav class="space-y-2">
        <a href="/admin" class="block hover:underline">Dashboard</a>
        <a href="/admin/accounting/accounts" class="block hover:underline">Accounts</a>
        <a href="/admin/accounting/journals" class="block hover:underline">Journals</a>
        <div class="mt-2">
          <div class="font-semibold">Reports</div>
          <a href="/admin/accounting/reports/trial-balance" class="block hover:underline">Trial Balance</a>
          <a href="/admin/accounting/reports/ledger" class="block hover:underline">Ledger</a>
        </div>
      </nav>
    </aside>
    <main class="flex-1 p-6">
      <slot />
    </main>
  </div>
</template>

<script setup>
// Placeholder layout for Inertia pages (to be wired when Inertia/Vue is configured)
</script>
