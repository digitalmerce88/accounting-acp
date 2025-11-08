<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
  <h1 class="text-xl font-semibold">ผู้ใช้</h1>
    <div class="flex items-center gap-2">
  <div v-if="flash.status" class="text-green-700 text-sm">{{ flash.status }}</div>
  <button class="px-3 py-1 border rounded text-sm" @click="openCreate">สร้างผู้ใช้</button>
    </div>
    </div>

    <div class="mt-4 overflow-x-auto" v-if="localUsers && localUsers.length">
      <table class="min-w-full border text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="p-2 text-left border">ชื่อ</th>
            <th class="p-2 text-left border">อีเมล</th>
            <th class="p-2 text-left border">สิทธิ์</th>
            <th class="p-2 text-left border">การทำงาน</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in localUsers" :key="user.id" class="border-b hover:bg-gray-50">
            <td class="p-2 border">{{ user.name }}</td>
            <td class="p-2 border">{{ user.email }}</td>
            <td class="p-2 border">
              <label v-for="role in roles" :key="role.slug" class="mr-3 inline-flex items-center">
                <input type="checkbox" class="mr-1" v-model="user.roleSlugs" :value="role.slug" />
                <span>{{ role.name }}</span>
              </label>
            </td>
            <td class="p-2 border">
              <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="save(user)" :disabled="saving[user.id]">
                {{ saving[user.id] ? 'กำลังบันทึก...' : 'บันทึก' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="mt-6 text-sm text-gray-600">ไม่พบผู้ใช้</div>

  <!-- Create User Modal -->
  <Modal :show="showCreateModal" @close="closeCreate">
    <div class="p-4 w-96">
      <h2 class="text-lg font-semibold mb-3">สร้างผู้ใช้ใหม่</h2>
      <div class="grid grid-cols-1 gap-3">
        <div>
          <label class="block text-sm text-gray-700 mb-1">ชื่อ</label>
          <input v-model="form.name" class="w-full border rounded px-2 py-1" placeholder="ชื่อเต็ม" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">อีเมล</label>
          <input v-model="form.email" class="w-full border rounded px-2 py-1" placeholder="email@example.com" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">รหัสผ่าน</label>
          <input v-model="form.password" type="password" class="w-full border rounded px-2 py-1" placeholder="อย่างน้อย 6 ตัว" />
        </div>
        <div>
          <label class="block text-sm text-gray-700 mb-1">สิทธิ์</label>
          <div class="flex flex-wrap">
            <label v-for="role in roles" :key="role.slug" class="mr-3 inline-flex items-center">
              <input type="checkbox" class="mr-1" v-model="form.roles" :value="role.slug" />
              <span>{{ role.name }}</span>
            </label>
          </div>
        </div>
      </div>
      <div class="mt-4 flex items-center gap-2">
        <button class="px-3 py-1 bg-blue-600 text-white rounded" @click="submitCreate" :disabled="creating">{{ creating ? 'กำลังสร้าง...' : 'สร้าง' }}</button>
        <button class="px-3 py-1 border rounded" @click="closeCreate">ยกเลิก</button>
        <span v-if="createError" class="text-red-600 text-sm">{{ createError }}</span>
      </div>
    </div>
  </Modal>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import { reactive, computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { alertError } from '@/utils/swal'

const page = usePage()
const users = computed(() => page.props.users || [])
const roles = computed(() => page.props.roles || [])
const flash = computed(() => page.props.flash || {})

// Local state with role slugs array for easy v-model binding
// Use a ref and watch so we populate/update when Inertia props arrive
const localUsers = ref([])
watch(users, (newUsers) => {
  localUsers.value = (newUsers || []).map(u => ({
    ...u,
    roleSlugs: (u.roles || []).map(r => r.slug)
  }))
}, { immediate: true })

const saving = reactive({})

// Create modal state
const showCreateModal = ref(false)
const form = ref({ name: '', email: '', password: '', roles: [] })
const creating = ref(false)
const createError = ref('')

function openCreate() {
  createError.value = ''
  form.value = { name: '', email: '', password: '', roles: [] }
  showCreateModal.value = true
}

function closeCreate() {
  showCreateModal.value = false
}

async function submitCreate() {
  creating.value = true
  createError.value = ''
  try {
    const res = await fetch('/admin/users', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify(form.value),
    })
    if (res.ok) {
      // reload to pick up new user via Inertia
      window.location.reload()
      return
    }
    const data = await res.json().catch(() => null)
    if (data && data.message) {
      createError.value = data.message
    } else if (data && data.errors) {
      // show first validation error
      const first = Object.values(data.errors)[0]
      createError.value = Array.isArray(first) ? first[0] : first
    } else {
      createError.value = 'ไม่สามารถสร้างผู้ใช้ได้'
    }
    await alertError(createError.value)
  } catch (e) {
    console.error(e)
    createError.value = 'เกิดข้อผิดพลาด'
    await alertError(createError.value)
  } finally {
    creating.value = false
  }
}

function csrfToken() {
  const el = document.head.querySelector('meta[name="csrf-token"]')
  return el ? el.getAttribute('content') : ''
}

async function save(user) {
  saving[user.id] = true
  try {
    const res = await fetch(`/admin/users/${user.id}/roles`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json',
      },
      body: JSON.stringify({ roles: user.roleSlugs })
    })
    if (!res.ok) {
      console.error('Save failed', await res.text())
      await alertError('บันทึกไม่สำเร็จ')
    }
  } finally {
    saving[user.id] = false
  }
}
</script>
