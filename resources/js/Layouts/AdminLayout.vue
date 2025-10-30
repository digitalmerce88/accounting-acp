<template>
  <div class="min-h-screen bg-gray-100 text-gray-900">
    <!-- Topbar -->
    <header class="bg-white border-b sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/dashboard" class="font-semibold">ACP</Link>
          <span class="text-gray-400">/</span>
          <span class="text-sm text-gray-600">แผงผู้ดูแลระบบ</span>
        </div>
        <div class="flex items-center gap-4">
          <Link href="/profile" class="text-sm text-gray-700 hover:text-gray-900">โปรไฟล์</Link>
          <form method="post" action="/logout">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button class="text-sm text-red-600 hover:text-red-700">ออกจากระบบ</button>
          </form>
        </div>
      </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-12 gap-6">
      <!-- Sidebar -->
      <aside class="col-span-12 md:col-span-3 lg:col-span-2">
        <nav class="bg-white border rounded-md p-3 space-y-1">
          <Link href="/admin" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin$') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">แดชบอร์ด</Link>
          <Link href="/admin/users" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/users') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">ผู้ใช้</Link>
          <div class="pt-2 text-xs font-semibold text-gray-500">งานบัญชี</div>
          <Link href="/admin/accounting/income" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/income') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">รายรับ</Link>
          <Link href="/admin/accounting/expense" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/expense') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">รายจ่าย</Link>
          <Link href="/admin/accounting/accounts" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/accounts') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">ผังบัญชี</Link>
          <Link href="/admin/accounting/journals" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/journals') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">บันทึกบัญชี</Link>
          <div class="pt-2 text-xs font-semibold text-gray-500">รายงาน</div>
          <Link href="/admin/accounting/reports/overview" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/overview') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">ภาพรวม</Link>
          <Link href="/admin/accounting/reports/by-category" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/by-category') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">ตามหมวด/บัญชี</Link>
          <Link href="/admin/accounting/reports/tax/purchase-vat" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/tax/purchase-vat') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">ภาษีซื้อ</Link>
          <Link href="/admin/accounting/reports/tax/sales-vat" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/tax/sales-vat') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">ภาษีขาย</Link>
          <Link href="/admin/accounting/reports/profit-and-loss" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/profit-and-loss') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">กำไรขาดทุน</Link>
          <Link href="/admin/accounting/reports/trial-balance" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/trial-balance') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">งบทดลอง</Link>
          <Link href="/admin/accounting/reports/ledger" class="block px-3 py-2 rounded-md text-sm" :class="isActive('/admin/accounting/reports/ledger') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">สมุดบัญชีแยกประเภท</Link>
        </nav>
      </aside>

      <!-- Content -->
      <main class="col-span-12 md:col-span-9 lg:col-span-10 space-y-3">
        <!-- Flash banners -->
        <div v-if="flash.error" class="p-3 border border-red-300 bg-red-50 text-red-700 rounded">{{ flash.error }}</div>
        <div v-else-if="flash.success" class="p-3 border border-green-300 bg-green-50 text-green-700 rounded">{{ flash.success }}</div>
        <div v-else-if="flash.status" class="p-3 border border-blue-300 bg-blue-50 text-blue-700 rounded">{{ flash.status }}</div>

        <div class="bg-white border rounded-md p-4">
          <slot />
        </div>
      </main>
    </div>
  </div>

</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const url = computed(() => page.url || (page?.value?.url ?? '/'))
const flash = computed(() => page.props.flash || {})
const csrfToken = computed(() => {
  const el = document.head.querySelector('meta[name="csrf-token"]')
  return el ? el.getAttribute('content') : ''
})

function isActive(prefix) {
  try {
    const re = new RegExp('^' + prefix)
    return re.test(url.value)
  } catch { return false }
}
</script>

<script>
export default {}
</script>
