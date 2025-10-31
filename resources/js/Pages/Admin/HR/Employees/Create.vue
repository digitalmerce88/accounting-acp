<template>
  <AdminLayout>
    <h1 class="text-xl font-semibold mb-4">เพิ่มพนักงาน</h1>
  <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm text-gray-600">รหัสพนักงาน</label>
        <input v-model="f.emp_code" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">ชื่อ-นามสกุล</label>
        <input v-model="f.name" required class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">ตำแหน่ง</label>
        <input v-model="f.position" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">วันที่เริ่มงาน</label>
        <input type="date" v-model="f.start_date" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">เงินเดือน (บาท)</label>
        <input type="number" step="0.01" v-model.number="f.base_salary_decimal" required class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">อีเมล</label>
        <input type="email" v-model="f.email" class="w-full border rounded px-2 py-1" />
      </div>
      <div>
        <label class="block text-sm text-gray-600">เบอร์โทร</label>
        <input v-model="f.phone" class="w-full border rounded px-2 py-1" />
      </div>
      <div class="flex items-center gap-2">
        <input id="sso" type="checkbox" v-model="f.sso_enabled" />
        <label for="sso" class="text-sm text-gray-600">เข้าประกันสังคม</label>
      </div>
      <div class="md:col-span-2">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-600">ธนาคาร</label>
            <select v-model="f.bank.code" class="w-full border rounded px-2 py-1">
              <option value="">- เลือกธนาคาร -</option>
              <option v-for="b in banks" :key="b.code" :value="b.code">{{ b.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm text-gray-600">เลขที่บัญชี</label>
            <input v-model="f.bank.number" class="w-full border rounded px-2 py-1" />
          </div>
        </div>
      </div>
      <div>
        <label class="block text-sm text-gray-600">หัก ณ ที่จ่าย (กำหนดเอง/บาท ต่อเดือน)</label>
        <input type="number" step="0.01" v-model.number="f.tax.wht_fixed_decimal" class="w-full border rounded px-2 py-1" placeholder="เช่น 3000" />
        <div class="text-xs text-gray-500 mt-1">ปล่อยว่างเพื่อใช้คำนวณอัตโนมัติตามขั้นบันไดภาษี</div>
      </div>
      <div class="md:col-span-2 flex gap-2">
        <button class="px-4 py-2 bg-green-700 text-white rounded">บันทึก</button>
        <a href="/admin/hr/employees" class="px-4 py-2 bg-gray-100 border rounded">ยกเลิก</a>
      </div>
    </form>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { reactive, computed } from 'vue'
const banks = computed(()=> usePage().props.banks || [])
const f = reactive({ emp_code:'', name:'', position:'', start_date:'', base_salary_decimal:0, email:'', phone:'', sso_enabled:true, bank:{ code:'', name:'', number:'' }, tax:{ wht_fixed_decimal: null } })
function submit(){
  const selected = banks.value.find(b=>b.code===f.bank.code)
  if (selected) f.bank.name = selected.name
  const payload = { ...f, sso_enabled: !!f.sso_enabled }
  router.post('/admin/hr/employees', payload)
}
</script>
