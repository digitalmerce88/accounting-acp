<template>
  <div class="min-h-screen bg-gray-100 text-gray-900">
    <!-- Topbar -->
    <header class="bg-white border-b sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Mobile hamburger -->
          <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 -ml-2 rounded hover:bg-gray-100" aria-label="Open menu">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
          </button>

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
      <!-- Desktop / Tablet: visible on md and up -->
      <aside class="hidden md:block col-span-12 md:col-span-3 lg:col-span-2">
        <nav class="bg-white border rounded-md p-3 space-y-2">
          <div>
            <Link href="/admin" class="flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="isActive('/admin$') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
              <span>แดชบอร์ด</span>
            </Link>
          </div>
          <!-- Documents -->
          <div>
            <button @click="toggle('documents')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="open.documents ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
              aria-haspopup="true" :aria-expanded="open.documents.toString()">
              <span>เอกสารขาย/ซื้อ</span>
              <span class="text-xs" v-text="open.documents ? '▾' : '▸'"></span>
            </button>
            <div v-show="open.documents" class="mt-1 space-y-1">
              <Link href="/admin/documents/invoices" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/documents/invoices') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบแจ้งหนี้</Link>
              <Link href="/admin/documents/bills" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/documents/bills') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบวางบิล</Link>
              <Link href="/admin/documents/quotes" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/documents/quotes') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบเสนอราคา</Link>
              <Link href="/admin/documents/po" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/documents/po') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบสั่งซื้อ</Link>
            </div>
          </div>
          <!-- Accounting -->
          <div>
            <button @click="toggle('accounting')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="open.accounting ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
              aria-haspopup="true" :aria-expanded="open.accounting.toString()">
              <span>งานบัญชี</span>
              <span class="text-xs" v-text="open.accounting ? '▾' : '▸'"></span>
            </button>
            <div v-show="open.accounting" class="mt-1 space-y-1">
              <Link href="/admin/accounting/income" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/income') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">รายรับ</Link>
              <Link href="/admin/accounting/expense" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/expense') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">รายจ่าย</Link>
              <Link href="/admin/accounting/bank-accounts" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/bank-accounts') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">กระทบยอดธนาคาร</Link>
              <Link href="/admin/accounting/accounts" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/accounts') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ผังบัญชี</Link>
              <Link href="/admin/accounting/journals" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/journals') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">บันทึกบัญชี</Link>
            </div>
          </div>
          <!-- Reports -->
          <div>
            <button @click="toggle('reports')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="open.reports ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
              aria-haspopup="true" :aria-expanded="open.reports.toString()">
              <span>รายงาน</span>
              <span class="text-xs" v-text="open.reports ? '▾' : '▸'"></span>
            </button>
            <div v-show="open.reports" class="mt-1 space-y-1">
              <Link href="/admin/accounting/reports/overview" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/overview') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ภาพรวม</Link>
              <Link href="/admin/accounting/reports/by-category" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/by-category') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ตามหมวดบัญชี</Link>
              <Link href="/admin/accounting/reports/tax/purchase-vat" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/tax/purchase-vat') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ภาษีซื้อ</Link>
              <Link href="/admin/accounting/reports/tax/sales-vat" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/tax/sales-vat') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ภาษีขาย</Link>
              <Link href="/admin/accounting/reports/profit-and-loss" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/profit-and-loss') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">กำไรขาดทุน</Link>
              <Link href="/admin/accounting/reports/trial-balance" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/trial-balance') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">งบทดลอง</Link>
              <Link href="/admin/accounting/reports/ledger" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/accounting/reports/ledger') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">สมุดบัญชีแยกประเภท</Link>
            </div>
          </div>
          <!-- Assets -->
          <div>
            <button @click="toggle('assets')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="open.assets ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
              aria-haspopup="true" :aria-expanded="open.assets.toString()">
              <span>ทรัพย์สิน</span>
              <span class="text-xs" v-text="open.assets ? '▾' : '▸'"></span>
            </button>
            <div v-show="open.assets" class="mt-1 space-y-1">
              <Link href="/admin/assets/assets" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/assets/assets') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ทะเบียนทรัพย์สิน</Link>
              <Link href="/admin/assets/categories" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/assets/categories') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">หมวดหมู่ทรัพย์สิน</Link>
            </div>
          </div>
          <!-- HR -->
          <div>
            <button @click="toggle('hr')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="open.hr ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
              aria-haspopup="true" :aria-expanded="open.hr.toString()">
              <span>บุคคล & เงินเดือน</span>
              <span class="text-xs" v-text="open.hr ? '▾' : '▸'"></span>
            </button>
            <div v-show="open.hr" class="mt-1 space-y-1">
              <Link href="/admin/hr/employees" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/hr/employees') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">พนักงาน</Link>
              <Link href="/admin/hr/payroll" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/hr/payroll') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">รอบเงินเดือน</Link>
            </div>
          </div>
          <!-- Settings -->
          <div>
            <button @click="toggle('settings')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
              :class="open.settings ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
              aria-haspopup="true" :aria-expanded="open.settings.toString()">
              <span>การตั้งค่า</span>
              <span class="text-xs" v-text="open.settings ? '▾' : '▸'"></span>
            </button>
            <div v-show="open.settings" class="mt-1 space-y-1">
              <Link href="/admin/users" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/users') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ผู้ใช้</Link>
              <Link href="/admin/settings/company" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/settings/company') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ข้อมูลบริษัท</Link>
              <Link href="/admin/settings/exchange-rates" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                :class="isActive('/admin/settings/exchange-rates') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">อัตราแลกเปลี่ยน</Link>
            </div>
          </div>
        </nav>
      </aside>

      <!-- Mobile drawer (small screens) -->
      <transition name="fade">
        <div v-show="sidebarOpen" class="fixed inset-0 z-50 md:hidden" aria-hidden="!sidebarOpen">
          <div class="absolute inset-0 bg-black bg-opacity-40" @click="sidebarOpen = false"></div>
          <aside class="absolute left-0 top-0 h-full w-64 bg-white border-r shadow-lg p-3 overflow-y-auto">
            <div class="flex items-center justify-between mb-3">
              <div class="font-semibold">เมนู</div>
              <button @click="sidebarOpen = false" class="p-1 rounded hover:bg-gray-100" aria-label="Close menu">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </div>
            <nav class="space-y-2">
              <Link href="/admin" class="flex items-center justify-between px-3 py-2 rounded-md text-base"
                :class="isActive('/admin$') ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">แดชบอร์ด</Link>
              <div>
                <button @click="toggle('m_documents')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
                  :class="open.m_documents ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
                  <span>เอกสารขาย/ซื้อ</span><span class="text-xs" v-text="open.m_documents ? '▾':'▸'"></span>
                </button>
                <div v-show="open.m_documents" class="mt-1 space-y-1">
                  <Link href="/admin/documents/invoices" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                    :class="isActive('/admin/documents/invoices') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบแจ้งหนี้</Link>
                  <Link href="/admin/documents/bills" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                    :class="isActive('/admin/documents/bills') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบวางบิล</Link>
                  <Link href="/admin/documents/quotes" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                    :class="isActive('/admin/documents/quotes') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบเสนอราคา</Link>
                  <Link href="/admin/documents/po" class="block pl-6 pr-3 py-1.5 rounded-md text-sm"
                    :class="isActive('/admin/documents/po') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ใบสั่งซื้อ</Link>
                </div>
              </div>
              <div>
                <button @click="toggle('m_accounting')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
                  :class="open.m_accounting ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
                  <span>งานบัญชี</span><span class="text-xs" v-text="open.m_accounting ? '▾':'▸'"></span>
                </button>
                <div v-show="open.m_accounting" class="mt-1 space-y-1">
                  <Link href="/admin/accounting/income" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/income') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">รายรับ</Link>
                  <Link href="/admin/accounting/expense" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/expense') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">รายจ่าย</Link>
                  <Link href="/admin/accounting/bank-accounts" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/bank-accounts') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">กระทบยอดธนาคาร</Link>
                  <Link href="/admin/accounting/accounts" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/accounts') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ผังบัญชี</Link>
                  <Link href="/admin/accounting/journals" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/journals') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">บันทึกบัญชี</Link>
                </div>
              </div>
              <div>
                <button @click="toggle('m_reports')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
                  :class="open.m_reports ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
                  <span>รายงาน</span><span class="text-xs" v-text="open.m_reports ? '▾':'▸'"></span>
                </button>
                <div v-show="open.m_reports" class="mt-1 space-y-1">
                  <Link href="/admin/accounting/reports/overview" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/overview') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ภาพรวม</Link>
                  <Link href="/admin/accounting/reports/by-category" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/by-category') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ตามหมวดบัญชี</Link>
                  <Link href="/admin/accounting/reports/tax/purchase-vat" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/tax/purchase-vat') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ภาษีซื้อ</Link>
                  <Link href="/admin/accounting/reports/tax/sales-vat" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/tax/sales-vat') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ภาษีขาย</Link>
                  <Link href="/admin/accounting/reports/profit-and-loss" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/profit-and-loss') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">กำไรขาดทุน</Link>
                  <Link href="/admin/accounting/reports/trial-balance" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/trial-balance') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">งบทดลอง</Link>
                  <Link href="/admin/accounting/reports/ledger" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/accounting/reports/ledger') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">สมุดบัญชีแยกประเภท</Link>
                </div>
              </div>
              <div>
                <button @click="toggle('m_assets')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
                  :class="open.m_assets ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
                  <span>ทรัพย์สิน</span><span class="text-xs" v-text="open.m_assets ? '▾':'▸'"></span>
                </button>
                <div v-show="open.m_assets" class="mt-1 space-y-1">
                  <Link href="/admin/assets/assets" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/assets/assets') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ทะเบียนทรัพย์สิน</Link>
                  <Link href="/admin/assets/categories" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/assets/categories') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">หมวดหมู่ทรัพย์สิน</Link>
                </div>
              </div>
              <div>
                <button @click="toggle('m_hr')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
                  :class="open.m_hr ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
                  <span>บุคคล & เงินเดือน</span><span class="text-xs" v-text="open.m_hr ? '▾':'▸'"></span>
                </button>
                <div v-show="open.m_hr" class="mt-1 space-y-1">
                  <Link href="/admin/hr/employees" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/hr/employees') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">พนักงาน</Link>
                  <Link href="/admin/hr/payroll" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/hr/payroll') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">รอบเงินเดือน</Link>
                </div>
              </div>
              <div>
                <button @click="toggle('m_settings')" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base"
                  :class="open.m_settings ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'">
                  <span>การตั้งค่า</span><span class="text-xs" v-text="open.m_settings ? '▾':'▸'"></span>
                </button>
                <div v-show="open.m_settings" class="mt-1 space-y-1">
                  <Link href="/admin/users" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/users') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ผู้ใช้</Link>
                  <Link href="/admin/settings/company" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/settings/company') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">ข้อมูลบริษัท</Link>
                  <Link href="/admin/settings/exchange-rates" class="block pl-6 pr-3 py-1.5 text-sm rounded-md"
                    :class="isActive('/admin/settings/exchange-rates') ? 'bg-gray-800 text-white' : 'text-gray-600 hover:bg-gray-100'">อัตราแลกเปลี่ยน</Link>
                </div>
              </div>
            </nav>
          </aside>
        </div>
      </transition>

      <!-- Content -->
      <main class="col-span-12 md:col-span-9 lg:col-span-10 space-y-3">
        <!-- Flash banners -->
        <div v-if="flash.error" class="p-3 border border-red-300 bg-red-50 text-red-700 rounded">{{ flash.error }}</div>
        <div v-else-if="flash.success" class="p-3 border border-green-300 bg-green-50 text-green-700 rounded">{{ flash.success }}</div>
        <div v-else-if="flash.status" class="p-3 border border-blue-300 bg-blue-50 text-blue-700 rounded">{{ flashLabel(flash.status) }}</div>

        <div class="bg-white border rounded-md p-4">
          <slot />
        </div>
      </main>
    </div>
  </div>

</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import { flashLabel } from '@/utils/statusLabels'

const page = usePage()
const sidebarOpen = ref(false)
const open = ref({ documents:false, accounting:false, reports:false, assets:false, hr:false, settings:false, m_documents:false, m_accounting:false, m_reports:false, m_assets:false, m_hr:false, m_settings:false })
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

function toggle(key){ open.value[key] = !open.value[key] }

onMounted(()=>{
  const u = url.value
  const map = [
    {key:'documents', pattern:/\/admin\/documents\//},
    {key:'accounting', pattern:/\/admin\/accounting\/(income|expense|bank-accounts|accounts|journals)/},
    {key:'reports', pattern:/\/admin\/accounting\/reports\//},
    {key:'assets', pattern:/\/admin\/assets\//},
    {key:'hr', pattern:/\/admin\/hr\//},
    {key:'settings', pattern:/\/admin\/(settings|users)\//},
  ]
  map.forEach(m=>{ if(m.pattern.test(u)){ open.value[m.key]=true } })
  // mobile groups mirror
  open.value.m_documents = open.value.documents
  open.value.m_accounting = open.value.accounting
  open.value.m_reports = open.value.reports
  open.value.m_assets = open.value.assets
  open.value.m_hr = open.value.hr
  open.value.m_settings = open.value.settings
})
</script>

<script>
export default {}
</script>
