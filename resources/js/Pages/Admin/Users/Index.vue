<template>
  <AdminLayout>
    <div class="flex items-center justify-between">
  <h1 class="text-xl font-semibold">ผู้ใช้</h1>
      <div v-if="flash.status" class="text-green-700 text-sm">{{ flash.status }}</div>
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
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { reactive, computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

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
      alert('Failed to save')
    }
  } finally {
    saving[user.id] = false
  }
}
</script>
