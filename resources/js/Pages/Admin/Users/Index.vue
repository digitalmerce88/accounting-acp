<template>
  <AdminLayout>
    <h1 class="text-2xl font-bold mb-4">Users</h1>
    <div v-if="flash.status" class="mb-4 text-green-700">{{ flash.status }}</div>

    <div class="overflow-x-auto">
      <table class="min-w-full border">
        <thead>
          <tr class="bg-gray-100">
            <th class="p-2 text-left border">Name</th>
            <th class="p-2 text-left border">Email</th>
            <th class="p-2 text-left border">Roles</th>
            <th class="p-2 text-left border">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in localUsers" :key="user.id" class="border-b">
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
                {{ saving[user.id] ? 'Saving...' : 'Save' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { reactive, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const props = page.props
const users = computed(() => props.value.users || [])
const roles = computed(() => props.value.roles || [])
const flash = computed(() => props.value.flash || {})

// Local state with role slugs array for easy v-model binding
const localUsers = reactive(users.value.map(u => ({
  ...u,
  roleSlugs: (u.roles || []).map(r => r.slug)
})))

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
